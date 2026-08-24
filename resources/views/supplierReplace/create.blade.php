@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText"></span></h4>
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
                                    <input type="text" id="replaceNumber" name="replaceNumber" class="form-control text-hitam disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="replaceDate">Replace Date*</label>
                                    <input type="text" id="replaceDate" name="replaceDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="supplier">Supplier*</label>
                                    <select class="select2 form-control" id="supplier" name="supplier" required>
                                        <option value=""></option>
                                        @foreach($suppliers as $val)
                                            <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="returnNumber">Return Number*</label>
                                    <select class="select2 form-control" id="returnNumber" name="returnNumber" required>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="returnDate">Return Date</label>
                                    <input type="text" id="returnDate" name="returnDate" class="form-control" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea id="note" name="note" class="form-control" rows="1"></textarea>
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
                            <input type="text" id="last_row_number" class="d-none" value="0">
                            <div class="" id="articleRow" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin"></div>
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
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                            <button class="btn btn-dark" type="button" id="cmdPrint" name="cmdPrint">Print</button>
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
    dariEdit = 'false';
    let printUrlTemplate = "{{ route('supplierReplace.print', ['id'=>':id']) }}";
    let lastPrintUrl = null;

    $(document).ready(function(){
        validateFormToast("frmAdd");
        $("#totalRow").val(0);
        $("#totalQTY").val(humanizeNumber(0));
        $('#statusText').text('New');
        $('#replaceDate').val(currentDate);
        $('#cmdSave').show();
        $('#cmdPrint').hide();
    });

    replaceDate = $('#replaceDate');
    if (replaceDate.length) {
        replaceDate.flatpickr({ dateFormat: "d-m-Y", maxDate: "today" });
    }

    function reloadPage(){ window.location.reload(); }

    $("#cmdPrint").click(function(){ if (lastPrintUrl){ window.open(lastPrintUrl, '_blank'); } });

    $('#supplier').change(function(){
        let value = $(this).val();
        searchReturn('returnNumber', value);
    });

    $('#returnNumber').change(function(){
        $("#returnDate").val('');
        let value  = $(this).val();
        let rDate  = $(this).find(":selected").data("date");
        $("#returnDate").val(rDate);
        searchReturnDet(value, 'false');
    });

    $("#cmdSave").click(function(){
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        } else {
            let $btnSave = $("#cmdSave");
            let originalHtml = $btnSave.html();
            $btnSave.attr('disabled','disabled');
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
                    let articleCode  = $this.data("code");
                    let articleUom   = $this.data("uom");
                    let qty          = objQty.eq(i).val().replace(/,/gi,'') || 0;
                    let qtyUom       = objUom.eq(i).val() || articleUom;
                    let sisa         = objSisa.eq(i).val().replace(/,/gi,'') || 0;
                    let totRet       = $("#articleRow input[name='totQtyReturn[]']").eq(i).val().replace(/,/gi,'') || 0;

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

            // buang baris qty 0 supaya tidak insert kosong
            articles = articles.filter(a => parseFloat(a.qty) > 0);

            if (articles.length == 0){ pesan += "Minimal 1 artikel harus diisi qty replace-nya<br>"; flag = 1; }
            if ($("#totalQTY").val() == 0){ pesan += "Total Qty tidak boleh 0<br>"; flag = 1; }

            if (flag == 0){
                $btnSave.html('<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> Saving...');
                $.ajax({
                    type: "post",
                    url: "{{ route('supplierReplace.store') }}",
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
                            $btnSave.html(originalHtml).removeAttr('disabled');
                        } else {
                            show_msg(data.title, data.message, data.alert);
                            $('#statusText').text(data.statusReplace);
                            $('#replaceNumber').val(data.replaceNumber);
                            $('#supplier').attr('disabled','disabled');
                            $('#returnNumber').attr('disabled','disabled');
                            $('#replaceDate').attr('disabled','disabled');
                            $('.input-qty').attr('disabled','disabled');
                            $btnSave.hide();
                            $('#cmdPrint').show();

                            let id = data.idKu;
                            lastPrintUrl = printUrlTemplate.replace('%3Aid', id).replace(':id', id);
                            window.open(lastPrintUrl, '_blank');
                            setTimeout(reloadPage, 1500);
                        }
                    },
                    error: function(xhr){
                        console.log(xhr);
                        $btnSave.html(originalHtml).removeAttr('disabled');
                        $('#cmdPrint').hide();
                        Swal.fire('Error','Gagal menyimpan data, silakan coba lagi.','error');
                    }
                });
            } else {
                $btnSave.html(originalHtml).removeAttr('disabled');
                Swal.fire('Warning..', pesan, 'warning');
            }
        }
    });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection