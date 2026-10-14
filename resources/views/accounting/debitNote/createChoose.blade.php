@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Debit Note ditujukan ke?</h4>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="col-md-4 mb-2">
                    <a href="{{ route('debitNote.create', ['type' => 'cust']) }}" class="btn btn-primary btn-block py-2">
                        <i data-feather="user" class="align-middle mr-50"></i>
                        Customer
                    </a>
                </div>
                <div class="col-md-4 mb-2">
                    <a href="{{ route('debitNote.create', ['type' => 'supp']) }}" class="btn btn-outline-primary btn-block py-2">
                        <i data-feather="truck" class="align-middle mr-50"></i>
                        Supplier
                    </a>
                </div>
            </div>
            <a href="{{ route('debitNote.index') }}" class="btn btn-light mt-1">Back</a>
        </div>
    </div>
</section>
@endsection
