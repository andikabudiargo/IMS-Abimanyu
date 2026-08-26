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
                        <ul class="list-inline mb-0"><li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li></ul>
                    </div>
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" autocomplete="off">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="scNumber">Number</label> <small class="text-muted">automatic</small>
                                    <input type="text" id="scNumber" name="scNumber" value="{{ $header->sc_number }}" class="form-control disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="scDate">Date*</label>
                                    <input type="text" id="scDate" name="scDate" value="{{ $header->sc_date }}" class="form-control" placeholder="DD-MM-YYYY" required/>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="location">Location*</label>
                                    <select class="select2 form-control" id="location" name="location" required>
                                        <option value=""></option>
                                        @foreach($locations as $val)
                                            <option value="{{ $val->location_code }}" {{ $val->location_code == $header->location_code ? 'selected' : '' }}>{{ $val->location_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="coa">COA*</label>
                                    <select class="select2 form-control" id="coa" name="coa" required>
                                        <option value=""></option>
                                        @foreach($coas as $c)
                                            <option value="{{ $c->account }}" {{ $c->account == $header->account ? 'selected' : '' }}>{{ $c->account }} - {{ $c->description }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes*</label>
                                    <textarea id="note" name="note" class="form-control" rows="1" required>{{ $header->note }}</textarea>
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
                        <button class="btn btn-primary" type="button" id="addNewRow" onclick="add_new_row();hitungGrandTotal();">
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
                            <button class="btn btn-primary" type="button" id="cmdSave">Save</button>
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
    let location = $('#location');

    document.querySelector('#cmdSave').addEventListener('click', () => {
        simpanData(document.getElementById('oEdit').value);
    });

    $(document).ready(function () {
        if (typeof validateFormToast === 'function') validateFormToast("frmAdd");
        $('#scDate').flatpickr({ dateFormat:"d-m-Y", defaultDate: $('#scDate').val() || null, allowInput:true });
        $('#location, #coa').select2({ placeholder:'- Pilih -', allowClear:true, width:'100%' });

        const initLoc = location.val();
        if (initLoc) isiArticleByLocation(initLoc);

        let timerId = setInterval(() => {
            if (dataArticle.length > 0) {
                clearInterval(timerId);
                let detail = {!! json_encode($details) !!};
                detail.forEach(function (d) {
                    add_new_row_edit(d.article_code, d.qty, d.uom, d.uom_member ?? '', d.note ?? '');
                });
            }
        }, 500);

        location.on('change', function () {
            const loc = $(this).val();
            $('#article_row').html('<input type="text" id="last_row_number" class="d-none" value="0">');
            hitungGrandTotal();
            if (loc) isiArticleByLocation(loc);
        });
    });
</script>
@endsection