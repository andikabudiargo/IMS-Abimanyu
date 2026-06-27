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
    <div class="form-group col-md-5">
        <label class="form-label" for="articleCode">Article Finish Goods*</label>
        <select class="select2 form-control" id="articleCode" name="articleCode" required>
            <option value=""></option>
            @foreach($articles as $val)
                <option value="{{ $val->article_code }}" data-detail="{{ $val->article_code }}|{{ $val->uom }}|{{ $val->cust_name }}|{{ $val->group }}|{{ $val->third_party }}|{{ $val->group_of_material }}" {{ $val->article_code == old("articleCode") ? "selected" : ""}} >
                    {{ $val->article_alternative_code }} - {{ $val->article_desc }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-1">
        <label for="uomHdr">UOM</label>
        <input type="text" id="uomHdr" name="uomHdr" class="form-control disabled-el" disabled />
    </div>
</div>

{{-- ====================== RAW MATERIAL DI BAWAH ARTICLE FG ====================== --}}

{{-- MODE TUNGGAL (uom != SET) --}}
<div class="form-row" id="rmSingleWrapper">
    <div class="form-group col-md-5">
        <label class="form-label" for="articleCodeRm">Article Raw Material*</label>
        <select class="select2 form-control" id="articleCodeRm" name="articleCodeRm" form="frmAdd" required>
            <option value=""></option>
            @foreach($articlesRm as $val)
                <option value="{{ $val->article_code }}"
                        data-detail="{{ $val->article_code }}|{{ $val->article_alternative_code }}|{{ $val->article_desc }}|{{ $val->uom }}"
                        {{ $val->article_code == old("articleCodeRm") ? "selected" : ""}} >
                    {{ $val->article_alternative_code }} - {{ $val->article_desc }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-1">
        <label for="uomRm">UOM</label>
        <input type="text" id="uomRm" name="uomRm" class="form-control disabled-el" disabled />
    </div>
</div>

{{-- MODE MULTI (uom == SET) --}}
<div class="form-row d-none mb-1" id="rmMultiWrapper">
    <div class="col-md-12">
        <label class="form-label">Article Raw Material*</label>
        <div id="rm_row" style="max-height: 20rem;overflow-x: hidden;overflow-y:auto;scrollbar-width: thin;">
            <input type="text" id="last_row_number_rm" class="d-none" value="0">
        </div>
        <button class="btn btn-primary btn-sm mt-50" type="button" id="addNewRowRm" onclick="add_new_row_rm();">
            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
            <span class="align-middle d-sm-inline-block d-none">Add Raw Material</span>
        </button>
    </div>
</div>

{{-- Template baris RM multi mode --}}
<div id="new_row_rm" class="d-none">
    <div id="baru_rm" class="tanda-baris-rm">
        <div class="form-row d-flex align-items-center mb-50">
            <div class="col-md-5 col-5">
                <div class="form-group mb-0">
                    <select class="form-control" id="articleCodeRmMulti" name="articleCodeRmMulti[]">
                        <option value=""></option>
                        @foreach($articlesRm as $val)
                            <option value="{{ $val->article_code }}"
                                    data-detail="{{ $val->article_code }}|{{ $val->article_alternative_code }}|{{ $val->article_desc }}|{{ $val->uom }}">
                                {{ $val->article_alternative_code }} - {{ $val->article_desc }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-1 col-1">
                <div class="form-group mb-0">
                    <input type="text" class="form-control disabled-el uomRmMulti" disabled placeholder="UOM" />
                </div>
            </div>
            <div class="col-md-1 col-1 text-center">
                <a onmouseover="this.style.cursor='pointer'" onclick="removeRmRow(this);">
                    <i data-feather="trash-2" class="remove_button feather-24"></i>
                </a>
            </div>
        </div>
    </div>
</div>
                            <div class="form-row mt-1">
                                <div class="form-group col-md-6">
                                    <label for="customer">Customer</label>
                                    <input type="text" id="customer" name="customer" class="form-control disabled-el"  disabled required/>
                                </div>
                                <div class="form-group col-md-3 d-none">
                                    <label for="group">Group of material</label>
                                    <input type="text" id="group" name="group" class="form-control disabled-el"  disabled />
                                </div>
                               
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="partNo">Part No</label>
                                    <input type="text" id="partNo" name="partNo" class="form-control" />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="model">Model</label>
                                    <input type="text" id="model" name="model" class="form-control" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ old('note') }}</textarea>
                                </div>
                            </div>
                            <hr>
                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <button class="btn btn-success btn-prev" type="button" id="addNewList" name="addNewList" onclick="listItem()">
                                        <i data-feather="upload" class="align-middle mr-sm-25 mr-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">Copy detail from other BOM</span>
                                    </button>
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
                    <hr>
                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                                <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                                <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                            </button>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-md-12 col-12">
                            <a href="{{ route('boms.index') }}" class="btn btn-light">Back</a>
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
@include('bom.listBom')
@include('bom.addArticlev2')
@include('bom.addArticleSb')
<script type="text/javascript">

    // ====================== RAW MATERIAL MULTI (SET) LOGIC ======================

let cloneCountRm = 0;
let isAddingRmRow = false;

// Single mode: update UOM saat RM dipilih
$('#articleCodeRm').on('change', function () {
    let detailStr = $(this).find(":selected").data("detail");
    let detail = detailStr ? detailStr.split("|") : [];
    $('#uomRm').val(detail[3] || '');
});

add_new_row_rm = (article, uom) => {
    if (isAddingRmRow) return;
    isAddingRmRow = true;

    cloneCountRm++;

    let $newRow = $($("#new_row_rm").html());
    $newRow.attr('id', 'new_row_rm' + cloneCountRm);

    let $select = $newRow.find('#articleCodeRmMulti');
    $select.attr('id', 'articleCodeRmMulti' + cloneCountRm);

    let $uomInput = $newRow.find('.uomRmMulti');
    $uomInput.attr('id', 'uomRmMulti' + cloneCountRm);

    $("#rm_row").append($newRow);

    $select.select2({
        placeholder: "Choose Raw Material",
        allowClear: true
    });

    // update UOM saat select berubah
    $select.on('change', function () {
        let detailStr = $(this).find(":selected").data("detail");
        let detail = detailStr ? detailStr.split("|") : [];
        $uomInput.val(detail[3] || '');
    });

    if (article) {
        $select.val(article).trigger('change');
        if (uom) $uomInput.val(uom);
    }

    setTimeout(() => { isAddingRmRow = false; }, 300);
};

removeRmRow = (el) => {
    let $rows = $('#rm_row .tanda-baris-rm');
    if ($rows.length <= 1) {
        $(el).closest('.tanda-baris-rm').find('select').val('').trigger('change');
        $(el).closest('.tanda-baris-rm').find('.uomRmMulti').val('');
        return;
    }
    $(el).closest('.tanda-baris-rm').remove();
};

// Toggle mode tunggal vs multi
$("#articleCode").on('change', function () {
    let detailStr = $(this).find(":selected").data("detail");
    if (!detailStr) return;

    let detail    = detailStr.split("|");
    let uomFg     = (detail[1] || '').toUpperCase().trim();
    let custName  = detail[2] || '';
    let group     = detail[3] || '';
    let custCode  = detail[4] || '';
    let groupCode = detail[5] || '';

    $('#uomHdr').val(detail[1] || '');
    $('#customer').val(custName);
    $('#group').val(group);

    if (uomFg === 'SET') {
        // sembunyikan single, tampilkan multi
        $("#rmSingleWrapper").addClass('d-none');
        $("#articleCodeRm").removeAttr('required').val('').trigger('change');
        $('#uomRm').val('');

        $("#rmMultiWrapper").removeClass('d-none');

        // reset dulu
        $('#rm_row').empty().append('<input type="text" id="last_row_number_rm" class="d-none" value="0">');
        cloneCountRm = 0;

        // default 2 baris kosong
        add_new_row_rm();
        setTimeout(() => add_new_row_rm(), 350);

    } else {
        // sembunyikan multi, tampilkan single
        $("#rmMultiWrapper").addClass('d-none');
        $('#rm_row').empty().append('<input type="text" id="last_row_number_rm" class="d-none" value="0">');
        cloneCountRm = 0;

        $("#rmSingleWrapper").removeClass('d-none');
        $("#articleCodeRm").attr('required', 'required');
    }
});

function getArticleCodeRmList() {
    let isSetMode = !$('#rmMultiWrapper').hasClass('d-none');
    let list = [];

    if (isSetMode) {
        $('#rm_row select[name="articleCodeRmMulti[]"]').each(function () {
            let val = $(this).val();
            if (val) {
                let detailStr = $(this).find(":selected").data("detail");
                let detail = detailStr ? detailStr.split("|") : [];
                list.push({
                    article_code: val,
                    article_alternative_code: detail[1] || '',
                    article_desc: detail[2] || ''
                });
            }
        });
    } else {
        let $this = $('#articleCodeRm');
        let val   = $this.val();
        if (val) {
            let detailStr = $this.find(":selected").data("detail");
            let detail    = detailStr ? detailStr.split("|") : [];
            list.push({
                article_code: val,
                article_alternative_code: detail[1] || '',
                article_desc: detail[2] || ''
            });
        }
    }
    return list;
}

$(document).ready(function(){
    // select2 untuk articleCodeRm single mode
    $('#articleCodeRm').select2({
        placeholder: "Choose Raw Material",
        allowClear: true
    });
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