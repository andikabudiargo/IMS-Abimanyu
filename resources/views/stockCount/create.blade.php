@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

@php
    $roleLabel = ['counter1'=>'Counter 1','counter2'=>'Counter 2','counter3'=>'Counter 3','accounting'=>'Accounting (Override)'][$accessRole] ?? $accessRole;
    $typeLabel = ['LOCATION'=>'Lokasi','SUPPLIER'=>'Supplier','CUSTOMER'=>'Customer'][$mapping->target_type] ?? $mapping->target_type;
    $typeBadge = ['LOCATION'=>'badge-light-primary','SUPPLIER'=>'badge-light-warning','CUSTOMER'=>'badge-light-info'][$mapping->target_type] ?? 'badge-light-secondary';
    $badgeMap  = ['INCOMPLETE'=>'badge-secondary','NOT MATCH'=>'badge-danger','RECOUNT'=>'badge-warning','MATCH'=>'badge-success'];

     // hitung total baris dan status dari semua sheet
    $totalLines = $sheets->sum(fn($s) => $s['lines']->count());
    $totalMatch = $sheets->sum(fn($s) => $s['lines']->where('count_status','MATCH')->count());
    $latestHdrStatus = $sheets->last()['hdr']->status ?? 1;

    $allLinesFlat = $sheets->flatMap(fn($s) => $s['lines']);
    $statusStats = collect(['INCOMPLETE','RECOUNT','NOT MATCH','MATCH'])->mapWithKeys(function ($st) use ($allLinesFlat) {
        $lines = $allLinesFlat->where('count_status', $st);
        return [$st => [
            'count' => $lines->count(),
            'qty'   => $lines->sum(fn($l) => (float) ($l->my_qty ?? 0)),
        ]];
    });
@endphp

