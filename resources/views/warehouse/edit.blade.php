@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusPo }}</span></h4>
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
                                    <label for="tsoCode">Target SO Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="tsoCode" name="tsoCode" class="form-control disabled-el" value="{{ $header->tso_code }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="tsoDate">Date*</label>
                                    <input type="text" id="tsoDate" name="tsoDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header->tso_date }}" required />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label for="tsoName">Target SO Name*</label>
                                    <input type="text" id="tsoName" name="tsoName" class="form-control" value="{{ $header->tso_name }}" required />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="customer">Customer*</label>
                                    <input type="text" id="customer" name="customer" class="form-control" value="{{ $header->customer }}" required />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ $header->note }}</textarea>
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
                <div class="card-body">
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('targetSo.headerColumn')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                @foreach ($details as $key =>$item)
                                    <div id="new_row{{ $key }}" class="tanda-baris" >
                                        <div class="form-row d-flex align-items-center">
                                            <div class="col-md-6 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="articleId" class="d-block d-md-none">Article</label>
                                                    <select class="form-control sku-select-system" id="articleId" name="articleId[]" data-dependent="articleId">
                                                        @foreach($articles as $val)
                                                            <option value="{{ $val->article_code }}" data-uom-group ="{{ $val->uom_group }}" data-uom ="{{ $val->uom }}" {{ $val->article_code == $item->article_code ? "selected" :"" }}>
                                                                    {{ $val->article_alternative_code }} - {{ $val->article_desc }}
                                                            </option>
                                                        @endforeach                                               
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="qtyTarget" class="d-block d-md-none">QTY Target</label>
                                                    <div class="input-group input-group-merge">
                                                        <input type="text" class="form-control numeral-mask-satuan text-right" value="{{ $item->qty_target }}" id="qtyTarget" name="qtyTarget[]" maxlength="9" />
                                                        <div class="input-group-append">
                                                            <span class="input-group-text" id ="uomTarget" name="uomTarget[]">{{ $item->uom }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="qtyForcast" class="d-block d-md-none">QTY Forcast</label>
                                                    <div class="input-group input-group-merge">
                                                        <input type="text" class="form-control numeral-mask-satuan text-right" value="{{ $item->qty_forcast }}"  id="qtyForcast" name="qtyForcast[]" maxlength="9" />
                                                        <div class="input-group-append">
                                                            <span class="input-group-text" id ="uomForcast" name="uomForcast[]">{{ $item->uom }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal();">
                                                        <i data-feather="trash-2" class="remove_button feather-24">
                                                        </i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group row mb-03">
                                <label for="totalAmount" class="col-sm-3 col-form-label titik-dua">Total QTY Target</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQtyTarget" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalAmount" class="col-sm-3 col-form-label titik-dua">Total QTY Forcast</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQtyForcast" disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('targetSo.index') }}" class="btn btn-warning">Back</a>
                                    @if( $approveValidate ? $approveValidate[0]->validate : '')
                                        <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                        <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                        <button class="btn btn-danger" type="button" id="cmdDecline" name="cmdDecline">Decline</button>
                                        <button class="btn btn-primary" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                                    @else
                                        @if( strtoupper($statusPo) == 'NEW' )
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
</style>
@endsection
@section('scripts')
@include('targetSo.addArticle')
<script type="text/javascript">
    const updateBtn = document.querySelector('#cmdUpdate');
    const approveBtn = document.querySelector('#cmdApprove');
    const declineBtn = document.querySelector('#cmdDecline');
    let cloneCount={{ count($details) }};
    if (updateBtn) {
        updateBtn.addEventListener('click',() =>{
            updateData('update');
        },{ once:true});
    }

    if (approveBtn) {
        approveBtn.addEventListener('click',() =>{
            updateData('approve');
        },{ once:true});
    }

    if (declineBtn) {
        declineBtn.addEventListener('click',() =>{
            declineData('decline');
        },{ once:true});
    }

    $(document).ready(function(){           
        validateForm('frmAdd');
        tombolPanah('qtyTarget','','qtyForcast');
        tombolPanah('qtyForcast','qtyTarget','');
        mask_thousand_satuan();
        splitArticle();
        hitungTotal();
        hitungGrandTotal();
        $('[data-toggle="tooltip"]').tooltip();
        $('.sku-select-system').select2();
    });
               
</script>
@endsection