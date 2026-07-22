@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

@php
    $statusLabel = [
        '1' => 'DRAFT', '2' => 'VALIDATED', '3' => 'APPROVED',
        '4' => 'POSTED', '5' => 'CANCELED', '6' => 'REVISED',
    ][$header->status] ?? '-';

    $statusBadge = [
        '1' => 'badge-primary', '2' => 'badge-info', '3' => 'badge-warning',
        '4' => 'badge-success', '5' => 'badge-danger', '6' => 'badge-warning',
    ][$header->status] ?? 'badge-secondary';

    $isPending = $header->status === '6';   // sudah direvisi, belum diposting ulang

    // Disiapkan di sini, BUKAN inline di dalam @json().
    // @json dikompilasi dengan explode(',', $expression) — koma dianggap
    // pemisah argumen ($options, $depth), jadi ekspresi apa pun yang
    // mengandung koma (array literal, argumen fungsi) akan terpotong dan
    // menghasilkan ParseError. @json($variabel) aman karena tanpa koma.
    $existingDet = $details->map(fn($d) => [
        'article_code' => $d->article_code,
        'label'        => trim(($d->article_alternative_code ?? $d->article_code) . ' - ' . ($d->article_desc ?? '')),
        'uom'          => $d->uom,
        'uom_member'   => $d->uom_member,
        'notes'        => $d->notes,
        'stock_before' => (float) $d->stock_before,
        'stock_after'  => (float) $d->stock_after,
        'qty'          => (float) $d->qty_adjustment,
        'direction'    => $d->direction,
    ])->values();
@endphp

