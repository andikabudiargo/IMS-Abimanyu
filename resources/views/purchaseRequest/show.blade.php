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
                                <div class="form-group col-md-3">
                                    <label for="prNumber">Request Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="prNumber" name="prNumber" class="form-control disabled-el" value="{{ $header->pr_number }}" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label class="form-label" for="poType">PO Type*</label>
                                    <select class="select2 form-control" id="poType" name="poType" required disabled >
                                        <option value="std" {{ $header->order_type == 'std' ? "selected" : ""}}>Standard</option>
                                        <option value="sub" {{ $header->order_type == 'sub' ? "selected" : ""}}>Subcontracting</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="orderDate">Order Date*</label>
                                    <input type="text" id="orderDate" name="orderDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header->date }}"required disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="dept">Department*</label>
                                    <select class="select2 form-control" id="dept" name="dept" required disabled >
                                        <option value=""></option>
                                        @foreach($depts as $val)
                                            <option value="{{$val->code}}" {{$val->code == $header->dept ? "selected" : ""}}>{{$val->code}} - {{$val->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-7">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled >{{ $header->note }}</textarea>
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
                    <div class="form-row d-flex align-items-end">
                        <div class="col-md-5 col-12 d-none d-md-block">
                            <div class="form-group">
                                <label class="d-none d-md-block">Article Code</label>
                            </div>
                        </div>
                        <div class="col-md-1 col-12 d-none d-md-block">
                            <div class="form-group">
                                <label class="d-none d-md-block text-right">Qty</label>
                            </div>
                        </div>
                        <div class="col-md-1 col-12 d-none d-md-block">
                            <div class="form-group">
                                <label class="d-none d-md-block">Uom</label>
                            </div>
                        </div>
                        <div class="col-md-3 col-12 d-none d-md-block">
                            <div class="form-group">
                                <label class="d-none d-md-block">Note</label>
                            </div>
                        </div>
                        <div class="col-md-1 col-12 d-none d-md-block">
                            <div class="form-group">
                                <label class="d-none d-md-block">-</label>
                            </div>
                        </div>
                    </div>
                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                        <input type="text" id ="last_row_number" class="d-none" value="{{ count($detail) }}">
                        @foreach ($detail as $key =>$item)
                            <div id="baru" class="tanda-baris" >
                                <div class="form-row d-flex align-items-end">
                                    <div class="col-md-5 col-12">
                                        <div class="form-group">
                                            <label for="article_id" class="d-block d-md-none">Article Code</label>
                                            <select class="form-control dynamicSelect sku-select-system" id="article_id{{ $key }}" name="article_id[]" data-dependent="article_id" disabled>
                                                @foreach($articles as $val)
                                                    <option value="{{ $val->article_code }}|{{ $val->uom }}|{{ $val->third_party }}" {{$val->article_code == $item->article_code ? "selected" : ""}}>{{$val->article_code}} - {{$val->article_desc}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-1 col-12">
                                        <div class="form-group">
                                            <label for="qty_order" class="d-block d-md-none">Qty</label>
                                            <input type="text" class="form-control numeral-mask text-right" id = "qty_order" name="qty_order[]" value="{{ $item->qty }}" maxlength="6" disabled/>
                                        </div>
                                    </div>
                                    <div class="col-md-1 col-12">
                                        <div class="form-group">
                                            <label for="uom" class="d-block d-md-none">Uom</label>
                                            <span class="" id = "uom" name="uom[]">{{ $item->uom }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-12">
                                        <div class="form-group">
                                            <label for="note" class="d-block d-md-none">Note</label>
                                            <input type="text" class="form-control" id = "note" name="note[]" value="{{ $item->note }}" maxlength="100" disabled>
                                        </div>
                                    </div>
                                </div>
                                <hr class="d-block d-md-none" />
                            </div>
                        @endforeach
                    </div>
                    <hr>
                    <br>
                    <div class="mt-75">
                        <a href="{{ route('purchaseRequests.index') }}" class="btn btn-warning">Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
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
        activate_angka();
        mask_thousand();
        splitArticle();
        $('.sku-select-system').select2();
    });

    $("#cmdCancel,#cmdNew").click(function(){
        reloadPage();
    });
    
    function splitArticle(){
        // split article with delimiter |
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objUom= $('#article_row span[name="uom[]"]'); 
        let objQty= $('#article_row input[name="qty_order[]"]'); 
        objArticle.change(function(e){        
            let objIndex = objArticle.index(this);
            let detail = objArticle.eq(objIndex).val();
            let arrDetail = detail.split("|");
            objUom.eq(objIndex).text(arrDetail[1]);
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