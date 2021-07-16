@extends('layouts.app')
@section('title', 'Home')
@section('content')
@include('layouts.breadcrumb')
<section id="home">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-transparent">
                <div class="card-header">Dashboard</div>
                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    You are logged in!
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