<section id="adj-edit">
    <div class="form-row">

        {{-- ── STATUS DOKUMEN ──────────────────────────────────────────
             Satu panel menggantikan dua alert bertumpuk. Isinya menjawab satu
             pertanyaan yang benar-benar dipunyai user saat membuka halaman ini:
             apakah angka di layar sudah sama dengan stok, atau belum.
        ──────────────────────────────────────────────────────────────── --}}
        @if($isRevision)
        <div class="col-12">
            <div class="doc-state {{ $isPending ? 'is-pending' : 'is-synced' }}">

                <div class="doc-state__bar">
                    <span class="doc-state__status">{{ $statusLabel }}</span>
                    @if($header->rev_no > 0)
                        <span class="doc-state__rev">rev.{{ $header->rev_no }}</span>
                    @endif
                    <code class="doc-state__code">{{ $header->adj_code }}</code>
                </div>
                <br>
                <p class="doc-state__note">
                    @if($isPending)
                        Keduanya baru sama setelah <strong>Posting Revisi</strong> dijalankan dari
                        <a href="{{ route('stockAdjustment.index') }}">halaman list</a>.
                    @else
                        Menyimpan perubahan membuat keduanya berbeda sampai dokumen diposting ulang.
                    @endif
                </p>

                <button class="doc-state__more" type="button"
                        data-toggle="collapse" data-target="#reviseRules"
                        aria-expanded="false" aria-controls="reviseRules">
                    <span class="doc-state__chev" aria-hidden="true"></span>
                    Aturan revisi
                </button>

                <div class="collapse" id="reviseRules">
                    <dl class="doc-state__rules">
                        <dt>Menyimpan</dt>
                        <dd>Status berubah jadi REVISED. Stok belum ikut berubah.</dd>

                        <dt>Posting ulang</dt>
                        <dd>Hanya <strong>selisih</strong> terhadap posting sebelumnya yang masuk stok.
                            Baris movement lama diperbarui, bukan ditambah baris baru.</dd>

                        <dt>Location</dt>
                        <dd>Terkunci. Kalau lokasinya salah, Cancel dokumen ini lalu buat yang baru.</dd>

                        <dt>Stock Before</dt>
                        <dd>Dihitung ulang dari saldo historis pada Adjustment Date, tanpa kontribusi
                            dokumen ini sendiri.</dd>
                    </dl>
                </div>

            </div>
        </div>
        @endif

        {{-- ── HEADER CARD ─────────────────────────────────────────────── --}}
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        Status: <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                        @if($header->rev_no > 0)
                            <span class="badge badge-light-warning">rev.{{ $header->rev_no }}</span>
                        @endif
                    </h4>

                    {{-- oEdit selalu true di halaman ini — simpanData() memilih route update --}}
                    <input type="hidden" id="oEdit" value="1">
                    <input type="hidden" id="isRevision" value="{{ $isRevision ? 1 : 0 }}">

                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" autocomplete="off">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="adjCode">Adjustment Code</label>
                                    <input type="text" id="adjCode" name="adjCode"
                                        class="form-control disabled-el"
                                        value="{{ $header->adj_code }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="adjDate">Adjustment Date *</label>
                                    <input type="text" id="adjDate" name="adjDate"
                                        class="form-control" placeholder="DD-MM-YYYY"
                                        value="{{ $header->adj_date }}" required />
                                </div>
                                <div class="form-group col-md-1">
                                    <label class="form-label" for="periode">Periode *</label>
                                    <select class="select2 form-control" id="periode" name="periode">
                                        <option value=""></option>
                                        @for ($i = 1; $i <= 12; $i++)
                                            <option value="{{ $i }}"
                                                {{ (string) $header->periode === (string) $i ? 'selected' : '' }}>
                                                {{ $i }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="adjType">Adjustment Type *</label>
                                    <select class="select2 form-control" id="adjType" name="adjType" required>
                                        <option value=""></option>
                                        @foreach($types as $val)
                                            <option value="{{ $val }}"
                                                {{ $header->adj_type === $val ? 'selected' : '' }}>
                                                {{ $val }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="location">
                                        Location *
                                        @if($isRevision)
                                            <i data-feather="lock" class="feather-14 text-muted"></i>
                                        @endif
                                    </label>
                                    <select class="select2 form-control" id="location" name="location"
                                        {{ $isRevision ? 'disabled' : '' }} required>
                                        <option value=""></option>
                                        @foreach($locations as $val)
                                            <option value="{{ $val->location_code }}"
                                                {{ $header->location_code === $val->location_code ? 'selected' : '' }}>
                                                {{ $val->location_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($isRevision)
                                        {{-- select disabled tidak ikut ter-submit form biasa, tapi
                                             simpanData() membacanya lewat $('#location').val() yang
                                             tetap bekerja. Hidden ini cadangan kalau suatu saat
                                             halaman ini disubmit sebagai form. --}}
                                        <input type="hidden" name="location_locked"
                                               value="{{ $header->location_code }}">
                                    @endif
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="description">Description</label>
                                    <input type="text" id="description" name="description"
                                        class="form-control" maxlength="255"
                                        value="{{ $header->description }}" />
                                </div>
                                
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── ARTICLE CARD ────────────────────────────────────────────── --}}
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article Detail</h4>
                </div>
                <div class="card-body">

                    {{-- Excel import — pada edit, import MENGGANTI seluruh baris --}}
                    <form id="frmExcel" name="frmExcel" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-row align-items-center">
                            <div class="col-lg-3 col-md-12">
                                <div class="form-group">
                                    <input type="file" class="custom-file-input" name="file"
                                        id="file" accept=".xls,.xlsx" required />
                                    <label class="custom-file-label" for="file" id="fileLabel">Choose file</label>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12 mb-1">
                                <a href="{{ route('stockAdjustment.export.excel') }}" class="btn btn-light">
                                    <i class="fa fa-download"></i> Download Template
                                </a>
                                <button type="button" class="btn btn-primary" id="uploadExcel">
                                    <i data-feather="upload" class="align-middle mr-sm-25 mr-0"></i>
                                    <span class="align-middle d-sm-inline-block d-none">Upload Excel</span>
                                </button>
                                <small class="text-muted d-block mt-25">
                                    Upload akan <strong>mengganti</strong> seluruh baris yang ada.
                                </small>
                            </div>
                        </div>
                    </form>

                    <hr style="margin-top:0">

                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('stockAdjustment.headerColumn')
                            <div id="article_row"
                                style="max-height:22rem;overflow-x:hidden;scrollbar-width:thin;">
                                <input type="text" id="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary" type="button" id="addNewRow"
                            onclick="add_new_row(); hitungGrandTotal();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>

                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold"
                                        id="totalRow" disabled />
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <a href="{{ route('stockAdjustment.index') }}" class="btn btn-light">Back</a>
                            <a href="{{ route('stockAdjustment.show', ['id' => $encId]) }}"
                               class="btn btn-outline-secondary">Detail</a>
                            <button class="btn {{ $isRevision ? 'btn-warning' : 'btn-primary' }}"
                                    type="button" id="cmdSave" name="cmdSave">
                                {{ $isRevision ? 'Simpan Revisi' : 'Save' }}
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ── REVISION HISTORY ────────────────────────────────────────── --}}
        @if($isRevision && $revisions->isNotEmpty())
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        Revision History
                        <span class="badge badge-warning">{{ $revisions->count() }}</span>
                    </h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <ul class="timeline">
                        @foreach($revisions as $rev)
                            @php $isCancel = $rev->action === 'CANCEL'; @endphp
                            <li class="timeline-item">
                                <span class="timeline-point timeline-point-{{ $isCancel ? 'danger' : 'warning' }}">
                                    <i data-feather="{{ $isCancel ? 'x-circle' : 'edit-2' }}"></i>
                                </span>
                                <div class="timeline-event">
                                    <div class="d-flex justify-content-between flex-sm-row flex-column mb-sm-0 mb-1">
                                        <h6 class="mb-25">
                                            Rev.{{ $rev->rev_no }} — {{ $rev->action }}
                                            oleh {{ $rev->revised_by }}
                                        </h6>
                                        <span class="timeline-event-time">
                                            {{ date('d-m-Y H:i', strtotime($rev->revised_at)) }}
                                        </span>
                                    </div>

                                    <p class="mb-50"><em>"{{ $rev->reason }}"</em></p>

                                    @foreach($rev->changes['header'] ?? [] as $c)
                                        <div class="small">
                                            <strong>{{ strtoupper($c['field']) }}</strong>:
                                            <span class="text-danger"><s>{{ $c['from'] ?: '-' }}</s></span>
                                            &rarr;
                                            <span class="text-success">{{ $c['to'] ?: '-' }}</span>
                                        </div>
                                    @endforeach

                                    @foreach($rev->changes['detail'] ?? [] as $d)
                                        <div class="small mt-25">
                                            <span class="badge badge-light-secondary">{{ $d['article_code'] }}</span>
                                            @if($d['type'] === 'ADDED')
                                                <span class="badge badge-light-success">Ditambah</span>
                                                qty {{ $d['after']['qty_adjustment'] ?? '-' }}
                                                ({{ $d['after']['direction'] ?? '' }})
                                            @elseif($d['type'] === 'REMOVED')
                                                <span class="badge badge-light-danger">Dihapus</span>
                                                qty {{ $d['before']['qty_adjustment'] ?? '-' }}
                                                ({{ $d['before']['direction'] ?? '' }})
                                            @else
                                                @foreach($d['fields'] as $f)
                                                    {{ $f['field'] }}:
                                                    <span class="text-danger"><s>{{ $f['from'] }}</s></span>
                                                    &rarr;
                                                    <span class="text-success">{{ $f['to'] }}</span>@if(!$loop->last);@endif
                                                @endforeach
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </li>
                        @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>{{-- /.form-row --}}
