@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: New</h4>
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
                                <div class="form-group col-md-2">
                                    <label for="trNumber">Transfer Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="trNumber" name="trNumber" class="form-control disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="trDate">Transfer Date*</label>
                                    <input type="text" id="trDate" name="trDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ old('trDate') }}" required/>
                                </div>
                                 <div class="form-group col-md-4">
                                    <label for="penerima">Penerima*</label>
                                    <input type="text" id="penerima" name="penerima" class="form-control" placeholder="Nama Penerima" value="{{ old('penerima') }}" required/>
                                </div>
                            </div>
                            <div class="form-row">
                                {{-- Location From: WAJIB dipilih lebih dulu --}}
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="locationFrom">Location From*</label>
                                   {{-- Location From --}}
                                        <select class="select2 form-control" id="locationFrom" name="locationFrom" required>
                                            <option value=""></option>
                                            @foreach($locationsFrom as $val)
                                            <option value="{{ $val->location_code }}">{{ $val->location_name }}</option>
                                            @endforeach
                                        </select>
                                </div>
                                {{-- Location To: id/name DIPERBAIKI (sebelumnya dobel locationFrom) --}}
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="locationTo">Location To*</label>
                                    {{-- Location To --}}
                                        <select class="select2 form-control" id="locationTo" name="locationTo" required disabled>
                                            <option value=""></option>
                                            @foreach($locationsTo as $val)
                                            <option value="{{ $val->location_code }}">{{ $val->location_name }}</option>
                                            @endforeach
                                        </select>
                                </div>
                            </div>

                            <div class="form-row d-none">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="noteSelect">Notes</label>
                                    <select class="select2 form-control" id="noteSelect" name="noteSelect">
                                        <option value=""></option>
                                        <option value="WOS SHIFT A" {{ old('noteSelect')== 'WOS SHIFT A' ? 'selected' : '' }} >WOS SHIFT A</option>
                                        <option value="WOS SHIFT B" {{ old('noteSelect')== 'WOS SHIFT A' ? 'selected' : '' }}>WOS SHIFT B</option>
                                        <option value="WOS BOOTH WERATE" {{ old('noteSelect')== 'WOS BOOTH WERATE' ? 'selected' : '' }}>WOS BOOTH WERATE</option>
                                        <option value="WOS SHIFT 2" {{ old('noteSelect')== 'WOS SHIFT 2' ? 'selected' : '' }}>WOS SHIFT 2</option>
                                        <option value="WOSH BOOTH 3-B" {{ old('noteSelect')== 'WOSH BOOTH 3-B' ? 'selected' : '' }}>WOSH BOOTH 3-B</option>
                                        <option value="Consumable SHIFT A" {{ old('noteSelect')== 'Consumable SHIFT A' ? 'selected' : '' }}>Consumable SHIFT A</option>
                                        <option value="Consumable SHIFT B" {{ old('noteSelect')== 'Consumable SHIFT B' ? 'selected' : '' }}>Consumable SHIFT B</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" ></textarea>
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
                    <hr>
                    {{-- Peringatan ketika Location From belum dipilih --}}
                    <div id="articleLockMsg" class="alert alert-warning">
                        <i data-feather="alert-triangle" class="align-middle mr-50"></i>
                        Silakan pilih <b>Location From</b> terlebih dahulu sebelum menambahkan artikel.
                    </div>

                    <form id="frmExcel" name="frmExcel" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-row">
                            <div class="col-lg-3 col-md-12">
                                <div class="form-group">
                                    <div>
                                        <input type="file" class="custom-file-input" name="file" id="file" required disabled/>
                                        <label class="custom-file-label" for="file" id="fileLabel">Choose file</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <a href="{{ route('transferOut.export.excel') }}" class="btn btn-light"><i class="fa fa-download"></i> Downlod Template</a>
                                <button type="button" class="btn btn-primary" id="uploadExcel" disabled>
                                    <i data-feather="upload" class="align-middle mr-sm-25 mr-0"></i>
                                    <span class="align-middle d-sm-inline-block d-none" >Upload Excel</span>
                                </button>
                            </div>
                        </div>
                    </form>
                    <hr style="margin-top: 0px;">
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('transfer.transferStock.headerColumn')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" disabled onclick="add_new_row();hitungGrandTotal();">
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
                            <a href="{{ route('transferStock.index') }}" class="btn btn-light">Back</a>
                            <button class="btn btn-info" type="reset" id="cmdNew" name="cmdCancel" data-trType="TROUT">New</button>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave" data-trType="TROUT" disabled>Save</button>
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
</style>
@endsection
@section('scripts')
@include('transfer.transferStock.addArticle')
<script type="text/javascript">
    let objToType   = $('#toType');
    let objTsoCode  = $('#tsoCode');
    let objTsoBox   = $('#tsoBox');
    let objNote     = $('#note');
    let locationFrom = $('#locationFrom');
    let locationTo   = $('#locationTo');

    /**
     * Map stok per artikel di Location From terpilih.
     * Diisi oleh isiArticleByLocation() -> dipakai untuk validasi qty.
     * { "ARTICLE_CODE": { qty: 10, uom: "PCS" }, ... }
     */
    let stockByArticle = {};

    document.querySelector('#cmdSave').addEventListener('click', () => {
        let oEdit = document.getElementById('oEdit');
        simpanData(oEdit.value);
    });

    // ---- Aktif/nonaktifkan bagian artikel sesuai Location From ----
    function toggleArticleSection(enable){
        const disabled = !enable;
        $('#addNewRow').prop('disabled', disabled);
        $('#uploadExcel').prop('disabled', disabled);
        $('#file').prop('disabled', disabled);
        $('#cmdSave').prop('disabled', disabled);
        $('#articleLockMsg').toggleClass('d-none', enable);
    }

    function resetArticleRows(){
        $('#article_row').html('<input type="text" id="last_row_number" class="d-none" value="0">');
        if (typeof hitungGrandTotal === 'function') hitungGrandTotal();
        if (typeof dataArticle !== 'undefined') dataArticle = [];
        stockByArticle = {};
    }

    $(document).ready(function () {


locationTo.on('change', function() {
    checkAndSetBoothFlag($(this).val());
});
        validateFormToast("frmAdd");
        $('#trDate').val(currentDate);
        objTsoBox.hide();

        // ---- Inisialisasi select2 untuk Location From & To ----
        $('#locationFrom, #locationTo').select2({
            placeholder: '- Pilih Location -',
            allowClear: true,
            width: '100%'
        });

        const locationToOptions = locationTo.html();

        // Kondisi awal: artikel terkunci sampai Location From dipilih
        toggleArticleSection(false);

        locationFrom.on('change', function () {
    const loc = $(this).val();

    resetArticleRows();

    locationTo.html(locationToOptions);
    if (loc) {
        locationTo.find('option[value="' + loc + '"]').prop('disabled', true);
    }

    locationTo.val('').prop('disabled', !loc).trigger('change');

    checkAndSetFromRmFlag(loc);   // ← tambahan: cek tipe Location From

    if (loc) {
        isiArticleByLocation('trArticleLocation', loc);
        toggleArticleSection(true);
    } else {
        toggleArticleSection(false);
    }
});

        // ---- Validasi qty <= stock (delegated, lihat catatan class) ----
        $(document).on('input change', '.qty-input', function () {
            const row  = $(this).closest('.article-item');
            const code = row.find('.article-code-input').val();
            const stock = (stockByArticle[code] && stockByArticle[code].qty) ? parseFloat(stockByArticle[code].qty) : 0;
            let val = parseFloat($(this).val()) || 0;

            if (code && val > stock) {
                $(this).val(stock);
                show_msg('Warning', 'Qty transfer melebihi stock tersedia (' + stock + ') di gudang ini.', 'warning');
            }
            if (typeof hitungGrandTotal === 'function') hitungGrandTotal();
        });

        // ---- Excel import ----
        $('#frmExcel').on('submit', function (event) {
            $('#message').html('');
            event.preventDefault();

            if (!locationFrom.val()) {
                Swal.fire('Error..', 'Pilih Location From terlebih dahulu !!', 'error');
                return;
            }

            if ($('#file').val()) {
                $.ajax({
                    url: "{{ route('transferOut.import.excel') }}",
                    method: "POST",
                    data: new FormData(this),
                    dataType: "json",
                    contentType: false,
                    cache: false,
                    processData: false,
                    beforeSend: function () {
                        $('#uploadExcel').attr('disabled', 'disabled');
                    },
                    success: function (data) {
                        if (data.status == 1) {
                            Swal.fire({
                                title: "Proses validasi...",
                                html: '0/0 Loaded',
                                icon: "warning",
                                showConfirmButton: false,
                                didOpen: () => {
                                    Swal.showLoading();

                                    if (data.dataDetail.length > 0) {
                                        let jumlahData = data.dataDetail.length;
                                        const dataDetail = data.dataDetail.reverse();
                                        Swal.getHtmlContainer().innerHTML = `<b> 0/${jumlahData} </b> Loaded`;

                                        let timerId = setInterval(() => checkVariable(), 1000);
                                        function checkVariable() {
                                            if (dataArticle.length > 0) {
                                                clearInterval(timerId);
                                                for (let i = jumlahData - 1; i >= 0; i--) {
                                                    setTimeout(() => {
                                                        if (Swal.isVisible()) {
                                                            if (dataDetail[i].article_code) {
                                                                add_new_row_edit(dataDetail[i].article_code, dataDetail[i].qty, dataDetail[i].uom, dataDetail[i].uom_member, '', dataDetail[i].location_code);
                                                                Swal.getHtmlContainer().innerHTML = `<b> ${jumlahData - i}/${jumlahData} </b> Loaded`;
                                                            }
                                                            if (i === 0) {
                                                                $("#uploadExcel").removeAttr('disabled');
                                                                show_msg(data.title, data.message, data.alert);
                                                                $(".loading-spinner-container").removeClass("-show");
                                                                swal.close();
                                                                clearFileInput('file');
                                                            }
                                                        }
                                                    }, (jumlahData - i) * 1000);
                                                }
                                            }
                                        }
                                    } else {
                                        swal.fire("warning", "Excel file is empty... !!", "warning");
                                        $("#uploadExcel").removeAttr('disabled');
                                        $(".loading-spinner-container").removeClass("-show");
                                    }
                                },
                            })
                        }

                        if (data.status == 0) {
                            for (let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $("#uploadExcel").removeAttr('disabled');
                            swal.fire("warning", data.pesan, "warning");
                            $(".loading-spinner-container").removeClass("-show");
                        }
                    },
                    error: function (xhr, status, error) {
                        let err = JSON.parse(xhr.responseText);
                        $("#uploadExcel").removeAttr('disabled');
                        Swal.fire('Error..', err.message, 'error');
                        $(".loading-spinner-container").removeClass("-show");
                    }
                })
            } else {
                Swal.fire('Error..', 'File is empty !!', 'error');
            }
        });

        function clearFileInput(inputId) {
            let input = $('#' + inputId);
            input.wrap('<form>').closest('form').get(0).reset();
            input.unwrap();
            $('#fileLabel').text('Choose file');
        }
    });

    objToType.change(function (e) {
        let toType = $(this).val();
        objTsoBox.hide();
        if (toType === 'prd') {
            objTsoBox.show();
            changeSelect({
                dependent: 'wos_list',
                obj: 'tsoCode',
                url: "{{ route('dynamic.dependent') }}"
            });
        }
    });

    objTsoCode.change(function (e) {
        let tsoCode = $(this).val();
        if (tsoCode) {
            $.ajax({
                type: "GET",
                url: "{{ route('transferOut.article.tso') }}",
                data: { tsoCode: tsoCode },
                dataType: "json",
                success: function (data) {
                    if (data) {
                        for (let i = 0; i < data.length; i++) {
                            add_new_row_edit(data[i].article_code, data[i].grand_total, data[i].uom, data[i].uom_member, '');
                        }
                    }
                },
                error: function (error) { console.log(error); }
            });
        }
    });
</script>
@endsection