@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

@php
    // currentDate dirender server-side sekali, tidak bergantung pada include addArticle
    $currentDateValue = date('d-m-Y');
@endphp

<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: New</h4>
                    <input type="hidden" id='oEdit' value="{{ $oEdit }}">
                    <input type="hidden" id="editReason" name="editReason" value="">
                    <div class="heading-elements">
                        <ul class="list-inline mb-0"><li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li></ul>
                    </div>
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" autocomplete="off">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="scNumber">Consumption Number</label> <small class="text-muted">automatic</small>
                                    <input type="text" id="scNumber" name="scNumber" class="form-control disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    {{-- FIX 1: value di-set via PHP, bukan JS, jadi flatpickr punya defaultDate yang valid --}}
                                    <label for="scDate">Date*</label>
                                    <input type="text" id="scDate" name="scDate"
                                        value="{{ $currentDateValue }}"
                                        class="form-control" placeholder="DD-MM-YYYY" required/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="locSelect">Location*</label>
                                    {{-- FIX 2: id diganti locSelect, hindari clash dgn reserved word 'location' di JS --}}
                                    <select class="select2 form-control" id="locSelect" name="location" required>
                                        <option value=""></option>
                                        @foreach($locations as $val)
                                            <option value="{{ $val->location_code }}">{{ $val->location_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                   <label class="form-label" for="coa">
    COA <small class="text-muted">(diisi oleh Accounting)</small>
</label>
                                    <select class="select2 form-control" id="coa" name="coa">
                                        <option value=""></option>
                                        @foreach($coas as $c)
                                            <option value="{{ $c->account }}">{{ $c->account }} - {{ $c->description }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="note">Notes*</label>
                                    <textarea id="note" name="note" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Article</h4></div>
                <div class="card-body">
                    <hr>
                    <div id="articleLockMsg" class="alert alert-warning">
                        <i data-feather="alert-triangle" class="align-middle mr-50"></i>
                        Silakan pilih <b>Location</b> terlebih dahulu sebelum menambahkan artikel.
                    </div>

                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('stockConsumption.headerColumn')
                            <div id="article_row" style="max-height:18rem;overflow-x:hidden;scrollbar-width:thin;">
                                <input type="text" id="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary" type="button" id="addNewRow" disabled
                            onclick="add_new_row();hitungGrandTotal();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-3"><input type="text" class="form-control text-right font-weight-bold" id="totalRow" disabled/></div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <a href="{{ route('stockConsumption.index') }}" class="btn btn-light">Back</a>
                            <button class="btn btn-info" type="reset" id="cmdNew" onclick="window.location.reload();">New</button>
                            <button class="btn btn-primary" type="button" id="cmdSave" disabled>Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
@include('stockConsumption.addArticle')
<script type="text/javascript">

    // FIX 3: pakai nama $locSelect (bukan 'location') — hindari clash dgn window.location
    let $locSelect = $('#locSelect');

    function toggleArticleSection(enable) {
        $('#addNewRow').prop('disabled', !enable);
        $('#cmdSave').prop('disabled', !enable);
        // toggleClass(class, state): state=true → tambah class 'd-none'; state=false → hapus
        $('#articleLockMsg').toggleClass('d-none', enable);
    }

    function resetArticleRows() {
        $('#article_row').html('<input type="text" id="last_row_number" class="d-none" value="0">');
        cloneCount = 0;
        dataArticle = "";
        if (typeof hitungGrandTotal === 'function') hitungGrandTotal();
    }

    document.querySelector('#cmdSave').addEventListener('click', function () {
        simpanData(document.getElementById('oEdit').value);
    });

    $(document).ready(function () {

        if (typeof validateFormToast === 'function') validateFormToast("frmAdd");

        // FIX 1: flatpickr dibind TANPA set val() lagi via JS —
        // value sudah di-render PHP di attribute value="{{ $currentDateValue }}"
        flatpickr('#scDate', {
            dateFormat  : "d-m-Y",
            allowInput  : true,
            maxDate     : "today",
            defaultDate : "{{ $currentDateValue }}",  // string literal dari PHP
            disableMobile: true
        });

        // FIX 2: target id 'locSelect', bukan 'location'
        $('#locSelect, #coa').select2({ placeholder: '- Pilih -', allowClear: true, width: '100%' });

        toggleArticleSection(false);

        // FIX 3: handler pakai $locSelect
        $locSelect.on('change', function () {
            const loc = $(this).val();
            resetArticleRows();
            if (loc) {
                // FIX 4: isiArticleByLocation async — enable tombol SETELAH data siap,
                // bukan langsung. Pakai callback/polling sama seperti edit.blade.php
                isiArticleByLocation(loc);

                // Polling sampai dataArticle terisi (max ~3 detik)
                let tries = 0;
                let waitTimer = setInterval(function () {
                    tries++;
                    if (dataArticle !== "" || tries > 30) {
                        clearInterval(waitTimer);
                        toggleArticleSection(true);
                        splitArticle();
                    }
                }, 100);
            } else {
                toggleArticleSection(false);
            }
        });
    });
</script>
@endsection