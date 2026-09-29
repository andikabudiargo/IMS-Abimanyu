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
                    <form id="frmAdd" name="frmAdd" action="{{ route('defect.store') }}" method="post" autocomplete="off">
                        @csrf
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="code">Code</label>
                                    <input type="text" id="code" name="code" class="form-control text-uppercase" value="{{ old('code') }}" required maxlength="20" autofocus />
                                </div>
                            </div>
                            <div class="col-8">
                                <div class="form-group">
                                    <label for="name">Name</label>
                                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required maxlength="150" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="category">Category</label>
                                    <input type="text" id="category" name="category" class="form-control" value="{{ old('category') }}" maxlength="50" />
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="severity">Severity*</label>
                                    <select class="select2 form-control" id="severity" name="severity" required>
                                        <option value="Minor">Minor</option>
                                        <option value="Major">Major</option>
                                        <option value="Critical">Critical</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="disposition">Disposition*</label>
                                    <select class="select2 form-control" id="disposition" name="disposition" required>
                                        <option value="Accept">Accept</option>
                                        <option value="Rework" selected>Rework</option>
                                        <option value="Reject">Reject</option>
                                        <option value="Downgrade">Downgrade</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label d-block">Applicable Post*</label>
                                    @foreach ($posts as $post)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="posts[]" value="{{ $post->id }}" id="post{{ $post->id }}">
                                        <label class="form-check-label" for="post{{ $post->id }}">{{ $post->name }}</label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label class="form-label d-block">Repairable</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="is_repairable" value="1" id="is_repairable" checked>
                                        <label class="form-check-label" for="is_repairable">Yes</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <input type="text" id="description" name="description" class="form-control" value="{{ old('description') }}" maxlength="255" />
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
