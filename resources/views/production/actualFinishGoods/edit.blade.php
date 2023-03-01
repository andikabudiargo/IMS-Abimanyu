@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: {{ $statusPrd }}</h4>
                    <input type="hidden" id='oEdit' value="{{ $oEdit }}">
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
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="prdNumber">Production Number</label>
                                    <input type="text" id="prdNumber" name="prdNumber" value="{{ $header->prod_code }}" class="form-control form-control-sm disabled-el" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="wosNumber">WOS Number</label>
                                    <input type="text" id="wosNumber" name="wosNumber" value="{{ $header->wo_code }}" class="form-control form-control-sm disabled-el" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="wosDate">Date*</label>
                                    <input type="text" id="wosDate" name="wosDate" value="{{ $header->prod_date }}" class="form-control" placeholder="DD-MM-YYYY" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="shift">Shift*</label>
                                    <input type="text" id="shift" name="shift" value="{{ $header->prod_shift }}" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="group">Group*</label>
                                    <input type="text" id="group" name="group" value="{{ $header->prod_group }}" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="wosTime">Start Time*</label>
                                    <input type="text" id="wosTime" name="wosTime" value="{{ $header->start_time }}" class="form-control" placeholder="HH:MM" disabled/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="workingHour">Working Hour*</label>
                                    <input type="text" id="workingHour" name="workingHour" value="{{ $header->working_hour }}" class="form-control numeral-mask-satuan text-right" maxlength="2" disabled/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="efficiency">Efficiency*</label>
                                    <input type="text" id="efficiency" name="efficiency" value="{{ $header->efficiency ? $header->efficiency : '95' }}" class="form-control numeral-mask-satuan text-right" maxlength="3" disabled />
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled>{{ $header->note }}</textarea>
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
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body" >
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            <br>
                            @include('production.actualFinishGoods.headerColumn')
                            <div class="" id="article_row" style="max-height: 20rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-start align-items-end mt-75">
                    </div>
                    {{-- @include('production.actualFinishGoods.summary') --}}
                    <hr>
                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('production.actualFinishGoods.index') }}" class="btn btn-warning">Back</a>
                                    @if( $approveValidate ? $approveValidate[0]->validate : '')
                                        <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                        <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                        <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                                        @if( $statusPrd =='POSTED')
                                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                                        @endif
                                    @else
                                        @if( !$approveValidate && $statusPrd =='POSTED')
                                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
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
                                            <div class="avatar bg-light-{{ $val->statusapprove == 1 ? 'success':'warning' }} mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="{{ $val->statusapprove == 1 ? 'check':'x' }}" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">{{ $val->statusapprove == 1 ? 'Approve':'Decline' }}-{{ $val->approval_order }}</h4>
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
      
    td.disabled{
        background-color:#f8f8f8;
        color:black;
    }

</style>
@endsection
@section('scripts')
@include('production.actualFinishGoods.addArticle')
<script type="text/javascript">
    const dWosNumber=$('#wosNumber');
    const dWosDate=$('#wosDate');
    const dShift=$('#shift');
    const dGroup=$('#group');
    const dWosTime=$('#wosTime');
    const dWorkingHour=$('#workingHour');
    const dEfficiency=$('#efficiency');
    const dNote=$('#note');
    const approveBtn = document.querySelector('#cmdApprove');

    if (approveBtn) {
        approveBtn.addEventListener('click',() =>{
            let prdNumber = $('#prdNumber').val();
            approve(prdNumber,'cmdApprove');
        },{ once:true});
    }

    $(document).ready(function(){           
        validateFormToast("frmAdd");
        let detail = {!!  $details !!};
        for(let i=0;i< detail.length;i++){
            soCode = detail[i].so_code;
            articleId= detail[i].article_code;
            articleCode = detail[i].article;
            articleRm = detail[i].article_rm_code;
            qtySo = detail[i].so_qty; //belum ada
            uom = 'PCS';
            planQtyFresh = detail[i].act_qty_fresh;
            planQtyRepaint = detail[i].act_qty_repaint;
            planTime = detail[i].act_time;
            planTag = detail[i].act_tag;
            originTag = detail[i].origin_tag;
            qtyFg=detail[i].act_finish_goods;
            urutan = detail[i].urutan;
            add_new_row_edit(soCode,articleCode,articleId,articleRm,qtySo,uom,planQtyFresh,planQtyRepaint,planTime,planTag,originTag,qtyFg,urutan);
        }
    });   

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection