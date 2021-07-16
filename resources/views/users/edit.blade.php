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

                    {!! Form::model($user, ['method' => 'PATCH','route' => ['users.update', $user->id],'class' => 'form form-vertical']) !!}
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label>Full Name</label>
                                {!! Form::text('name', null, array('placeholder' => 'Name','class' => 'form-control','autofocus')) !!}
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Username</label>
                                {!! Form::text('username', null, array('placeholder' => 'username','class' => 'form-control','disabled')) !!}
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <strong>Password:</strong>
                                {!! Form::password('password', array('placeholder' => 'Password','class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <strong>Confirm Password:</strong>
                                {!! Form::password('confirm-password', array('placeholder' => 'Confirm Password','class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <strong>Role:</strong>
                                {!! Form::select('roles[]', $roles,$userRole, array('class' => 'select2 form-control','multiple'=>'multiple')) !!}
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <a href="{{ URL::previous() }}" class="btn btn-danger">
                        <i class="fa fa-chevron-left"></i> Cancel
                    </a>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
</section>
@endsection