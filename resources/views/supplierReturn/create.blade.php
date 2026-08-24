@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: New</h4>
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
                            <input type="text" id="article" name="article" hidden>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="returnNumber">Supplier Return Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="returnNumber" name="returnNumber" class="form-control disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="returnDate">Return Date*</label>
                                    <input type="text" id="returnDate" name="returnDate" class="form-control" placeholder="DD-MM-YYYY" required/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="supplier">Supplier*</label>
                                    <select class="select2 form-control" id="supplier" name="supplier" required>
                                        <option value="">Choose Supplier</option>
                                        @foreach($suppliers as $val)
                                            <option value="{{$val->kode}}" >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="location">Location*</label>
                                    <select class="select2 form-control" id="location" name="location" required>
                                        <option value="">Choose Location</option>
                                        @foreach($locations as $val)
                                            <option value="{{$val->location_number}}">{{$val->location_number}} - {{$val->location_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            {{--<div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="poNumber">PO Number</label>
                                    <input type="text" id="poNumber" name="poNumber" class="form-control disabled-el" />
                                </div>
                            </div>--}}
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
                    <h4 class="card-title">Article Detail</h4>
                </div>
                <div class="card-body" >
                    <div style="padding-right:10px">
                        @include("supplierReturn.headerColumn")
                    </div>
                    <div class="" id="article_row" style="max-height: 30rem;overflow-x: hidden;scrollbar-width:thin;margin-top:7px;padding-right:10px">
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                        <h6>Line:<span id="records"></span></h6>
                    </div>
                    <hr>
                    <div class="mt-75">
                        <a href="{{ route('supplierReturn.index') }}" class="btn btn-light">Back</a>
                        <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
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
</style>
@endsection
@section('scripts')
@include('supplierReturn.addArticle')
<script type="text/javascript">
    $(document).ready(function(){
        validateFormToast("frmAdd");
        $('#returnDate').val("{{ $currentDate }}");
    });

    $("#cmdSave").click(function(){
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{
            $('#cmdSave').attr('disabled','disabled');
            $('.disabled-el').removeAttr('disabled');
            let objQty = $('#article_row input[name="qtyOrder[]"]');
            let objUom = $('#article_row span[name="uom[]"]');
            let articles = [];
            let flag=0;
            let pesan="";

            $("#article_row select[name='articleCode[]']").map(function(i) {
                let $this=$(this);
                if ($this.val()){
                    let articleCode=$this.val();
                    let articleName=$this.select2('data')[0].text;
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let uom=objUom.eq(i).text();

                    let obj = $.grep(articles, function(obj){
                        return obj.article_code === articleCode;
                    })[0];

                    if(obj) {
                        pesan +="Article "+articleName+" entered more than once !! <br>";
                        flag=1;
                    } else {
                        if ((articleCode!=='') && (qty> 0)){
                            articles.push({
                                "article_code":articleCode,
                                "qty":qty,
                                "uom":uom
                            });
                        }
                    }
                    if ( qty == 0 ){
                        pesan +=`QTY of items ${articleName} cannot be 0 <br>`;
                        flag=1;
                    }
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>";
                flag=1;
            }

            if (flag==0){
                let returnDate = $('#returnDate').val();
                let supplierId = $('#supplier').val();
                let locationNumber = $('#location').val();
                let poNumber = $('#poNumber').val();
                let note = $('#note').val();

                $.ajax({
                    type: "POST",
                    url: "{{ route('supplierReturn.store') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        returnDate:returnDate,
                        supplierId:supplierId,
                        locationNumber:locationNumber,
                        note:note,
                        poNumber:poNumber
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#cmdSave').removeAttr('disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#returnNumber').attr('disabled','disabled');
                            $('#cmdSave').attr('disabled','disabled');
                            $('#addNewRow').attr('disabled','disabled');
                            $('#returnNumber').val(data.returnNumber);
                        }
                    },
                    error: function(error) {
                        Swal.fire('Error..',error,'error');
                        $('#cmdSave').removeAttr('disabled');
                    }
                });
            }else{
                $('#cmdSave').removeAttr('disabled');
                Swal.fire('Warning..',pesan,'warning');
            }
        }
    });

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });
</script>
@endsection