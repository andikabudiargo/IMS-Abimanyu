@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: NEW</h4>
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
                            <input type="text" id="article" name="article" hidden>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="bomNumber" class="form-label">BOM Number</label>
                                    <input type="text" id="bomNumber" name="bomNumber" class="form-control form-control-sm disabled-el" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="articleCode">Article Finish Goods*</label>
                                    <select class="select2 form-control" id="articleCode" name="articleCode" required>
                                        <option value=""></option>
                                        @foreach($articles as $val)
                                            <option value="{{ $val->article_code }}" data-detail ="{{ $val->article_code }}|{{ $val->uom }}|{{ $val->cust_name }}|{{ $val->group }}|{{ $val->third_party }}|{{ $val->group_of_material }}" {{ $val->article_code == old("articleCode") ? "selected" : ""}} >{{ $val->article_alternative_code }} - {{ $val->article_desc }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="articleCodeRm">Article Raw material*</label>
                                    <select class="select2 form-control" id="articleCodeRm" name="articleCodeRm" required>
                                        <option value=""></option>
                                        @foreach($articlesRm as $val)
                                            <option value="{{ $val->article_code }}" {{ $val->article_code == old("articleCodeRm") ? "selected" : ""}} >{{ $val->article_alternative_code }} - {{ $val->article_desc }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="customer">Customer</label>
                                    <input type="text" id="customer" name="customer" class="form-control disabled-el"  disabled required/>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="group">Group of material</label>
                                    <input type="text" id="group" name="group" class="form-control disabled-el"  disabled />
                                </div>
                                <div class="form-group col-md-1">
                                    <label for="uomHdr">UOM</label>
                                    <input type="text" id="uomHdr" name="uomHdr" class="form-control disabled-el"  disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="partNo">Part No</label>
                                    <input type="text" id="partNo" name="partNo" class="form-control" />
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="model">Model</label>
                                    <input type="text" id="model" name="model" class="form-control" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="tag">Tag*</label>
                                    <input type="text" id="tag" name="tag" value="{{ old('tag') }}" class="form-control numeral-mask-digit" maxlength="5" required/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="passRate">Pass Rate*</label>
                                    <input type="text" id="passRate" name="passRate" value="{{ old('passRate') }}" class="form-control numeral-mask-digit" maxlength="5" required/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="passThru">Pass trough*</label>
                                    <div class="input-group input-group-merge">
                                        <input type="text" id="passThru" name="passThru" value="{{ old('passThru') }}" class="form-control numeral-mask-digit" maxlength="5" required/>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="cycleTime">Cycle time buffing</label>
                                    <input type="text" id="cycleTime" name="cycleTime" value="{{ old('cycleTime') }}" class="form-control numeral-mask-digit" maxlength="5" required/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ old('note') }}</textarea>
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
                            @include('bom.headerColumn')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-md-12 col-12">
                            <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel">New</button>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                        </div>
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

    @media screen 
    and (min-device-width: 1200px) 
    and (max-device-width: 1600px) 
    and (-webkit-min-device-pixel-ratio: 1) { 
        .lebar-list-item{
            width:110%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
    }

    @media only screen and (min-width: 600px)
    and (max-width: 1200px)
    {
        .lebar-list-item{
            width:100%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
    }
</style>
@endsection
@section('scripts')
@include('bom.addArticle')
<script type="text/javascript">
        
    $(document).ready(function(){     
        validateFormToast("frmAdd");
        mask_thousand_digit(numberOfDecimalDigit);
    });

    $("#cmdSave").click(function(){ 
        let oEdit = $('#oEdit').val();
        saveData(oEdit);
    });
 
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection