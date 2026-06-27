@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="edit-form">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: {{ $statusBom }}</h4>
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

                            {{-- BOM Number --}}
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="bomNumber" class="form-label">BOM Number</label>
                                    <input type="text" id="bomNumber" name="bomNumber"
                                           value="{{ $header->bom_code }}"
                                           class="form-control form-control-sm" disabled />
                                </div>
                            </div>

                            {{-- Article Finish Goods + UOM --}}
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="articleCode">Article Finish Goods*</label>
                                    <input type="text" id="articleCode" name="articleCode"
                                           value="{{ old('articleCode', $header->article) }}"
                                           data-article-code="{{ old('articleCode', $header->article_code) }}"
                                           class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-1">
                                    <label for="uomHdr">UOM</label>
                                    <input type="text" id="uomHdr" name="uomHdr"
                                           value="{{ old('uomHdr', $header->uom) }}"
                                           class="form-control" disabled />
                                </div>
                            </div>

                            {{-- ====================== RAW MATERIAL ====================== --}}

                            {{-- MODE TUNGGAL (uom != SET) --}}
                            <div class="form-row" id="rmSingleWrapper">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="articleCodeRm">Article Raw Material*</label>
                                    <select class="select2 form-control" id="articleCodeRm" name="articleCodeRm"
                                            form="frmAdd" required>
                                        <option value=""></option>
                                        {{-- Ambil RM pertama dari bom_rm (single mode) --}}
                                        @php $defaultRm = $headerRmList->first()->article_code ?? null; @endphp
                                        @foreach($articlesRm as $val)
                                            <option value="{{ $val->article_code }}"
                                                    data-detail="{{ $val->article_code }}|{{ $val->article_alternative_code }}|{{ $val->article_desc }}|{{ $val->uom }}"
                                                    {{ $val->article_code == old('articleCodeRm', $defaultRm) ? 'selected' : '' }}>
                                                {{ $val->article_alternative_code }} - {{ $val->article_desc }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-1">
                                    <label for="uomRm">UOM</label>
                                    <input type="text" id="uomRm" name="uomRm"
                                           class="form-control disabled-el" disabled />
                                </div>
                            </div>

                            {{-- MODE MULTI (uom == SET) --}}
                            <div class="form-row d-none mb-1" id="rmMultiWrapper">
                                <div class="col-md-12">
                                    <label class="form-label">Article Raw Material*</label>
                                    <div id="rm_row" style="max-height: 20rem;overflow-x: hidden;overflow-y:auto;scrollbar-width: thin;">
                                        <input type="text" id="last_row_number_rm" class="d-none" value="0">
                                    </div>
                                    <button class="btn btn-primary btn-sm mt-50" type="button"
                                            id="addNewRowRm" onclick="add_new_row_rm();">
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
                                                <select class="form-control" id="articleCodeRmMulti"
                                                        name="articleCodeRmMulti[]">
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
                                                <input type="text" class="form-control disabled-el uomRmMulti"
                                                       disabled placeholder="UOM" />
                                            </div>
                                        </div>
                                        <div class="col-md-1 col-1 text-center">
                                            <a onmouseover="this.style.cursor='pointer'"
                                               onclick="removeRmRow(this);">
                                                <i data-feather="trash-2" class="remove_button feather-24"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Customer & Group --}}
                            <div class="form-row mt-1">
                                <div class="form-group col-md-6">
                                    <label for="customer">Customer</label>
                                    <input type="text" id="customer" name="customer"
                                           value="{{ old('customer', $header->cust_name) }}"
                                           data-customer-code="{{ $header->customer }}"
                                           class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-3 d-none">
                                    <label for="group">Group of material</label>
                                    <input type="text" id="group" name="group"
                                           value="{{ old('group', $header->group) }}"
                                           data-group="{{ $header->group_of_material }}"
                                           class="form-control" disabled />
                                </div>
                            </div>

                            {{-- Part No & Model --}}
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="partNo">Part No</label>
                                    <input type="text" id="partNo" name="partNo"
                                           value="{{ old('partNo', $header->part_no) }}"
                                           class="form-control" />
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="model">Model</label>
                                    <input type="text" id="model" name="model"
                                           value="{{ old('model', $header->model) }}"
                                           class="form-control" />
                                </div>
                            </div>

                            {{-- Notes --}}
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea id="note" name="note" class="form-control"
                                              rows="1">{{ old('note', $header->note) }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- SPRAY BOOTH --}}
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Spray Booth</h4>
                </div>
                <div class="card-body">
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('bom.headerColumnSb')
                            <div class="" id="article_row_sb"
                                 style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                <input type="text" id="last_row_number_sb" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        @if($statusBom == 'NEW')
                            <button class="btn btn-primary btn-prev" type="button"
                                    id="addNewRowSp" onclick="add_new_row_sb();">
                                <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                                <span class="align-middle d-sm-inline-block d-none">Add</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ARTICLE --}}
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body">
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('bom.headerColumn')
                            <div class="" id="article_row"
                                 style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        @if($statusBom == 'NEW')
                            <button class="btn btn-primary btn-prev" type="button"
                                    id="addNewRow" onclick="add_new_row();">
                                <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                                <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                            </button>
                        @endif
                    </div>
                    <hr>

                    {{-- Action Buttons --}}
                    <div class="form-row">
                        <div class="col-md-12">
                            <a href="{{ route('boms.index') }}" class="btn btn-light">Back</a>
                            @if($approveValidate ? $approveValidate[0]->validate : '')
                                <input type="text" id="approveLevel" name="approveLevel"
                                       class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                <input type="text" id="maxLevel" name="maxLevel"
                                       class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                <button class="btn btn-success" type="button"
                                        id="cmdApprove" name="cmdApprove">Approve</button>
                                @if($statusBom == 'NEW')
                                    <button class="btn btn-primary" type="button"
                                            id="cmdUpdate" name="cmdUpdate">Update</button>
                                @endif
                            @else
                                @if(!$approveValidate && $statusBom == 'NEW')
                                    <button class="btn btn-primary" type="button"
                                            id="cmdUpdate" name="cmdUpdate">Update</button>
                                @endif
                            @endif
                        </div>
                    </div>
                    <hr>

                    {{-- Approval History --}}
                    <div class="form-row card-statistics">
                        @foreach($approvalHistory as $val)
                            <div class="statistics-body">
                                <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                    <div class="media">
                                        <div class="avatar {{ $val->status ? 'bg-light-success' : 'bg-light-danger' }} mr-2">
                                            <div class="avatar-content">
                                                <i data-feather="{{ $val->status ? 'check' : 'x' }}"
                                                   class="avatar-icon"></i>
                                            </div>
                                        </div>
                                        <div class="media-body my-auto">
                                            <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}</h4>
                                            <p class="card-text mb-0">{{ $val->status ? $val->name : $val->petugas }}</p>
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
<style>
    textarea { resize: none; }

    @media screen
    and (min-device-width: 1200px)
    and (max-device-width: 1600px)
    and (-webkit-min-device-pixel-ratio: 1) {
        .lebar-list-item { width: 120%; }
        .container-list-item {
            max-width: 100%;
            overflow-x: auto;
            scrollbar-width: thin;
            margin-top: 7px;
        }
    }

    @media only screen and (min-width: 600px) and (max-width: 1200px) {
        .lebar-list-item { width: 200%; }
        .container-list-item {
            max-width: 100%;
            overflow-x: auto;
            scrollbar-width: thin;
            margin-top: 7px;
        }
    }
</style>
@endsection

@section('scripts')
@include('bom.addArticlev2')
@include('bom.addArticleSb')

<script type="text/javascript">

    let currentDate = "{{ $currentDateValue }}";
    const approveBtn = document.querySelector('#cmdApprove');
    let cloneCountRm = 0;
    let isAddingRmRow = false;

    // ====================== LOADING SPINNER ======================
    function checkVariable(obj) {
        if (allSelectsAreFilledjQuery(obj)) {
            clearInterval(timerId);
            $(".loading-spinner-container").removeClass("-show");
        }
    }

    $(document).ready(function () {
        setTimeout(function () {
            $(".loading-spinner-container").addClass("-show");
        }, 500);

        timerId = setInterval(() =>
            checkVariable("#article_row select[name='article_id[]']"), 1000);

        validateForm('frmAdd');
        mask_thousand_digit(numberOfDecimalDigit);

        // ====================== INIT SELECT2 RM SINGLE MODE ======================
        $('#articleCodeRm').select2({
            placeholder: "Choose Raw Material",
            allowClear: true
        });

        // Update UOM saat RM single dipilih
        $('#articleCodeRm').on('change', function () {
            let detailStr = $(this).find(":selected").data("detail");
            let detail = detailStr ? detailStr.split("|") : [];
            $('#uomRm').val(detail[3] || '');
        });

        // ====================== LOAD DETAIL ARTIKEL ======================
        let detail      = {!! $detail !!};
        let sprayBooths = {!! $sprayBooth !!};

        for (let i = 0; i < detail.length; i++) {
            add_new_row_edit(
                detail[i].article_code, detail[i].qty,    detail[i].uom,
                detail[i].uom_con,      detail[i].type_name, detail[i].uom_member,
                detail[i].uoms,         detail[i].factor_qty, detail[i].pos,
                detail[i].tone,         detail[i].brand
            );
        }

        for (let a = 0; a < sprayBooths.length; a++) {
            add_new_row_edit_sb(
                sprayBooths[a].spray_booth, sprayBooths[a].tone,
                sprayBooths[a].tack,        sprayBooths[a].pass_rate,
                sprayBooths[a].pass_thru,   sprayBooths[a].cycle_time
            );
        }

        // ====================== TENTUKAN MODE RM SAAT LOAD ======================
       let uomFg = ($('#uomHdr').val() || '').toUpperCase().trim();
if (uomFg === 'SET') {
    _switchToMultiMode();

    @if(isset($headerRmList) && count($headerRmList) > 0)
        let existingRmList = {!! json_encode($headerRmList) !!};
        console.log('RM List loaded:', existingRmList);

        // Chain: baris berikutnya HANYA dibuat setelah baris sebelumnya selesai
        (function loadRmSequential(i) {
            if (i >= existingRmList.length) return;
            let rm = existingRmList[i];
            add_new_row_rm(rm.article_code, rm.uom_rm ?? null, function () {
                loadRmSequential(i + 1); // dipanggil oleh callback, bukan setTimeout
            });
        })(0);
    @else
        add_new_row_rm();
    @endif
} else {
    _switchToSingleMode();
    setTimeout(() => $('#articleCodeRm').trigger('change'), 100);
}

        // ====================== DISABLE STI CUSTOMER ======================
        if ($('#customer').data("customer-code") == 'STI00001CUST') {
            $('#articleCodeRm').removeAttr('required');
        }
    });

    // ====================== RAW MATERIAL MULTI (SET) LOGIC ======================

    add_new_row_rm = (article, uom, done) => {
    cloneCountRm++;
    let thisCount = cloneCountRm; // ← capture, hindari bug closure

    let $newRow = $($("#new_row_rm").html());
    $newRow.attr('id', 'new_row_rm' + thisCount);

    let $select = $newRow.find('[id="articleCodeRmMulti"]');
    $select.attr('id', 'articleCodeRmMulti' + thisCount)
           .attr('name', 'articleCodeRmMulti[]');

    let $uomInput = $newRow.find('.uomRmMulti');
    $uomInput.attr('id', 'uomRmMulti' + thisCount);

    $("#rm_row").append($newRow);

    $select.select2({ placeholder: "Choose Raw Material", allowClear: true });

    $select.on('change', function () {
        let detailStr = $(this).find(":selected").data("detail");
        let detail = detailStr ? detailStr.split("|") : [];
        $('#uomRmMulti' + thisCount).val(detail[3] || '');
    });

    if (article) {
        let $option = $select.find('option[value="' + article + '"]');
        console.log('Cari RM:', article, '| option ketemu?', $option.length); // ← diagnostik

        if (!$option.length) {
            // Option tidak ada di dropdown (kefilter article_type) → buat manual
            $select.append(new Option(article, article, true, true));
        }
        $select.val(article).trigger('change');
        if (uom) $uomInput.val(uom);
    }

    feather.replace();
    if (typeof done === 'function') setTimeout(done, 50); // lanjut baris berikutnya
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

    function _switchToMultiMode() {
        $("#rmSingleWrapper").addClass('d-none');
        $("#articleCodeRm").removeAttr('required').val('').trigger('change');
        $('#uomRm').val('');
        $("#rmMultiWrapper").removeClass('d-none');
    }

    function _switchToSingleMode() {
        $("#rmMultiWrapper").addClass('d-none');
        $('#rm_row').empty().append(
            '<input type="text" id="last_row_number_rm" class="d-none" value="0">'
        );
        cloneCountRm = 0;
        $("#rmSingleWrapper").removeClass('d-none');
        $("#articleCodeRm").attr('required', 'required');
    }

    // Kumpulkan RM list — dipanggil oleh saveData()
    // BARU — ganti di addArticle.blade.php
function getArticleCodeRmList() {
    let isSetMode = !$('#rmMultiWrapper').hasClass('d-none');
    let list = [];

    if (isSetMode) {
        // Iterasi per baris, tidak bergantung pada name attribute
        $('#rm_row .tanda-baris-rm').each(function () {
            let $select = $(this).find('select');
            let val = $select.val();
            if (val) {
                let detailStr = $select.find('option[value="' + val + '"]').data('detail');
                let detail    = detailStr ? detailStr.split('|') : [];
                list.push({
                    article_code:             val,
                    article_alternative_code: detail[1] || '',
                    article_desc:             detail[2] || ''
                });
            }
        });
    } else {
        let $this = $('#articleCodeRm');
        let val   = $this.val();
        if (val) {
            let detailStr = $this.find(':selected').data('detail');
            let detail    = detailStr ? detailStr.split('|') : [];
            list.push({
                article_code:             val,
                article_alternative_code: detail[1] || '',
                article_desc:             detail[2] || ''
            });
        }
    }

    console.log('getArticleCodeRmList result:', list); // debug, boleh dihapus setelah OK
    return list;
}

    // ====================== APPROVE & UPDATE ======================

    if (approveBtn) {
        approveBtn.addEventListener('click', () => {
            let bomNumber = $('#bomNumber').val();
            approve(bomNumber);
        }, { once: true });
    }

    $("#cmdUpdate").click(function () {
        saveData(true);
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>
@endsection