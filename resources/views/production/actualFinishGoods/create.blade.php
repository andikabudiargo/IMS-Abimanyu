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
                                    <input type="text" id="fgNumber" name="fgNumber"
                                           placeholder="Automatic"
                                           class="form-control disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="fgDate">Date*</label>
                                    <input type="text" id="fgDate" name="fgDate"
                                           class="form-control" placeholder="DD-MM-YYYY" required />
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
                    <div class="container-list-item">
                        <div class="lebar-list-item">

                            {{-- HEADER KOLOM --}}
                            <div class="form-row d-flex align-items-end">
                                <div class="col-md-5 col-12 d-none d-md-block">
                                    <div class="form-group">
                                        <label class="d-none d-md-block">Article</label>
                                    </div>
                                </div>
                                <div class="col-md-1 col-12 d-none d-md-block">
                                    <div class="form-group">
                                        <label class="d-none d-md-block text-right">Qty FG</label>
                                    </div>
                                </div>
                                <div class="col-md-1 col-12 d-none d-md-block">
                                    <div class="form-group">
                                        <label class="d-none d-md-block text-right">Qty OT</label>
                                    </div>
                                </div>
                                <div class="col-md-1 col-12 d-none d-md-block">
                                    <div class="form-group">
                                        <label class="d-none d-md-block text-right">Qty WIP</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-12 d-none d-md-block">
                                    <div class="form-group">
                                        <label class="d-none d-md-block">Note</label>
                                    </div>
                                </div>
                                <div class="col-md-1 col-12 d-none d-md-block">
                                    <div class="form-group">
                                        <label class="d-none d-md-block">&nbsp;</label>
                                    </div>
                                </div>
                            </div>

                            {{-- AREA ROWS --}}
                            <div id="article_row"
                                 style="max-height:22rem;overflow-x:hidden;scrollbar-width:thin;">
                            </div>

                        </div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold"
                                           id="totalRow" disabled/>
                                </div>
                            </div>
                        </div>
                        <div>
                            <button class="btn btn-success btn-sm" type="button" id="cmdAddArticle">
                                <i data-feather="plus-circle"
                                   style="width:14px;height:14px;vertical-align:-2px;"></i>
                                Add Article
                            </button>
                        </div>
                    </div>

                    <hr>
                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <a href="{{ route('production.actualFinishGoods.index') }}"
                               class="btn btn-light">Back</a>
                            <button class="btn btn-info"    type="button" id="cmdNew">New</button>
                            <button class="btn btn-primary" type="button" id="cmdSave">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ROW TEMPLATE UNTUK DI-CLONE (di luar section) --}}
<div id="new_row_fg" class="d-none">
    <div class="tanda-baris">
        <div class="form-row d-flex align-items-center">
            <div class="col-md-6 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">Article</label>
                    <select class="select2-article-fg form-control" name="article_code[]"
                            data-placeholder="-- Cari Article FG --">
                        <option value=""></option>
                    </select>
                    <input type="hidden" name="uom[]" value="">
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">Qty FG</label>
                    <input type="text" class="form-control numeral-mask-digit text-right qty-fg"
                           name="qty_fg[]" maxlength="12" value="0">
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">Qty OT</label>
                    <input type="text" class="form-control numeral-mask-digit text-right qty-ot"
                           name="qty_ot[]" maxlength="12" value="0">
                </div>
            </div>
            <div class="col-md-3 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">Note</label>
                    <input type="text" class="form-control" name="note[]" maxlength="150">
                </div>
            </div>
            <div class="col-md-1 col-12 text-center">
                <button type="button" class="btn btn-danger btn-sm btn-del-row" title="Hapus">
                    <i data-feather="trash-2" style="width:13px;height:13px;"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
    textarea { resize: none; }
    .mb-03{ margin-bottom: 0.3rem; }
    label.titik-dua::after{ content:":"; position:absolute; right:1px; }
    .margin-nol{ margin-bottom:0.5rem; }

    .qty-error{
        background-color:#f8d7da !important;
        border-color:#f5c2c7 !important;
        color:#842029 !important;
        font-weight:600;
    }
    .btn-del-row{ padding:2px 7px; line-height:1.4; }

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
    let rowCounter = 0;

    if (fgDate.length) {
        fgDate.flatpickr({ dateFormat: "d-m-Y" });
    }

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // ============================================================
    // HELPERS
    // ============================================================
    function toNum(v){
        let n = parseFloat(String(v).replace(/,/g,''));
        return isNaN(n) ? 0 : n;
    }
    function updateTotalRow(){
        $('#totalRow').val($('#article_row .tanda-baris').length || '');
    }

   const articleFgData = @json($listArticleFg);

// ============================================================
// INIT SELECT2 PADA SATU ROW — pakai data statis, filter client-side
// ============================================================
function initRowSelect2($row){
    let $sel = $row.find('.select2-article-fg');
    $sel.select2({
        placeholder : '-- Cari Article FG --',
        allowClear  : true,
        width       : '100%',
        data        : articleFgData,   // <-- data statis, tidak AJAX
        matcher     : function(params, data){
            // filter by ketikan: cocokkan ke id atau text
            if (!params.term || params.term.trim() === '') return data;
            let term = params.term.toLowerCase();
            if (
                data.id.toLowerCase().includes(term) ||
                data.text.toLowerCase().includes(term)
            ){
                return data;
            }
            return null;
        }
    });

    $sel.on('select2:select', function(e){
        let d = e.params.data;
        $row.find('input[name="uom[]"]').val(d.uom || '');
    });

    $sel.on('select2:clear', function(){
        $row.find('input[name="uom[]"]').val('');
    });
}

    // ============================================================
    // TAMBAH BARIS BARU
    // ============================================================
    function addNewRow(){
        rowCounter++;
        // clone dari template, ambil inner HTML saja
        let $clone = $('#new_row_fg').clone().removeAttr('id').removeClass('d-none');
        $('#article_row').append($clone);

        let $row = $('#article_row .tanda-baris').last();
        initRowSelect2($row);

        if (typeof feather !== 'undefined') feather.replace();
        updateTotalRow();
    }

    // ============================================================
    // TOMBOL ADD ARTICLE
    // ============================================================
    $('#cmdAddArticle').on('click', function(){
        addNewRow();
    });

    // ============================================================
    // HAPUS BARIS
    // ============================================================
    $('#article_row').on('click', '.btn-del-row', function(){
        $(this).closest('.tanda-baris').remove();
        updateTotalRow();
    });

    // ============================================================
    // NEW / RESET
    // ============================================================
    $('#cmdNew').on('click', function(){ window.location.reload(); });

    // ============================================================
    // SAVE
    // ============================================================
    $('#cmdSave').on('click', function(){
        let fgDateVal  = $('#fgDate').val();
        let headerNote = $('#note').val();

        if (!fgDateVal){
            Swal.fire("Info","Tanggal wajib diisi.","info"); return;
        }

        let $rows = $('#article_row .tanda-baris');
        if ($rows.length === 0){
            Swal.fire("Info","Belum ada artikel. Klik Add Article terlebih dahulu.","info"); return;
        }

        let articles = [];
        let adaIsi   = false;
        let adaKosong = false;

        $rows.each(function(){
            let $r           = $(this);
            let articleCode  = $r.find('select[name="article_code[]"]').val();
            let uom          = $r.find('input[name="uom[]"]').val();
            let qtyFg        = toNum($r.find('.qty-fg').val());
            let qtyOt        = toNum($r.find('.qty-ot').val());
            let qtyWip       = toNum($r.find('.qty-wip').val());
            let noteVal      = $r.find('input[name="note[]"]').val();

            if (!articleCode){
                adaKosong = true;
                $r.find('.select2-article-fg').next('.select2-container')
                  .find('.select2-selection').addClass('border-danger');
                return; // next iteration
            }

            if (qtyFg > 0 || qtyOt > 0) adaIsi = true;

            articles.push({
                article_code : articleCode,
                uom          : uom,
                qty_fg       : qtyFg,
                qty_ot       : qtyOt,
                qty_wip      : qtyWip,
                note         : noteVal
            });
        });

        if (adaKosong){
            Swal.fire("Info","Ada baris yang belum dipilih article-nya.","info"); return;
        }
        if (!adaIsi){
            Swal.fire("Info","Minimal satu artikel harus punya Qty FG atau OT > 0.","info"); return;
        }

        // cek duplikat article
        let codes = articles.map(a => a.article_code);
        if (new Set(codes).size !== codes.length){
            Swal.fire("Info","Ada article yang duplikat. Setiap article hanya boleh muncul sekali.","info"); return;
        }

        let $btn     = $(this);
        let origHtml = $btn.html();
        $btn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm mr-1"></span>Saving...');

        $.ajax({
            url    : "{{ route('production.actualFinishGoods.store') }}",
            method : "POST",
            data   : {
                articles : JSON.stringify(articles),
                fgDate   : fgDateVal,
                note     : headerNote
            },
            success: function(res){
                if (res.status == 1){
                    Swal.fire({ icon:'success', title:res.title, text:res.message })
                        .then(() => window.location.reload());
                } else {
                    let msg = Array.isArray(res.message)
                              ? res.message.flat().join('<br>') : res.message;
                    Swal.fire({ icon:'error', title:res.title || 'Error', html:msg });
                }
            },
            error: function(xhr){
                Swal.fire("Error",
                    "Gagal menyimpan. " + (xhr.responseJSON?.message || xhr.statusText || ''),
                    "error");
            },
            complete: function(){
                $btn.prop('disabled', false).html(origHtml);
            }
        });
    });

    // ============================================================
    // READY
    // ============================================================
    $(document).ready(function(){
        if (typeof validateFormToast === 'function'){
            validateFormToast("frmAdd");
            // ignore elemen di luar #frmAdd dari validator
            let validator = $.data($('#frmAdd')[0], 'validator');
            if (validator) validator.settings.ignore = ':hidden, [tabindex="-1"]';
        }
        fgDate.val(currentDate);
        if (typeof feather !== 'undefined') feather.replace();
    });
</script>
@endsection