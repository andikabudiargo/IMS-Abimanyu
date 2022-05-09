@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
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
                                    <select class="select2 form-control" id="poType" name="poType" required>
                                        <option value="std" {{ $header->order_type == 'std' ? "selected" : ""}}>Standard</option>
                                        <option value="sub" {{ $header->order_type == 'sub' ? "selected" : ""}}>Subcontracting</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="orderDate">Order Date*</label>
                                    <input type="text" id="orderDate" name="orderDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header->date }}"required />
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="dept">Department*</label>
                                    <select class="select2 form-control" id="dept" name="dept" required>
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
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ $header->note }}</textarea>
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
                            <div id="new_row{{ $key }}" class="tanda-baris" >
                                <div class="form-row d-flex align-items-end">
                                    <div class="col-md-5 col-12">
                                        <div class="form-group">
                                            <label for="article_id" class="d-block d-md-none">Article Code</label>
                                            <select class="form-control dynamicSelect sku-select-system" id="article_id{{ $key }}" name="article_id[]" data-dependent="article_id">
                                                @foreach($articles as $val)
                                                    <option value="{{ $val->article_code }}|{{ $val->uom }}|{{ $val->third_party }}" {{$val->article_code == $item->article_code ? "selected" : ""}}>{{$val->article_code}} - {{$val->article_desc}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-1 col-12">
                                        <div class="form-group">
                                            <label for="qty_order" class="d-block d-md-none">Qty</label>
                                            <input type="text" class="form-control numeral-mask text-right" id = "qty_order" name="qty_order[]" value="{{ $item->qty }}" maxlength="6" />
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
                                            <input type="text" class="form-control" id = "note" name="note[]" value="{{ $item->note }}" maxlength="100">
                                        </div>
                                    </div>
                                    <div class="col-md-1 col-12">
                                        <div class="form-group">
                                            <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();">
                                                <i data-feather="trash-2" class="remove_button feather-24">
                                                </i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <hr class="d-block d-md-none" />
                            </div>
                        @endforeach
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>
                    <br>
                    <div class="mt-75">
                        <a href="{{ route('purchaseRequests.index') }}" class="btn btn-warning">Back</a>
                        <a href="{{ route('purchaseRequest.create') }}" class="btn btn-success">New</a>
                        <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Update</button>
                        {{-- <button class="btn btn-primary" type="button" id="cmdValidate" name="cmdValidate">Validate</button>
                        <button class="btn btn-primary" type="button" id="cmdAuthorized" name="cmdAuthorized">Auhorized</button> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('purchaseRequest.addArticle')
@endsection
@section('styles')
<style>
    textarea {
        resize: none;
    }
        
</style>
@endsection
@section('scripts')
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');    
    $(document).ready(function(){           
        validateFormToast("frmAdd");
        $('#orderDate').val(currentDate);
        tombolPanah('qty_order');
        activate_angka();
        mask_thousand();
        splitArticle();
        // $('.sku-select-system').select2();
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
        let objQty = $('input[name="qty_order[]"]');
        let objNote = $('input[name="note[]"]');
        let objUom = $('span[name="uom[]"]'); 
        let dept = $('#dept').val(); 
        let articles = []; 
        let flag=0; 
        let pesan="";

        $("#article_row select[name='article_id[]']").map(function(i) {  
		    let $this=$(this);
            if ($this.val()){
                let article=$this.val().split("|");
                let articleName=$this.select2('data')[0].text;
                let plu=article[0];
                let supp=article[2];
                let uom=article[1];
                let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                let note=objNote.eq(i).val();
                            
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
                            "supp":supp,
                            "note":note,
                        });
                    }
                } 
            
                if (qty == 0){
                    pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
                    flag=1;
                }
            }
        });

        if (dept == ''){
			pesan +="Department must be filled in <br>"; 
			flag=1;
		}

        if (articles.length == 0){
			pesan +="Articles must be filled in completely <br>"; 
			flag=1;
		}

        if (flag==0){

            let orderDate = $('#orderDate').val();
            let dept = $('#dept').val();
            let note = $('#note').val();
            let prNumber = $('#prNumber').val();

            $.ajax({
                type: "post",
                url: "{{ route('purchaseRequest.update') }}",
                data: {
                    articles:JSON.stringify(articles),
                    orderDate:orderDate,
                    dept:dept,
                    note:note,
                    prNumber:prNumber
                },
                dataType: "json",
                success: function(data) {
                    if (data.status == 0 ){
                        let message="";
                        for(let i = 0; i < data.message.length; i++) {
                            show_msg(data.title, data.message[i], data.alert);
                        }
                        $('#prNumber').attr('disabled','disabled');

                    }else{
                        show_msg(data.title, data.message, data.alert);
                        $('#prNumber').attr('disabled','disabled');
                        // $('#addNewRow').attr('disabled','disabled');                        
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
    
    let cloneCount=$('#last_row_number').val();
    function add_new_row() {
        let poType = $('#poType').val();
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
        poType =='std' ? changeselect('article_pr','article_id'+ cloneCount) : changeselect('article_pr_sub','article_id'+ cloneCount);
        $("#article_id"+cloneCount).select2();
        $('#remove_button').tooltip();
        tombolPanah('qty_order');
        tombolPanah('newPrice');
        activate_angka();
        mask_thousand();
        splitArticle();
    };

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

    function tombolPanah(objname){
        // function kalo mau pindah filed dari atas ke bawah atau sebaliknya
        let obj = $('input[name="'+objname+'[]"]');
        obj.keyup(function(e) {
            indexnya= obj.index(this);
            indexnya=parseInt(indexnya);
            if (e.keyCode == 38) {
                //panah atas
                indexTarget = indexnya-1;
                obj.eq(indexTarget).focus().select();
                return false;
            }
            if (e.keyCode == 40) {
                //panah bawah
                indexTarget = indexnya+1;
                obj.eq(indexTarget).focus().select();
                return false;
            }
        });
    }
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection