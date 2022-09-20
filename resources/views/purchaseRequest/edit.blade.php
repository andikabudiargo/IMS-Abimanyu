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
                    <h4 class="card-title">Status: {{ $statusPr }}</h4>
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
                                    <select class="select2 form-control" id="poType" name="poType" required disabled>
                                        <option value="std">Standard</option>
                                        {{-- <option value="sub">Subcontracting</option> --}}
                                        <option value="tso">Target SO</option>
                                        <option value="rm">Raw Material</option>
                                        <option value="std" {{ $header->order_type == 'std' ? "selected" : ""}}>Standard</option>
                                        {{-- <option value="sub" {{ $header->order_type == 'sub' ? "selected" : ""}}>Subcontracting</option> --}}
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
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('purchaseRequests.index') }}" class="btn btn-warning">Back</a>
                                    @if( $approveValidate ? $approveValidate[0]->validate : '')
                                        <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                        <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                        <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                                        @if( $statusPr =='NEW')
                                            <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate" >Update</button>
                                        @endif
                                    @else
                                        @if( !$approveValidate && $statusPr =='NEW')
                                            <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate" >Update</button>
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
@include('purchaseRequest.addArticle')
<script type="text/javascript">
    let timerId="";
    $(document).ready(function(){           
        validateFormToast("frmAdd");
        isiArticle('article_pr');
        setTimeout(function () {
            $(".loading-spinner-container").addClass("-show");
        }, 500);
        timerId= setInterval(() => checkVariable(), 1000);
    });

    let detail = {!! $details !!};
    function checkVariable() {
        if (dataArticle.length > 0) {
            clearInterval(timerId);
            isiData(detail);
        }
    }

    isiData = (data) =>{
        if (data){
            for(let i=0;i<data.length;i++){
                article = data[i].article_code;
                qty = data[i].qty;
                uom =  data[i].uom;
                uomGroup = data[i].uom_group;
                note = data[i].note;
                add_new_row_edit(article,qty,uom,uomGroup,note);
                if (i==(data.length-1)){
                    $(".loading-spinner-container").removeClass("-show");
                }
            }
        }
    }

    orderDate = $('#orderDate');
    if (orderDate.length) {
        orderDate.flatpickr({
            dateFormat: "d-m-Y",
        });
    }
    
    $("#cmdUpdate").click(function(){
        $('.disabled-el').removeAttr('disabled');
        // ambil semua data article
        let objQty = $('#article_row input[name="qty_order[]"]');
        let objNote = $('#article_row input[name="note[]"]');
        let objUom = $('#article_row span[name="uom[]"]'); 
        let dept = $('#dept').val(); 
        let articles = []; 
        let flag=0; 
        let pesan="";

        $("#article_row select[name='article_id[]']").map(function(i) {  
		    let $this=$(this);
            if ($this.val()){
                let article=$this.find(":selected").data("detail").split('|');
                let articleName=$this.select2('data')[0].text;
                let plu=article[0];
                let supp=article[2];
                let uom=objUom.eq(i).text();
                let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                let note=objNote.eq(i).val();
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
    
    $("#cmdApprove").click(function(){    
        let prNumber = $('#prNumber').val();
        $.ajax({
            type: "post",
            url: "{{ route('purchaseRequest.approve') }}",
            data: {
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
                    $('#cmdApprove').attr('disabled','disabled');
                    $('#addNewRow').attr('disabled','disabled');                       
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
    });
    
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