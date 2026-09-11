@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

<section id="add-index">
    <div class="row">
        <div class="col-8">
            <div class="card">
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('location.store') }}" method="post" autocomplete="off">
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="location_code">Location Code <span class="text-danger">*</span></label>
                                    <input type="text" id="location_code" name="location_code" class="form-control text-uppercase"
                                           value="{{ old('location_code') }}" required maxlength="10" autofocus
                                           placeholder="mis. 050" />
                                    <small class="text-muted">Kode tidak bisa diubah lagi setelah disimpan.</small>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label for="location_name">Location Name <span class="text-danger">*</span></label>
                                    <input type="text" id="location_name" name="location_name" class="form-control text-uppercase"
                                           value="{{ old('location_name') }}" required maxlength="150"
                                           placeholder="mis. GUDANG FINISH GOODS" />
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="location_type">Location Type</label>
                                <select class="select2 form-control" id="location_type" name="location_type">
                                    <option value=""></option>
                                    @foreach($locationTypes as $val => $label)
                                        <option value="{{ $val }}" {{ old('location_type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label" for="dept_code">Departemen</label>
                                <select class="select2 form-control" id="dept_code" name="dept_code">
                                    <option value=""></option>
                                    @foreach($depts as $d)
                                        <option value="{{ $d->code }}" {{ old('dept_code') === $d->code ? 'selected' : '' }}>{{ $d->code }} — {{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="article_type">Article Type <small class="text-muted">(bisa pilih lebih dari satu)</small></label>
                                <select class="select2 form-control" id="article_type" name="article_type[]" multiple>
                                    @foreach($articleTypes as $val => $label)
                                        <option value="{{ $val }}" {{ in_array($val, (array) old('article_type', [])) ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- ── Parent / Child (dinamis) ── --}}
                        @php $locationKind = old('location_kind', 'parent'); @endphp
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="form-label d-block">Jenis Lokasi</label>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="kind_parent" name="location_kind" class="custom-control-input" value="parent" {{ $locationKind === 'parent' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="kind_parent">Parent (lokasi mandiri)</label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="kind_child" name="location_kind" class="custom-control-input" value="child" {{ $locationKind === 'child' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="kind_child">Child (sub-lokasi dari lokasi lain)</label>
                                </div>
                            </div>
                        </div>
                        <div class="row" id="parentWrap" style="display:none;">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="parent_location">Parent Location <span class="text-danger">*</span></label>
                                <select class="select2 form-control" id="parent_location" name="parent_location">
                                    <option value=""></option>
                                    @foreach($parents as $p)
                                        <option value="{{ $p->location_code }}" {{ old('parent_location') === $p->location_code ? 'selected' : '' }}>{{ $p->location_code }} — {{ $p->location_name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Stock lokasi child ini akan tercatat di lokasi parent.</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="form-label" for="status">Status</label>
                                <select class="select2 form-control" id="status" name="status">
                                    <option value="1" {{ old('status', '1') === '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ old('status') === '0' ? 'selected' : '' }}>Non-Aktif</option>
                                </select>
                            </div>
                            <div class="form-group col-md-8">
                                <label for="pic">PIC</label>
                                <input type="text" id="pic" name="pic" class="form-control" value="{{ old('pic') }}" maxlength="100" placeholder="opsional" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="note">Keterangan</label>
                                    <textarea id="note" name="note" class="form-control" rows="2" maxlength="500" placeholder="opsional">{{ old('note') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <a href="{{ route('location.index') }}" class="btn btn-outline-secondary" id="cmdCancel">Cancel</a>
                                <button class="btn btn-success" type="button" id="cmdSave" name="cmdSave">Save</button>
                            </div>
                        </div>
                    </form>
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
<script type="text/javascript">
    $(document).ready(function(){
        $('.select2').select2({ width: '100%' });

        function toggleParent() {
            const isChild = $('input[name="location_kind"]:checked').val() === 'child';
            $('#parentWrap').toggle(isChild);
            $('#parent_location').prop('required', isChild);
            if (!isChild) {
                $('#parent_location').val('').trigger('change');
            }
        }
        $('input[name="location_kind"]').on('change', toggleParent);
        toggleParent();

        $("#frmAdd").validate({
            invalidHandler: function(event, validator) {
                let errors = validator.numberOfInvalids();
                if (errors) {
                    let message = errors == 1
                        ? 'You missed 1 field. It has been highlighted'
                        : 'You missed ' + errors + ' fields. They have been highlighted';
                    $("#alert-message .alert-body").html(message);
                    $("#alert-message").show();
                    $("#alert-message").fadeTo(5000, 500).slideUp(500, function(){
                        $("#alert-message").slideUp(500);
                    });
                } else {
                    $("#alert-message").hide();
                }
            }
        }).settings.ignore = "";
    });

    $("#cmdSave").click(function(){
        $("#frmAdd").submit();
    });
</script>
@endsection