<section id="stock-count-create">

    {{-- ════ HEADER INFO ════ --}}
    <div class="card scc-header-card">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between flex-wrap scc-header-top">
                <div>
                    <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
                        <h4 class="mb-0">{{ $targetName }}</h4>
                        @if($mapping->finish_time)
                            <span class="badge badge-success">Completed</span>
                        @else
                            <span class="badge badge-primary">On Going</span>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-muted" style="font-size:.72rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;">Peran Saya</div>
                    <div class="font-weight-bold">{{ $roleLabel }}</div>
                    @if($mapping->is_blind)
                        <span class="badge badge-light-primary">Blind Count</span>
                    @else
                        <span class="badge badge-light-info">Parsial (Non-Blind)</span>
                    @endif
                </div>
            </div>

            <hr class="my-75">

            <div class="row scc-meta-row">
                <div class="col-6 col-md-3 scc-meta-item">
                    <div class="scc-meta-label"><i data-feather="calendar" style="width:12px;height:12px;"></i> STO Date</div>
                    <div class="scc-meta-value">{{ $mapping->sto_date }}</div>
                </div>
                <div class="col-6 col-md-3 scc-meta-item">
                    <div class="scc-meta-label"><i data-feather="target" style="width:12px;height:12px;"></i> Target Akurasi</div>
                    <div class="scc-meta-value">{{ number_format($mapping->target_plan_loc,2) }}%</div>
                </div>
                <div class="col-6 col-md-3 scc-meta-item">
                    <div class="scc-meta-label"><i data-feather="trending-up" style="width:12px;height:12px;"></i> Realisasi</div>
                    <div class="scc-meta-value" id="realisasiValue" data-target-plan="{{ $mapping->target_plan_loc }}">
                        <span class="{{ $mapping->target_act_loc >= $mapping->target_plan_loc ? 'text-success' : 'text-warning' }}">
                            {{ number_format($mapping->target_act_loc,2) }}%
                        </span>
                    </div>
                </div>
                 <div class="col-6 col-md-3 scc-meta-item">
                    <div class="scc-meta-label"><i data-feather="layers" style="width:12px;height:12px;"></i> Total Sheet / Baris</div>
                    <div class="scc-meta-value" id="totalSheetMeta">{{ $sheets->count() }} / <span id="totalLinesMeta">{{ $totalLines }}</span></div>
                </div>
            </div>

            <div class="row scc-status-cards">
                <div class="col-6 col-md-3">
                    <div class="scc-stat-card scc-stat-incomplete">
                        <div class="scc-stat-label">Incomplete</div>
                        <div class="scc-stat-count" id="statCountIncomplete">{{ $statusStats['INCOMPLETE']['count'] }} Article</div>
                        <div class="scc-stat-qty">Qty: <span id="statQtyIncomplete">{{ number_format($statusStats['INCOMPLETE']['qty'], 2) }}</span></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="scc-stat-card scc-stat-recount">
                        <div class="scc-stat-label">Recount</div>
                        <div class="scc-stat-count" id="statCountRecount">{{ $statusStats['RECOUNT']['count'] }} Article</div>
                        <div class="scc-stat-qty">Qty: <span id="statQtyRecount">{{ number_format($statusStats['RECOUNT']['qty'], 2) }}</span></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="scc-stat-card scc-stat-notmatch">
                        <div class="scc-stat-label">Not Match</div>
                        <div class="scc-stat-count" id="statCountNotMatch">{{ $statusStats['NOT MATCH']['count'] }} Article</div>
                        <div class="scc-stat-qty">Qty: <span id="statQtyNotMatch">{{ number_format($statusStats['NOT MATCH']['qty'], 2) }}</span></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="scc-stat-card scc-stat-match">
                        <div class="scc-stat-label">Match</div>
                        <div class="scc-stat-count" id="statCountMatch">{{ $statusStats['MATCH']['count'] }} Article</div>
                        <div class="scc-stat-qty">Qty: <span id="statQtyMatch">{{ number_format($statusStats['MATCH']['qty'], 2) }}</span></div>
                    </div>
                </div>
            </div>

            @if($accessRole == 'accounting')
            <div class="alert alert-warning mt-1 mb-0 py-50 px-1" style="font-size:.8rem;">
                <i data-feather="alert-triangle" style="width:14px;height:14px;" class="align-middle mr-25"></i>
                Anda mengakses sebagai <strong>Accounting (override)</strong>.
            </div>
            @endif
        </div>
    </div>

    {{-- ════ INPUT FORM ════ --}}
    @if(!$mapping->finish_time || $accessRole == 'accounting')

    @if($isAuto)
    {{-- ── MODE AUTO (005/006/042/049): input per artikel ── --}}
    <div class="card">
        <div class="card-header"><h4 class="card-title">Input Artikel</h4></div>
        <div class="card-body">
            <div class="form-row d-flex align-items-end">
                @if($isPartner)
                <div class="form-group col-md-2 col-6">
                    <label>Lokasi*</label>
                    <select class="form-control" id="inLocation" style="width:100%">
                        <option value="">- Pilih -</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->location_code }}">{{ $loc->location_name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="form-group col-md-4 col-12">
                    <label>Article</label>
                    <select class="form-control" id="inArticle" style="width:100%"></select>
                </div>
                <div class="form-group col-md-1 col-6">
                    <label>UOM</label>
                    <select class="form-control" id="inUom"></select>
                </div>
                <div class="form-group col-md-1 col-6">
                    <label>Min Pkg</label>
                    <input type="text" class="form-control text-right" id="inMinPkg" readonly tabindex="-1"/>
                </div>
                <div class="form-group col-md-1 col-6">
                    <label>QTY*</label>
                    <input type="text" class="form-control numeral-mask-digit text-right" id="inQty" maxlength="12"/>
                </div>
                <div class="form-group col-md-2 col-6">
                    <label>Note</label>
                    <input type="text" class="form-control" id="inNote" maxlength="150"/>
                </div>
                <div class="form-group col-md-2 col-12">
                    <button type="button" class="btn btn-success btn-block" id="btnAddLine">
                        <i data-feather="save" class="align-middle mr-25"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    @else
    {{-- ── MODE SHEET (non-auto): 7 baris input, kartu vertikal (mobile-friendly) ── --}}
    <div class="card" id="cardSheetInput">
        <div class="card-header">
            <h4 class="card-title">Input Kartu STO</h4>
            <div class="heading-elements">
                <span class="text-muted" style="font-size:.8rem;">Isi 1–7 baris, lalu klik Simpan Sheet</span>
            </div>
        </div>
        <div class="card-body">

            {{-- ── Nomor STO manual (dropdown, hilang otomatis kalau sudah dipakai) ── --}}
            <div class="form-row">
                <div class="form-group col-md-3 col-12">
                    <label>Nomor STO* <small class="text-muted" id="numberRangeInfo"></small></label>
                    <select class="form-control" id="inSelectedNumber">
                        <option value="">- Memuat nomor... -</option>
                    </select>
                </div>
            </div>

            <hr class="my-75">

            <div id="sheetInputBody">
                @for($i = 0; $i < 7; $i++)
                  <div class="scc-sheet-row-card" data-row="{{ $i }}">
                    <div class="scc-sheet-row-num">{{ $i + 1 }}</div>
                    <div class="form-row">
                        @if($isPartner)
                        <div class="form-group col-md-2 col-6">
                            <label class="scc-field-label">Lokasi*</label>
                            <select class="form-control sheet-location" style="width:100%">
                                <option value="">- Pilih -</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->location_code }}">{{ $loc->location_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="form-group col-md-4 col-12">
                            <label class="scc-field-label">Article*</label>
                            <select class="form-control sheet-article" style="width:100%"></select>
                        </div>
                        <div class="form-group col-md-1 col-6">
                            <label class="scc-field-label">UOM</label>
                            <select class="form-control sheet-uom"></select>
                        </div>
                        <div class="form-group col-md-1 col-6">
                            <label class="scc-field-label">Min Packing</label>
                            <input type="text" class="form-control text-right sheet-minpkg" readonly tabindex="-1"/>
                        </div>
                        <div class="form-group col-md-2 col-6">
                            <label class="scc-field-label">QTY*</label>
                            <input type="text" class="form-control text-right numeral-mask-digit sheet-qty" maxlength="12"/>
                        </div>
                        <div class="form-group col-md-2 col-6">
                            <label class="scc-field-label">Note</label>
                            <input type="text" class="form-control sheet-note" maxlength="150"/>
                        </div>
                    </div>
                </div>
                @endfor
            </div>

            <button type="button" class="btn btn-success mt-75" id="btnSaveSheet">
                <i data-feather="save" class="align-middle mr-25"></i> Simpan Kartu STO
            </button>
        </div>
    </div>
    @endif

    @endif

      {{-- ════ ACCORDION SHEETS TERSIMPAN ════ --}}
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">List Kartu STO Tersimpan</h4>
            <small>Review terlebih dahulu sebelum tandai selesai</small>
        </div>

        <div class="scc-filter-bar">
            <div class="scc-filter-item">
                <label class="scc-filter-label">Nomor STO</label>
                <select class="form-control form-control-sm" id="searchStoNumber">
                    <option value="">Semua Nomor</option>
                </select>
            </div>
            <div class="scc-filter-item">
                <label class="scc-filter-label">Status</label>
                <select class="form-control form-control-sm" id="filterStatus">
                    <option value="">Semua Status</option>
                    <option value="MATCH">MATCH</option>
                    <option value="RECOUNT">RECOUNT</option>
                    <option value="NOT MATCH">NOT MATCH</option>
                    <option value="INCOMPLETE">INCOMPLETE</option>
                </select>
            </div>
            <div class="scc-filter-item">
                <label class="scc-filter-label">Cari Artikel</label>
                <input type="text" class="form-control form-control-sm" id="searchLine" placeholder="Kode / nama artikel...">
            </div>
        </div>
          <div class="card-body p-0">
            <div class="accordion" id="accordionSheets">
                @forelse($sheets as $si => $sheet)
                @php
                    $hdr        = $sheet['hdr'];
                    $sheetLines = $sheet['lines'];
                    $matchCnt   = $sheetLines->where('count_status','MATCH')->count();
                    $rcCnt      = $sheetLines->where('count_status','RECOUNT')->count();
                    $nmCnt      = $sheetLines->where('count_status','NOT MATCH')->count();
                    $icCnt      = $sheetLines->where('count_status','INCOMPLETE')->count();
                    $allMatch   = $icCnt === 0 && $nmCnt === 0;
                @endphp
                  <div class="card mb-0 border-bottom scc-sheet-card" id="sheetCard{{ $hdr->sto_id }}" data-sto-number="{{ $hdr->sto_number }}">
                    <div class="card-header py-75 px-1" id="sheetHeading{{ $hdr->sto_id }}" style="cursor:pointer;"
                         data-toggle="collapse" data-target="#sheetCollapse{{ $hdr->sto_id }}"
                         aria-expanded="{{ $si === $sheets->count()-1 ? 'true' : 'false' }}">
                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:.4rem;">
                            <div class="d-flex align-items-center" style="gap:8px;">
                                <i data-feather="chevron-down" style="width:14px;height:14px;" class="scc-chevron"></i>
                                <span class="font-weight-bold" style="font-size:.85rem;">{{ $hdr->sto_number }}</span>
                                <span class="badge badge-light-secondary" style="font-size:.72rem;">{{ $sheetLines->count() }} baris</span>
                            </div>
                            <div class="d-flex align-items-center" style="gap:4px;">
                                @if($matchCnt) <span class="badge badge-success">MATCH: {{ $matchCnt }}</span> @endif
                                @if($rcCnt)    <span class="badge badge-warning">RECOUNT: {{ $rcCnt }}</span> @endif
                                @if($nmCnt)    <span class="badge badge-danger">NOT MATCH: {{ $nmCnt }}</span> @endif
                                @if($icCnt)    <span class="badge badge-secondary">INCOMPLETE: {{ $icCnt }}</span> @endif
                            </div>
                        </div>
                    </div>
                    <div id="sheetCollapse{{ $hdr->sto_id }}"
                         class="collapse {{ $si === $sheets->count()-1 ? 'show' : '' }}">
                        <div class="table-responsive">
                            <table class="table table-sm scc-lines-table mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width:30px;">#</th>
                                        <th>Code</th>
                                        <th>Article Desc</th>
                                        @if($isPartner)
                                        <th class="d-none d-md-table-cell">Lokasi</th>
                                        @endif
                                        <th class="d-none d-md-table-cell">UOM</th>
                                        <th class="d-none d-md-table-cell text-right">Min Packing</th>
                                        <th class="text-right">QTY</th>
                                        <th class="text-center">Status</th>
                                        <th class="d-none d-md-table-cell">Note</th>
                                        <th style="width:36px;"></th>
                                    </tr>
                                </thead>
                                <tbody class="sheet-tbody" id="sheetBody{{ $hdr->sto_id }}">
                                    @foreach($sheetLines as $li => $l)
                                       <tr class="sto-line" data-id="{{ $l->dtl_id }}" data-sto-id="{{ $hdr->sto_id }}"
                                          data-status="{{ $l->count_status }}"
                                          data-article-code="{{ $l->article_code }}"
                                          data-article-desc="{{ $l->article_desc }}"
                                          data-is-manual="{{ $l->is_manual ? '1' : '0' }}"
                                          data-uom="{{ $l->uom }}"
                                          data-min-package="{{ $l->min_package }}"
                                          data-my-qty="{{ $l->my_qty }}"
                                          data-note="{{ $l->note }}"
                                          data-qty-counter1="{{ $l->qty_counter1 }}"
                                          data-qty-counter2="{{ $l->qty_counter2 }}"
                                          data-qty-counter3="{{ $l->qty_counter3 }}"
                                          data-location-number="{{ $l->location_number }}"
                                          data-location-name="{{ $l->location_name }}">
                                        <td class="scc-idx text-muted">{{ $li + 1 }}</td>
                                        <td class="font-weight-bold">{{ $l->article_code ?? 'OTHER' }}</td>
                                        <td>{{ $l->article_desc }}</td>
                                        @if($isPartner)
                                        <td class="d-none d-md-table-cell">{{ $l->location_name ?? ($l->location_number ?? '-') }}</td>
                                        @endif
                                        <td class="d-none d-md-table-cell">{{ $l->uom }}</td>
                                        <td class="d-none d-md-table-cell text-right">{{ $l->min_package }}</td>
                                        <td class="text-right font-weight-bold qty-cell">{{ $l->my_qty !== null ? number_format((float)$l->my_qty,2) : '-' }}</td>
                                        <td class="text-center"><span class="badge {{ $badgeMap[$l->count_status] ?? 'badge-secondary' }}">{{ $l->count_status }}</span></td>
                                        <td class="d-none d-md-table-cell text-truncate" style="max-width:120px;" title="{{ $l->note }}">{{ $l->note }}</td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center" style="gap:6px;">
                                                <a class="text-primary" style="cursor:pointer" onclick="editLine({{ $l->dtl_id }}, this)" title="Edit">
                                                    <i data-feather="edit-2" style="width:14px;height:14px;"></i>
                                                </a>
                                                {{--<a class="text-danger" style="cursor:pointer" onclick="deleteLine({{ $l->dtl_id }}, this)" title="Hapus">
                                                    <i data-feather="trash-2" style="width:14px;height:14px;"></i>
                                                </a>--}}
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>{{-- /table-responsive --}}

                        {{-- ── tombol tambah artikel ke sheet ini (buka modal) ── --}}
                        @if(!$mapping->finish_time || $accessRole == 'accounting')
                        <div class="scc-inline-add">
                            <button type="button" class="btn btn-sm btn-outline-success scc-btn-add-article"
                                    data-sto-id="{{ $hdr->sto_id }}"
                                    data-sto-number="{{ $hdr->sto_number }}">
                                <i data-feather="plus-circle" style="width:14px;height:14px;" class="align-middle mr-25"></i>
                                Tambah Artikel ke {{ $hdr->sto_number }}
                            </button>
                        </div>
                        @endif
                    </div>{{-- /sheetCollapse --}}
                </div>
                @empty
                <div class="text-center text-muted py-2" id="emptySheetMsg">Belum ada kartu sto tersimpan.</div>
                @endforelse
            </div>
        </div>
        <div class="card-footer">
            <a href="{{ route('stockCount.index') }}" class="btn btn-light">Back</a>
            @if(!$mapping->finish_time)
            <button type="button" class="btn btn-primary" id="btnFinish">
                <i data-feather="check-circle" class="align-middle mr-25"></i> Tandai Selesai
            </button>
            @endif
        </div>
    </div>