</section>

@endsection

@section('styles')
<style>
    textarea { resize: none; }
    .mb-03   { margin-bottom: 0.3rem; }
    label.titik-dua::after { content:":"; position:absolute; right:1px; }

    /* Stock Before yang bergeser dari nilai tersimpan */
    .sb-shifted { border-color:#ff9f43 !important; background:#fff8f0 !important; }

    /* ══════════════════════════════════════════════════════════════════
       PANEL STATUS DOKUMEN

       Menggantikan dua alert bertumpuk. Elemen utamanya adalah operator
       = / ≠ di tengah: satu glyph yang menyatakan seluruh persoalan revisi —
       apakah angka di layar sudah sama dengan stok atau belum. Sisanya
       sengaja dibuat tenang supaya glyph itu yang terbaca lebih dulu.

       Palet mengikuti Vuexy (warning #FF9F43, success #28C76F, garis #EBE9F1,
       teks #6E6B7B / #5E5873) supaya menyatu dengan tema, bukan tempelan.
    ══════════════════════════════════════════════════════════════════ */

    .doc-state {
        --ds-accent: #28C76F;
        --ds-tint:   rgba(40,199,111,.05);

        position: relative;
        margin-bottom: 1rem;
        border: 1px solid #EBE9F1;
        border-radius: .428rem;
        background: #fff;
        box-shadow: 0 2px 6px rgba(34,41,47,.04);
        overflow: hidden;
    }
    .doc-state.is-pending {
        --ds-accent: #FF9F43;
        --ds-tint:   rgba(255,159,67,.05);
    }
    /* Pita aksen kiri — penanda status yang terbaca sebelum teks apa pun */
    .doc-state::before {
        content: "";
        position: absolute;
        top: 0; left: 0; bottom: 0;
        width: 3px;
        background: var(--ds-accent);
    }

    /* ── Baris identitas ── */
    .doc-state__bar {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .625rem 1.25rem;
        border-bottom: 1px solid #F3F2F7;
        background: var(--ds-tint);
    }
    .doc-state__status {
        font-size: .75rem;
        font-weight: 600;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--ds-accent);
    }
    .doc-state__rev {
        font-size: .6875rem;
        font-weight: 500;
        color: #6E6B7B;
        background: #F3F2F7;
        border-radius: 1rem;
        padding: .0625rem .5rem;
    }
    /* Kode dokumen itu data, bukan prosa — monospace bikin lebih mudah dipindai */
    .doc-state__code {
        margin-left: auto;
        padding: 0;
        font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
        font-size: .75rem;
        color: #B9B9C3;
        background: none;
    }

    /* ── Rekonsiliasi ── */
    .doc-state__recon {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 1.25rem;
        padding: 1.25rem;
    }
    .doc-state__pane { display: flex; flex-direction: column; gap: .1875rem; min-width: 0; }
    .doc-state__pane:last-child { text-align: right; }

    .doc-state__label {
        font-size: .6875rem;
        font-weight: 500;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #B9B9C3;
    }
    .doc-state__value {
        font-size: .9375rem;
        font-weight: 500;
        line-height: 1.35;
        color: #5E5873;
    }
    .is-pending .doc-state__value { color: #4B4B5A; }

    .doc-state__op {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 50%;
        font-size: 1.5rem;
        font-weight: 400;
        line-height: 1;
        color: var(--ds-accent);
        background: var(--ds-tint);
        box-shadow: inset 0 0 0 1px var(--ds-accent);
    }

    /* ── Catatan tindakan ── */
    .doc-state__note {
        margin: 0;
        padding: 0 1.25rem .875rem;
        font-size: .8125rem;
        line-height: 1.5;
        color: #6E6B7B;
    }
    .doc-state__note a { color: var(--ds-accent); font-weight: 500; }

    /* ── Aturan (tertutup secara default) ── */
    .doc-state__more {
        display: flex;
        align-items: center;
        gap: .375rem;
        width: 100%;
        padding: .625rem 1.25rem;
        border: 0;
        border-top: 1px solid #F3F2F7;
        background: #FCFCFD;
        font-size: .75rem;
        font-weight: 500;
        letter-spacing: .02em;
        color: #6E6B7B;
        text-align: left;
        cursor: pointer;
    }
    .doc-state__more:hover  { background: #F8F8F8; color: #5E5873; }
    .doc-state__more:focus-visible { outline: 2px solid var(--ds-accent); outline-offset: -2px; }

    .doc-state__chev {
        width: 0; height: 0;
        border-top: 4px solid transparent;
        border-bottom: 4px solid transparent;
        border-left: 5px solid #B9B9C3;
        transition: transform .18s ease;
    }
    .doc-state__more[aria-expanded="true"] .doc-state__chev {
        transform: rotate(90deg);
    }

    .doc-state__rules {
        display: grid;
        grid-template-columns: 8.5rem 1fr;
        gap: .5rem 1rem;
        margin: 0;
        padding: 1rem 1.25rem 1.25rem;
        border-top: 1px solid #F3F2F7;
        font-size: .8125rem;
        line-height: 1.5;
    }
    .doc-state__rules dt {
        font-weight: 600;
        color: #5E5873;
    }
    .doc-state__rules dd {
        margin: 0;
        color: #6E6B7B;
    }

    /* ── Mobile ── */
    @media (max-width: 767.98px) {
        .doc-state__recon {
            grid-template-columns: 1fr;
            gap: .75rem;
            padding: 1rem;
        }
        .doc-state__pane:last-child { text-align: left; }
        .doc-state__op { width: 2.25rem; height: 2.25rem; font-size: 1.25rem; }
        .doc-state__rules { grid-template-columns: 1fr; gap: .125rem .75rem; }
        .doc-state__rules dd { margin-bottom: .625rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .doc-state__chev { transition: none; }
    }
</style>
@endsection

@section('scripts')
@include('stockAdjustment.addArticle')
<script type="text/javascript">

/* ══════════════════════════════════════════════════════════════════════
   KONTEKS HALAMAN
══════════════════════════════════════════════════════════════════════ */
const IS_REVISION  = {{ $isRevision ? 'true' : 'false' }};
const ADJ_CODE     = @json($header->adj_code);
const EXISTING_DET = @json($existingDet);

/* addArticle.blade.php membaca dua global ini.
   adjRevisionCode dikirim ke stockBefore/stockBeforeBulk supaya kontribusi
   dokumen ini sendiri dikeluarkan dari saldo historis — kalau tidak,
   Stock Before jadi dobel (get_last_qty_new memfilter <= tanggal, jadi
   movement dokumen ini ikut terhitung). */
adjRevisionCode = IS_REVISION ? ADJ_CODE : null;
adjReviseReason = null;

/* ══════════════════════════════════════════════════════════════════════
   SAVE
══════════════════════════════════════════════════════════════════════ */
document.querySelector('#cmdSave').addEventListener('click', () => {
    if (!IS_REVISION) { simpanData(true); return; }

    const preview = buildRevisePreview();
    if (preview.error) {
        Swal.fire({ title: 'Validation Error', html: preview.error, icon: 'warning' });
        return;
    }

    Swal.fire({
        title: 'Alasan Revisi',
        html: `
            <div class="text-left">
                ${preview.html}
                <label class="form-label mt-1 d-block">
                    Kenapa dokumen ini direvisi? <span class="text-danger">*</span>
                </label>
                <textarea id="swalReviseReason" class="form-control" rows="3" maxlength="500"
                    placeholder="cth: salah input qty, saldo akhir seharusnya 12 bukan 10"
                    style="resize:none;"></textarea>
                <small class="text-muted d-block mt-25">
                    Tersimpan permanen di revision history bersama rincian perubahannya.
                </small>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Simpan Revisi',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#FF9F43',
        reverseButtons: true,
        focusConfirm: false,
        didOpen: () => {
            document.getElementById('swalReviseReason').focus();
        },
        preConfirm: () => {
            const reason = document.getElementById('swalReviseReason').value.trim();
            if (!reason) {
                Swal.showValidationMessage('Alasan revisi wajib diisi');
                return false;
            }
            return reason;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            adjReviseReason = result.value;
            simpanData(true);
        }
    });
});

/* Dipanggil dari simpanData() di addArticle.blade.php setelah response sukses. */
function onSaveSuccess(data) {
    adjReviseReason = null;   // jangan dipakai ulang untuk submit berikutnya
    setTimeout(() => { window.location.href = "{{ route('stockAdjustment.index') }}"; }, 900);
}
function onSaveFailed() {
    adjReviseReason = null;
}

/* ══════════════════════════════════════════════════════════════════════
   PREVIEW DAMPAK REVISI

   simpanData() melewati baris yang selisihnya 0 (balance == stockBefore).
   Pada revisi, "dilewati" bukan berarti tidak berubah — artikel yang tadinya
   punya adjustment lalu jadi selisih 0 akan DIHAPUS dari dokumen, dan
   movement-nya ikut hilang. Itu keputusan besar, jadi ditunjukkan dulu.
══════════════════════════════════════════════════════════════════════ */
function buildRevisePreview() {
    const before  = {};
    EXISTING_DET.forEach(d => {
        before[d.article_code] = d.direction === '+' ? d.qty : -d.qty;
    });

    const seen    = {};
    const changed = [];
    const added   = [];
    const removed = [];
    let   kept    = 0;

    $('#article_row .tanda-baris').each(function () {
        const rowId   = $(this).attr('id').replace('new_row', '');
        const artCode = $('#articleId' + rowId).val();
        if (!artCode) return;

        const label   = $('#articleId' + rowId).find(':selected').text() || artCode;
        const sbRaw   = parseFloat($('#stockBefore' + rowId).data('raw')) || 0;
        const balance = parseFloat(String($('#balanceQty' + rowId).val()).replace(/,/g, '')) || 0;
        const diff    = Math.round((balance - sbRaw) * 10000) / 10000;

        seen[artCode] = true;

        if (diff === 0) {
            if (artCode in before) removed.push(label);
            return;
        }
        if (!(artCode in before)) { added.push(`${label} (${diff > 0 ? '+' : ''}${diff})`); return; }
        if (Math.abs(before[artCode] - diff) > 0.000001) {
            const f = before[artCode];
            changed.push(`${label}: ${f > 0 ? '+' : ''}${f} &rarr; ${diff > 0 ? '+' : ''}${diff}`);
        } else {
            kept++;
        }
    });

    // baris yang dihapus dari grid sama sekali
    EXISTING_DET.forEach(d => { if (!seen[d.article_code]) removed.push(d.label); });

    const total = changed.length + added.length + removed.length;

    if (total === 0) {
        return { error: 'Tidak ada perubahan pada artikel. Ubah minimal satu saldo akhir, '
                      + 'atau tekan Back kalau memang tidak jadi merevisi.' };
    }
    if (kept === 0 && changed.length === 0 && added.length === 0 && removed.length > 0) {
        return { error: 'Semua artikel akan terhapus dari dokumen ini. Kalau maksudnya membatalkan '
                      + 'seluruh adjustment, gunakan <strong>Cancel</strong> dari halaman list, '
                      + 'bukan revisi.' };
    }

    let html = '<div class="alert alert-light-secondary p-75 mb-0"><small>';
    html += '<strong>Dampak revisi ini:</strong><ul class="mb-0 pl-2 mt-25">';
    if (changed.length) html += `<li>${changed.length} artikel berubah qty:<br><span class="text-warning">${changed.join('<br>')}</span></li>`;
    if (added.length)   html += `<li>${added.length} artikel ditambah:<br><span class="text-success">${added.join('<br>')}</span></li>`;
    if (removed.length) html += `<li>${removed.length} artikel <strong>dihapus</strong> (movement-nya ikut hilang, stok dikembalikan):<br><span class="text-danger">${removed.join('<br>')}</span></li>`;
    if (kept)           html += `<li>${kept} artikel tidak berubah.</li>`;
    html += '</ul></small></div>';

    return { html };
}

/* ══════════════════════════════════════════════════════════════════════
   LOAD BARIS EXISTING

   Menunggu dataArticle selesai (isiArticle async) — kalau tidak, articleMeta
   masih kosong dan label artikel jatuh ke kode mentah.
══════════════════════════════════════════════════════════════════════ */
function loadExistingRows() {
    if (dataArticle === null) { setTimeout(loadExistingRows, 150); return; }

    EXISTING_DET.forEach(function (d) {
        add_new_row_edit(
            d.article_code,
            d.stock_after,      // balance = saldo akhir yang dituju
            d.uom,
            d.uom_member,
            d.notes,
            d.stock_before,     // placeholder; ditimpa hasil fetch di bawah
            { lazySelect: true, skipFetch: true, skipFeather: true }
        );
    });

    feather.replace();
    hitungGrandTotal();

    // Ambil Stock Before historis yang benar untuk semua baris sekaligus.
    // skipFetch di atas sengaja true supaya tidak jadi N request terpisah.
    refreshStockBulkFromRows();
}

/**
 * Bulk-fetch Stock Before untuk seluruh baris yang ada di grid.
 * Dipakai saat load awal, dan saat Adjustment Date / Location berubah.
 */
function refreshStockBulkFromRows() {
    const rowMeta = [];
    $('#article_row .tanda-baris').each(function () {
        const n = $(this).attr('id').replace('new_row', '');
        const c = $('#articleId' + n).val();
        if (c) rowMeta.push({ n: n, articleCode: c });
    });
    if (rowMeta.length === 0) return;

    _fetchStockBeforeBulk(rowMeta);
    setTimeout(markShiftedStock, 1200);
}

/**
 * Tandai baris yang Stock Before-nya bergeser dari nilai tersimpan di det.
 *
 * Bisa terjadi kalau ada transaksi backdate lain yang masuk setelah dokumen
 * ini diposting. Konsekuensinya: untuk mencapai saldo akhir yang sama, qty
 * adjustment jadi ikut berubah — meski user tidak mengetik apa pun.
 */
function markShiftedStock() {
    const stored = {};
    EXISTING_DET.forEach(d => { stored[d.article_code] = d.stock_before; });

    $('#article_row .tanda-baris').each(function () {
        const n = $(this).attr('id').replace('new_row', '');
        const c = $('#articleId' + n).val();
        if (!c || !(c in stored)) return;

        const now = parseFloat($('#stockBefore' + n).data('raw')) || 0;
        const was = stored[c];
        const $sb = $('#stockBefore' + n);

        if (Math.abs(now - was) > 0.000001) {
            $sb.addClass('sb-shifted')
               .attr('title', `Saldo historis bergeser: tersimpan ${was}, sekarang ${now}. `
                            + `Kemungkinan ada transaksi backdate lain setelah dokumen ini diposting.`);
        } else {
            $sb.removeClass('sb-shifted').removeAttr('title');
        }
    });
}

/* ══════════════════════════════════════════════════════════════════════
   INIT
══════════════════════════════════════════════════════════════════════ */
$(document).ready(function () {
    validateFormToast("frmAdd");

    $('#periode, #adjType, #location').trigger('change.select2');

    if (IS_REVISION) {
        $('#location').prop('disabled', true).trigger('change.select2');
    }

    isiArticle('trArticle');
    loadExistingRows();

    /* Location berubah (hanya mungkin di jalur draft) */
    $('#location').on('change', function () { refreshStockBulkFromRows(); });

    /* adjDate berubah → seluruh Stock Before historis ikut berubah.
       Di jalur revisi ini efeknya besar: qty tiap artikel ikut bergeser. */
    $('#adjDate').on('change', function () {
        refreshStockBulkFromRows();
        if (IS_REVISION) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'warning',
                title: 'Tanggal berubah — Stock Before & qty semua artikel dihitung ulang.',
                timer: 2600, showConfirmButton: false });
        }
    });

    /* ── Excel upload: pada edit, MENGGANTI seluruh baris ── */
    $('#frmExcel').on('submit', function (e) {
        e.preventDefault();
        if (!$('#file').val()) { Swal.fire('Error..', 'File is empty!', 'error'); return; }

        $(".loading-spinner-container").addClass("-show");
        $('#uploadExcel').attr('disabled', 'disabled');

        $.ajax({
            url: "{{ route('stockAdjustment.import.excel') }}",
            method: "POST",
            data: new FormData(this),
            dataType: "json",
            contentType: false,
            cache: false,
            processData: false,
            success: function (data) {
                if (data.status == 1 && data.dataDetail.length > 0) {
                    $('#article_row .tanda-baris').remove();   // ganti, bukan tambah
                    importRowsFast(data.dataDetail);
                } else if (data.status == 0) {
                    data.message.forEach(m => show_msg(data.title, m, data.alert));
                    Swal.fire('Warning', data.pesan, 'warning');
                    $('#uploadExcel').removeAttr('disabled');
                    $(".loading-spinner-container").removeClass("-show");
                } else {
                    Swal.fire('Warning', 'Excel file is empty!', 'warning');
                    $('#uploadExcel').removeAttr('disabled');
                    $(".loading-spinner-container").removeClass("-show");
                }
            },
            error: function (xhr) {
                let err = JSON.parse(xhr.responseText);
                Swal.fire('Error..', err.message, 'error');
                $('#uploadExcel').removeAttr('disabled');
                $(".loading-spinner-container").removeClass("-show");
            }
        });
    });

    $('#uploadExcel').on('click', function () {
        Swal.fire({
            title: 'Ganti seluruh baris?',
            text: 'Semua artikel yang sekarang ada di grid akan dihapus dan diganti isi file Excel.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, ganti',
            cancelButtonText: 'Batal',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $(".loading-spinner-container").addClass("-show");
            $('#uploadExcel').attr('disabled', 'disabled');
            $('#frmExcel').submit();
        });
    });

    $('#file').on('change', function () {
        let name = $(this).val().split('\\').pop() || 'Choose file';
        $('#fileLabel').text(name);
    });
});

/* ══════════════════════════════════════════════════════════════════════
   HELPERS
══════════════════════════════════════════════════════════════════════ */
function clearFileInput(id) {
    let inp = $('#' + id);
    inp.wrap('<form>').closest('form').get(0).reset();
    inp.unwrap();
    $('#fileLabel').text('Choose file');
}

$.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});

</script>
@endsection