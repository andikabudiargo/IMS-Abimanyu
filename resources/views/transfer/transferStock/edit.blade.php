@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: {{ $statusTr }}</h4>
                    <input type="hidden" id='oEdit' value="{{ $oEdit }}">
                    <input type="hidden" id="editReason" name="editReason" value="{{ $editReason ?? '' }}">
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
                                <div class="form-group col-md-2">
                                    <label for="trNumber">Transfer Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="trNumber" name="trNumber"
                                        value="{{ $header->tr_number }}"
                                        class="form-control disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="trDate">Transfer Date*</label>
                                    <input type="text" id="trDate" name="trDate"
                                        value="{{ $header->tr_date }}"
                                        class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="penerima">Penerima*</label>
                                    <input type="text" id="penerima" name="penerima" class="form-control" placeholder="Nama Penerima" value="{{ $header->penerima }}" required/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="locationFrom">Location From*</label>
                                    <select class="select2 form-control" id="locationFrom" name="locationFrom" required>
                                        <option value=""></option>
                                        @foreach($locationsFrom as $val)
                                            <option value="{{ $val->location_code }}"
                                                {{ $val->location_code == $header->location_from ? 'selected' : '' }}>
                                                {{ $val->location_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="locationTo">Location To*</label>
                                    <select class="select2 form-control" id="locationTo" name="locationTo" required>
                                        <option value=""></option>
                                        @foreach($locationsTo as $val)
                                            <option value="{{ $val->location_code }}"
                                                {{ $val->location_code == $header->location_to ? 'selected' : '' }}>
                                                {{ $val->location_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea id="note" name="note" class="form-control" rows="1">{{ $header->note }}</textarea>
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
                    <hr>
                    <div id="articleLockMsg" class="alert alert-warning d-none">
                        <i data-feather="alert-triangle" class="align-middle mr-50"></i>
                        Silakan pilih <b>Location From</b> terlebih dahulu sebelum menambahkan artikel.
                    </div>

                    <form id="frmExcel" name="frmExcel" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-row">
                            <div class="col-lg-3 col-md-12">
                                <div class="form-group">
                                    <input type="file" class="custom-file-input" name="file" id="file" required />
                                    <label class="custom-file-label" for="file" id="fileLabel">Choose file</label>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <a href="{{ route('transferOut.export.excel') }}" class="btn btn-light">
                                    <i class="fa fa-download"></i> Download Template
                                </a>
                                <button type="button" class="btn btn-primary" id="uploadExcel">
                                    <i data-feather="upload" class="align-middle mr-sm-25 mr-0"></i>
                                    <span class="align-middle d-sm-inline-block d-none">Upload Excel</span>
                                </button>
                            </div>
                        </div>
                    </form>
                    <hr style="margin-top: 0px;">

                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('transfer.transferStock.headerColumn')
                            <div id="article_row" style="max-height: 18rem; overflow-x: hidden; scrollbar-width: thin;">
                                <input type="text" id="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary" type="button" id="addNewRow"
                            onclick="add_new_row();hitungGrandTotal();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <a href="{{ route('transferStock.index') }}" class="btn btn-light">Back</a>
                                    <button class="btn btn-primary" type="button" id="cmdSave">Save</button>
                        </div>
                    </div>
                    <hr>

                    {{-- Approval History --}}
                    <div class="form-row card-statistics">
                        @foreach($approvalHistory as $val)
                            <div class="statistics-body">
                                <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                    <div class="media">
                                        <div class="avatar bg-light-{{ $val->status == true ? ($val->statusapprove == 1 ? 'success' : 'warning') : 'danger' }} mr-2">
                                            <div class="avatar-content">
                                                <i data-feather="{{ $val->status == true ? ($val->statusapprove == 1 ? 'check' : 'x') : 'x' }}"
                                                    class="avatar-icon"></i>
                                            </div>
                                        </div>
                                        <div class="media-body my-auto">
                                            <h4 class="font-weight-bolder mb-0">
                                                {{ $val->status == true ? ($val->statusapprove == 1 ? 'Approve' : 'Decline') : 'Approve' }}-{{ $val->approval_order }}
                                            </h4>
                                            <p class="card-text mb-0">
                                                {{ $val->status == true ? $val->name : $val->petugas }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('styles')
<style></style>
@endsection

@section('scripts')
@include('transfer.transferStock.addArticle')
<script type="text/javascript">
    let locationFrom = $('#locationFrom');
    let locationTo   = $('#locationTo');
    let objNote      = $('#note');

    // ── Save & Approve ──────────────────────────────────────────
    const saveBtn    = document.querySelector('#cmdSave');
    const approveBtn = document.querySelector('#cmdApprove');

    if (saveBtn) {
        saveBtn.addEventListener('click', () => {
            let oEdit = document.getElementById('oEdit');
            simpanData(oEdit.value);
        });
    }

    if (approveBtn) {
        approveBtn.addEventListener('click', () => {
            let trNumber = $('#trNumber').val();
            approve(trNumber, 'cmdApprove');
        }, { once: true });
    }

    $(document).ready(function () {
        validateFormToast("frmAdd");

      $('#trDate').flatpickr({
    dateFormat: "d-m-Y",
    defaultDate: $('#trDate').val() || null,
    allowInput: true
});

        // ── Init select2 ────────────────────────────────────────
        $('#locationFrom, #locationTo').select2({
            placeholder: '- Pilih Location -',
            allowClear: true,
            width: '100%'
        });

        // ── Hook locationTo untuk booth flag ────────────────────
        locationTo.on('change', function () {
            checkAndSetBoothFlag($(this).val());
        });

        // ── Trigger booth flag sesuai nilai awal (edit) ─────────
        checkAndSetBoothFlag(locationTo.val());

        // ── Load artikel berdasarkan location_from ───────────────
        const initLocFrom = locationFrom.val();
        if (initLocFrom) {
            isiArticleByLocation('trArticleLocation', initLocFrom);
        }

        // ── Tunggu dataArticle siap, lalu populate rows ──────────
        let timerId = setInterval(() => {
            if (dataArticle.length > 0) {
                clearInterval(timerId);
                let detail = {!! json_encode($details) !!};
                detail.forEach(function (d) {
                    add_new_row_edit(
                        d.article_code,
                        d.qty,
                        d.uom,
                        d.uom_member ?? '',
                        d.note ?? '',
                    );
                });
                $(".loading-spinner-container").removeClass("-show");
            }
        }, 500);

        // ── locationFrom change ──────────────────────────────────
        locationFrom.on('change', function () {
            const loc = $(this).val();
            if (loc) {
                isiArticleByLocation('trArticleLocation', loc);
            }
        });
    });
</script>
@endsection