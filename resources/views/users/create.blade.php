@extends('layouts.app')
@section('title', 'Create User')
@section('content')
@include('layouts.breadcrumb')
<section id="user-create">
    <div class="form-row">
        <div class="col-md-6 col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">@yield('title')</h4>
                </div>
                <div class="card-body">
                    {!! Form::open(array('route' => 'users.store','method'=>'POST','class' => '','id' => 'frmAdd')) !!}                    
                    <div class="form-row">
                        {{ csrf_field() }}
                            <div class="form-group col-md-12">
                                <label>Full Name</label>
                                {!! Form::text('name', null, array('placeholder' => 'full name','class' => 'form-control','autofocus','required')) !!}
                            </div>
                            <div class="form-group col-md-12">
                                <label>Username</label>
                                {!! Form::text('username', null, array('placeholder' => 'username','class' => 'form-control','required','maxlength'=>10)) !!}
                            </div>
                            <div class="form-group col-md-12">
                                <label>Password</label>
                                {!! Form::password('password', array('placeholder' => 'Password','class' => 'form-control')) !!}
                            </div>
                            <div class="form-group col-md-12">
                                <label>Confirm Password</label>
                                {!! Form::password('confirm-password', array('placeholder' => 'Confirm Password','class' => 'form-control')) !!}
                            </div>
                            <div class="form-group col-md-12">
                                <label>Role</label>
                                {!! Form::select('roles[]', $roles,[], array('class' => 'select2 form-control select2-hidden-accessible','multiple'=>'multiple','required')) !!}
                            </div>
                        </div>
                        <a href="{{ URL::previous() }}" class="btn btn-success">Back</a>
                        <button type="button" id="cmdSave" class="btn btn-primary">Save</button>
                    {!! Form::close() !!}
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
        $("#frmAdd").submit(); // Submit the form
    });
</script>
@endsection



