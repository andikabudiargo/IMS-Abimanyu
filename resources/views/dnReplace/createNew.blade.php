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
                            <input type="hidden" id="idRec" name="idRec" class="form-control" />
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
                                    <input type="text" id="replaceNumber" name="replaceNumber" class="form-control text-hitam disabled-el"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="replaceDate">Receiving Date*</label>
                                    <input type="text" id="replaceDate" name="replaceDate" class="form-control" placeholder="DD-MM-YYYY" required/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="customer">Customer*</label>
                                    <select class="select2 form-control" id="customer" name="customer" required>
                                        <option value=""></option>
                                        @foreach($cust as $val)
                                            <option value="{{$val->kode}}" >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="dnReturnNumber">DN Return Number*</label>
                                    <select class="select2 form-control" id="dnReturnNumber" name="dnReturnNumber" required>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="dnNumber">Customer DN number</label>
                                    <input type="text" id="dnNumber" name="dnNumber" class="form-control" disabled/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" ></textarea>
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
                <div class="card-body" >
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('dnReplace.headerColumn') 
                            <input type="text" id ="last_row_number" class="d-none" value="0">
                            <div class="" id="articleRow" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4 ">
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
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQTY" disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-12">
                            <a href="{{ route('dnReplace.index') }}" class="btn btn-light">Back</a>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                            <button class="btn btn-dark" type="button" id="cmdPrint" name="cmdPrint">Print</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('dnReplace.addArticle')
@endsection
@section('styles')
@endsection
@section('scripts')
<script type="text/javascript">
    dariEdit = 'false';
    
    $(document).ready(function(){
        validateFormToast("frmAdd");
        $("#totalRow").val(0);
        $("#totalQTY").val(humanizeNumber(0));
        $("#totalQtyFree").val(humanizeNumber(0));
        $("#grandTotalQty").val(humanizeNumber(0));
        $('#statusText').text('New');
        $('#replaceDate').val(currentDate);
        $('#cmdSave').show();
        // $('#cmdPosting').hide();
        $('#cmdPrint').hide();
    });

    replaceDate = $('#replaceDate');
    if (replaceDate.length) {
        replaceDate.flatpickr({
            dateFormat: "d-m-Y",
            maxDate: "today",
        });
    }
    
    function reloadPage(){
        window.location.reload();
    }

    $("#cmdNew").click(function(){
        reloadPage();
    });

    $("#cmdSave").click(function(){
    if (!$("#frmAdd")[0].checkValidity()){
        $("#frmAdd").submit();
    } else {
        $("#cmdSave").attr('disabled', 'disabled');
        $('.disabled-el').removeAttr('disabled');
        let dnReturnNumber = $('#dnReturnNumber').val();

        let objQtyReturn = $('#articleRow input[name="qtyReturn[]"]');
        let objQty       = $('#articleRow input[name="qtyReplace[]"]');
        let objQtyStock  = $('#articleRow input[name="qtyStock[]"]');
        let objUom       = $('#articleRow input[name="uom[]"]');

        let articles = [];
        let flag     = 0;
        let pesan    = "";

        $("#articleRow input[name='articleCode[]']").each(function(i) {
            let $this       = $(this);
            if ($this.val()) {
                let articleCode  = $this.data("code");
                let articleUom   = $this.data("uom");
                let returnNumber = $this.data("returnNumber");
                let qty          = parseFloat(objQty.eq(i).val().replace(/,/gi, ''))      || 0;
                let qtyReturn    = parseFloat(objQtyReturn.eq(i).val().replace(/,/gi, '')) || 0;
                let qtyStock     = parseFloat(objQtyStock.eq(i).val().replace(/,/gi, ''))  || 0;
                let qtyUom       = objUom.eq(i).val() || articleUom;
                let namaArticle  = $this.val();

                if (qty > qtyReturn && qty != 0) {
                    pesan += `Article: ${namaArticle} — Qty Replace (${qty}) melebihi Qty Return (${qtyReturn})<br>`;
                    flag = 1;
                }

                if (qty > qtyStock && qty != 0) {
                    pesan += `Article: ${namaArticle} — Qty Replace (${qty}) melebihi Qty Stock (${qtyStock})<br>`;
                    flag = 1;
                }

                articles.push({
                    "return_number" : dnReturnNumber,
                    "article_code"  : articleCode,
                    "qty_return"    : qtyReturn,
                    "qty"           : qty,
                    "uom"           : qtyUom,
                });
            }
        });

        if (articles.length == 0) {
            pesan += "Articles must be filled in completely<br>";
            flag = 1;
        }

        if ($("#totalQTY").val() == 0) {
            pesan += "Total Qty cannot be 0<br>";
            flag = 1;
        }

        if (flag == 0) {
            // ✅ AJAX yang hilang — ini yang menyebabkan tidak terjadi apa-apa
            let replaceNumber  = $('#replaceNumber').val() || 0;
            let replaceDate    = $('#replaceDate').val();
            let customer       = $('#customer').val();
            let note           = $('#note').val();

            $.ajax({
                type    : "post",
                url     : "{{ route('dnReplace.store') }}",
                data    : {
                    articles      : JSON.stringify(articles),
                    replaceNumber : replaceNumber,
                    replaceDate   : replaceDate,
                    returnNumber  : dnReturnNumber,
                    customer      : customer,
                    note          : note,
                },
                dataType: "json",
                success : function(data) {
                    if (data.status == 0) {
                        for (let i = 0; i < data.message.length; i++) {
                            show_msg(data.title, data.message[i], data.alert);
                        }
                        $('#cmdSave').removeAttr('disabled');
                    } else {
                        show_msg(data.title, data.message, data.alert);
                        $('#statusText').text(data.statusReplace);
                        $('#replaceNumber').val(data.replaceNumber);

                        // Disable semua input setelah save berhasil
                        $('#replaceNumber').attr('disabled', 'disabled');
                        $('#cmdSave').attr('disabled', 'disabled');
                        $('#customer').attr('disabled', 'disabled');
                        $('#dnReturnNumber').attr('disabled', 'disabled');
                        $('#replaceDate').attr('disabled', 'disabled');
                        $('.input-qty').attr('disabled', 'disabled');

                        $('#cmdSave').hide();
                        $('#cmdPrint').show();

                        // Buka print di tab baru lalu reload
                        let id  = data.idKu;
                        let url = "{{ route('dnReplace.print', ['id'=>':id']) }}";
                        url     = url.replace('%3Aid', id);
                        window.open(url, '_blank');
                        reloadPage();
                    }
                },
                error: function(error) {
                    console.log(error);
                    $('#cmdSave').removeAttr('disabled');  // ✅ re-enable kalau error
                }
            });

        } else {
            $('#cmdSave').removeAttr('disabled');
            $('#cmdPrint').hide();
            Swal.fire('Warning..', pesan, 'warning');
        }
    }
});

    $('#customer').change(function(){
        let value= $(this).val();
        searchDn('dnReturnNumber',value);
    });
     
    $('#dnReturnNumber').change(function(){
        $("#dnNumber").val('');
        let value= $(this).val();
        let dnNumber=$(this).find(":selected").data("dn");
        $("#dnNumber").val(dnNumber);
        searchDnDet(value,'false');
    })   
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection