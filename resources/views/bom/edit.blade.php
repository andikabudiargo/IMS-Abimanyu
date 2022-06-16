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
                                    <input type="text" id="bomNumber" name="bomNumber" class="form-control form-control-sm" value="{{ $header->bom_code }}" disabled />
                                </div>
                            </div>  
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="articleCode">Article*</label>
                                    <select class="select2 form-control" id="articleCode" name="articleCode" disabled>
                                        @foreach($articleHeader as $val)
                                            <option value="{{ $val->article_code }}" data-detail ="{{ $val->article_code }}|{{ $val->uom }}|{{ $val->cust_name }}|{{ $val->group }}|{{ $val->third_party }}|{{ $val->group_of_material }}" {{$val->article_code == old("articleCode",$header->article_code) ? "selected" : ""}}>{{ $val->article_alternative_code }} - {{ $val->article_desc }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="customer">Customer</label>
                                    <input type="text" id="customer" name="customer" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="group">Group of material</label>
                                    <input type="text" id="group" name="group" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-1">
                                    <label for="uom">UOM</label>
                                    <input type="text" id="uom" name="uom" class="form-control" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="tag">Tag</label>
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
                    @include('bom.headerColumn')
                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;">
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        {{-- @if( !$approveValidate && $statusBom =='NEW') --}}
                            <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                                <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                                <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                            </button>
                        {{-- @endif --}}
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
                                        <button class="btn btn-primary" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
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
@include('bom.addArticle')
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
    let currentDate = "{{ $currentDateValue }}";
    const approveBtn = document.querySelector('#cmdApprove'); 
    $(document).ready(function(){           
        validateForm('frmAdd');
        mask_thousand_digit(numberOfDecimalDigit);
        isiDetailHeader();
        // tombolPanah('qtyBom');
        splitArticle();
        setMasking();
        $('.sku-select-system').select2();


        let detail = {!!  $detail !!};
        for(let i=0;i<detail.length;i++){
            article = detail[i].article_code;
            qty = detail[i].qty;
            uom =  detail[i].uom;
            typeName = detail[i].type_name;
            uomMember = detail[i].uom_member;
            add_new_row_edit(article,qty,uom,typeName,uomMember);
        }

    });

    if (approveBtn) {
        approveBtn.addEventListener('click',() =>{
            approve();
        },{ once:true});
    }

    $("#cmdCancel,#cmdNew").click(function(){
        $('#bomNumber').val('');
        window.location.reload();
    });

    function setMasking(){
        let objQtyBom = $("#article_row input[name='qtyBom[]']");
        objQtyBom.map(function(i) {  
            let $this=$(this);
            let id_qty = objQtyBom.eq(i).attr('id');
            uomGroup = $('#'+id_qty).data('uom-group'); 
            if ( uomGroup === 'PIECE' ){
                mask_thousand_digit_by_id(id_qty,0);
            }else{
                mask_thousand_digit_by_id(id_qty,numberOfDecimalDigit);
            }
        });
    }

    $("#cmdUpdate").click(function(){  
        
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{
            $('.disabled-el').removeAttr('disabled');
            // ambil semua data article
            let bomNumber = $('#bomNumber').val();
            let objArticle = $("#article_row select[name='article_id[]']");
            let objQty = $('#article_row input[name="qtyBom[]"]');
            let objUom = $('#article_row select[name="uom[]"]');
            let articleCode = $('#articleCode').val();
            let articleCode1 = $('#articleCode').find(":selected").data("detail").split("|");
            let uom = articleCode1[1];
            let group = articleCode1[5];
            let customer  = articleCode1[4];
            let tag = $('#tag').val().replace(/,/gi, '') || 0;
            let passRate = $('#passRate').val().replace(/,/gi, '') || 0;
            let passThru = $('#passThru').val().replace(/,/gi, '') || 0;
            let cycleTime = $('#cycleTime').val().replace(/,/gi, '') || 0;
            
            let note = $('#note').val();
            let articles = []; 
            let flag=0; 
            let pesan="";

            objArticle.map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let articleName=$this.select2('data')[0].text;
                    let plu=$this.val();
                    let uom=objUom.eq(i).val();
                    let detail = $this.find(":selected").data("detail").split("|");
                    let type=detail[4];
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;

                    let obj = articles.find(obj => obj.plu == plu);
                    
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
                                "type":type
                            });
                        }
                    } 

                    console.log(articles);
                
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
                        bomNumber:bomNumber,
                        tag:tag,
                        passRate:passRate,
                        passThru:passThru,
                        cycleTime:cycleTime
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            let message="";
                            for(let i = 0; i < data.message.length; i++) {
                                message += "-"+data.message[i]+"<br>";                           
                            }
                            show_msg("Update BOM", message, data.alert);
                        }else{
                            show_msg("Update BOM", data.message, data.alert);
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });

            }else{
                Swal.fire('Warning..',pesan,'warning');
            }
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
        $("#articleCode").change(function(){
        let $this = $(this);
        let detail = $this.find(":selected").data("detail").split("|");
        $('#uom').val(detail[1]);
        $('#customer').val(detail[2]);
        $('#group').val(detail[5]);
    })
    })

    let cloneCount=1;
    add_new_row_edit = (article,qty,uom,typeName,uomMember)=>{
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
        changeselect('article_bom','article_id'+ cloneCount,article);
        $("#new_row"+ cloneCount).find('#qtyBom').attr('id', 'qtyBom'+ cloneCount);
        $("#qtyBom"+ cloneCount).val(qty);
        $("#new_row"+ cloneCount).find('#type').attr('id', 'type'+ cloneCount);
        $("#type"+ cloneCount).text(typeName);
        $("#article_id"+cloneCount).select2();
        let uomOption="";
        if (uomMember){
            let arrUomMember = uomMember.split(',');
            $.each(arrUomMember, function(index, val) {
                uomOption +=`<option>${val}</option>`;
            });
        }else{
            if(uom){
                uomOption +=`<option>${uom}</option>`;
            }
        }
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        $("#uom"+ cloneCount).html(uomOption);
        $("#uom"+ cloneCount).val(uom).trigger('change');
        $('#remove_button').tooltip();
        tombolPanah('qtyBom');
    }

    function add_new_row() {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
        changeselect('article_bom','article_id'+ cloneCount);
        $("#new_row"+ cloneCount).find('#qtyBom').attr('id', 'qtyBom'+ cloneCount);
        $("#article_id"+cloneCount).select2();
        $('#remove_button').tooltip();
        tombolPanah('qtyBom');
        splitArticle();
    };

    function splitArticle(){
        // split article with delimiter |
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objUom= $('#article_row select[name="uom[]"]'); 
        let objType= $('#article_row span[name="type[]"]'); 
        let objQty = $('#article_row input[name="qtyBom[]"]');
        objArticle.change(function(e){
            let objIndex = objArticle.index(this);
            let article = objArticle.eq(objIndex).val();
            let detail="";
            if (article){
                detail = objArticle.eq(objIndex).find(":selected").data("detail");
            }            
            let arrDetail = detail.split("|");
            let uomMember = objArticle.eq(objIndex).find(":selected").data("uom-member");
            let uomGroup = objArticle.eq(objIndex).find(":selected").data("uom-group");
            objType.eq(objIndex).text(arrDetail[4]);
            let uomOption="";
            if (uomMember){
                let arrUomMember = uomMember.split(',');
                $.each(arrUomMember, function(index, val) {
                    uomOption +=`<option>${val}</option>`;
                });
            }else{
                if(arrDetail[1]){
                    uomOption +=`<option>${arrDetail[1]}</option>`;
                }
            }
            objUom.eq(objIndex).html(uomOption);
            objUom.eq(objIndex).val(arrDetail[1]).trigger('change');

            //jangan di filter dulu karena untuk qty BOM bisa pake Koma
            // if ( uomGroup === 'PIECE' ){
            //     objQty.eq(objIndex).removeClass("numeral-mask-digit");
            //     objQty.eq(objIndex).addClass("numeral-mask-satuan");               
            //     mask_thousand_satuan();
            // }else{
            //     objQty.eq(objIndex).removeClass("numeral-mask-satuan");
            //     objQty.eq(objIndex).addClass("numeral-mask-digit");
            //     mask_thousand_digit(numberOfDecimalDigit);
            // }

            if (detail){
                setTimeout(() => {
                    objQty.eq(objIndex).focus().select();
                }, 5);
            }
            
        });
    }

    function changeselect(dependent,obj,value) {
      $.ajax({
        url:"{{ route('dynamic.dependent') }}",
        method:"POST",
        data:{
            dependent:dependent
        },
        success:function(result){
            $('#'+obj).html(result);
            $('#'+obj).val(value).trigger('change');
        }
      })
    }

    approve = () =>{    
        let bomNumber = $('#bomNumber').val();
        $.ajax({
            type: "post",
            url: "{{ route('bom.approve') }}",
            data: {
                bomNumber:bomNumber
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#bomNumber').attr('disabled','disabled');
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#bomNumber').attr('disabled','disabled');
                    $('#cmdApprove').attr('disabled','disabled');
                    $('#addNewRow').attr('disabled','disabled');      
                    window.location.reload();                 
                }
            },
            error: function(error) {
                console.log(error);
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