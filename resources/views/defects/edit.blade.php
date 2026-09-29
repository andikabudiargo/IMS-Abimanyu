@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="edit-index">
    <div class="row">
        <div class="col-8">
            <div class="card">
                <div class="card-body">
                    <form id="frmEdit" name="frmEdit" action="{{ route('defect.update') }}" method="post" autocomplete="off">
                        @csrf
                        <input type="hidden" name="id" value="{{ $defect->id }}" />
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="code">Code</label>
                                    <input type="text" id="code" class="form-control text-uppercase" value="{{ $defect->code }}" disabled />
                                </div>
                            </div>
                            <div class="col-8">
                                <div class="form-group">
                                    <label for="name">Name</label>
                                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $defect->name) }}" required maxlength="150" autofocus />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="category">Category</label>
                                    <input type="text" id="category" name="category" class="form-control" value="{{ old('category', $defect->category) }}" maxlength="50" />
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="severity">Severity*</label>
                                    <select class="select2 form-control" id="severity" name="severity" required>
                                        @foreach (['Minor','Major','Critical'] as $opt)
                                        <option value="{{ $opt }}" {{ $defect->severity == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="disposition">Disposition*</label>
                                    <select class="select2 form-control" id="disposition" name="disposition" required>
                                        @foreach (['Accept','Rework','Reject','Downgrade'] as $opt)
                                        <option value="{{ $opt }}" {{ $defect->disposition == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                        @endforeach
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
                                        <input class="form-check-input" type="checkbox" name="posts[]" value="{{ $post->id }}" id="post{{ $post->id }}" {{ in_array($post->id, $selectedPosts) ? 'checked' : '' }}>
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
                                        <input class="form-check-input" type="checkbox" name="is_repairable" value="1" id="is_repairable" {{ $defect->is_repairable ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_repairable">Yes</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="select2 form-control" id="status" name="status">
                                        <option value="1" {{ $defect->status == '1' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ $defect->status == '0' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <input type="text" id="description" name="description" class="form-control" value="{{ old('description', $defect->description) }}" maxlength="255" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <a href="{{ route('defect.index') }}" class="btn btn-outline-secondary">Cancel</a>
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
        $("#frmEdit").validate({}).settings.ignore = "";
    });
    $("#cmdSave").click(function(){
        $("#frmEdit").submit();
    });
</script>
@endsection
