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
                                    <input type="text" id="prNumber" name="prNumber" class="form-control disabled-el"  disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label class="form-label" for="poType">PO Type*</label>
                                    <select class="select2 form-control" id="poType" name="poType" required>
                                        <option value="std">Standard</option>
                                        {{-- <option value="sub">Subcontracting</option> --}}
                                        <option value="tso">Target SO</option>
                                        <option value="rm">Raw Material</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="orderDate">Request Date*</label>
                                    <input type="text" id="orderDate" name="orderDate" class="form-control" placeholder="DD-MM-YYYY" disabled/>
                                </div>
                                
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="dept">Department*</label>
                                    <select class="select2 form-control" id="dept" name="dept" required>
                                        <option value=""></option>
                                        @foreach($depts as $val)
                                            <option value="{{$val->code}}" >{{$val->code}} - {{$val->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row" id="tsoBox">
                                <div class="form-group col-md-2">
                                    <label for="stockDate">Stock Date*</label>
                                    <input type="text" id="stockDate" name="stockDate" class="form-control" placeholder="DD-MM-YYYY" required/>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="tsoCode">Target SO Number</label>
                                    <select class="select2 form-control" id="tsoCode" name="tsoCode">
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8">
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
                        @include("purchaseRequest.headerColumn")
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
                        <a href="{{ route('purchaseRequests.index') }}" class="btn btn-warning">Back</a>
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
    textarea {
        resize: none;
    }    
</style>
@endsection
@section('scripts')
@include('purchaseRequest.addArticle')
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');
    $(document).ready(function(){           
        validateFormToast("frmAdd");
        $('#orderDate').val("{{ $currentDate }}");
        isiArticle('article_pr');
        objTsoBox.hide();
        add_new_row();
    });
    
    if (orderDate.length) {
        orderDate.flatpickr({
            dateFormat: "d-m-Y",
        });
    }

    if (stockDate.length) {
        stockDate.flatpickr({
            dateFormat: "d-m-Y",
            // defaultDate:currentDate
        });
    }   

    objPoType.change(function(e){
        let potype=$(this).val();
        $('#article_row').empty();
        oDept.val("").trigger("change");
        cloneCount=0;
        objTsoBox.hide();
        if (potype ==='tso'){
            objTsoBox.show();
            dependent = 'tso_list'
            changeSelect({
                dependent:dependent,
                obj:'tsoCode',
                url:"{{ route('dynamic.dependent') }}"            
            });
            addNewRow.attr('disabled','disabled');
        }else{
            stockDate.val("");
            add_new_row();
            addNewRow.removeAttr('disabled');
        }
    });

    objTsoCode.change(function(e){
        if (!$("#frmAdd")[0].checkValidity() && $(this).val()){
            $("#frmAdd").submit();
            $(this).val("").trigger('change');
        }else{
            let tsoCode = $(this).val();
            let dStockDate = stockDate.val();
            if (tsoCode){        
                $(".loading-spinner-container").addClass("-show");
                $.ajax({
                    type: "GET",
                    url: "{{ route('purchaseRequest.article.tso') }}",
                    data: {
                        tsoCode:tsoCode,
                        stockDate:dStockDate
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data){
                            for(let i=0;i<data.length;i++){
                                add_new_row_sto(data[i].article_code,data[i].grand_total,data[i].uom,'',data[i].qty_stock,data[i].alternative,data[i].article_desc,data[i].uom_group,data[i].supp);
                                if (i==(data.length-1)){
                                    $(".loading-spinner-container").removeClass("-show");
                                    isiUom();
                                }
                            }
                        }
                    },
                    error: function(error) {
                        Swal.fire('Error..',error,'error');
                    }
                });
            }else{
                $('#article_row').empty();
                cloneCount=0;
            }
        }

        // add_new_row_sto(article,qty,note);
    }); 
    
    $("#cmdSave").click(function(){
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{
            $('#cmdSave').attr('disabled','disabled');
            $('.disabled-el').removeAttr('disabled');
            let objQty = $('#article_row input[name="qty_order[]"]');
            let objNote = $('#article_row input[name="note[]"]');
            let objUom = $('#article_row span[name="uom[]"]'); 
            let objHitung = $('#article_row input[name="qtyHitung[]"]'); 
            let objStock = $('#article_row input[name="qtyStock[]"]'); 
            let articles = []; 
            let flag=0; 
            let pesan="";
            let poType = $('#poType').val();

            $("#article_row select[name='article_id[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let article=$this.find(":selected").data("detail").split('|');
                    let articleName=$this.select2('data')[0].text;
                    let plu=$this.val();
                    let supp=article[2];
                    let uom=objUom.eq(i).text();
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let note=objNote.eq(i).val();
                    let qtyHitung=objHitung.eq(i).val() || 0;
                    let qtyStock=objStock.eq(i).val() || 0;
                            
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
                                "qty_hitung":qtyHitung,
                                "qty_stock":qtyStock,
                            });
                        }
                    } 

                    if ( qty == 0 ){
                        pesan +=`QTY of items ${articleName} cannot be 0 <br>`; 
                        flag=1;
                    }

                    if ( (poType=='tso') && (qty > qtyHitung) ){
                        pesan +=`QTY of items ${articleName} tidak boleh melebihi qty hasil hitung ${qtyHitung} <br>`; 
                        flag=1;
                    }
                }
            });            

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){
                let dOrderDate = $('#orderDate').val();
                let dStockDate = $('#stockDate').val();
                let dept = $('#dept').val();
                
                let tsoCode = $('#tsoCode').val();
                let note = $('#note').val();
                
                $.ajax({
                    type: "post",
                    url: "{{ route('purchaseRequest.store') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        orderDate:dOrderDate,
                        poType:poType,
                        dept:dept,
                        note:note,
                        tsoCode:tsoCode,
                        stockDate:dStockDate
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#prNumber').attr('disabled','disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#prNumber').attr('disabled','disabled');
                            $('#cmdSave').attr('disabled','disabled');
                            $('#addNewRow').attr('disabled','disabled');
                            $('#prNumber').val(data.prNumber);
                        }
                    },
                    error: function(error) {
                        Swal.fire('Error..',error,'error');
                    }
                });
            }else{
                $('#cmdSave').removeAttr('disabled');
                Swal.fire('Warning..',pesan,'warning');
            }
        }
    });
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection