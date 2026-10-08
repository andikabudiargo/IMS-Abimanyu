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
                            <input type="hidden" id="fgNumber" name="fgNumber" value="{{ $header->fg_code }}">
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="fgNumberShow">AFG Number</label>
                                    <input type="text" id="fgNumberShow" value="{{ $header->fg_code }}"
                                           class="form-control disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="fgDate">Date*</label>
                                    <input type="text" id="fgDate" name="fgDate" value="{{ $header->fg_date_fmt }}"
                                           class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="location">Location*</label>
                                    <select class="select2 form-control" id="location" name="location"
                                            data-placeholder="-- Select Location --" required>
                                        <option value=""></option>
                                        @foreach($listLocation as $loc)
                                        <option value="{{ $loc->location_code }}" {{ $header->spray_booth == $loc->location_code ? 'selected' : '' }}>{{ $loc->location_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea id="note" name="note" class="form-control" rows="3">{{ $header->note }}</textarea>
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
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('production.actualFinishGoods.headerColumn')
                            <div id="article_row" style="max-height:22rem;overflow-x:hidden;scrollbar-width:thin;"></div>
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary btn-prev" type="button" id="cmdAddArticle">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>
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

                    <hr>
                    <div class="form-row">
                        <div class="col-md-12">
                            <a href="{{ route('production.actualFinishGoods.index') }}" class="btn btn-light">Back</a>
                            @if( $approveValidate ? $approveValidate[0]->validate : '')
                                <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                            @else
                                @if( !$approveValidate && $header->status == 1 )
                                    <button class="btn btn-primary" type="button" id="cmdSave">Save</button>
                                @endif
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="form-row card-statistics">
                        @foreach($approvalHistory as $val)
                            @if($val->status == true)
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-{{ $val->statusapprove == 1 ? 'success':'warning' }} mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="{{ $val->statusapprove == 1 ? 'check':'x' }}" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">{{ $val->statusapprove == 1 ? 'Approve':'Decline' }}-{{ $val->approval_order }}</h4>
                                                <p class="card-text mb-0">{{ $val->name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-danger mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="x" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}</h4>
                                                <p class="card-text mb-0">{{ $val->petugas }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
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
    const fgDate = $('#fgDate');
    if (fgDate.length) { fgDate.flatpickr({ dateFormat: "d-m-Y" }); }

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    function toNum(v){
        let n = parseFloat(String(v).replace(/,/g,''));
        return isNaN(n) ? 0 : n;
    }
    function updateTotalRow(){
        $('#totalRow').val($('#article_row .tanda-baris').length || '');
    }

    const articleFgData = @json($listArticleFg);
    const existingDetails = @json($details);

    function initRowSelect2($row){
        let $sel = $row.find('.select2-article-fg');
        $sel.select2({
            placeholder : '-- Cari Article FG --',
            allowClear  : true,
            width       : '100%',
            data        : articleFgData,
            matcher     : function(params, data){
                if (!params.term || params.term.trim() === '') return data;
                let term = params.term.toLowerCase();
                if (data.id.toLowerCase().includes(term) || data.text.toLowerCase().includes(term)) return data;
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

    function addNewRow(detail){
        let $clone = $('#new_row_fg').clone().removeAttr('id').removeClass('d-none');
        $('#article_row').append($clone);

        let $row = $('#article_row .tanda-baris').last();
        initRowSelect2($row);

        if (detail){
            let opt = new Option(detail.article_label || detail.article_code, detail.article_code, true, true);
            $row.find('.select2-article-fg').append(opt).trigger('change');
            $row.find('input[name="uom[]"]').val(detail.uom || '');
            $row.find('.qty-fg').val(detail.qty_fg || 0);
            $row.find('.qty-ot').val(detail.qty_ot || 0);
            $row.find('input[name="note[]"]').val(detail.note || '');
        }

        if (typeof feather !== 'undefined') feather.replace();
        updateTotalRow();
    }

    $('#cmdAddArticle').on('click', function(){ addNewRow(); });

    $('#article_row').on('click', '.btn-del-row', function(){
        $(this).closest('.tanda-baris').remove();
        updateTotalRow();
    });

    $('#cmdSave').on('click', function(){
        let fgDateVal  = $('#fgDate').val();
        let locationVal= $('#location').val();
        let headerNote = $('#note').val();
        let fgNumber   = $('#fgNumber').val();

        if (!fgDateVal){ Swal.fire("Info","Tanggal wajib diisi.","info"); return; }
        if (!locationVal){ Swal.fire("Info","Location wajib dipilih.","info"); return; }

        let $rows = $('#article_row .tanda-baris');
        if ($rows.length === 0){
            Swal.fire("Info","Belum ada artikel. Klik Add Article terlebih dahulu.","info"); return;
        }

        let articles = [], adaIsi = false, adaKosong = false;

        $rows.each(function(){
            let $r          = $(this);
            let articleCode = $r.find('select[name="article_code[]"]').val();
            let uom         = $r.find('input[name="uom[]"]').val();
            let qtyFg       = toNum($r.find('.qty-fg').val());
            let qtyOt       = toNum($r.find('.qty-ot').val());
            let noteVal     = $r.find('input[name="note[]"]').val();

            if (!articleCode){
                adaKosong = true;
                $r.find('.select2-article-fg').next('.select2-container')
                  .find('.select2-selection').addClass('border-danger');
                return;
            }
            if (qtyFg > 0 || qtyOt > 0) adaIsi = true;

            articles.push({ article_code: articleCode, uom: uom, qty_fg: qtyFg, qty_ot: qtyOt, note: noteVal });
        });

        if (adaKosong){ Swal.fire("Info","Ada baris yang belum dipilih article-nya.","info"); return; }
        if (!adaIsi){ Swal.fire("Info","Minimal satu artikel harus punya Qty FG atau OT > 0.","info"); return; }

        let codes = articles.map(a => a.article_code);
        if (new Set(codes).size !== codes.length){
            Swal.fire("Info","Ada article yang duplikat. Setiap article hanya boleh muncul sekali.","info"); return;
        }

        let $btn = $(this), origHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span>Saving...');

        $.ajax({
            url    : "{{ route('production.actualFinishGoods.update') }}",
            method : "POST",
            data   : { articles: JSON.stringify(articles), fgNumber: fgNumber, fgDate: fgDateVal, location: locationVal, note: headerNote },
            success: function(res){
                if (res.status == 1){
                    Swal.fire({ icon:'success', title:res.title, text:res.message }).then(() => window.location.href = "{{ route('production.actualFinishGoods.index') }}");
                } else {
                    let msg = Array.isArray(res.message) ? res.message.flat().join('<br>') : res.message;
                    Swal.fire({ icon:'error', title:res.title || 'Error', html:msg });
                }
            },
            error: function(xhr){
                Swal.fire("Error","Gagal menyimpan. "+(xhr.responseJSON?.message||xhr.statusText||''),"error");
            },
            complete: function(){ $btn.prop('disabled', false).html(origHtml); }
        });
    });

    function approve(fgNumber, objButton){
        $('#'+objButton).attr('disabled','disabled');
        $.ajax({
            type: "POST",
            url: "{{ route('production.actualFinishGoods.approve') }}",
            data: { fgNumber: fgNumber },
            dataType: "json",
            success: function(data) {
                show_msg(data.title, data.message, data.alert);
                if (data.status == 1) window.location.reload();
                else $('#'+objButton).removeAttr('disabled');
            },
            error: function(error) { console.log(error); }
        });
    }

    const approveBtn = document.querySelector('#cmdApprove');
    if (approveBtn) {
        approveBtn.addEventListener('click', () => {
            approve($('#fgNumber').val(), 'cmdApprove');
        }, { once:true });
    }

    $(document).ready(function(){
        if (typeof validateFormToast === 'function'){
            validateFormToast("frmAdd");
        }
        existingDetails.forEach(function(d){
            addNewRow({
                article_code : d.article_code,
                article_label: (d.article_alternative_code || d.article_code) + ' — ' + (d.article_desc || ''),
                uom          : d.uom,
                qty_fg       : d.qty_fg,
                qty_ot       : d.qty_ot,
                note         : d.note
            });
        });
        if (typeof feather !== 'undefined') feather.replace();
    });
</script>
@endsection
