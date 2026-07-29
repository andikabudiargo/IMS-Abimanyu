@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

@php $oEdit = $oEdit ?? false; $hdr = $hdr ?? null; @endphp

<section id="sto-create">
    <div class="form-row">

        {{-- ════ CARD 1 — HEADER ════ --}}
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title" id="cardStatus">Status: {{ $oEdit ? 'Edit — '.$hdr->sto_code : 'New' }}</h4>
                    <input type="hidden" id="oEdit"    value="{{ $oEdit ? '1' : '0' }}">
                    <input type="hidden" id="configId" value="{{ $oEdit ? Crypt::encryptString($hdr->config_id) : '' }}">
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
                                <div class="form-group col-md-2">
                                    <label>STO Code</label>
                                    <small class="text-muted"> automatic</small>
                                    <input type="text" id="stoCode" class="form-control disabled-el"
                                           value="{{ $hdr->sto_code ?? '' }}" disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="periode">Periode*</label>
                                    <input type="month" id="periode" name="periode" class="form-control"
                                           value="{{ $hdr->periode ?? '' }}"
                                           {{ $oEdit ? 'disabled' : 'required' }} />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="stoType">STO Type*</label>
                                    <select class="select2 form-control" id="stoType" name="stoType"
                                            {{ $oEdit ? 'disabled' : 'required' }}>
                                        <option value=""></option>
                                        @foreach($stoTypes as $val => $lbl)
                                            <option value="{{ $val }}"
                                                {{ isset($hdr) && $hdr->sto_type == $val ? 'selected' : '' }}>
                                                {{ $lbl }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label for="notes">Notes</label>
                                    <textarea id="notes" name="notes" class="form-control"
                                              rows="3">{{ $hdr->notes ?? '' }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ════ CARD 2 — MAPPING (mixed basis) ════ --}}
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Mapping Target &amp; Counter</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted" style="font-size:.82rem;">
                        <i data-feather="info" style="width:13px;height:13px;"></i>
                        Tiap baris bebas pilih tipe (Lokasi / Supplier / Customer) dan tanggalnya sendiri.
                        Counter 1 wajib, Counter 2 opsional (blind count).
                    </p>
                    <hr>

                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('stockTakingOrder.headerColumn')
                            <div id="mapping_row"
                                 style="max-height:24rem;overflow-x:hidden;scrollbar-width:thin;"></div>
                        </div>
                    </div>

                    <hr style="margin-top:0;">

                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary" type="button" id="addNewRow"
                                onclick="addMappingRow();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Target</span>
                        </button>
                    </div>

                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label class="col-sm-5 col-form-label titik-dua">Jumlah Target</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold"
                                           id="totalRow" value="0" disabled />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group row mb-03">
                                <label class="col-sm-7 col-form-label titik-dua">Target Akurasi Global</label>
                                <div class="col-sm-5">
                                    <div class="input-group">
                                        {{-- ganti input targetPlanGlobal --}}
                                        <input type="text" id="targetPlanGlobal"
                                            class="form-control text-right font-weight-bold"
                                            value="98.00" />  {{-- hilangkan disabled --}}
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <a href="{{ route('stockTakingOrder.index') }}" class="btn btn-light">Back</a>
                            <button class="btn btn-info"    type="button" id="cmdNew">New</button>
                            <button class="btn btn-primary" type="button" id="cmdSave">
                                <i data-feather="save" class="align-middle mr-sm-25 mr-0"></i>
                                <span class="align-middle">Save</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<div id="new_row" class="d-none">
    <div class="tanda-baris">
        <div class="form-row d-flex align-items-center">
            {{-- Tipe --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none font-weight-bold">Tipe*</label>
                    <select class="form-control sel-type" name="targetType[]">
                        <option value="LOCATION">Lokasi</option>
                        <option value="SUPPLIER">Supplier</option>
                        <option value="CUSTOMER">Customer</option>
                    </select>
                </div>
            </div>
            {{-- Target --}}
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none font-weight-bold">Target*</label>
                    <select class="form-control sel-target" name="target[]">
                        <option value=""></option>
                    </select>
                </div>
            </div>
            {{-- STO Date --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none font-weight-bold">STO Date*</label>
                    <input type="text" class="form-control sto-date-input"
                           name="stoDate[]" placeholder="DD-MM-YYYY" />
                </div>
            </div>
            {{-- No. Dari --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none font-weight-bold">No. Dari*</label>
                    <input type="number" class="form-control text-right no-dari-input"
                           name="noDari[]" placeholder="0" min="0" />
                </div>
            </div>
            {{-- No. Sampai --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none font-weight-bold">No. Sampai*</label>
                    <input type="number" class="form-control text-right no-sampai-input"
                           name="noSampai[]" placeholder="999" min="0" />
                </div>
            </div>
            {{-- Counter 1 --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none font-weight-bold">Counter 1*</label>
                    <select class="form-control sel-counter1" name="counter1[]">
                        <option value=""></option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            {{-- Counter 2 --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none font-weight-bold">Counter 2 <small>(opsional)</small></label>
                    <select class="form-control sel-counter2" name="counter2[]">
                        <option value=""></option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-1 col-12">
    <div class="form-group margin-nol">
        <label class="d-block d-md-none font-weight-bold">Counter 3 <small>(opsional)</small></label>
        <select class="form-control sel-counter3" name="counter3[]">
            <option value=""></option>
            @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
</div>
            {{-- Target % --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none font-weight-bold">Target %*</label>
                    <div class="input-group">
                        <input type="text"
                               class="form-control text-right target-plan-input numeral-mask-digit"
                               name="targetPlan[]" placeholder="0–100" maxlength="6" />
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-1 col-12">
    <div class="form-group margin-nol text-center">
        <label class="d-block d-md-none font-weight-bold">Blind Count?</label>
        <div class="custom-control custom-checkbox mt-50">
            <input type="checkbox" class="custom-control-input chk-blind" checked>
            <label class="custom-control-label">&nbsp;</label>
        </div>
    </div>
</div>
            {{-- Hapus --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol text-center">
                    <a style="cursor:pointer;" onclick="removeRow(this);">
                        <i data-feather="trash-2" class="feather-24"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
    textarea    { resize: none; }
    .mb-03      { margin-bottom: .3rem; }
    .margin-nol { margin-bottom: .5rem; }
    label.titik-dua::after { content:":"; position:absolute; right:1px; }
    .container-list-item   { max-width:100%; overflow-x:auto; scrollbar-width:thin; margin-top:7px; }
    .lebar-list-item       { width:100%; }
    @media only screen and (min-width:600px) and (max-width:1400px) {
        .lebar-list-item { width:180%; }
    }
</style>
@endsection

@section('scripts')
<script type="text/javascript">

const oEdit    = $('#oEdit').val() == '1';
const configId = $('#configId').val();
let   cloneCount = 0;
// Jadi ini — tambahkan flag guard:
let isLoadingMappings = false; // ← tambah variabel ini di atas, sejajar cloneCount

// opsi lokasi statis
const LOC_OPTIONS = `
    <option value=""></option>
    @foreach($locations as $loc)<option value="{{ $loc->location_code }}">{{ $loc->location_name }}</option>@endforeach
`;

// cache partner options per tipe (supp/cust) — di-load sekali lalu dipakai ulang
let partnerCache = { supp: null, cust: null };

$(document).ready(function () {
    validateFormToast("frmAdd");
    $('#stoType').select2({ width:'100%' });

    if (!oEdit) {
        const now  = new Date();
        const mm   = String(now.getMonth()+1).padStart(2,'0');
        $('#periode').val(`${now.getFullYear()}-${mm}`);
    }

    $(document).on('input change', '.target-plan-input', recalcGlobal);
    $('#cmdNew').on('click',  () => location.href = "{{ route('stockTakingOrder.create') }}");
    $('#cmdSave').on('click', simpanData);

    // preload kedua tipe partner supaya cepat, lalu init baris
    preloadPartners(function () {
        if (oEdit) loadExistingMappings();
        else       addMappingRow();
    });
});

// ══════════════════════════════════════════════
// PRELOAD PARTNER (supp & cust)
// ══════════════════════════════════════════════
function preloadPartners(done) {
    let pending = 2;
    ['supp', 'cust'].forEach(function (t) {
        $.get("{{ route('stockTakingOrder.getPartners') }}", { type: t }, function (data) {
            let opts = '<option value=""></option>';
            data.forEach(p => opts += `<option value="${p.kode}">${p.nama}</option>`);
            partnerCache[t] = opts;
            if (--pending === 0 && typeof done === 'function') done();
        }, 'json').fail(function () {
            partnerCache[t] = '<option value=""></option>';
            if (--pending === 0 && typeof done === 'function') done();
        });
    });
}

// opsi target sesuai tipe baris
function optionsForType(type) {
    if (type === 'SUPPLIER') return partnerCache.supp || '<option value=""></option>';
    if (type === 'CUSTOMER') return partnerCache.cust || '<option value=""></option>';
    return LOC_OPTIONS;
}

function placeholderForType(type) {
    if (type === 'SUPPLIER') return '- Pilih Supplier -';
    if (type === 'CUSTOMER') return '- Pilih Customer -';
    return '- Pilih Lokasi -';
}

// ══════════════════════════════════════════════
// ADD ROW
// ══════════════════════════════════════════════
// tambah parameter noDari, noSampai
function addMappingRow(type, ref, stoDate, c1, c2, tp, noDari, noSampai, c3, isBlind) {
    const $clone = $('#new_row > .tanda-baris').clone();
    cloneCount++;

    $('#mapping_row').append($clone);
    const $row = $('#mapping_row .tanda-baris').last();

    const initType = type || 'LOCATION';
    $row.find('.sel-type').val(initType);
    $row.find('.sel-target').html(optionsForType(initType));

    $row.find('.sel-type').select2({ width:'100%' });
    $row.find('.sel-target').select2({ width:'100%', placeholder: placeholderForType(initType), allowClear:true });
    $row.find('.sel-counter1').select2({ width:'100%', placeholder:'- Counter 1 -', allowClear:true });
    $row.find('.sel-counter2').select2({ width:'100%', placeholder:'- Counter 2 (opsional) -', allowClear:true });
     $row.find('.sel-counter3').select2({ width:'100%', placeholder:'- Counter 3 (opsional) -', allowClear:true });
    $row.find('.chk-blind').prop('checked', isBlind === undefined ? true : !!isBlind);

    $row.find('.sto-date-input').flatpickr({ dateFormat:'d-m-Y' });

    $row.find('.sel-type').on('change', function () {
        const t = $(this).val();
        const $t = $row.find('.sel-target');
        $t.html(optionsForType(t)).val('')
          .select2({ width:'100%', placeholder: placeholderForType(t), allowClear:true })
          .trigger('change');
    });

    if (ref)     $row.find('.sel-target').val(ref).trigger('change');
    if (stoDate) $row.find('.sto-date-input').val(stoDate);
    if (c1)      $row.find('.sel-counter1').val(c1).trigger('change');
    if (c2)      $row.find('.sel-counter2').val(c2).trigger('change');
    if (c3) $row.find('.sel-counter3').val(c3).trigger('change');
    //if (tp   !== undefined && tp   !== null) $row.find('.target-plan-input').val(tp);
    const tpValue = (tp !== undefined && tp !== null) ? tp : getGlobalTarget();
$row.find('.target-plan-input').val(tpValue);
    if (noDari   !== undefined && noDari   !== null) $row.find('.no-dari-input').val(noDari);
    if (noSampai !== undefined && noSampai !== null) $row.find('.no-sampai-input').val(noSampai);

    // validasi live: no_dari tidak boleh > no_sampai
    $row.find('.no-dari-input, .no-sampai-input').on('change input', function () {
        const dari   = parseInt($row.find('.no-dari-input').val());
        const sampai = parseInt($row.find('.no-sampai-input').val());
        if (!isNaN(dari) && !isNaN(sampai) && dari > sampai) {
            $row.find('.no-sampai-input').addClass('is-invalid');
        } else {
            $row.find('.no-sampai-input').removeClass('is-invalid');
        }
    });

    updateRowCount();
    recalcGlobal();
    if (typeof feather !== 'undefined') feather.replace();
}

function removeRow(el) {
    if ($('#mapping_row .tanda-baris').length <= 1) {
        show_msg('Warning', 'Minimal harus ada satu target.', 'warning');
        return;
    }
    $(el).closest('.tanda-baris').remove();
    updateRowCount();
    recalcGlobal();
}

function updateRowCount() {
    $('#totalRow').val($('#mapping_row .tanda-baris').length);
}

function recalcGlobal() {
    // tidak perlu recalc dari baris; global adalah master
    // cukup validasi range saja
    const v = parseFloat($('#targetPlanGlobal').val());
    if (isNaN(v) || v < 0 || v > 100) {
        $('#targetPlanGlobal').addClass('is-invalid');
    } else {
        $('#targetPlanGlobal').removeClass('is-invalid');
    }
}

function getGlobalTarget() {
    const v = parseFloat($('#targetPlanGlobal').val());
    return isNaN(v) ? 98 : Math.min(100, Math.max(0, v));
}

// ══════════════════════════════════════════════
// COLLECT & VALIDASI
// ══════════════════════════════════════════════
function collectMappings() {
    const mappings = [];
    let flag = 0; let pesan = [];
    const seen      = [];
    const rangesSeen = []; // untuk deteksi overlap range antar baris

    $('#mapping_row .tanda-baris').each(function (i) {
        const $row   = $(this);
        const rowNo  = i + 1;
        const type   = $row.find('select[name="targetType[]"]').val();
        const ref    = $row.find('select[name="target[]"]').val();
        const sdate  = $row.find('input[name="stoDate[]"]').val();
        const noDari   = $row.find('input[name="noDari[]"]').val();
        const noSampai = $row.find('input[name="noSampai[]"]').val();
        const c1     = $row.find('select[name="counter1[]"]').val();
        const c2     = $row.find('select[name="counter2[]"]').val();
        const c3   = $row.find('select[name="counter3[]"]').val();
        const blind = $row.find('.chk-blind').is(':checked');
        const tp     = $row.find('input[name="targetPlan[]"]').val();
        const tpF    = parseFloat(tp);
        const tLabel = type === 'LOCATION' ? 'Lokasi' : (type === 'SUPPLIER' ? 'Supplier' : 'Customer');
        const noDariI   = parseInt(noDari);
        const noSampaiI = parseInt(noSampai);

        if (!ref)   { pesan.push(`Baris ${rowNo}: ${tLabel} wajib dipilih.`); flag = 1; }
        if (!sdate) { pesan.push(`Baris ${rowNo}: STO Date wajib diisi.`); flag = 1; }
        if (noDari === '' || isNaN(noDariI))   { pesan.push(`Baris ${rowNo}: No. Dari wajib diisi.`); flag = 1; }
        if (noSampai === '' || isNaN(noSampaiI)) { pesan.push(`Baris ${rowNo}: No. Sampai wajib diisi.`); flag = 1; }
        if (!isNaN(noDariI) && !isNaN(noSampaiI) && noDariI > noSampaiI) {
            pesan.push(`Baris ${rowNo}: No. Dari tidak boleh lebih besar dari No. Sampai.`); flag = 1;
        }
        if (!c1) { pesan.push(`Baris ${rowNo}: Counter 1 wajib dipilih.`); flag = 1; }
        if (c2 && c1 && c2 === c1) { pesan.push(`Baris ${rowNo}: Counter 2 tidak boleh sama dengan Counter 1.`); flag = 1; }
        if (c3 && c1 && c3 === c1) { pesan.push(`Baris ${rowNo}: Counter 3 tidak boleh sama dengan Counter 1.`); flag = 1; }
        if (c3 && c2 && c3 === c2) { pesan.push(`Baris ${rowNo}: Counter 3 tidak boleh sama dengan Counter 2.`); flag = 1; }
        if (tp === '' || isNaN(tpF)) { pesan.push(`Baris ${rowNo}: Target Akurasi wajib diisi.`); flag = 1; }
        else if (tpF < 0 || tpF > 100) { pesan.push(`Baris ${rowNo}: Target Akurasi harus 0–100.`); flag = 1; }

        if (ref) {
            const key = type + '|' + ref;
            if (seen.includes(key)) { pesan.push(`Baris ${rowNo}: ${tLabel} sudah dipilih di baris lain.`); flag = 1; }
            else seen.push(key);
        }

        // cek overlap range antar baris
        if (!isNaN(noDariI) && !isNaN(noSampaiI) && noDariI <= noSampaiI) {
            for (const r of rangesSeen) {
                if (noDariI <= r.sampai && noSampaiI >= r.dari) {
                    pesan.push(`Baris ${rowNo}: Range nomor (${noDariI}–${noSampaiI}) overlap dengan baris ${r.rowNo} (${r.dari}–${r.sampai}).`);
                    flag = 1;
                    break;
                }
            }
            rangesSeen.push({ rowNo, dari: noDariI, sampai: noSampaiI });
        }

       mappings.push({
    target_type : type,
    target_ref  : ref    || '',
    sto_date    : sdate  || '',
    no_dari     : isNaN(noDariI)   ? 0 : noDariI,
    no_sampai   : isNaN(noSampaiI) ? 0 : noSampaiI,
    counter1    : c1     || '',
    counter2    : c2     || '',
    counter3    : c3     || '',
    is_blind    : blind,
    target_plan : isNaN(tpF) ? 0 : tpF,
});
    });

    if (flag) { Swal.fire({ title:'Warning', html:pesan.join('<br>'), icon:'warning' }); return null; }
    return mappings;
}

// ══════════════════════════════════════════════
// SIMPAN
// ══════════════════════════════════════════════
function simpanData() {
    if (!$('#frmAdd')[0].checkValidity()) { $('#frmAdd').submit(); return; }

    const periode = $('#periode').val();
    const stoType = $('#stoType').val();
    const notes   = $('#notes').val();

    if (!oEdit && !periode) { Swal.fire('Warning','Periode wajib diisi.','warning'); return; }
    if (!oEdit && !stoType) { Swal.fire('Warning','STO Type wajib dipilih.','warning'); return; }

    const mappings = collectMappings();
    if (!mappings) return;

    const $btn = $('#cmdSave');
    $btn.data('original-html', $btn.html())
        .prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm mr-50"></span>Menyimpan...');

    const url = oEdit
        ? "{{ route('stockTakingOrder.update') }}"
        : "{{ route('stockTakingOrder.store') }}";

    $.ajax({
        type:'POST', url:url, dataType:'json',
        data: {
            _token    : "{{ csrf_token() }}",
            config_id : configId,
            periode   : periode,
            sto_type  : stoType,
            notes     : notes,
            mappings  : JSON.stringify(mappings),
        },
        success: function (res) {
            $btn.prop('disabled', false).html($btn.data('original-html'));
            if (typeof feather !== 'undefined') feather.replace();
            if (res.status == 1) {
                show_msg(res.title, res.message, res.alert);
                $('#stoCode').val(res.sto_code || '');
                setTimeout(() => window.location.href = res.redirect_url, 1000);
            } else {
                (Array.isArray(res.message) ? res.message : [res.message])
                    .forEach(m => show_msg(res.title, m, res.alert));
            }
        },
        error: function () {
            $btn.prop('disabled', false).html($btn.data('original-html'));
            if (typeof feather !== 'undefined') feather.replace();
            show_msg('Error', 'Terjadi kesalahan, cek console.', 'error');
        }
    });
}

// ══════════════════════════════════════════════
// EDIT MODE — load existing
// ══════════════════════════════════════════════
// Lalu di loadExistingMappings(), wrap dengan flag:
function loadExistingMappings() {
    $('#mapping_row').empty();
    cloneCount = 0;
    isLoadingMappings = true; // ← aktifkan guard
    $.get("{{ route('stockTakingOrder.getMappings') }}", { config_id: configId },
    function (data) {
        if (!data.length) { addMappingRow(); return; }
       data.forEach(m => addMappingRow(
    m.target_type, m.target_ref, m.sto_date,
    m.counter1_user, m.counter2_user, m.target_plan_loc,
    m.no_dari, m.no_sampai, m.counter3_user, m.is_blind
));
        recalcGlobal();
        isLoadingMappings = false; // ← matikan setelah selesai
    }, 'json');
}

$("input[type='text']").click(function () { $(this).select(); });

$('#targetPlanGlobal').on('input change', function () {
    recalcGlobal();
    if (!isLoadingMappings) {
        const g = getGlobalTarget();
        $('#mapping_row .target-plan-input').val(g);
    }
});
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

</script>
@endsection