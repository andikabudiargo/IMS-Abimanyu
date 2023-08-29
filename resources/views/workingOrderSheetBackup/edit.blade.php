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
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ $header->note }}</textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <a href="{{ route('boms.index') }}" class="btn btn-warning">Cancel</a>
                                    {{-- <a href="{{ route('bom.create') }}" class="btn btn-success">New</a> --}}
                                    <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
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
                                                <select class="select2 dynamicSelect sku-select-system" id="article_id{{ $key }}" name="article_id[]" data-dependent="article_id">
                                                    @foreach($articles as $val)
                                                        <option value="{{ $val->article_code }}|{{ $val->uom }}|{{ $val->costprice }}|{{ $val->article_type }}|{{ $val->type_name }}" {{$val->article_code == $item->article_code ? "selected" : ""}}>{{$val->article_code}} - {{$val->article_desc}}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="isian" style="width: 5%">
                                                <input type="text" class="form-control-plaintext numeral-mask text-right" id = "qtyBom" name="qtyBom[]" value="{{ $item->qty }}" maxlength="6" />
                                            </td>
                                            <td class="isian disabled" style="width: 5%">
                                                <span class="" id = "uom" name="uom[]">{{ $item->uom }}</span>
                                            </td>
                                            <td class="isian disabled" style="width: 10%">
                                                <input type="text" class="form-control-plaintext numeral-mask text-right" id = "price" name="price[]" value="{{ $item->cost_price }}" disabled>
                                            </td>
                                            <td class="isian disabled" style="width: 10%">
                                                <span class="" id = "type" name="type[]">{{ $item->uom }}</span>
                                            </td>
                                            <td class="isian text-center" style="width: 5%">
                                                <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();">
                                                    <i data-feather="trash-2" class="remove_button feather-24">
                                                    </i>
                                                </a>
                                            </td>
                                        </tr>
                                        
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75 ml-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
{{-- @include('bom.addArticle') --}}
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
        mask_thousand();
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

    $("#cmdCancel,#cmdNew").click(function(){
        reloadPage();
    });

    $("#cmdSave").click(function(){     
        $('.disabled-el').removeAttr('disabled');
        // ambil semua data article
        let bomNumber = $('#bomNumber').val();
        let objArticle = $("#article_row select[name='article_id[]']");
        let objQty = $('input[name="qtyBom[]"]');
        let articleCode1 = $('#articleCode').val().split("|");
        articleCode = articleCode1[0];
        let uom = articleCode1[1];
        let group = articleCode1[5];
        let customer  = articleCode1[4];
        let note = $('#note').val();
        let articles = []; 
        let flag=0; 
        let pesan="";

        objArticle.map(function(i) {  
		    let $this=$(this);
            if ($this.val()){
                let article=$this.val().split("|");
                let articleName=$this.select2('data')[0].text;
                let plu=article[0];
                let uom=article[1];
                let price=article[2]||0;
                let type=article[3];
                let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                            
                //es6
                // let obj = ingredient.find(obj => obj.plu == plu);

                //jquery
                //cek apakah article ada yang double input ato ngk
                let obj = $.grep(articles, function(obj){
                    return obj.article_code === plu;
                })[0];
                
                if(obj) {
                    pesan +="Article "+articleName+" entered more than once !! <br>"; 
                    flag=1;
                } else {
                    if ((plu!=='') && (qty> 0)){
                        articles.push({
                            "article_code":plu,
                            "qty":qty,
                            "uom":uom,
                            "customer_code":customer,
                            "price":price,
                            "type":type
                        });
                    }
                } 
            
                if (qty == 0){
                    pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
                    flag=1;
                }
            }
        });

        if (customer == ''){
			pesan +="Customer must be filled in <br>"; 
			flag=1;
		}

        if (articles.length == 0){
			pesan +="Articles must be filled in completely <br>"; 
			flag=1;
		}

        if (flag==0){

            $.ajax({
                type: "post",
                url: "{{ route('bom.update') }}",
                data: {
                    articles:JSON.stringify(articles),
                    articleCode:articleCode,
                    customer:customer,
                    note:note,
                    group:group,
                    uom:uom,
                    bomNumber:bomNumber
                },
                dataType: "json",
                success: function(data) {
                    if (data.status == 0 ){
                        let message="";
                        for(let i = 0; i < data.message.length; i++) {
                            message += "-"+data.message[i]+"<br>";                           
                        }
                        $("#alert-message-success").addClass(data.alert);
                        $("#alert-message-success .alert-body").html(message);
                        $("#alert-message-success").show();
                        $("#alert-message-success").fadeTo(5000, 500).slideUp(500, function(){
                            $("#alert-message-success").slideUp(500);
                        });
                        $('#bomNumber').attr('disabled','disabled');

                    }else{
                        $("#alert-message-success").addClass(data.alert);
                        $("#alert-message-success .alert-body").html(data.message);
                        $("#alert-message-success").show();
                        $("#alert-message-success").fadeTo(5000, 500).slideUp(500, function(){
                            $("#alert-message-success").slideUp(500);
                        });
                        $('#bomNumber').attr('disabled','disabled');
                        // $('#cmdSave').attr('disabled','disabled');
                        // $('#addNewRow').attr('disabled','disabled');
                        $('#bomNumber').val(data.bomNumber);
                        
                    }
                    
                },
                error: function(error) {
                    console.log(error);
                }
            });

        }else{
            Swal.fire('Warning..',pesan,'warning');
        }
    
    });

    function isiDetailHeader(){
        let $this = $("#articleCode");
        let detail = $this.val().split("|");
        $('#uom').val(detail[1]);
        $('#customer').val(detail[2]);
        $('#group').val(detail[3]);
    }

    $("#articleCode").change(function(){
        let $this = $(this);
        let detail = $this.val().split("|");
        $('#uom').val(detail[1]);
        $('#customer').val(detail[2]);
        $('#group').val(detail[3]);
    })

    let cloneCount=$('#last_row_number').val();
    function add_new_row() {
        let customer = $('#customer');
        let cust = customer.val();
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
        changeselect('article_bom','article_id'+ cloneCount);
        $("#article_id"+cloneCount).select2();
        $('#remove_button').tooltip();
        tombolPanah('qtyBom');
        activate_angka();
        mask_thousand();
        splitArticle();
    };

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