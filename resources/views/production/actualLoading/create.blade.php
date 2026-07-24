@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="row">
        {{-- CARD 1: INFO --}}
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
                                <div class="form-group col-md-3">
                                    <label for="prdNumber">Production Number</label>
                                    <input type="text" id="prdNumber" name="prdNumber" placeholder="Automatic" class="form-control disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-3">
    <label for="loadingDate">Loading Date*</label>
    <input type="text" id="loadingDate" name="loadingDate" class="form-control" placeholder="DD-MM-YYYY" required />
</div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="sprayBooth">Spray Booth*</label>
                                        <select class="select2 form-control" id="sprayBooth" name="sprayBooth" data-placeholder="-- Select Spray Booth --" required>
                                            <option value=""></option>
                                            @foreach($sprayBooths as $val)
                                            <option value="{{ $val->location_code }}">{{ $val->location_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                               <div class="col-md-3">
    <div class="form-group">
        <label for="reference">WOS Date*</label>
        <input type="text" class="form-control" id="reference" name="reference"
               placeholder="DD-MM-YYYY" maxlength="100" required />
    </div>
</div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="note">Notes*</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="3" required></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 2: ARTICLE --}}
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body">
                    <hr>
                    <div id="articleLockMsg" class="alert alert-warning">
                        <i data-feather="alert-triangle" class="align-middle mr-50"></i>
                        Silakan pilih <b>Spray Booth</b> terlebih dahulu sebelum menambahkan artikel.
                    </div>

                    <hr style="margin-top: 0px;">
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('production.actualLoading.headerColumn')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" disabled onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" disabled/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group row mb-03 d-none">
                                <label for="totalQty" class="col-sm-3 col-form-label titik-dua">Total QTY</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQty" disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <a href="#" class="btn btn-light">Back</a>
                            <button class="btn btn-info" type="reset" id="cmdNew" name="cmdCancel">New</button>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave" disabled>Save</button>
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
    textarea { resize: none; }
    .mb-03{ margin-bottom: 0.3rem; }
    label.titik-dua::after{ content : ":"; position : absolute; right : 1px; }
</style>
@endsection
@section('scripts')
@include('production.actualLoading.addArticle')
<script type="text/javascript">
   const currentDate = "{{ $currentDateValue ?? date('d-m-Y') }}";
const loadingDate = $('#loadingDate');
const reference = $('#reference');   // ⬅ NEW

if (loadingDate.length) {
    loadingDate.flatpickr({
        dateFormat: "d-m-Y",
        maxDate: "today"   // ⬅ NEW: tidak bisa pilih tanggal maju
    });
}

if (reference.length) {              // ⬅ NEW
    reference.flatpickr({
        dateFormat: "d-m-Y",
        maxDate: "today"   // ⬅ NEW: tidak bisa pilih tanggal maju
    });
}

function toggleArticleSection(enable){
    const disabled = !enable;
    $('#addNewRow').prop('disabled', disabled);
    $('#cmdSave').prop('disabled', disabled);
    $('#articleLockMsg').toggleClass('d-none', enable);
}

function resetArticleRows(){
    $('#article_row').html('<input type="text" id="last_row_number" class="d-none" value="0">');
    if (typeof sumData === 'function') sumData();
}

$(document).ready(function(){
    validateFormToast("frmAdd");
    loadingDate.val(currentDate);
    toggleArticleSection(false); // terkunci di awal

    // ⬅ NEW: pastikan placeholder Select2 muncul meski select2 di-init global di file lain
    $('#sprayBooth').select2({
        placeholder: $('#sprayBooth').data('placeholder') || 'Select Spray Booth',
        allowClear: true,
        width: '100%'
    });
});

$('#sprayBooth').on('change', function () {
    let loc = $(this).val();
    resetArticleRows();
    toggleArticleSection(!!loc);
    isiArticleBySprayBooth(loc);
});

$.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});
</script>
@endsection