@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="add-index">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="form-group row">
                        <label for="bomNumber" class="col-sm-4 col-form-label col-form-label-sm">BOM Number</label>
                        <div class="col-md-8">
                            <input type="text" id="bomNumber" name="bomNumber" class="form-control form-control-sm disabled-el" value="{{ $header->bom_code }}" disabled />
                        </div>
                    </div>                    
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
                            <div class="row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="articleCode">Article*</label>
                                    <select class="select2 form-control" id="articleCode" name="articleCode" disabled>
                                        <option value="">All</option>
                                        @foreach($articleHeader as $val)
                                            <option value="{{ $val->article_code }}|{{ $val->uom }}|{{ $val->cust_name }}|{{ $val->group }}|{{ $val->third_party }}|{{ $val->group_of_material }}" {{$val->article_code == $header->article_code ? "selected" : ""}}>{{ $val->article_alternative_code }} - {{ $val->article_desc }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="customer">Customer</label>
                                    <input type="text" id="customer" name="customer" class="form-control disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="group">Group of material</label>
                                    <input type="text" id="group" name="group" class="form-control disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="uom">UOM</label>
                                    <input type="text" id="uom" name="uom" class="form-control disabled-el" disabled />
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled>{{ $header->note }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body">
                    <div>
                        <table class="" style="width:98%;table-layout: fixed;">
                            <tbody>
                                <tr>
                                    <td class="isian-satu" style="width: 25%">
                                        <label>Article Code</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>QTY</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>UOM</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>Price</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>Type</label>
                                    </td>
                                    <td class="isian text-center" style="width: 5%">
                                        <label>-</label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;">
                        <input type="text" id ="last_row_number" class="d-none" value="{{ count($detail) }}">
                        @foreach ($detail as $key =>$item)
                            <div id="new_row{{ $key }}" class="tanda-baris" >
                                <table class="table-bordered" style="width: 98%;table-layout: fixed;">
                                    <tbody>
                                        <tr>
                                            <td class="isian-satu" style="width: 25%">
                                                <select class="select2 dynamicSelect sku-select-system" id="article_id{{ $key }}" name="article_id[]" data-dependent="article_id" disabled>
                                                    @foreach($articles as $val)
                                                        <option value="{{ $val->article_code }}|{{ $val->uom }}|{{ $val->costprice }}|{{ $val->article_type }}|{{ $val->type_name }}" {{$val->article_code == $item->article_code ? "selected" : ""}}>{{$val->article_code}} - {{$val->article_desc}}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="isian" style="width: 5%">
                                                <input type="text" class="form-control-plaintext numeral-mask-digit text-right" id = "qtyBom" name="qtyBom[]" value="{{ $item->qty }}" disabled />
                                            </td>
                                            <td class="isian disabled" style="width: 5%">
                                                <span class="" id = "uom" name="uom[]">{{ $item->uom }}</span>
                                            </td>
                                            <td class="isian disabled" style="width: 10%">
                                                <input type="text" class="form-control-plaintext numeral-mask-digit text-right" id = "price" name="price[]" value="{{ $item->cost_price }}" disabled>
                                            </td>
                                            <td class="isian disabled" style="width: 10%">
                                                <span class="" id = "type" name="type[]">{{ $item->uom }}</span>
                                            </td>
                                        </tr>
                                        
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('bom.addArticle')
@endsection
@section('styles')
<style>

    textarea {
        resize: none;
    }

    .mb-03{
        margin-bottom: 0.3rem;
    }
    
    label.titik-dua::after{
        content : ":"; 
        position : absolute;
        right : 1px;
    }
    td.isian{
        padding-right:10px;
        padding-left:10px;
    }

    td.isian-satu{
        padding-right:5px;
        padding-left:15px;
        width: 25%;border-top: 1px solid #ffffff !important;
        border-bottom: 1px solid #ffffff !important;
        border-left: 1px solid #ffffff !important;
    }

    td.disabled{
        background-color:#f8f8f8;
        color:black;
    }

    label.tanpa-padding{
        padding-top: 5px;
        padding-bottom: 0px;
    }

</style>
@endsection
@section('scripts')
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');    
    $(document).ready(function(){           
        validateForm('frmAdd');
        $('#orderDate').val(currentDate);
        isiDetailHeader();
        tombolPanah('qtyBom');
        activate_angka();
        mask_thousand_digit(3);
        splitArticle();
    });

    orderDate = $('#orderDate');
    if (orderDate.length) {
        orderDate.flatpickr({
            dateFormat: "d-m-Y",
        });
    }
    
    function reloadPage(){
        window.location.reload();
    }

   
    function isiDetailHeader(){
        let $this = $("#articleCode");
        let detail = $this.val().split("|");
        $('#uom').val(detail[1]);
        $('#customer').val(detail[2]);
        $('#group').val(detail[3]);
    }

    function splitArticle(){
        // split article with delimiter |
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objUom= $('#article_row span[name="uom[]"]'); 
        let objType= $('#article_row span[name="type[]"]'); 
        let objPrice= $('#article_row input[name="price[]"]'); 
        let objQty = $('input[name="qtyBom[]"]');
        
        objArticle.change(function(e){        
            let objIndex = objArticle.index(this);
            let detail = objArticle.eq(objIndex).val();
            let arrDetail = detail.split("|");
            objUom.eq(objIndex).text(arrDetail[1]);
            objPrice.eq(objIndex).text(arrDetail[3]);
            objType.eq(objIndex).text(arrDetail[4]);
            if (detail){
                setTimeout(() => {
                    objQty.eq(objIndex).focus().select();
                }, 5);
            }
		});
    }

    function changeselect(dependent,obj) {
      $.ajax({
        url:"{{route('dynamic.dependent')}}",
        method:"POST",
        data:{
            dependent:dependent
        },
        success:function(result){
            $('#'+obj).html(result);
            $('#'+obj).val('').trigger('change');
        }
      })
    }
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection