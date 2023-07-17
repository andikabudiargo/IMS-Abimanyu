@extends('layouts.app')
@section('title', 'Edit User')
@section('content')
@include('layouts.breadcrumb')
<section id="user-create">
    <div class="row">
        <div class="col-md-6 col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">@yield('title')</h4>
                </div>
                <div class="card-body">
                    @if (count($errors) > 0)
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <div class="alert-body">
                            <strong>Whoops!</strong> There were some problems with your input.<br><br>
                            <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                            </ul>
                        </div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    @endif

                    {!! Form::model($user, ['method' => 'PATCH','route' => ['users.update', $user->id],'class' => 'form form-vertical','id' => 'frmAdd']) !!}
                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Full Name</label>
                                {!! Form::text('name', null, array('placeholder' => 'Name','class' => 'form-control','autofocus')) !!}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Username</label>
                                {!! Form::text('username', null, array('placeholder' => 'username','class' => 'form-control','disabled')) !!}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <strong>Password:</strong>
                                {!! Form::password('password', array('placeholder' => 'Password','class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <strong>Confirm Password:</strong>
                                {!! Form::password('confirm-password', array('placeholder' => 'Confirm Password','class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="form-group col-md-12">
                            <label>Department</label>
                            {!! Form::select('depts[]', $depts,$userDept, array('class' => 'select2 form-control select2-hidden-accessible','multiple'=>'multiple','required')) !!}
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                {{ Form::select('roles[]', $roles,$userRole, array('class' => 'select2 form-control select2-hidden-accessible','multiple'=>'multiple')) }}
                            </div>
                        </div>
                    </div>
                    <a href="{{ URL::previous() }}" class="btn btn-light">Back</a>
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