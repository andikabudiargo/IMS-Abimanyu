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
                    <form id="frmEdit" name="frmEdit" action="{{ route('inspectionRule.update') }}" method="post" autocomplete="off">
                        @csrf
                        <input type="hidden" name="id" value="{{ $rule->id }}" />
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="inspection_post_id">Inspection Post</label>
                                <select class="select2 form-control" id="inspection_post_id" name="inspection_post_id" required>
                                    <option value="">-- Select Post --</option>
                                    @foreach ($posts as $post)
                                    <option value="{{ $post->id }}" {{ old('inspection_post_id', $rule->inspection_post_id) == $post->id ? 'selected' : '' }}>{{ $post->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="article_code">Article Code <small class="text-muted">(blank = default for all articles)</small></label>
                                <input type="text" id="article_code" name="article_code" class="form-control" value="{{ old('article_code', $rule->article_code) }}" maxlength="255" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="target_pass_rate">Target Pass Rate (%)</label>
                                <input type="number" step="0.01" min="0" max="100" id="target_pass_rate" name="target_pass_rate" class="form-control" value="{{ old('target_pass_rate', $rule->target_pass_rate) }}" required />
                            </div>
                            <div class="form-group col-md-6">
                                <label for="target_pass_through_rate">Target Pass Through Rate (%)</label>
                                <input type="number" step="0.01" min="0" max="100" id="target_pass_through_rate" name="target_pass_through_rate" class="form-control" value="{{ old('target_pass_through_rate', $rule->target_pass_through_rate) }}" required />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="note">Note</label>
                                <textarea id="note" name="note" class="form-control" rows="2">{{ old('note', $rule->note) }}</textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="form-label" for="status">Status</label>
                                <select class="select2 form-control" id="status" name="status">
                                    <option value="1" {{ $rule->status == '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ $rule->status == '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <a href="{{ route('inspectionRule.index') }}" class="btn btn-outline-secondary">Cancel</a>
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
