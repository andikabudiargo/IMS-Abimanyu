@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: {{ $status }}</h4>
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
                                    <label for="tDnNumber">General DN Number</label>
                                    <small class="text-muted"> automatic</small>
                                    <input type="text" id="tDnNumber" name="tDnNumber"
                                           class="form-control disabled-el"
                                           value="{{ $header->tdn_number }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="deliveryDate">Delivery Date*</label>
                                    <input type="text" id="deliveryDate" name="deliveryDate"
                                           class="form-control" placeholder="DD-MM-YYYY"
                                           value="{{ $header->delivery_date }}" required />
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="form-label">Type</label>
                                   @php
    $typeLabel = [
        'rm' => 'Return RM', 'ot' => 'Return OT', 'box' => 'Box Kosong', 'troli' => 'Troli Kosong',
        'trial' => 'Trial & Sample', 'ms' => 'Material Support', 'cs' => 'Chemical Support',
        'lnb3' => 'Limbah Non B3', 'rig' => 'Return Isi Gas', 'other' => 'Other'
    ];
    $typeBadge = [
        'rm' => 'badge-danger', 'ot' => 'badge-info', 'box' => 'badge-secondary', 'troli' => 'badge-secondary',
        'trial' => 'badge-primary', 'ms' => 'badge-light-primary', 'cs' => 'badge-warning',
        'lnb3' => 'badge-dark', 'rig' => 'badge-info', 'other' => 'badge-warning'
    ];
@endphp
                                    <div class="form-control" style="background:#f8f8f8;">
                                        <span class="badge {{ $typeBadge[$header->dn_type] ?? 'badge-secondary' }}">
                                            {{ $typeLabel[$header->dn_type] ?? strtoupper($header->dn_type) }}
                                        </span>
                                    </div>
                                    {{-- Hidden input agar dnType tetap tersedia di JS --}}
                                    <input type="hidden" id="dnType" value="{{ $header->dn_type }}" />
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-7">
                                    <label class="form-label" for="cust">
                                        @if ($header->dn_type === 'rm') Supplier
                                        @elseif ($header->dn_type === 'ot') Customer
                                        @else Customer / Supplier
                                        @endif *
                                    </label>
                                    <select class="select2 form-control" id="cust" name="cust" required>
                                        <option value="">Pilih</option>
                                        @if ($header->dn_type === 'rm')
                                            @foreach ($suppliers as $val)
                                                <option value="{{ $val->kode }}"
                                                    {{ $val->kode == $header->customer_id ? 'selected' : '' }}>
                                                    {{ $val->kode }} - {{ $val->nama }}
                                                </option>
                                            @endforeach
                                        @elseif ($header->dn_type === 'ot')
                                            @foreach ($customers as $val)
                                                <option value="{{ $val->kode }}"
                                                    {{ $val->kode == $header->customer_id ? 'selected' : '' }}>
                                                    {{ $val->kode }} - {{ $val->nama }}
                                                </option>
                                            @endforeach
                                        @else
                                            @foreach ($allParties as $val)
                                                <option value="{{ $val->kode }}"
                                                    {{ $val->kode == $header->customer_id ? 'selected' : '' }}>
                                                    {{ $val->kode }} - {{ $val->nama }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-7">
                                    <label for="perihal">Perihal*</label>
                                    <input type="text" id="perihal" name="perihal"
                                           class="form-control" value="{{ $header->perihal }}" required />
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-7">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea id="note" name="note" class="form-control" rows="1">{{ $header->note }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Article Detail Card ── --}}
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        Article Detail
                        <span class="badge {{ $typeBadge[$header->dn_type] ?? 'badge-secondary' }} ml-50"
                              style="font-size:0.85rem;">
                            {{ $typeLabel[$header->dn_type] ?? strtoupper($header->dn_type) }}
                        </span>
                    </h4>
                </div>
                <div class="card-body">

                    <div id="articleLoading" style="display:none;" class="text-center py-1">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ml-50 text-muted">Loading articles…</span>
                    </div>

                    <div id="articleSection">
                        <div style="padding-right:10px">
                            @include('dnGeneral.headerColumn')
                        </div>
                        <div id="article_row"
                             style="max-height:30rem;overflow-x:hidden;
                                    scrollbar-width:thin;margin-top:7px;padding-right:10px;">
                        </div>
                        <div class="d-flex justify-content-between align-items-end mt-75">
                            <button class="btn btn-primary" type="button"
                                    id="addNewRow" onclick="add_new_row();">
                                <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                                <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                            </button>
                            <h6>Line: <span id="records">0</span></h6>
                        </div>
                    </div>

                    <hr>
                    <div class="mt-75">
                        <a href="{{ route('dnGeneral.index') }}" class="btn btn-light">Back</a>
                        <button class="btn btn-primary" type="button"
                                id="cmdUpdate" name="cmdUpdate">Update</button>
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
</style>
@endsection

@section('scripts')
@include('dnGeneral.addArticle')
<script type="text/javascript">

// ─── State (mode edit: type & customer sudah fix) ─────────────────────────────
var currentType = '{{ $header->dn_type }}';
var currentCust = '{{ $header->customer_id }}';

// Data existing detail untuk di-load setelah artikel siap
var existingDetails = {!! json_encode($details) !!};
var detailLoaded    = false;

// ─── Load artikel saat halaman buka ──────────────────────────────────────────
$(document).ready(function () {
    validateFormToast('frmAdd');

    // Flatpickr
    let dp = document.querySelector('#deliveryDate');
    if (dp) flatpickr(dp, { dateFormat: 'd-m-Y' });

    // Langsung fetch artikel karena type & customer sudah ada
    fetchArticlesEdit();

    // ── Customer change: reload artikel ──
    $('#cust').on('change', function () {
        currentCust = $(this).val() || null;
        if (currentCust) fetchArticlesEdit();
    });

    // ── Update ───────────────────────────────────────────────────────────────
    $('#cmdUpdate').on('click', function () {
        if (!$('#frmAdd')[0].checkValidity()) {
            $('#frmAdd').submit();
            return;
        }

        var result = collectArticles();   // fungsi di addArticle.blade

        if (result.flag) {
            Swal.fire('Warning', result.pesan, 'warning');
            return;
        }

        $('#cmdUpdate').attr('disabled', 'disabled');
        $('.disabled-el').removeAttr('disabled');

        $.ajax({
            type    : 'POST',
            url     : '{{ route("dnGeneral.update") }}',
            data    : {
                articles    : JSON.stringify(result.articles),
                tDnNumber   : $('#tDnNumber').val(),
                deliveryDate: $('#deliveryDate').val(),
                customerId  : $('#cust').val(),
                perihal     : $('#perihal').val(),
                note        : $('#note').val(),
            },
            dataType: 'json',
            success : function (data) {
                if (data.status == 0) {
                    if (Array.isArray(data.message)) {
                        $.each(data.message, function (i, m) { show_msg(data.title, m, data.alert); });
                    } else {
                        show_msg(data.title, data.message, data.alert);
                    }
                    $('#cmdUpdate').removeAttr('disabled');
                } else {
                    show_msg(data.title, data.message, data.alert);
                }
                $('#tDnNumber').attr('disabled', 'disabled');
            },
            error: function () {
                Swal.fire('Error', 'Terjadi kesalahan saat update.', 'error');
                $('#cmdUpdate').removeAttr('disabled');
            }
        });
    });
});

// ─── Fetch artikel & setelah siap isi existing detail ────────────────────────
function fetchArticlesEdit() {
    $('#articleLoading').show();
    $('#articleSection').hide();
    articleOptions = [];
    dataArticle    = '';

    // Destroy & reset baris dulu
    $('#article_row').empty();
    recordCount();

    $.ajax({
        type    : 'GET',
        url     : '{{ route("dnGeneral.articlesByType") }}',
        data    : { type: currentType, customer: currentCust },
        dataType: 'json',
        success : function (data) {
            setArticleOptions(data || []);   // fungsi di addArticle.blade
            $('#articleLoading').hide();
            $('#articleSection').show();
            feather.replace();

            // Isi baris existing detail (hanya sekali)
            if (!detailLoaded) {
                detailLoaded = true;
                isiDataEdit(existingDetails);
            }
        },
        error: function () {
            $('#articleLoading').hide();
            Swal.fire('Error', 'Gagal memuat artikel.', 'error');
        }
    });
}

// ─── Isi baris dari data DB ───────────────────────────────────────────────────
function isiDataEdit(data) {
    if (!data || data.length === 0) return;
    data.forEach(function (row) {
        // Artikel manual OTHER: kirim article_code='OTHER', article_name=article_desc
        add_new_row_edit(
            row.article_code,
            row.qty,
            row.uom,
            row.article_desc   // deskripsi asli (untuk OTHER = nama manual, untuk normal = desc)
        );
    });
    recordCount();
}

$.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});
</script>
@endsection