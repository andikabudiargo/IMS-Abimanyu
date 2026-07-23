@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="row">
        {{-- CARD 1: INFO --}}
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: {{ $statusPrd }}</h4>
                    <input type="hidden" id='oEdit' value="{{ $oEdit }}">
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
                                    <label for="fgNumber">FG Number</label>
                                    <input type="text" id="fgNumber" name="fgNumber" placeholder="Automatic" class="form-control disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="fgDate">Date*</label>
                                    <input type="text" id="fgDate" name="fgDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="loadingCode">Actual Loading*</label>
                                        <select class="select2 form-control" id="loadingCode" name="loadingCode" required>
                                            <option value=""></option>
                                            @foreach($listLoading as $val)
                                                <option value="{{ $val->prod_code }}"
                                                        data-reference="{{ $val->wos_reference }}"
                                                        data-date="{{ $val->loading_date_fmt }}">
                                                    {{ $val->prod_code }}@if($val->wos_reference) ({{ $val->wos_reference }})@endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="reference">Referensi WOS</label>
                                        <input type="text" class="form-control" id="reference" name="reference" placeholder="-" readonly tabindex="-1" />
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea id="note" name="note" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 2: ARTICLE FINISH GOODS --}}
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article Finish Goods</h4>
                </div>
                <div class="card-body">
                    <hr>
                    <div id="articleLockMsg" class="alert alert-warning">
                        <i data-feather="alert-triangle" class="align-middle mr-50"></i>
                        Silakan pilih <b>Actual Loading</b> terlebih dahulu untuk memuat artikel.
                    </div>

                    <div id="articleWrap" class="d-none">
                        <hr style="margin-top: 0px;">
                        <div class="container-list-item">
                            <div class="lebar-list-item">
                                {{-- HEADER COLUMN --}}
                                <div class="form-row d-none d-md-flex font-weight-bold text-muted mb-50" style="font-size:12px;">
                                    <div class="col-md-5">Article</div>
                                    <div class="col-md-1 text-right">Qty Loading</div>
                                    <div class="col-md-1 text-right">Qty FG</div>
                                    <div class="col-md-1 text-right">Qty OT</div>
                                    <div class="col-md-1 text-right">Qty WIP</div>
                                    <div class="col-md-3">Note</div>
                                </div>
                                <div id="article_row" style="max-height: 22rem;overflow-x:hidden;scrollbar-width:thin;">
                                    <input type="text" id="last_row_number" class="d-none" value="0">
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-end mt-75">
                            <div class="col-md-4">
                                <div class="form-group row mb-03">
                                    <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                    <div class="col-sm-3">
                                        <input type="text" class="form-control text-right font-weight-bold" id="totalRow" disabled/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <a href="{{ route('production.actualFinishGoods.index') }}" class="btn btn-light">Back</a>
                            <button class="btn btn-info" type="button" id="cmdNew">New</button>
                            <button class="btn btn-primary" type="button" id="cmdSave" disabled>Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('styles')
