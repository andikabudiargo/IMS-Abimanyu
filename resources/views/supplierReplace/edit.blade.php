@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $status }}</span></h4>
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
                                <div class="form-group col-md-4">
                                    <label for="replaceNumber">Replace Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="replaceNumber" name="replaceNumber" class="form-control text-hitam disabled-el" value="{{ $header->replace_number }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="replaceDate">Replace Date*</label>
                                    <input type="text" id="replaceDate" name="replaceDate" class="form-control" value="{{ $header->replace_date }}" placeholder="DD-MM-YYYY" required />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="supplier">Supplier</label> <small class="text-muted">tidak dapat diubah saat edit</small>
                                    <select class="select2 form-control" id="supplier" name="supplier" disabled>
                                        <option value=""></option>
                                        @foreach($suppliers as $val)
                                            <option value="{{$val->kode}}" {{$val->kode == $header->supplier_id ? "selected" : ""}}>{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="returnNumber">Return Number</label> <small class="text-muted">tetap</small>
                                    <input type="text" id="returnNumber" name="returnNumber" class="form-control" value="{{ $header->return_number }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="returnDate">Return Date</label>
                                    <input type="text" id="returnDate" name="returnDate" class="form-control" value="{{ $header->return_date }}" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea id="note" name="note" class="form-control" rows="1">{{ $header->note }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body">
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('supplierReplace.headerColumn')
                            <div class="" id="articleRow" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin">
                                <input type="text" id="last_row_number" class="d-none" value="{{ count($detail) }}">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75"></div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-04">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" disabled/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group row mb-03">
                                <label for="totalQTY" class="col-sm-4 col-form-label titik-dua">Total Qty</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQTY" disabled/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-12">
                            <a href="{{ route('supplierReplace.index') }}" class="btn btn-light">Back</a>
                            <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate">Update</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('supplierReplace.addArticle')
@endsection
@section('scripts')
<script type="text/javascript">
    dariEdit = 'true';

    $(document).ready(function(){
        validateFormToast("frmAdd");

        let detail = {!! $detail !!};
        for (let i = 0; i < detail.length; i++){
            let article       = detail[i].article_code;
            let articleCode   = detail[i].article_alternative_code;
            let articleDesc   = detail[i].article_desc;
            let totQtyReturn  = detail[i].tot_qty_return  * 1;
            let sisaQtyReturn = detail[i].sisa_qty_return * 1;
            let uom           = detail[i].uom;
            let qty           = detail[i].qty * 1;
            let returnNumber  = detail[i].return_number;
            addNewRow(article, articleCode, articleDesc, totQtyReturn, sisaQtyReturn, uom, qty, returnNumber);
        }
    });

    function reloadPage(){ window.location.reload(); }

    replaceDate = $('#replaceDate');
    if (replaceDate.length) {
        replaceDate.flatpickr({ dateFormat: "d-m-Y", maxDate: "today" });
    }

    $("#cmdUpdate").click(function(){
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        } else {
            let $btnUpdate = $("#cmdUpdate");
            let originalHtml = $btnUpdate.html();
            $btnUpdate.attr('disabled','disabled');
            $('.disabled-el').removeAttr('disabled');

            let returnNumber = $('#returnNumber').val();
            let objQty       = $('input[name="qtyReplace[]"]');
            let objUom       = $('select[name="uom[]"], input[name="uom[]"]');
            let objSisa      = $('input[name="sisaQtyReturn[]"]');
            let articles = [];
            let flag = 0;
            let pesan = "";

            $("#articleRow input[name='articleCode[]']").map(function(i){
                let $this = $(this);
                if ($this.val()){
                    let articleCode = $this.data("code");
                    let articleUom  = $this.data("uom");
                    let qty         = objQty.eq(i).val().replace(/,/gi,'') || 0;
                    let qtyUom      = objUom.eq(i).val() || articleUom;
                    let sisa        = objSisa.eq(i).val().replace(/,/gi,'') || 0;
                    let totRet      = $("#articleRow input[name='totQtyReturn[]']").eq(i).val().replace(/,/gi,'') || 0;

                    if ((parseFloat(qty) > parseFloat(sisa)) && (parseFloat(qty) != 0)){
                        pesan += `Article ${articleCode}: Qty Replace > Sisa Qty Return<br>`;
                        flag = 1;
                    }

                    articles.push({
                        "return_number": returnNumber,
                        "article_code": articleCode,
                        "qty_return": totRet,
                        "qty": qty,
                        "uom": qtyUom,
                    });
                }
            });

            articles = articles.filter(a => parseFloat(a.qty) > 0);

            if (articles.length == 0){ pesan += "Minimal 1 artikel harus diisi qty replace-nya<br>"; flag = 1; }
            if ($("#totalQTY").val() == 0){ pesan += "Total Qty tidak boleh 0<br>"; flag = 1; }

            if (flag == 0){
                $btnUpdate.html('<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> Saving...');
                $.ajax({
                    type: "post",
                    url: "{{ route('supplierReplace.update') }}",
                    data: {
                        articles: JSON.stringify(articles),
                        replaceNumber: $('#replaceNumber').val() || 0,
                        replaceDate: $('#replaceDate').val(),
                        returnNumber: returnNumber,
                        note: $('#note').val(),
                    },
                    dataType: "json",
                    success: function(data){
                        if (data.status == 0){
                            if (Array.isArray(data.message)){
                                for (let i = 0; i < data.message.length; i++){ show_msg(data.title, data.message[i], data.alert); }
                            } else {
                                show_msg(data.title, data.message, data.alert);
                            }
                            $btnUpdate.html(originalHtml).removeAttr('disabled');
                        } else {
                            show_msg(data.title, data.message, data.alert);
                            $('#statusText').text(data.statusReplace);
                            $('#replaceDate').attr('disabled','disabled');
                            $('.input-qty').attr('disabled','disabled');
                            $btnUpdate.html(originalHtml).attr('disabled','disabled');
                            setTimeout(reloadPage, 1200);
                        }
                    },
                    error: function(xhr){
                        console.log(xhr);
                        $btnUpdate.html(originalHtml).removeAttr('disabled');
                        Swal.fire('Error','Gagal menyimpan perubahan, silakan coba lagi.','error');
                    }
                });
            } else {
                $btnUpdate.html(originalHtml).removeAttr('disabled');
                Swal.fire('Warning..', pesan, 'warning');
            }
        }
    });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection