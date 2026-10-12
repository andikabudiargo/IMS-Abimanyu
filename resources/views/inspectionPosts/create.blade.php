@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="add-index">
    <div class="row">
        <div class="col-6">
            <div class="card">
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('inspectionPost.store') }}" method="post" autocomplete="off">
                        @csrf
                        <div class="row">
                            <div class="col-8">
                                <div class="form-group">
                                    <label for="name">Name</label>
                                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required maxlength="100" autofocus />
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="sequence">Sequence</label>
                                    <input type="number" id="sequence" name="sequence" class="form-control" value="{{ old('sequence', 1) }}" required min="1" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="target_pass_rate">Target Pass Rate (%)</label>
                                    <input type="number" step="0.01" min="0" max="100" id="target_pass_rate" name="target_pass_rate" class="form-control" value="{{ old('target_pass_rate', 100) }}" required />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="target_pass_through_rate">Target Pass Through Rate (%)</label>
                                    <input type="number" step="0.01" min="0" max="100" id="target_pass_through_rate" name="target_pass_through_rate" class="form-control" value="{{ old('target_pass_through_rate', 100) }}" required />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <button class="btn btn-outline-secondary" type="reset" id="cmdCancel">Cancel</button>
                                <button class="btn btn-success" type="button" id="cmdSave">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@section('scripts')
<script type="text/javascript">
    $(document).ready(function(){
        $("#frmAdd").validate({}).settings.ignore = "";
    });
    $("#cmdSave").click(function(){
        $("#frmAdd").submit();
    });
</script>
@endsection
