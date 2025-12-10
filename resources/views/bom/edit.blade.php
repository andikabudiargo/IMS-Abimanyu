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
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="articleCode">Article Finish Goods*</label>
                                    <input type="text" id="articleCode" name="articleCode" value="{{ old('articleCode',$header->article) }}" data-article-code="{{ old('articleCode',$header->article_code) }}" class="form-control" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="articleCodeRm">Article Raw material*</label>
                                    <select class="select2 form-control" id="articleCodeRm" name="articleCodeRm" required>
                                        <option value=""></option>
                                        @foreach($articlesRm as $val)
                                            <option value="{{ $val->article_code }}" {{ $val->article_code == old("articleCodeRm",$header->article_code_rm) ? "selected" : ""}} >{{ $val->article_alternative_code }} - {{ $val->article_desc }}</option>
                                        @endforeach
                                    </select>
                                    {{-- <input type="text" id="articleCodeRm" name="articleCodeRm" value="{{ old('articleCodeRm',$header->article_rm) }}" data-article-code="{{ old('articleCodeRm',$header->article_code_rm) }}" class="form-control" disabled /> --}}
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
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Spray Booth</h4>
                </div>
                <div class="card-body">
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('bom.headerColumnSb')
                            <div class="" id="article_row_sb" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                <input type="text" id ="last_row_number_sb" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRowSp" onclick="add_new_row_sb();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add</span>
                        </button>
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
                                    <a href="{{ route('boms.index') }}" class="btn btn-light">Back</a>
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
@include('bom.addArticleSb')
<script type="text/javascript">
    let currentDate = "{{ $currentDateValue }}";
    const approveBtn = document.querySelector('#cmdApprove'); 
    let kondisiEdit = true;

    function checkVariable(obj) {
        if (allSelectsAreFilledjQuery(obj)) {
            clearInterval(timerId);
            $(".loading-spinner-container").removeClass("-show");
        }
    }

    $(document).ready(function(){           

        setTimeout(function () {
            $(".loading-spinner-container").addClass("-show");
        }, 500);

        timerId= setInterval(() => checkVariable("#article_row select[name='article_id[]']"), 1000);

        validateForm('frmAdd');
        mask_thousand_digit(numberOfDecimalDigit);
        let detail = {!!  $detail !!};
        let sprayBooths = {!!  $sprayBooth !!};
        for(let i=0;i<detail.length;i++){
            article = detail[i].article_code;
            qty = detail[i].qty;
            uom =  detail[i].uom;
            uomCon =  detail[i].uom_con;
            typeName = detail[i].type_name;
            uomMember = detail[i].uom_member;
            uoms = detail[i].uoms;
            factor = detail[i].factor_qty;
            pos = detail[i].pos;
            tone = detail[i].tone;
            brand = detail[i].brand;
            add_new_row_edit(article,qty,uom,uomCon,typeName,uomMember,uoms,factor,pos,tone,brand);
        }

        for(let a=0;a<sprayBooths.length;a++){
            let sprayBooth = sprayBooths[a].spray_booth;
            let tone = sprayBooths[a].tone;
            let tack =  sprayBooths[a].tack;
            let passRate =  sprayBooths[a].pass_rate;
            let passThru =  sprayBooths[a].pass_thru;
            let cycleTime =  sprayBooths[a].cycle_time;
            add_new_row_edit_sb(sprayBooth,tone,tack,passRate,passThru,cycleTime);            
        }

        if ($('#customer').data("customer-code") == 'STI00001CUST'){
            $('#articleCodeRm').removeAttr('required');
        }else{
            $('#articleCodeRm').attr('required','required');
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
  
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection