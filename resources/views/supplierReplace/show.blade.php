@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="detail-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $status }}</span></h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Replace Number</label>
                                <input type="text" class="form-control" value="{{ $header->replace_number }}" disabled />
                            </div>
                            <div class="form-group col-md-2">
                                <label>Replace Date</label>
                                <input type="text" class="form-control" value="{{ $header->replace_date }}" disabled />
                            </div>
                            <div class="form-group col-md-3">
                                <label>Return Number</label>
                                <input type="text" class="form-control" value="{{ $header->return_number }}" disabled />
                            </div>
                            <div class="form-group col-md-3">
                                <label>Return Date</label>
                                <input type="text" class="form-control" value="{{ $header->return_date }}" disabled />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Supplier</label>
                                <input type="text" class="form-control" value="{{ $header->supplier_name }}" disabled />
                            </div>
                            <div class="form-group col-md-6">
                                <label>Notes</label>
                                <input type="text" class="form-control" value="{{ $header->note }}" disabled />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Article</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>Article Code</th>
                                    <th>Description</th>
                                    <th class="text-right">Qty Return</th>
                                    <th class="text-right">Qty Replace</th>
                                    <th>UOM</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($details as $i => $d)
                                <tr>
                                    <td>{{ $i+1 }}</td>
                                    <td>{{ $d->article_alternative_code }}</td>
                                    <td>{{ $d->article_desc }}</td>
                                    <td class="text-right">{{ number_format($d->qty_return, 2) }}</td>
                                    <td class="text-right">{{ number_format($d->qty, 2) }}</td>
                                    <td>{{ $d->uom }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <hr>
                    <a href="{{ route('supplierReplace.index') }}" class="btn btn-light">Back</a>
                    <a href="{{ route('supplierReplace.print', ['id'=>Crypt::encryptString($header->id)]) }}" target="_blank" class="btn btn-dark">Print</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection