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
                                            class="form-control disabled-el" disabled />
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="deliveryDate">Delivery Date*</label>
                                        <input type="text" id="deliveryDate" name="deliveryDate"
                                            class="form-control" placeholder="DD-MM-YYYY" required />
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label class="form-label" for="type">Type*</label>
                                        <select class="select2 form-control" id="type" name="type" required>
                                            <option value="">Choose Type</option>
                                            <option value="rm">Return RM</option>
                                            <option value="ot">Return OT</option>
                                            <option value="box">Box Kosong</option>
                                            <option value="troli">Troli Kosong</option>
                                            <option value="trial">Trial & Sample</option>
                                            <option value="ms">Material Support</option>
                                            <option value="cs">Chemical Support</option>
                                            <option value="lb3">Limbah B3</option>
                                            <option value="lnb3">Limbah Non B3</option>
                                            <option value="rig">Return Isi Gas</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-7">
                                        <label class="form-label" for="cust">
                                            <span id="custLabel">Customer / Supplier</span>*
                                        </label>
                                        <select class="select2 form-control" id="cust" name="cust" required>
                                            <option value="">Choose Customer / Supplier</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-7">
                                        <label for="perihal">Perihal</label>*
                                        <input type="text" id="perihal" name="perihal" class="form-control" required />
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-7">
                                        <label class="form-label" for="note">Notes</label>
                                        <textarea id="note" name="note" class="form-control" rows="1"></textarea>
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
                            <span id="typeBadge" class="badge badge-secondary ml-50"
                                style="display:none;font-size:0.85rem;"></span>
                        </h4>
                    </div>
                    <div class="card-body">

                        <div id="typeWarning" class="alert alert-warning mb-1" role="alert">
                            <i data-feather="alert-circle" class="mr-50"></i>
                            Pilih <strong>Type</strong> terlebih dahulu.
                        </div>

                        <div id="custWarning" class="alert alert-info mb-1" role="alert" style="display:none;">
                            <i data-feather="info" class="mr-50"></i>
                            Pilih <strong>Customer / Supplier</strong> agar artikel dapat difilter.
                        </div>

                        <div id="articleLoading" style="display:none;" class="text-center py-1">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <span class="ml-50 text-muted">Loading articles…</span>
                        </div>

                        <div id="articleSection" style="display:none;">
                            <div style="padding-right:10px">
                                @include("dnGeneral.headerColumn")
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
                                    id="cmdSave" name="cmdSave">Save</button>
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

    // Data third_party dari controller, dikelompokkan per type
    var partyData = {
    rm   : @json($suppliers),
    ot   : @json($customers),
    box  : @json($allParties),
    troli: @json($allParties),
    trial: @json($allParties),
    ms   : @json($allParties),
    cs   : @json($allParties),
    lb3 : @json($allParties),
    lnb3 : @json($allParties),
    rig  : @json($allParties),
    other: @json($allParties)
};

    // ─── State ────────────────────────────────────────────────────────────────────
    var currentType    = null;
    var currentCust    = null;
    var articleOptions = [];

    // ─── Helpers UI ───────────────────────────────────────────────────────────────
    function showWarning(id) {
        $('#typeWarning, #custWarning, #articleLoading, #articleSection').hide();
        if (id) $('#' + id).show();
    }

    function setBadge(type) {
    var label = {
        rm:'Return RM', ot:'Return OT', box:'Box Kosong', troli:'Troli Kosong',
        trial:'Trial & Sample', ms:'Material Support', cs:'Chemical Support',  lb3:'Limbah B3',
        lnb3:'Limbah Non B3', rig:'Return Isi Gas', other:'Other'
    };
    var color = {
        rm:'badge-danger', ot:'badge-info', box:'badge-secondary', troli:'badge-secondary',
        trial:'badge-primary', ms:'badge-light-primary', cs:'badge-warning',  lb3:'badge-dark',
        lnb3:'badge-dark', rig:'badge-info', other:'badge-warning'
    };
    $('#typeBadge')
        .removeClass('badge-danger badge-info badge-warning badge-secondary badge-primary badge-light-primary badge-dark')
        .addClass(color[type] || 'badge-secondary')
        .text(label[type] || '')
        .toggle(!!type);
}

    // Toggle header kolom stok vs deskripsi sesuai type
    function toggleHeaderColumns(type) {
        // Layout kolom seragam untuk semua type (Article | Stok | Qty | UOM).
        // Perbedaan hanya pada baris: UOM span (rm/ot) vs UOM input (other),
        // ditangani saat add_new_row().
        if (typeof applyTypeToRows === 'function') applyTypeToRows(type);
    }

    function resetRows() {
        // Destroy semua select2 dulu sebelum empty
        $('#article_row .article-count, #article_row select[name="articleCode[]"]').each(function() {
            if ($(this).data('select2')) $(this).select2('destroy');
        });
        $('#article_row').empty();
        recordCount();
    }

    // ─── Cek siap fetch ───────────────────────────────────────────────────────────
    function tryLoadArticles() {
        if (!currentType) { showWarning('typeWarning'); setBadge(null); return; }
        setBadge(currentType);
        toggleHeaderColumns(currentType);
        if (!currentCust) { showWarning('custWarning'); return; }
        fetchArticles();
    }

    // ─── Fetch artikel dari server ────────────────────────────────────────────────
    function fetchArticles() {
        resetRows();
        showWarning('articleLoading');
        articleOptions = [];
        dataArticle    = '';

        $.ajax({
            type    : 'GET',
            url     : "{{ route('dnGeneral.articlesByType') }}",
            data    : { type: currentType, customer: currentCust },
            dataType: 'json',
            success : function(data) {
                setArticleOptions(data || []);   // fungsi di addArticle.blade
                showWarning(null);
                $('#articleSection').show();
                feather.replace();

                if (articleOptions.length === 0) {
                    toastr.warning('Tidak ada artikel tersedia untuk pilihan ini.', 'Info');
                }
            },
            error   : function() {
                showWarning('typeWarning');
                Swal.fire('Error', 'Gagal memuat artikel. Silakan coba lagi.', 'error');
            }
        });
    }

    // ─── On Ready ─────────────────────────────────────────────────────────────────
    $(document).ready(function() {
        validateFormToast('frmAdd');
        $('#deliveryDate').val("{{ $currentDate }}");

        // ── Type change: rebuild dropdown customer + reset artikel ──
        $('#type').on('change', function() {
            currentType = $(this).val() || null;

            // Rebuild opsi customer sesuai type
            var list = currentType ? (partyData[currentType] || []) : [];
            var opts = '<option value="">Choose Customer / Supplier</option>';
            $.each(list, function(i, p) {
                opts += '<option value="' + p.kode + '">' + p.kode + ' - ' + p.nama + '</option>';
            });
            $('#cust').html(opts).val('').trigger('change.select2');

            // Update label
            var custLabels = { rm: 'Supplier', ot: 'Customer', other: 'Customer / Supplier' };
            $('#custLabel').text(custLabels[currentType] || 'Customer / Supplier');

            currentCust = null;
            tryLoadArticles();
        });

        // ── Customer change: re-fetch artikel ──
        $('#cust').on('change', function() {
            currentCust = $(this).val() || null;
            if (currentType) tryLoadArticles();
        });

        // ── Save ──────────────────────────────────────────────────────────────────
        $('#cmdSave').on('click', function() {
            if (!$('#frmAdd')[0].checkValidity()) {
                $('#frmAdd').submit();
                return;
            }

            var result = collectArticles();   // fungsi di addArticle.blade

            if (result.flag) {
                Swal.fire('Warning', result.pesan, 'warning');
                return;
            }

            $('#cmdSave').attr('disabled', 'disabled');
            $('.disabled-el').removeAttr('disabled');

            $.ajax({
                type    : 'POST',
                url     : "{{ route('dnGeneral.store') }}",
                data    : {
                    articles    : JSON.stringify(result.articles),
                    deliveryDate: $('#deliveryDate').val(),
                    customerId  : $('#cust').val(),
                    perihal     : $('#perihal').val(),
                    note        : $('#note').val(),
                    dnType      : currentType
                },
                dataType: 'json',
                success : function(data) {
                    if (data.status == 0) {
                        $.each(data.message, function(i, m) { show_msg(data.title, m, data.alert); });
                        $('#cmdSave').removeAttr('disabled');
                    } else {
                        show_msg(data.title, data.message, data.alert);
                        $('#tDnNumber').val(data.tDnNumber);
                        $('#cmdSave, #addNewRow').attr('disabled', 'disabled');
                        $('#type, #cust').attr('disabled', 'disabled');
                    }
                    $('#tDnNumber').attr('disabled', 'disabled');
                },
                error   : function() {
                    Swal.fire('Error', 'Terjadi kesalahan saat menyimpan.', 'error');
                    $('#cmdSave').removeAttr('disabled');
                }
            });
        });
    });

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    </script>
    @endsection