<style>
    textarea { resize: none; }
    .mb-03{ margin-bottom: 0.3rem; }
    label.titik-dua::after{ content : ":"; position : absolute; right : 1px; }
    .margin-nol{ margin-bottom: 0.5rem; }
    .fg-article-label { font-weight:600; }
    .fg-article-desc  { font-size:11.5px; color:#6c757d; }

    /* kolom qty saat akumulasi FG+OT > qty loading */
    .qty-error{
        background-color:#f8d7da !important;
        border-color:#f5c2c7 !important;
        color:#842029 !important;
        font-weight:600;
    }
    .fg-warning{ font-size:11.5px; }

    @media screen and (min-device-width:1200px) and (max-device-width:1600px){
        .lebar-list-item{ width:100%; }
        .container-list-item{ max-width:100%; overflow-x:auto; scrollbar-width:thin; margin-top:7px; }
    }
    @media only screen and (min-width:600px) and (max-width:1200px){
        .lebar-list-item{ width:200%; }
        .container-list-item{ max-width:100%; overflow-x:auto; scrollbar-width:thin; margin-top:7px; }
    }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
    const currentDate = "{{ $currentDateValue ?? date('d-m-Y') }}";
    const fgDate = $('#fgDate');

    if (fgDate.length) {
        fgDate.flatpickr({ dateFormat: "d-m-Y" });
    }

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // toast non-blocking utk notif qty over
    const ToastQty = Swal.mixin({
        toast: true, position: 'top-end', showConfirmButton: false,
        timer: 2500, timerProgressBar: true
    });

    // ============================================================
    // HELPER ANGKA
    // ============================================================
    function toNum(v){
        let n = parseFloat(String(v).replace(/,/g,''));
        return isNaN(n) ? 0 : n;
    }
    function fmtQty(v){
        let n = toNum(v);
        return parseFloat(n.toFixed(2)).toString();
    }

    function toggleArticleSection(enable){
        $('#articleWrap').toggleClass('d-none', !enable);
        $('#articleLockMsg').toggleClass('d-none', enable);
        $('#cmdSave').prop('disabled', !enable);
    }

    function resetArticleRows(){
        $('#article_row').html('<input type="text" id="last_row_number" class="d-none" value="0">');
        $('#totalRow').val('');
    }

    // ============================================================
    // BUILD 1 BARIS ARTIKEL (auto dari Actual Loading, read-only artikel)
    //  - FG : input (default kosong)
    //  - OT : input, default 0
    //  - WIP: readonly, default 0, auto = loading - fg - ot
    // ============================================================
    function buildRow(r){
        let altCode = r.article_alternative_code ?? r.article_code;
        return `
        <div class="tanda-baris" data-article="${r.article_code}">
            <div class="form-row d-flex align-items-center">
                <div class="col-md-5 col-12">
                    <div class="form-group margin-nol">
                        <div class="fg-article-label">${altCode}</div>
                        <div class="fg-article-desc">${r.article_desc ?? ''}</div>
                        <input type="hidden" name="article_code[]" value="${r.article_code}">
                        <input type="hidden" name="uom[]"          value="${r.uom ?? ''}">
                        <input type="hidden" name="qty_loading[]"   value="${toNum(r.qty_loading)}">
                    </div>
                </div>
                <div class="col-md-1 col-12">
                    <div class="form-group margin-nol">
                        <label class="d-block d-md-none">Qty Loading</label>
                        <input type="text" class="form-control text-right font-weight-bold"
                               value="${fmtQty(r.qty_loading)}" readonly tabindex="-1">
                    </div>
                </div>
                <div class="col-md-1 col-12">
                    <div class="form-group margin-nol">
                        <label class="d-block d-md-none">Qty FG</label>
                        <input type="text" class="form-control numeral-mask-digit text-right qty-fg"
                               name="qty_fg[]" maxlength="12">
                    </div>
                </div>
                <div class="col-md-1 col-12">
                    <div class="form-group margin-nol">
                        <label class="d-block d-md-none">Qty OT</label>
                        <input type="text" class="form-control numeral-mask-digit text-right qty-ot"
                               name="qty_ot[]" maxlength="12" value="0">
                    </div>
                </div>
                <div class="col-md-1 col-12">
                    <div class="form-group margin-nol">
                        <label class="d-block d-md-none">Qty WIP</label>
                        <input type="text" class="form-control text-right qty-wip"
                               name="qty_wip[]" value="0" readonly tabindex="-1">
                    </div>
                </div>
                <div class="col-md-3 col-12">
                    <div class="form-group margin-nol">
                        <label class="d-block d-md-none">Note</label>
                        <input type="text" class="form-control" name="note[]" maxlength="150">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="col-12 fg-warning text-danger d-none">
                    <i data-feather="alert-triangle" style="width:12px;height:12px;vertical-align:-1px;"></i>
                    <span class="fg-warning-text"></span>
                </div>
            </div>
        </div>`;
    }

    // ============================================================
    // RECALC 1 BARIS: WIP = loading - fg - ot, tandai merah kalau over
    // return true kalau baris over (fg+ot > loading)
    // ============================================================
    function recalcRow($row){
        let loading = toNum($row.find('input[name="qty_loading[]"]').val());
        let $fg  = $row.find('.qty-fg');
        let $ot  = $row.find('.qty-ot');
        let $wip = $row.find('.qty-wip');

        let fg  = toNum($fg.val());
        let ot  = toNum($ot.val());
        let wip = loading - fg - ot;

        $wip.val(fmtQty(wip));

        let exceed = (fg + ot) > loading; // artinya wip < 0
        $fg.toggleClass('qty-error', exceed);
        $ot.toggleClass('qty-error', exceed);
        $wip.toggleClass('qty-error', exceed);

        let $warn = $row.find('.fg-warning');
        if (exceed){
            $warn.removeClass('d-none')
                 .find('.fg-warning-text')
                 .text(' Qty FG + OT melebihi stock loading (maks ' + fmtQty(loading) + ')');
            // notif toast hanya sekali per transisi ke kondisi over
            if (!$row.data('wasExceed')){
                ToastQty.fire({ icon:'error', title:'Qty melebihi stock loading' });
                $row.data('wasExceed', true);
            }
            if (typeof feather !== 'undefined') feather.replace();
        } else {
            $warn.addClass('d-none');
            $row.data('wasExceed', false);
        }
        return exceed;
    }

    function renderArticleRows(list){
        resetArticleRows();
        if (!list || list.length === 0){
            Swal.fire("Info", "Actual Loading ini tidak punya artikel.", "info");
            toggleArticleSection(false);
            return;
        }
        let html = '';
        $.each(list, function(i, r){ html += buildRow(r); });
        $('#article_row').append(html);
        $('#totalRow').val(list.length);

        if (typeof mask_thousand_digit === 'function' && typeof numberOfDecimalDigit !== 'undefined') {
            mask_thousand_digit(numberOfDecimalDigit);
        }
        if (typeof feather !== 'undefined') feather.replace();
        toggleArticleSection(true);
    }

    // recalc WIP tiap FG / OT diketik
    $('#article_row').on('input', '.qty-fg, .qty-ot', function(){
        recalcRow($(this).closest('.tanda-baris'));
    });

    // ============================================================
    // AMBIL ARTIKEL SAAT ACTUAL LOADING DIPILIH
    // ============================================================
    $('#loadingCode').on('change', function(){
        let code = $(this).val();
        let ref  = $(this).find(':selected').data('reference') || '';
        $('#reference').val(ref);

        if (!code){
            resetArticleRows();
            toggleArticleSection(false);
            return;
        }

        $.ajax({
            url: "{{ route('production.actualFinishGoods.articleByLoading') }}",
            method: "GET",
            data: { prod_code: code },
            success: function(res){ renderArticleRows(res); },
            error: function(){
                Swal.fire("Warning", "Gagal memuat artikel dari Actual Loading ini.", "warning");
                toggleArticleSection(false);
            }
        });
    });

    // ============================================================
    // NEW / RESET
    // ============================================================
    $('#cmdNew').on('click', function(){ window.location.reload(); });

    // ============================================================
    // SAVE
    // ============================================================
    $('#cmdSave').on('click', function(){
        let loadingCode = $('#loadingCode').val();
        let fgDateVal   = $('#fgDate').val();
        let reference   = $('#reference').val();
        let headerNote  = $('#note').val();

        if (!loadingCode){ Swal.fire("Info","Actual Loading wajib dipilih.","info"); return; }
        if (!fgDateVal){   Swal.fire("Info","Tanggal wajib diisi.","info"); return; }

        let $rows = $('#article_row .tanda-baris');
        if ($rows.length === 0){ Swal.fire("Info","Belum ada artikel.","info"); return; }

        let articles  = [];
        let adaIsi    = false;
        let anyExceed = false;

        $rows.each(function(){
            let $r = $(this);
            if (recalcRow($r)) anyExceed = true; // pastikan status merah ter-update

            let qtyWip = toNum($r.find('.qty-wip').val());
            let qtyFg  = toNum($r.find('.qty-fg').val());
            let qtyOt  = toNum($r.find('.qty-ot').val());

            if (qtyFg > 0 || qtyOt > 0 || qtyWip > 0) adaIsi = true;

            articles.push({
                article_code : $r.find('input[name="article_code[]"]').val(),
                uom          : $r.find('input[name="uom[]"]').val(),
                qty_loading  : toNum($r.find('input[name="qty_loading[]"]').val()),
                qty_wip      : qtyWip,
                qty_fg       : qtyFg,
                qty_ot       : qtyOt,
                note         : $r.find('input[name="note[]"]').val()
            });
        });

        if (anyExceed){
            Swal.fire("Tidak bisa disimpan","Ada baris dengan Qty FG + OT melebihi stock loading (kolom merah). Perbaiki dulu.","error");
            return;
        }
        if (!adaIsi){
            Swal.fire("Info","Minimal satu artikel harus punya qty FG/OT/WIP.","info");
            return;
        }

        let $btn = $(this);
        let originalHtml = $btn.html();
        $btn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>Saving...');

        $.ajax({
            url: "{{ route('production.actualFinishGoods.store') }}",
            method: "POST",
            data: {
                articles    : JSON.stringify(articles),
                loadingCode : loadingCode,
                reference   : reference,
                fgDate      : fgDateVal,
                note        : headerNote
            },
            success: function(res){
                if (res.status == 1){
                    Swal.fire({ icon:'success', title: res.title, text: res.message })
                        .then(() => window.location.reload());
                } else {
                    let msg = Array.isArray(res.message) ? res.message.flat().join('<br>') : res.message;
                    Swal.fire({ icon:'error', title: res.title || 'Error', html: msg });
                }
            },
            error: function(xhr){
                Swal.fire("Error", "Gagal menyimpan. " + (xhr.responseJSON?.message || xhr.statusText || ''), "error");
            },
            complete: function(){
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    $(document).ready(function(){
        if (typeof validateFormToast === 'function') validateFormToast("frmAdd");
        fgDate.val(currentDate);
        toggleArticleSection(false);
    });
</script>
@endsection