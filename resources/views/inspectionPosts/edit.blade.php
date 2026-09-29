@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="edit-index">
    <div class="row">
        <div class="col-6">
            <div class="card">
                <div class="card-body">
                    <form id="frmEdit" name="frmEdit" action="{{ route('inspectionPost.update') }}" method="post" autocomplete="off">
                        @csrf
                        <input type="hidden" name="id" value="{{ $post->id }}" />
                        <div class="row">
                            <div class="col-8">
                                <div class="form-group">
                                    <label for="name">Name</label>
                                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $post->name) }}" required maxlength="100" autofocus />
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="sequence">Sequence</label>
                                    <input type="number" id="sequence" name="sequence" class="form-control" value="{{ old('sequence', $post->sequence) }}" required min="1" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="form-label" for="status">Status</label>
                                <select class="select2 form-control" id="status" name="status">
                                    <option value="1" {{ $post->status == '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ $post->status == '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <a href="{{ route('inspectionPost.index') }}" class="btn btn-outline-secondary">Cancel</a>
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