</section>
@endsection

@section('styles')
<style>
    .scc-header-card { border-top: 3px solid #5a6acf; }
    .scc-meta-item   { margin-bottom: .6rem; }
    .scc-meta-label  { font-size:.68rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#8892a3;display:flex;align-items:center;gap:4px;margin-bottom:2px; }
    .scc-meta-value  { font-size:1rem;font-weight:700;color:#2a3342; }

    /* ── kartu per baris input sheet (mode non-auto) ── */
    .scc-sheet-row-card{
        position:relative; border:1px solid #e5e8ee; border-radius:6px;
        padding:.75rem .75rem .1rem 2.2rem; margin-bottom:.6rem; background:#fff;
    }
    .scc-sheet-row-card:hover{ border-color:#c9d0da; }
    .scc-sheet-row-num{
        position:absolute; left:.6rem; top:.7rem; font-weight:700; color:#8892a3; font-size:.8rem;
    }
    .scc-sheet-row-card .form-group{ margin-bottom:.5rem; }

      .scc-field-label{
        font-size:.68rem; font-weight:600; color:#8892a3; margin-bottom:.2rem;
        text-transform:uppercase; letter-spacing:.03em; display:block;
    }

    /* ══ INLINE ADD — tombol per sheet (buka modal) ══ */
    .scc-inline-add {
        padding: .65rem .85rem;
        border-top: 1px dashed #e5e8ee;
        background: #f7fbf8;
    }
    .scc-btn-add-article {
        font-size: .78rem;
        padding: .3rem .75rem;
    }

    /* ══════════════════════════════════════════════
       FILTER BAR — section terpisah, bukan satu blok dengan judul
    ══════════════════════════════════════════════ */
    .scc-filter-bar{
        display:flex; flex-wrap:wrap; gap:.75rem; align-items:flex-end;
        background:#f7f9fc; border-top:1px solid #e5e8ee; border-bottom:1px solid #e5e8ee;
        padding:.85rem 1.25rem;
    }
    .scc-filter-item{
        display:flex; flex-direction:column; min-width:160px; flex:1 1 160px;
    }
    .scc-filter-item .form-control{ width:100%; }
    .scc-filter-label{
        font-size:.66rem; font-weight:700; color:#8892a3; text-transform:uppercase;
        letter-spacing:.04em; margin-bottom:.25rem;
    }
    @media (min-width:768px){
        .scc-filter-item{ flex:0 1 200px; }
    }
    @media (max-width:575px){
        .scc-filter-bar{ padding:.75rem; }
    }

    /* ══════════════════════════════════════════════
       ACCORDION SHEET TERSIMPAN — clean & rapi
    ══════════════════════════════════════════════ */
    #accordionSheets{
        padding: .85rem;
        background: #fff;               /* satu warna latar, tidak dobel abu-abu */
    }
    .scc-sheet-card{
        border: 1px solid #e5e8ee !important;
        border-radius: 8px;
        margin-bottom: .75rem !important;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(20,25,35,.04);
    }
    .scc-sheet-card:last-child{ margin-bottom: 0 !important; }

    .scc-sheet-card > .card-header{
        background: #f7f9fc;
        border-bottom: 1px solid #e5e8ee;
        padding: .65rem .85rem !important;
    }
    .scc-sheet-card > .card-header:hover{ background: #eef1f8; }

    .scc-chevron{ transition: transform .2s; color:#8892a3; }
    .scc-sheet-card > .card-header[aria-expanded="true"] .scc-chevron{ transform: rotate(180deg); }

    /* ── tabel di dalam sheet: zebra-stripe + rapat tapi jelas ── */
    .scc-lines-table{ margin-bottom: 0 !important; }
    .scc-lines-table thead th{
        font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
        white-space: nowrap; border-top: 0; border-bottom: 1px solid #e5e8ee !important;
        background: #fbfbfd; color:#6b7688; padding: .5rem .65rem;
    }
    .scc-lines-table tbody td{
        vertical-align: middle; padding: .5rem .65rem; font-size: .82rem;
        border-top: 1px solid #f0f1f5;
    }
    .scc-lines-table tbody tr:nth-child(even){ background: #fafbfc; }
    .scc-lines-table tbody tr:hover{ background: #eef2ff; }
    .scc-idx{ color:#8892a3; font-weight:700; }

    /* ── dropdown Nomor STO biar tidak terlalu sempit ── */
    #inSelectedNumber{ font-weight: 600; }

    /* ── select2 di dalam modal tampil di atas backdrop ── */
    .select2-container--open{ z-index: 9999; }

    /* ══════════════════════════════════════════════
       4 KARTU STATUS (Incomplete / Recount / Not Match / Match)
    ══════════════════════════════════════════════ */
    .scc-status-cards{ margin-top:.5rem; }
    .scc-stat-card{
        border-radius:8px; padding:.7rem .85rem; margin-bottom:.6rem;
        border:1px solid transparent;
    }
    .scc-stat-label{
        font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
        opacity:.85; margin-bottom:.2rem;
    }
    .scc-stat-count{ font-size:1.35rem; font-weight:800; line-height:1.1; }
    .scc-stat-qty{ font-size:.72rem; font-weight:600; opacity:.85; margin-top:.15rem; }

    .scc-stat-incomplete{ background:#f1f2f5; border-color:#e2e4e9; color:#5b6472; }
    .scc-stat-recount    { background:#fdf4e3; border-color:#f3e2c4; color:#8a5a08; }
    .scc-stat-notmatch   { background:#fbeae8; border-color:#f5c2c7; color:#8a1f18; }
    .scc-stat-match      { background:#e8f4ec; border-color:#cfe6d8; color:#1a5c37; }
</style>
@endsection

@section('scripts')
<script>
const encMappingId  = "{{ $encMappingId }}";
const IS_AUTO       = {{ $isAuto ? 'true' : 'false' }};
const IS_PARTNER    = {{ $isPartner ? 'true' : 'false' }};
const IS_ACCOUNTING = {{ $accessRole == 'accounting' ? 'true' : 'false' }};
const MANUAL_PREFIX = 'MANUAL::';
// dipakai untuk isi ulang dropdown Lokasi di modal Edit (select2 butuh option di-generate manual)
const LOCATIONS_DATA = {!! json_encode($isPartner ? $locations->map(fn($l) => ['code' => $l->location_code, 'name' => $l->location_name])->values() : []) !!};

// ── helpers ──
function statusBadge(st) {
    const map = { 'INCOMPLETE':'badge-secondary','NOT MATCH':'badge-danger','RECOUNT':'badge-warning','MATCH':'badge-success' };
    return `<span class="badge ${map[st]||'badge-secondary'}">${st}</span>`;
}
function isManualValue(v) { return !!v && v.indexOf(MANUAL_PREFIX) === 0; }

function updateRealisasi(pct) {
    const $el = $('#realisasiValue');
    const plan = parseFloat($el.data('target-plan')) || 0;
    const p    = Number(pct) || 0;
    $el.html(`<span class="${p >= plan ? 'text-success' : 'text-warning'}">${p.toFixed(2)}%</span>`);
}

function updateTotalLines() {
    const total = $('#accordionSheets .sto-line').length;
    $('#totalLinesMeta').text(total);
}

function updateStatusStats() {
    const stats = {
        'INCOMPLETE': { key: 'Incomplete', count: 0, qty: 0 },
        'RECOUNT':    { key: 'Recount',    count: 0, qty: 0 },
        'NOT MATCH':  { key: 'NotMatch',   count: 0, qty: 0 },
        'MATCH':      { key: 'Match',      count: 0, qty: 0 },
    };

    $('#accordionSheets tr.sto-line').each(function() {
        const status = $(this).data('status');
        if (!stats[status]) return;
        stats[status].count++;

        const qtyText = $(this).find('.qty-cell').text().replace(/,/g, '').trim();
        const qtyVal  = parseFloat(qtyText);
        if (!isNaN(qtyVal)) stats[status].qty += qtyVal;
    });

    Object.values(stats).forEach(s => {
        $(`#statCount${s.key}`).text(s.count);
        $(`#statQty${s.key}`).text(s.qty.toFixed(2));
    });
}

// ── init select2 untuk 1 elemen article (mode auto) ──
function initArticleSelect2($el) {
    $el.select2({
        width: '100%',
        placeholder: '- Pilih atau ketik Article -',
        allowClear: true,
        tags: true,
        createTag: function(params) {
            const term = $.trim(params.term);
            if (!term) return null;
            return { id: MANUAL_PREFIX + term, text: term, newOption: true };
        },
        templateResult: function(data) {
            if (data.newOption) return $(`<span><span class="badge badge-light-warning mr-50">OTHER</span>${data.text}</span>`);
            return data.text;
        },
        templateSelection: function(data) {
            return (data.id && data.id.indexOf(MANUAL_PREFIX) === 0) ? 'OTHER - ' + data.text : data.text;
        }
    });
}

// ── init select2 untuk 1 elemen lokasi ──
function initLocationSelect2($el, dropdownParent) {
    const opts = { width: '100%', placeholder: '- Pilih Lokasi -', allowClear: true };
    if (dropdownParent) opts.dropdownParent = dropdownParent;
    $el.select2(opts);
}

// ── load artikel ke select ──
function loadArticlesInto($sel, opts) {
    let html = '<option value=""></option>';
    if (opts.in_stock && opts.in_stock.length) {
        html += '<optgroup label="Tersedia">';
        opts.in_stock.forEach(a => {
            html += `<option value="${a.article_alternative_code}" data-desc="${a.article_desc||''}" data-uom="${a.uom||''}" data-uom-member="${a.uom_member||''}" data-min-pkg="${a.min_package||''}">${a.article_alternative_code} - ${a.article_desc}</option>`;
        });
        html += '</optgroup>';
    }
    if (opts.others && opts.others.length) {
        html += '<optgroup label="Lainnya">';
        opts.others.forEach(a => {
            html += `<option value="${a.article_alternative_code}" data-desc="${a.article_desc||''}" data-uom="${a.uom||''}" data-uom-member="${a.uom_member||''}" data-min-pkg="${a.min_package||''}">${a.article_alternative_code} - ${a.article_desc}</option>`;
        });
        html += '</optgroup>';
    }
    $sel.html(html);
}

let articleOptions = null; // cache

function getArticleOptions(cb) {
    if (articleOptions) { cb(articleOptions); return; }
    $.get("{{ route('stockCount.getArticles') }}", { mapping_id: encMappingId }, function(data) {
        articleOptions = data;
        cb(data);
    }, 'json');
}


function loadAvailableNumbers() {
    $.get("{{ route('stockCount.getAvailableNumbers') }}", { mapping_id: encMappingId }, function(res) {
        const $sel = $('#inSelectedNumber');

        if ($sel.data('select2')) $sel.select2('destroy');

        $sel.html('<option value="">- Pilih Nomor -</option>');
        (res.available || []).forEach(item => {
            $sel.append(`<option value="${item.no}">${item.label}</option>`);
        });
        $('#numberRangeInfo').text(`( ${res.range_label} )`);
        if (!res.available.length) {
            $sel.html('<option value="">Nomor habis — hubungi Accounting</option>');
        }

        $sel.select2({ width: '100%', placeholder: '- Pilih Nomor -', allowClear: true });
    }, 'json');
}

function updateStoNumberFilterOptions() {
    const $filter  = $('#searchStoNumber');
    const current  = $filter.val(); // pertahankan pilihan user kalau masih ada
    const numbers  = [];

    $('#accordionSheets .scc-sheet-card').each(function () {
        const no = $(this).data('sto-number');
        if (no && !numbers.includes(no)) numbers.push(no);
    });
    numbers.sort();

    if ($filter.data('select2')) $filter.select2('destroy');

    let html = '<option value="">Semua Nomor STO</option>';
    numbers.forEach(no => html += `<option value="${no}">${no}</option>`);
    $filter.html(html);

    if (numbers.includes(current)) $filter.val(current);

    $filter.select2({ width: '100%', placeholder: 'Semua Nomor STO', allowClear: true });
}

$(document).ready(function () {

    // ════ SELECT2 UNTUK DROPDOWN LOKASI (partner) ════
    if (IS_PARTNER) {
        if ($('#inLocation').length) initLocationSelect2($('#inLocation'));
        $('.sheet-location').each(function () { initLocationSelect2($(this)); });
    }

    // ════ MODE AUTO ════
    if (IS_AUTO) {
        getArticleOptions(function(data) {
            loadArticlesInto($('#inArticle'), data);
            initArticleSelect2($('#inArticle'));
        });

         $(document).on('change', '#inArticle', function() {
            const val = $(this).val();
            if (isManualValue(val)) {
                if ($('#inUom').is('select')) $('#inUom').replaceWith('<input type="text" class="form-control" id="inUom" placeholder="UOM* (wajib diisi)">');
                $('#inMinPkg').val('0');
                return;
            }
            if ($('#inUom').is('input')) $('#inUom').replaceWith('<select class="form-control" id="inUom"></select>');
            const opt = $(this).find(':selected');
            const uomMember = opt.data('uom-member');
            const uom       = opt.data('uom');
            let uomOpt = '';
            if (uomMember) String(uomMember).split(',').forEach(u => uomOpt += `<option>${u}</option>`);
            else if (uom) uomOpt = `<option>${uom}</option>`;
            $('#inUom').html(uomOpt);
            const mp = opt.data('min-pkg');
            $('#inMinPkg').val((mp === undefined || mp === null || mp === '') ? '0' : mp);
        });

        $('#btnAddLine').on('click', function() { submitLineAuto(false); });
    }

      // ════ MODE SHEET ════
    if (!IS_AUTO) {
        getArticleOptions(function(data) {
            $('#sheetInputBody .sheet-article').each(function() {
                loadArticlesInto($(this), data);
                initArticleSelect2($(this));
            });
        });

        loadAvailableNumbers();   // ← isi dropdown Nomor STO

        // update uom & minpkg saat artikel dipilih di tiap baris sheet
         $(document).on('change', '.sheet-article', function() {
            const $row  = $(this).closest('.scc-sheet-row-card');
            const val   = $(this).val();
            let $uom    = $row.find('.sheet-uom');
            const $pkg  = $row.find('.sheet-minpkg');

            if (isManualValue(val)) {
                if ($uom.is('select')) {
                    $uom.replaceWith('<input type="text" class="form-control sheet-uom" placeholder="UOM* (wajib diisi)">');
                }
                $pkg.val('0');
                return;
            }

            if ($uom.is('input')) {
                $uom.replaceWith('<select class="form-control sheet-uom"></select>');
                $uom = $row.find('.sheet-uom'); // ambil ulang elemen setelah replaceWith
            }
            const opt = $(this).find(':selected');
            const uomMember = opt.data('uom-member');
            const uom       = opt.data('uom');
            let uomOpt = '';
            if (uomMember) String(uomMember).split(',').forEach(u => uomOpt += `<option>${u}</option>`);
            else if (uom) uomOpt = `<option>${uom}</option>`;
            $uom.html(uomOpt);
            const mp = opt.data('min-pkg');
            $pkg.val((mp === undefined || mp === null || mp === '') ? '0' : mp);
        });

        $('#btnSaveSheet').on('click', submitSheet);
    }

    updateStoNumberFilterOptions();   // ← isi dropdown filter dari kartu yang sudah ada (server-rendered)

    // ════ FILTER GABUNGAN: Nomor STO + Status + Cari Artikel ════
    function applyAllFilters() {
        const selectedNo     = $('#searchStoNumber').val();
        const selectedStatus = $('#filterStatus').val();
        const term           = ($('#searchLine').val() || '').toLowerCase().trim();

        $('#accordionSheets .scc-sheet-card').each(function() {
            const $card = $(this);
            const stoNumber = ($card.data('sto-number') || '').toString();

            const matchNumber = !selectedNo || stoNumber === selectedNo;
            const matchStatus = !selectedStatus || $card.find(`tr.sto-line[data-status="${selectedStatus}"]`).length > 0;

            let anyRowMatchesText = term === '';
            $card.find('tr.sto-line').each(function() {
                const rowMatches = term === '' || $(this).text().toLowerCase().includes(term);
                $(this).toggle(rowMatches);
                if (rowMatches) anyRowMatchesText = true;
            });

            $card.toggle(matchNumber && matchStatus && anyRowMatchesText);
        });
    }

    $('#searchStoNumber').on('change', applyAllFilters);
    $('#filterStatus').on('change', applyAllFilters);
    $('#searchLine').on('input', applyAllFilters);

    // ════ FINISH ════
    $('#btnFinish').on('click', function() {
        Swal.fire({
            title: 'Tandai Target Ini Selesai?',
            text: 'Setelah selesai, baris tidak bisa ditambah lagi kecuali oleh Accounting.',
            icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Selesai'
        }).then(r => {
            if (!r.isConfirmed) return;
            $.post("{{ route('stockCount.finish') }}", { _token: "{{ csrf_token() }}", mapping_id: encMappingId }, function(res) {
                show_msg(res.title, res.message, res.alert);
                if (res.status == 1) setTimeout(() => window.location.href = res.redirect_url, 1200);
            }, 'json');
        });
    });

    // ════ INIT MODAL TAMBAH ARTIKEL ════
    initModalAddArticle();
});

// ════════════════════════════════════════════════
// SUBMIT LINE (AUTO)
// ════════════════════════════════════════════════
function submitLineAuto(confirmAccumulate) {
    const val = $('#inArticle').val();
    const qty = ($('#inQty').val() || '').replace(/,/g, '');
    if (!val)  { Swal.fire('Warning','Pilih atau ketik artikel dulu.','warning'); return; }
    //if (!qty || parseFloat(qty) <= 0) { Swal.fire('Warning','QTY harus lebih dari 0.','warning'); return; }

    const manual      = isManualValue(val);
    const article     = manual ? '' : val;
    const articleDesc = manual
        ? val.substring(MANUAL_PREFIX.length)
        : ($('#inArticle').find(':selected').data('desc') || '');

    if (manual && !($('#inUom').val() || '').trim()) {
        Swal.fire('Warning', 'UOM wajib diisi untuk artikel manual.', 'warning');
        return;
    }

    const locationNumber = IS_PARTNER ? $('#inLocation').val() : '';
    if (IS_PARTNER && !locationNumber) { Swal.fire('Warning','Pilih lokasi dulu.','warning'); return; }

   const $btn = $('#btnAddLine');
    $btn.data('original-html', $btn.html())
        .prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm mr-50" role="status"></span>Menyimpan...');

    $.post("{{ route('stockCount.storeLine') }}", {
        _token: "{{ csrf_token() }}",
        mapping_id: encMappingId,
        is_manual: manual ? 1 : 0,
        article: article,
        article_desc: articleDesc,
        uom: $('#inUom').val(),
        min_package: $('#inMinPkg').val(),
        qty: qty,
        note: $('#inNote').val(),
        location_number: locationNumber,
        confirm_accumulate: confirmAccumulate ? 1 : 0,
    }, function(res) {
        $btn.prop('disabled', false).html($btn.data('original-html'));
        if (typeof feather !== 'undefined') feather.replace();

        if (res.confirm_required) {
            Swal.fire({
                title: res.title, text: res.message, icon: 'question',
                showCancelButton: true, confirmButtonText: 'Ya, Tambahkan', cancelButtonText: 'Batal',
            }).then(r => { if (r.isConfirmed) submitLineAuto(true); });
            return;
        }
        if (res.status == 1) {
            renderRowToSheet(res.row, res.sto_number);
            updateRealisasi(res.target_act_loc);
            Swal.fire({ toast:true, position:'top-end', icon:'success', title: `Tersimpan: ${res.sto_number}`, showConfirmButton:false, timer:2000 });
            $('#inArticle').val(null).trigger('change');
            $('#inQty, #inNote, #inMinPkg').val('');
            if (IS_PARTNER) $('#inLocation').val(null).trigger('change');
            if ($('#inUom').is('select')) $('#inUom').html('');
        } else {
            (Array.isArray(res.message) ? res.message : [res.message]).forEach(m => show_msg(res.title, m, res.alert));
        }
   }, 'json').fail(() => {
        $btn.prop('disabled', false).html($btn.data('original-html'));
        if (typeof feather !== 'undefined') feather.replace();
        show_msg('Error', 'Terjadi kesalahan, cek console.', 'error');
    });
}

// ════════════════════════════════════════════════
// SUBMIT SHEET (NON-AUTO)
// ════════════════════════════════════════════════
function submitSheet() {
    const lines = [];
    const articlesSeen = [];
    let valid = true;

    $('#sheetInputBody .scc-sheet-row-card').each(function() {
        const val     = $(this).find('.sheet-article').val();
        const qty     = ($(this).find('.sheet-qty').val() || '').replace(/,/g,'');
        const uom     = $(this).find('.sheet-uom').val();
        const minpkg  = $(this).find('.sheet-minpkg').val();
        const note    = $(this).find('.sheet-note').val();

        if (!val && (!qty || parseFloat(qty) <= 0)) return; // baris kosong, skip

        if (!val) { show_msg('Warning','Ada baris yang belum dipilih artikelnya.','warning'); valid=false; return false; }
        //if (!qty || parseFloat(qty) <= 0) { show_msg('Warning','Ada baris yang QTY-nya kosong atau 0.','warning'); valid=false; return false; }

         const manual      = isManualValue(val);
        const article     = manual ? '' : val;
        const articleDesc = manual
            ? val.substring(MANUAL_PREFIX.length)
            : ($(this).find('.sheet-article').find(':selected').data('desc') || '');

        if (manual && !(uom || '').trim()) {
            show_msg('Warning', `UOM wajib diisi untuk artikel manual: ${articleDesc}`, 'warning');
            valid = false; return false;
        }

        const locationNumber = IS_PARTNER ? $(this).find('.sheet-location').val() : '';
        if (val && IS_PARTNER && !locationNumber) { show_msg('Warning',`Pilih lokasi untuk artikel: ${articleDesc}`,'warning'); valid=false; return false; }

        const key = manual ? ('MANUAL::'+articleDesc.toUpperCase()) : article;

        if (articlesSeen.includes(key)) { show_msg('Warning',`Artikel duplikat: ${articleDesc}`,'warning'); valid=false; return false; }
        articlesSeen.push(key);

        lines.push({ is_manual: manual?1:0, article, article_desc: articleDesc, uom, min_package: minpkg, qty, note, location_number: locationNumber });
    });

    if (!valid) return;
    if (lines.length === 0) { show_msg('Warning','Minimal 1 baris harus diisi beserta QTY-nya.','warning'); return; }

    if (!IS_AUTO) {
        const selNo = $('#inSelectedNumber').val();
        if (!selNo) { show_msg('Warning', 'Pilih Nomor STO terlebih dahulu.', 'warning'); return; }
    }

     const $btn = $('#btnSaveSheet');
    $btn.data('original-html', $btn.html())
        .prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm mr-50" role="status"></span>Menyimpan...');

    $.post("{{ route('stockCount.storeSheet') }}", {
        _token: "{{ csrf_token() }}",
        mapping_id: encMappingId,
        lines: lines,
        selected_number: $('#inSelectedNumber').length ? $('#inSelectedNumber').val() : null,
    }, function(res) {
        $btn.prop('disabled', false).html($btn.data('original-html'));
        if (typeof feather !== 'undefined') feather.replace();
         if (res.status == 1) {
            res.lines.forEach(r => renderRowToSheet(r, res.sto_number));
            updateRealisasi(res.target_act_loc);
            Swal.fire({ toast:true, position:'top-end', icon:'success', title:`Nomor STO ${res.sto_number} tersimpan`, showConfirmButton:false, timer:2500 });
            resetSheetInput();
            if (!IS_AUTO) loadAvailableNumbers();   // ← refresh dropdown, nomor terpakai hilang
        } else {
            (Array.isArray(res.message) ? res.message : [res.message]).forEach(m => show_msg(res.title, m, res.alert));
        }
    }, 'json').fail(() => {
        $btn.prop('disabled', false).html($btn.data('original-html'));
        if (typeof feather !== 'undefined') feather.replace();
        show_msg('Error', 'Terjadi kesalahan, cek console.', 'error');
    });
}

function resetSheetInput() {
    $('#sheetInputBody .scc-sheet-row-card').each(function() {
        $(this).find('.sheet-article').val(null).trigger('change'); // otomatis kembalikan UOM ke <select>
        $(this).find('.sheet-minpkg').val('');
        $(this).find('.sheet-qty').val('');
        $(this).find('.sheet-note').val('');
        if (IS_PARTNER) $(this).find('.sheet-location').val(null).trigger('change');
    });
}

// ════════════════════════════════════════════════
// RENDER BARIS KE ACCORDION
// ════════════════════════════════════════════════
function renderRowToSheet(r, stoNumber) {
    const stoId     = r.sto_id || null;
    const accordId  = `acc_${stoNumber.replace(/\//g,'_')}`;
    const locCell   = IS_PARTNER ? `<td class="d-none d-md-table-cell">${r.location_name ?? (r.location_number ?? '-')}</td>` : '';

     const isManualRow = !r.article_code || r.article_code === 'OTHER';
    const rowHtml = `
      <tr class="sto-line" data-id="${r.dtl_id}" data-sto-id="${r.sto_id ?? ''}" data-status="${r.count_status}"
          data-article-code="${isManualRow ? '' : r.article_code}"
          data-article-desc="${r.article_desc ?? ''}"
          data-is-manual="${isManualRow ? '1' : '0'}"
          data-uom="${r.uom ?? ''}"
          data-min-package="${r.min_package ?? ''}"
          data-my-qty="${r.my_qty ?? ''}"
          data-qty-counter1="${r.qty_counter1 ?? ''}"
          data-qty-counter2="${r.qty_counter2 ?? ''}"
          data-qty-counter3="${r.qty_counter3 ?? ''}"
          data-note="${r.note ?? ''}"
          data-location-number="${r.location_number ?? ''}"
          data-location-name="${r.location_name ?? ''}">
        <td class="scc-idx text-muted"></td>
        <td class="font-weight-bold">${r.article_code}</td>
        <td>${r.article_desc}</td>
        ${locCell}
        <td class="d-none d-md-table-cell">${r.uom ?? ''}</td>
        <td class="d-none d-md-table-cell text-right">${r.min_package ?? ''}</td>
        <td class="text-right font-weight-bold qty-cell">${r.my_qty ?? '-'}</td>
        <td class="text-center">${statusBadge(r.count_status)}</td>
        <td class="d-none d-md-table-cell text-truncate" style="max-width:120px;" title="${r.note??''}">${r.note??''}</td>
        <td class="text-center">
          <div class="d-flex justify-content-center" style="gap:6px;">
            <a class="text-primary" style="cursor:pointer" onclick="editLine(${r.dtl_id}, this)" title="Edit">
              <i data-feather="edit-2" style="width:14px;height:14px;"></i>
            </a>
            <a class="text-danger" style="cursor:pointer" onclick="deleteLine(${r.dtl_id}, this)" title="Hapus">
              <i data-feather="trash-2" style="width:14px;height:14px;"></i>
            </a>
          </div>
        </td>
      </tr>`;

    // cek apakah accordion sheet ini sudah ada
    let $tbody = $(`#accordionSheets .scc-sheet-card[data-sto-number="${stoNumber}"] .sheet-tbody`);

   if ($tbody.length === 0) {
        // buat accordion baru
        const sheetHtml = `
          <div class="card mb-0 border-bottom scc-sheet-card" id="sheetCard_${accordId}" data-sto-number="${stoNumber}">
            <div class="card-header py-75 px-1" style="cursor:pointer;"
                 data-toggle="collapse" data-target="#${accordId}" aria-expanded="true">
              <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:.4rem;">
                <div class="d-flex align-items-center" style="gap:8px;">
                  <i data-feather="chevron-down" style="width:14px;height:14px;" class="scc-chevron"></i>
                  <span class="font-weight-bold" style="font-size:.85rem;">${stoNumber}</span>
                  <span class="badge badge-light-secondary sheet-count-badge">0 baris</span>
                </div>
                <div class="d-flex align-items-center sheet-status-badges" style="gap:4px;"></div>
              </div>
            </div>
            <div id="${accordId}" class="collapse show">
              <div class="table-responsive">
                <table class="table table-sm scc-lines-table mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th style="width:30px;">#</th>
                      <th>Code</th><th>Article Desc</th>
                      ${IS_PARTNER ? '<th class="d-none d-md-table-cell">Lokasi</th>' : ''}
                      <th class="d-none d-md-table-cell">UOM</th>
                      <th class="d-none d-md-table-cell text-right">Min Packing</th>
                      <th class="text-right">QTY Saya</th>
                      <th class="text-center">Status</th>
                      <th class="d-none d-md-table-cell">Note</th>
                      <th style="width:36px;"></th>
                    </tr>
                  </thead>
                  <tbody class="sheet-tbody"></tbody>
                </table>
              </div>
              ${(IS_ACCOUNTING || true) ? `
              <div class="scc-inline-add">
                <button type="button" class="btn btn-sm btn-outline-success scc-btn-add-article"
                        data-sto-id="${stoId ?? ''}" data-sto-number="${stoNumber}">
                  <i data-feather="plus-circle" style="width:14px;height:14px;" class="align-middle mr-25"></i>
                  Tambah Artikel ke ${stoNumber}
                </button>
              </div>` : ''}
            </div>
          </div>`;

        $('#accordionSheets').append(sheetHtml);
        $('#emptySheetMsg').remove();
        updateStoNumberFilterOptions();
        $tbody = $(`#accordionSheets .scc-sheet-card[data-sto-number="${stoNumber}"] .sheet-tbody`);
    }

    // update atau insert baris
    const $existing = $tbody.find(`tr[data-id="${r.dtl_id}"]`);
    if ($existing.length) $existing.replaceWith(rowHtml);
    else $tbody.append(rowHtml);

    // renumber baris dalam sheet ini
    $tbody.find('tr').each(function(i) { $(this).find('.scc-idx').text(i + 1); });

    // update badge count
    const $card = $tbody.closest('.scc-sheet-card');
    const cnt   = $tbody.find('tr').length;
    $card.find('.sheet-count-badge').text(`${cnt} baris`);

    // update total lines meta
    updateTotalLines();
    updateStatusStats();

    if (typeof feather !== 'undefined') feather.replace();
}

// ════════════════════════════════════════════════
// DELETE LINE
// ════════════════════════════════════════════════
function deleteLine(dtlId, el) {
    Swal.fire({
        title: IS_ACCOUNTING ? 'Hapus Baris Ini? (semua qty 3 counter ikut hilang)' : 'Hapus Baris Ini?',
        icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Hapus'
    }).then(r => {
        if (!r.isConfirmed) return;
        $.ajax({
            url: "{{ route('stockCount.deleteLine', ['dtlId' => '__ID__']) }}".replace('__ID__', dtlId),
            method: 'DELETE',
            data: { _token: "{{ csrf_token() }}" },
            success: function(res) {
                if (res.status == 1) {
                    const $row  = $(`#accordionSheets tr[data-id="${dtlId}"]`);
                    const $tbody = $row.closest('.sheet-tbody');
                    const $card  = $tbody.closest('.scc-sheet-card');

                    if (res.whole_deleted) {
                        $row.remove();
                        $tbody.find('tr').each(function(i) { $(this).find('.scc-idx').text(i + 1); });
                        const remaining = $tbody.find('tr').length;
                        $card.find('.sheet-count-badge').text(`${remaining} baris`);
                        if (remaining === 0) {
                            $card.remove();
                            updateStoNumberFilterOptions();
                        }
                    } else {
                        renderRowToSheet(res.row, res.row.sto_number);
                        show_msg('Info', res.message, 'info');
                    }
                    if (res.target_act_loc !== null && res.target_act_loc !== undefined) {
                        updateRealisasi(res.target_act_loc);
                    }
                    updateTotalLines();
                    updateStatusStats();
                } else {
                    show_msg(res.title, res.message, res.alert);
                }
            }
        });
    });
}

// ════════════════════════════════════════════════
// EDIT LINE — ubah artikel/qty/uom/note/lokasi TANPA hapus baris
// ════════════════════════════════════════════════
function editLine(dtlId, el) {
    const $row = $(el).closest('tr.sto-line');

    const curArticleCode = $row.data('article-code') || '';
    const curArticleDesc = ($row.data('article-desc') || '').toString();
    const curIsManual    = $row.data('is-manual') == '1' || $row.data('is-manual') === 1;
    const curUom         = ($row.data('uom') || '').toString();
    const curMinPkg      = $row.data('min-package');
    const curQty         = $row.data('my-qty');
    const curQtyC1       = $row.data('qty-counter1');
    const curQtyC2       = $row.data('qty-counter2');
    const curQtyC3       = $row.data('qty-counter3');
    const curNote        = ($row.data('note') || '').toString();
    const curLocation    = ($row.data('location-number') || '').toString();

    const qtyBlock = IS_ACCOUNTING ? `
        <div class="form-row mt-50">
            <div class="col-4">
                <label class="scc-field-label">Qty C1</label>
                <input type="text" id="editQtyC1" class="form-control text-right" value="${curQtyC1 ?? ''}">
            </div>
            <div class="col-4">
                <label class="scc-field-label">Qty C2</label>
                <input type="text" id="editQtyC2" class="form-control text-right" value="${curQtyC2 ?? ''}">
            </div>
            <div class="col-4">
                <label class="scc-field-label">Qty C3</label>
                <input type="text" id="editQtyC3" class="form-control text-right" value="${curQtyC3 ?? ''}">
            </div>
        </div>` : `
        <div class="col-4">
            <label class="scc-field-label">QTY*</label>
            <input type="text" id="editQty" class="form-control text-right" value="${curQty ?? ''}">
        </div>`;

    Swal.fire({
        title: IS_ACCOUNTING ? 'Edit Baris (3 Qty Counter)' : 'Edit Baris',
        html: `
            <div class="text-left">
                ${IS_PARTNER ? `
                <div class="form-group mb-50">
                    <label class="scc-field-label">Lokasi*</label>
                    <select id="editLocationSelect" class="form-control" style="width:100%"></select>
                </div>` : ''}
                <div class="form-group mb-50">
                    <label class="scc-field-label">Article</label>
                    <select id="editArticleSelect" class="form-control" style="width:100%"></select>
                </div>
                <div class="form-row">
                    <div class="col-${IS_ACCOUNTING ? '6' : '4'}">
                        <label class="scc-field-label">UOM</label>
                        <div id="editUomWrap"><select id="editUomSelect" class="form-control"></select></div>
                    </div>
                    <div class="col-${IS_ACCOUNTING ? '6' : '4'}">
                        <label class="scc-field-label">Min Pkg</label>
                        <input type="text" id="editMinPkg" class="form-control text-right" readonly>
                    </div>
                    ${IS_ACCOUNTING ? '' : qtyBlock}
                </div>
                ${IS_ACCOUNTING ? qtyBlock : ''}
                <div class="form-group mt-50 mb-0">
                    <label class="scc-field-label">Note</label>
                    <input type="text" id="editNote" class="form-control" value="${curNote}">
                </div>
            </div>
        `,
        width: 520,
        showCancelButton: true,
        confirmButtonText: 'Simpan Perubahan',
        cancelButtonText: 'Batal',
        focusConfirm: false,
        didOpen: () => {
            if (IS_PARTNER) {
                let locOpt = '<option value=""></option>';
                LOCATIONS_DATA.forEach(l => {
                    locOpt += `<option value="${l.code}">${l.name}</option>`;
                });
                $('#editLocationSelect').html(locOpt);
                initLocationSelect2($('#editLocationSelect'), Swal.getPopup());
                if (curLocation) $('#editLocationSelect').val(curLocation).trigger('change');
            }

            getArticleOptions(function(data) {
                loadArticlesInto($('#editArticleSelect'), data);
                $('#editArticleSelect').select2({
                    width: '100%',
                    dropdownParent: Swal.getPopup(),
                    placeholder: '- Pilih atau ketik Article -',
                    allowClear: true,
                    tags: true,
                    createTag: function(params) {
                        const term = $.trim(params.term);
                        if (!term) return null;
                        return { id: MANUAL_PREFIX + term, text: term, newOption: true };
                    },
                    templateResult: function(d) {
                        if (d.newOption) return $(`<span><span class="badge badge-light-warning mr-50">OTHER</span>${d.text}</span>`);
                        return d.text;
                    },
                    templateSelection: function(d) {
                        return (d.id && d.id.indexOf(MANUAL_PREFIX) === 0) ? 'OTHER - ' + d.text : d.text;
                    }
                });

                if (curIsManual) {
                    const manualVal = MANUAL_PREFIX + curArticleDesc;
                    if ($('#editArticleSelect').find(`option[value="${manualVal}"]`).length === 0) {
                        $('#editArticleSelect').append(new Option(curArticleDesc, manualVal, true, true));
                    }
                    $('#editArticleSelect').val(manualVal).trigger('change');
                    $('#editUomWrap').html(`<input type="text" id="editUomSelect" class="form-control" placeholder="UOM* (wajib diisi)" value="${curUom}">`);
                    $('#editMinPkg').val(curMinPkg ?? '0');
                } else {
                    $('#editArticleSelect').val(curArticleCode).trigger('change');
                    const $opt = $('#editArticleSelect').find(`option[value="${curArticleCode}"]`);
                    let uomOpt = '';
                    const uomMember = $opt.data('uom-member');
                    const uomSingle = $opt.data('uom');
                    if (uomMember) String(uomMember).split(',').forEach(u => uomOpt += `<option${u===curUom?' selected':''}>${u}</option>`);
                    else if (uomSingle) uomOpt = `<option selected>${uomSingle}</option>`;
                    else uomOpt = `<option selected>${curUom}</option>`;
                    $('#editUomSelect').html(uomOpt);
                    $('#editMinPkg').val(curMinPkg ?? $opt.data('min-pkg') ?? '');
                }

                $('#editArticleSelect').off('change.editform').on('change.editform', function() {
                    const val = $(this).val();
                    const isManual = !!val && val.indexOf(MANUAL_PREFIX) === 0;
                    if (isManual) {
                        if ($('#editUomSelect').is('select')) {
                            $('#editUomSelect').replaceWith('<input type="text" id="editUomSelect" class="form-control" placeholder="UOM* (wajib diisi)">');
                        }
                        $('#editMinPkg').val('0');
                        return;
                    }
                    if ($('#editUomSelect').is('input')) {
                        $('#editUomSelect').replaceWith('<select id="editUomSelect" class="form-control"></select>');
                    }
                    const opt = $(this).find(':selected');
                    const uomMember = opt.data('uom-member');
                    const uom = opt.data('uom');
                    let uomOpt = '';
                    if (uomMember) String(uomMember).split(',').forEach(u => uomOpt += `<option>${u}</option>`);
                    else if (uom) uomOpt = `<option>${uom}</option>`;
                    $('#editUomSelect').html(uomOpt);
                    const mp = opt.data('min-pkg');
                    $('#editMinPkg').val((mp === undefined || mp === null || mp === '') ? '0' : mp);
                });
            });
        },
        preConfirm: () => {
            const val = $('#editArticleSelect').val();
            if (!val) { Swal.showValidationMessage('Pilih atau ketik artikel dulu.'); return false; }

            const locationVal = IS_PARTNER ? ($('#editLocationSelect').val() || '') : '';
            if (IS_PARTNER && !locationVal) { Swal.showValidationMessage('Lokasi wajib dipilih.'); return false; }

            const manual = !!val && val.indexOf(MANUAL_PREFIX) === 0;
            const article = manual ? '' : val;
            const articleDesc = manual
                ? val.substring(MANUAL_PREFIX.length)
                : ($('#editArticleSelect').find(':selected').data('desc') || $('#editArticleSelect').find(':selected').text().split(' - ').slice(1).join(' - ') || '');
            const uomVal = ($('#editUomSelect').val() || '').trim();

            if (manual && !uomVal) { Swal.showValidationMessage('UOM wajib diisi untuk artikel manual.'); return false; }

            const payload = {
                is_manual: manual ? 1 : 0,
                article: article,
                article_desc: articleDesc,
                uom: uomVal,
                min_package: $('#editMinPkg').val(),
                note: $('#editNote').val(),
                location_number: locationVal,
            };

            if (IS_ACCOUNTING) {
                const c1 = ($('#editQtyC1').val() || '').replace(/,/g,'');
                const c2 = ($('#editQtyC2').val() || '').replace(/,/g,'');
                const c3 = ($('#editQtyC3').val() || '').replace(/,/g,'');
                if (![c1,c2,c3].some(v => v !== '' && parseFloat(v) > 0)) {
                    Swal.showValidationMessage('Minimal salah satu QTY counter harus > 0.'); return false;
                }
                payload.qty_counter1 = c1;
                payload.qty_counter2 = c2;
                payload.qty_counter3 = c3;
           } else {
    const qty = ($('#editQty').val() || '').replace(/,/g, '');
    if (qty === '' || parseFloat(qty) < 0) { Swal.showValidationMessage('QTY tidak boleh negatif.'); return false; }
    payload.qty = qty;
}

            return payload;
        }
    }).then(result => {
        if (!result.isConfirmed || !result.value) return;

        $.ajax({
            url: "{{ route('stockCount.updateLine', ['dtlId' => '__ID__']) }}".replace('__ID__', dtlId),
            method: 'PUT',
            data: Object.assign({ _token: "{{ csrf_token() }}" }, result.value),
            dataType: 'json',
            success: function(res) {
                if (res.status == 1) {
                    renderRowToSheet(res.row, res.row.sto_number);
                    updateRealisasi(res.target_act_loc);
                    updateStatusStats();
                    show_msg(res.title, res.message, res.alert);
                } else {
                    (Array.isArray(res.message) ? res.message : [res.message]).forEach(m => show_msg(res.title, m, res.alert));
                }
            },
            error: function(xhr) {
                console.log('Status :', xhr.status);
                console.log('Response :', xhr.responseText);
            }
        });
    });
}

// ════════════════════════════════════════════════
// TAMBAH ARTIKEL KE SHEET — pakai SweetAlert
// ════════════════════════════════════════════════
function initModalAddArticle() { /* no-op, dibiarkan supaya panggilan di ready() aman */ }

$(document).on('click', '.scc-btn-add-article', function () {
    const stoId     = $(this).data('sto-id');
    const stoNumber = $(this).data('sto-number');

    Swal.fire({
        title: `Tambah Artikel ke ${stoNumber}`,
        html: `
            <div class="text-left">
                ${IS_PARTNER ? `
                <div class="form-group mb-50">
                    <label class="scc-field-label">Lokasi*</label>
                    <select id="maLocation" class="form-control" style="width:100%"></select>
                </div>` : ''}
                <div class="form-group mb-50">
                    <label class="scc-field-label">Article*</label>
                    <select id="maArticle" class="form-control" style="width:100%"></select>
                </div>
                <div class="form-row">
                    <div class="col-4">
                        <label class="scc-field-label">UOM</label>
                        <div id="maUomWrap"><select id="maUom" class="form-control"></select></div>
                    </div>
                    <div class="col-4">
                        <label class="scc-field-label">Min Pkg</label>
                        <input type="text" id="maMinPkg" class="form-control text-right" readonly>
                    </div>
                    <div class="col-4">
                        <label class="scc-field-label">QTY*</label>
                        <input type="text" id="maQty" class="form-control text-right">
                    </div>
                </div>
                <div class="form-group mt-50 mb-0">
                    <label class="scc-field-label">Note</label>
                    <input type="text" id="maNote" class="form-control" maxlength="150">
                </div>
            </div>
        `,
        width: 560,
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal',
        focusConfirm: false,
        didOpen: () => {
            // ── Lokasi (partner) ──
            if (IS_PARTNER) {
                let locOpt = '<option value=""></option>';
                LOCATIONS_DATA.forEach(l => { locOpt += `<option value="${l.code}">${l.name}</option>`; });
                $('#maLocation').html(locOpt);
                initLocationSelect2($('#maLocation'), Swal.getPopup());
            }

            // ── Article ──
            getArticleOptions(function (data) {
                loadArticlesInto($('#maArticle'), data);
                $('#maArticle').select2({
                    width: '100%',
                    dropdownParent: Swal.getPopup(),
                    placeholder: '- Pilih atau ketik Article -',
                    allowClear: true,
                    tags: true,
                    createTag: function (params) {
                        const term = $.trim(params.term);
                        if (!term) return null;
                        return { id: MANUAL_PREFIX + term, text: term, newOption: true };
                    },
                    templateResult: function (d) {
                        if (d.newOption) return $(`<span><span class="badge badge-light-warning mr-50">OTHER</span>${d.text}</span>`);
                        return d.text;
                    },
                    templateSelection: function (d) {
                        return (d.id && d.id.indexOf(MANUAL_PREFIX) === 0) ? 'OTHER - ' + d.text : d.text;
                    }
                });

                // ganti artikel → refresh UOM & MinPkg
                $('#maArticle').on('change', function () {
                    const val = $(this).val();
                    if (isManualValue(val)) {
                        if ($('#maUom').is('select')) {
                            $('#maUomWrap').html('<input type="text" id="maUom" class="form-control" placeholder="UOM* (wajib diisi)">');
                        }
                        $('#maMinPkg').val('0');
                        return;
                    }
                    if ($('#maUom').is('input')) {
                        $('#maUomWrap').html('<select id="maUom" class="form-control"></select>');
                    }
                    const opt = $(this).find(':selected');
                    const uomMember = opt.data('uom-member');
                    const uom = opt.data('uom');
                    let uomOpt = '';
                    if (uomMember) String(uomMember).split(',').forEach(u => uomOpt += `<option>${u}</option>`);
                    else if (uom) uomOpt = `<option>${uom}</option>`;
                    $('#maUom').html(uomOpt);
                    const mp = opt.data('min-pkg');
                    $('#maMinPkg').val((mp === undefined || mp === null || mp === '') ? '0' : mp);
                });
            });
        },
        preConfirm: () => {
            const val = $('#maArticle').val();
            if (!val) { Swal.showValidationMessage('Pilih atau ketik artikel dulu.'); return false; }

            const qty = ($('#maQty').val() || '').replace(/,/g, '');
            //if (!qty || parseFloat(qty) <= 0) { Swal.showValidationMessage('QTY harus lebih dari 0.'); return false; }

            const locationVal = IS_PARTNER ? ($('#maLocation').val() || '') : '';
            if (IS_PARTNER && !locationVal) { Swal.showValidationMessage('Lokasi wajib dipilih.'); return false; }

            const manual = isManualValue(val);
            const articleDesc = manual
                ? val.substring(MANUAL_PREFIX.length)
                : ($('#maArticle').find(':selected').data('desc') || '');
            const uomVal = ($('#maUom').val() || '').trim();

            if (manual && !uomVal) { Swal.showValidationMessage('UOM wajib diisi untuk artikel manual.'); return false; }

            return {
                is_manual: manual ? 1 : 0,
                article: manual ? '' : val,
                article_desc: articleDesc,
                uom: uomVal,
                min_package: $('#maMinPkg').val(),
                qty: qty,
                note: $('#maNote').val(),
                location_number: locationVal,
            };
        }
    }).then(result => {
        if (!result.isConfirmed || !result.value) return;
        postAddArticle(stoNumber, result.value, false);
    });
});

function postAddArticle(stoNumber, payload, confirmAccumulate) {
    $.post("{{ route('stockCount.storeLine') }}", Object.assign({
        _token: "{{ csrf_token() }}",
        mapping_id: encMappingId,
        selected_number: stoNumber,
        confirm_accumulate: confirmAccumulate ? 1 : 0,
    }, payload), function (res) {

        if (res.confirm_required) {
            Swal.fire({
                title: res.title, text: res.message, icon: 'question',
                showCancelButton: true, confirmButtonText: 'Ya, Tambahkan', cancelButtonText: 'Batal',
            }).then(r => { if (r.isConfirmed) postAddArticle(stoNumber, payload, true); });
            return;
        }

        if (res.status == 1) {
            renderRowToSheet(res.row, res.sto_number);
            updateRealisasi(res.target_act_loc);
            updateStatusStats();
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `Tersimpan di ${stoNumber}`, showConfirmButton: false, timer: 2000 });
        } else {
            (Array.isArray(res.message) ? res.message : [res.message]).forEach(m => show_msg(res.title, m, res.alert));
        }
    }, 'json').fail(() => {
        show_msg('Error', 'Terjadi kesalahan, cek console.', 'error');
    });
}

$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection