@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="row">
        <div class="col-6">
            <div class="card">
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('accSub.update',['id'=>Crypt::encryptString($accSub->id)]) }}" method="post" autocomplete="off">
                        @csrf
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="subCode">Code*</label>
                                    <input type="text" id="subCode" class="form-control disabled-el" value="{{ $accSub->sub_code }}" disabled />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label" for="typeCode">Account Type*</label>
                                    <select class="select2 w-100" id="typeCode" name="typeCode" required>
                                        <option value=""></option>
                                        @foreach($types as $val)
                                            <option value="{{$val->code}}" {{ old('typeCode',$accSub->type_code)==$val->code ? 'selected' : '' }}>{{$val->code}} - {{$val->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="desc">Description*</label>
                                    <input type="text" id="desc" name="desc" class="form-control" value="{{ old('desc',$accSub->description) }}" required maxlength="100" autofocus />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <a href="{{ route('accSub.index') }}" class="btn btn-outline-secondary">Back</a>
                                <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
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
        validateFormToast("frmAdd");
    });

    $("#cmdSave").click(function(){
        $('.disabled-el').removeAttr('disabled');
        $("#frmAdd").submit();
    });
</script>
@endsection
