    @extends('layouts.app')
    @section('title', $title)
    @section('content')
    @include('layouts.breadcrumb')

    <section id="adj-create">
        <div class="form-row">

            {{-- ── HEADER CARD ─────────────────────────────────────────────── --}}
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Status: New</h4>
                        <input type="hidden" id="oEdit" value="{{ $oEdit }}">
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
                                        <label for="adjCode">Adjustment Code</label>
                                        <small class="text-muted"> automatic</small>
                                        <input type="text" id="adjCode" name="adjCode"
                                            class="form-control disabled-el" disabled />
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="adjDate">Adjustment Date *</label>
                                        <input type="text" id="adjDate" name="adjDate"
                                            class="form-control" placeholder="DD-MM-YYYY"
                                            value="{{ old('adjDate') }}" required />
                                    </div>
                                <div class="form-group col-md-1">
        <label class="form-label" for="periode">Periode *</label>
        <select class="select2 form-control" id="periode" name="periode">
            <option value=""></option>
            @for ($i = 1; $i <= 12; $i++)
                <option value="{{ $i }}">{{ $i }}</option>
            @endfor
        </select>
    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-3">
                                        <label class="form-label" for="adjType">Adjustment Type *</label>
                                        <select class="select2 form-control" id="adjType" name="adjType" required>
                                            <option value=""></option>
                                            @foreach($types as $val)
                                                <option value="{{ $val }}">{{ $val }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label class="form-label" for="location">Location *</label>
                                        <select class="select2 form-control" id="location" name="location" required>
                                            <option value=""></option>
                                            @foreach($locations as $val)
                                                <option value="{{ $val->location_code }}">{{ $val->location_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="description">Description</label>
                                        <input type="text" id="description" name="description"
                                            class="form-control" maxlength="255" />
                                    </div>
                                    {{--<div class="form-group col-md-3">
                                        <label for="note">Note</label>
                                        <textarea id="note" name="note" class="form-control" rows="1"></textarea>
                                    </div>--}}
                                </div>
                                {{--<div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label class="form-label d-block">Direction *</label>
                                        <div class="btn-group btn-group-toggle" id="directionToggle" data-toggle="buttons">
                                            <label class="btn btn-outline-success active" id="btnDirPlus">
                                                <input type="radio" name="direction" id="dirPlus" value="+" checked autocomplete="off">
                                                Stock In &nbsp;(+)
                                            </label>
                                            <label class="btn btn-outline-danger" id="btnDirMinus">
                                                <input type="radio" name="direction" id="dirMinus" value="-" autocomplete="off">
                                                Stock Out (−)
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-25">
                                            Direction berlaku untuk semua artikel dalam transaksi ini.
                                        </small>
                                    </div>
                                </div>--}}
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── ARTICLE CARD ─────────────────────────────────────────────── --}}
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Article Detail</h4>
                    </div>
                    <div class="card-body">

                        {{-- Excel import/export --------------------------------- --}}
                        <form id="frmExcel" name="frmExcel" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-row align-items-center">
                                <div class="col-lg-3 col-md-12">
                                    <div class="form-group">
                                        <input type="file" class="custom-file-input" name="file"
                                            id="file" accept=".xls,.xlsx" required />
                                        <label class="custom-file-label" for="file" id="fileLabel">Choose file</label>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12 mb-1">
                                    <a href="{{ route('stockAdjustment.export.excel') }}" class="btn btn-light">
                                        <i class="fa fa-download"></i> Download Template
                                    </a>
                                    <button type="button" class="btn btn-primary" id="uploadExcel">
                                        <i data-feather="upload" class="align-middle mr-sm-25 mr-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">Upload Excel</span>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <hr style="margin-top:0">

                        {{-- Info box ------------------------------------------------- 
                        <div class="alert alert-info alert-dismissible mb-1 p-75" role="alert">
                            <i data-feather="info" class="mr-25"></i>
                            Input qty adjustment selalu <strong>angka positif</strong>.
                            Arah penambahan atau pengurangan stok ditentukan lewat tombol
                            <strong>Direction</strong> di atas.
                            Stock After yang berwarna merah berarti stok akan menjadi negatif — tidak dapat disimpan.
                            <button type="button" class="close p-75" data-dismiss="alert">&times;</button>
                        </div>--}}

                        {{-- Article rows ---------------------------------------- --}}
                        <div class="container-list-item">
                            <div class="lebar-list-item">
                                @include('stockAdjustment.headerColumn')
                                <div id="article_row"
                                    style="max-height:22rem;overflow-x:hidden;scrollbar-width:thin;">
                                    <input type="text" id="last_row_number" class="d-none" value="0">
                                </div>
                            </div>
                        </div>

                        <hr>

                        {{-- Add row button --}}
                        <div class="d-flex justify-content-between align-items-end mt-75">
                            <button class="btn btn-primary" type="button" id="addNewRow"
                                onclick="add_new_row(); hitungGrandTotal();">
                                <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                                <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                            </button>
                        </div>

                       {{-- Totals --}}
<div class="d-flex justify-content-between align-items-end mt-75">
    <div class="col-md-8">
        <div class="form-row">
            <div class="form-group row col-md-6 mb-03">
                <label for="totalRow" class="col-sm-6 col-form-label titik-dua">Row(s)</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control text-right font-weight-bold"
                        id="totalRow" disabled />
                </div>
            </div>
            <div class="form-group row col-md-6 mb-03">
                <label for="totalActualBalance" class="col-sm-6 col-form-label titik-dua">Total Actual Balance</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control text-right font-weight-bold"
                        id="totalActualBalance" disabled />
                </div>
            </div>
        </div>
    </div>
</div>

                        <hr>

                        {{-- Action buttons --}}
                        <div class="form-row mt-75">
                            <div class="col-md-12">
                                <a href="{{ route('stockAdjustment.index') }}" class="btn btn-light">Back</a>
                                <button class="btn btn-info" type="reset" id="cmdNew" name="cmdNew">New</button>
                                <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>{{-- /.form-row --}}
    </section>

    @endsection

    @section('styles')
    <style>
        textarea { resize: none; }
        .mb-03   { margin-bottom: 0.3rem; }
        label.titik-dua::after { content:":"; position:absolute; right:1px; }
    </style>
    @endsection

    @section('scripts')
    @include('stockAdjustment.addArticle')
    <script type="text/javascript">

        /* ── save ──────────────────────────────────────────────────────────── */
        document.querySelector('#cmdSave').addEventListener('click', () => {
            simpanData($('#oEdit').val());
        });

        /* ── init ──────────────────────────────────────────────────────────── */
        $(document).ready(function () {
            validateFormToast("frmAdd");
            $('#adjDate').val(currentDate);
            isiArticle('trArticle');   // load article list via AJAX

            /* ── location change → reload stock per article ── */
            $('#location').on('change', function () {
                refreshStockOnRows();
            });

            /* ── Excel upload ──
            Rendering baris hasil import sekarang didelegasikan ke
            importRowsFast() (didefinisikan di addArticle.blade.php),
            yang membangun HTML per batch dan insert sekali per batch,
            jauh lebih ringan dibanding proses lama (append satu-satu
            + bind event listener per baris). ── */
            $('#frmExcel').on('submit', function (e) {
                e.preventDefault();
                if (!$('#file').val()) { Swal.fire('Error..', 'File is empty!', 'error'); return; }

                $(".loading-spinner-container").addClass("-show");
                $('#uploadExcel').attr('disabled', 'disabled');

                $.ajax({
                    url: "{{ route('stockAdjustment.import.excel') }}",
                    method: "POST",
                    data: new FormData(this),
                    dataType: "json",
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function (data) {
                        if (data.status == 1 && data.dataDetail.length > 0) {
                            importRowsFast(data.dataDetail);
                        } else if (data.status == 0) {
                            data.message.forEach(m => show_msg(data.title, m, data.alert));
                            Swal.fire('Warning', data.pesan, 'warning');
                            $('#uploadExcel').removeAttr('disabled');
                            $(".loading-spinner-container").removeClass("-show");
                        } else {
                            Swal.fire('Warning', 'Excel file is empty!', 'warning');
                            $('#uploadExcel').removeAttr('disabled');
                            $(".loading-spinner-container").removeClass("-show");
                        }
                    },
                    error: function (xhr) {
                        let err = JSON.parse(xhr.responseText);
                        Swal.fire('Error..', err.message, 'error');
                        $('#uploadExcel').removeAttr('disabled');
                        $(".loading-spinner-container").removeClass("-show");
                    }
                });
            });

            $('#uploadExcel').on('click', function () {
                if (!$('#frmExcel')[0].checkValidity()) {
                    $('#frmExcel').submit();
                } else {
                    $(".loading-spinner-container").addClass("-show");
                    $('#uploadExcel').attr('disabled', 'disabled');
                    $('#frmExcel').submit();
                }
            });

            $('#cmdNew,#cmdCancel').on('click', function () { window.location.reload(); });

            /* label file */
            $('#file').on('change', function () {
                let name = $(this).val().split('\\').pop() || 'Choose file';
                $('#fileLabel').text(name);
            });
        });

        /* ── helpers ───────────────────────────────────────────────────────── */
        function clearFileInput(id) {
            let inp = $('#' + id);
            inp.wrap('<form>').closest('form').get(0).reset();
            inp.unwrap();
            $('#fileLabel').text('Choose file');
        }

        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

    </script>
    @endsection