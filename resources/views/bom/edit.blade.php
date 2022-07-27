@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="edit-form">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: {{ $statusBom }}</h4>
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
                                    <label for="bomNumber" class="form-label">BOM Number</label>
                                    <input type="text" id="bomNumber" name="bomNumber" value="{{ $header->bom_code }}" class="form-control form-control-sm"  disabled />
                                </div>
                            </div>  
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="articleCode">Article Finish Goods*</label>
                                    <input type="text" id="articleCode" name="articleCode" value="{{ old('articleCode',$header->article) }}" data-article-code="{{ old('articleCode',$header->article_code) }}" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="articleCodeRm">Article Raw material*</label>
                                    <input type="text" id="articleCodeRm" name="articleCodeRm" value="{{ old('articleCodeRm',$header->article_rm) }}" data-article-code="{{ old('articleCodeRm',$header->article_code_rm) }}" class="form-control" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="customer">Customer</label>
                                    <input type="text" id="customer" name="customer" value="{{ old('customer',$header->cust_name) }}" data-customer-code = "{{ $header->customer }}" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="group">Group of material</label>
                                    <input type="text" id="group" name="group" value="{{ old('group',$header->group) }}" data-group = "{{ $header->group_of_material }}" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-1">
                                    <label for="uomHdr">Uom</label>
                                    <input type="text" id="uomHdr" name="uomHdr" value="{{ old('uomHdr',$header->uom) }}" class="form-control" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="partNo">Part No</label>
                                    <input type="text" id="partNo" name="partNo" value="{{ old('partNo',$header->part_no) }}" class="form-control" />
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="model">Model</label>
                                    <input type="text" id="model" name="model" value="{{ old('model',$header->model) }}" class="form-control" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="tag">Tact*</label>
                                    <input type="text" id="tag" name="tag" value="{{ old('tag',$header->tag) }}" class="form-control numeral-mask-digit" maxlength="5" />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="passRate">Pass Rate</label>
                                    <input type="text" id="passRate" name="passRate" value="{{ old('passRate',$header->pass_rate) }}" class="form-control numeral-mask-digit" maxlength="5"/>
                                </div>

                                <div class="form-group col-md-2">
                                    <label for="passThru">Pass trough</label>
                                    <div class="input-group input-group-merge">
                                        <input type="text" id="passThru" name="passThru" value="{{ old('passThru',$header->pass_thru) }}" class="form-control numeral-mask-digit" maxlength="5"/>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="cycleTime">Cycle time buffing</label>
                                    <input type="text" id="cycleTime" name="cycleTime" value="{{ old('cycleTime',$header->cycle_time) }}" class="form-control numeral-mask-digit" maxlength="5"/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ old('note',$header->note) }}</textarea>
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
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('bom.headerColumn')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        @if( $statusBom =='NEW')
                            <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                                <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                                <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                            </button>
                        @endif
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('boms.index') }}" class="btn btn-warning">Back</a>
                                    @if( $approveValidate ? $approveValidate[0]->validate : '')
                                        <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                        <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                        <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                                        @if( $statusBom =='NEW')
                                            <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate">Update</button>
                                        @endif
                                    @else
                                        @if( !$approveValidate && $statusBom =='NEW')
                                            <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate">Update</button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row card-statistics">
                        @foreach($approvalHistory as $val)
                            @if($val->status == true)
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-success mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="check" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}</h4>
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

@endsection
@section('styles')
<style>
    textarea {
        resize: none;
    }   
</style>
@endsection
@section('scripts')
@include('bom.addArticle')
<script type="text/javascript">
    let currentDate = "{{ $currentDateValue }}";
    const approveBtn = document.querySelector('#cmdApprove'); 
    $(document).ready(function(){           
        validateForm('frmAdd');
        mask_thousand_digit(numberOfDecimalDigit);
        let detail = {!!  $detail !!};
        for(let i=0;i<detail.length;i++){
            article = detail[i].article_code;
            qty = detail[i].qty;
            uom =  detail[i].uom;
            uomCon =  detail[i].uom_con;
            typeName = detail[i].type_name;
            uomMember = detail[i].uom_member;
            uoms = detail[i].uoms;
            factor = detail[i].factor_qty;
            add_new_row_edit(article,qty,uom,uomCon,typeName,uomMember,uoms,factor);
        }
    });

    if (approveBtn) {
        
        approveBtn.addEventListener('click',() =>{
            let bomNumber = $('#bomNumber').val();
            approve(bomNumber);
        },{ once:true});
    }

    $("#cmdUpdate").click(function(){  
        let oEdit = true;
        saveData(oEdit);
    });

    // $("#cmdUpdate").click(function(){  
    //     if (!$("#frmAdd")[0].checkValidity()){
    //         $("#frmAdd").submit();
    //     }else{
    //         $('.disabled-el').removeAttr('disabled');
    //         let bomNumber = $('#bomNumber').val();
    //         let objArticle = $("#article_row select[name='article_id[]']");
    //         let objQty = $('#article_row input[name="qtyBom[]"]');
    //         let objUom = $('#article_row select[name="uom[]"]');
    //         let objUomCon = $('#article_row select[name="uomCon[]"]');
    //         let articleCode = $('#articleCode').val();
    //         let articleCode1 = $('#articleCode').find(":selected").data("detail").split("|");
    //         let uom = articleCode1[1];
    //         let group = articleCode1[5];
    //         let customer  = articleCode1[4];
    //         let tag = $('#tag').val().replace(/,/gi, '') || 0;
    //         let passRate = $('#passRate').val().replace(/,/gi, '') || 0;
    //         let passThru = $('#passThru').val().replace(/,/gi, '') || 0;
    //         let cycleTime = $('#cycleTime').val().replace(/,/gi, '') || 0;
            
    //         let note = $('#note').val();
    //         let articles = []; 
    //         let flag=0; 
    //         let pesan="";

    //         objArticle.map(function(i) {  
    //             let $this=$(this);
    //             if ($this.val()){
    //                 let articleName=$this.select2('data')[0].text;
    //                 let plu=$this.val();
    //                 let uom=objUom.eq(i).val();
    //                 let detail = $this.find(":selected").data("detail").split("|");
    //                 let type=detail[4];
    //                 let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;

    //                 let obj = articles.find(obj => obj.plu == plu);
                    
    //                 if(obj) {
    //                     pesan +="Article "+articleName+" entered more than once !! <br>"; 
    //                     flag=1;
    //                 } else {
    //                     if ((plu!=='') && (qty> 0)){
    //                         articles.push({
    //                             "article_code":plu,
    //                             "qty":qty,
    //                             "uom":uom,
    //                             "customer_code":customer,
    //                             "type":type
    //                         });
    //                     }
    //                 } 

    //                 console.log(articles);
                
    //                 if (qty == 0){
    //                     pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
    //                     flag=1;
    //                 }
    //             }
    //         });

    //         if (customer == ''){
    //             pesan +="Customer must be filled in <br>"; 
    //             flag=1;
    //         }

    //         if (articles.length == 0){
    //             pesan +="Articles must be filled in completely <br>"; 
    //             flag=1;
    //         }

    //         if (flag==0){
    //             $.ajax({
    //                 type: "post",
    //                 url: "{{ route('bom.update') }}",
    //                 data: {
    //                     articles:JSON.stringify(articles),
    //                     articleCode:articleCode,
    //                     customer:customer,
    //                     note:note,
    //                     group:group,
    //                     uom:uom,
    //                     bomNumber:bomNumber,
    //                     tag:tag,
    //                     passRate:passRate,
    //                     passThru:passThru,
    //                     cycleTime:cycleTime
    //                 },
    //                 dataType: "json",
    //                 success: function(data) {
    //                     if (data.status == 0 ){
    //                         let message="";
    //                         for(let i = 0; i < data.message.length; i++) {
    //                             message += "-"+data.message[i]+"<br>";                           
    //                         }
    //                         show_msg("Update BOM", message, data.alert);
    //                     }else{
    //                         show_msg("Update BOM", data.message, data.alert);
    //                     }
    //                 },
    //                 error: function(error) {
    //                     console.log(error);
    //                 }
    //             });

    //         }else{
    //             Swal.fire('Warning..',pesan,'warning');
    //         }
    //     }
    
    // });
  
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection