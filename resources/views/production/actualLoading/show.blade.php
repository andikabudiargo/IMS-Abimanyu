@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="show-form">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusPrd }}</span></h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmShow" name="frmShow" autocomplete="off">
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="prdNumber">Production Number</label>
                                    <input type="text" id="prdNumber" name="prdNumber" value="{{ $header->prod_code }}" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="loadingDate">Loading Date</label>
                                    <input type="text" id="loadingDate" name="loadingDate" value="{{ $header->loading_date_fmt }}" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="sprayBooth">Spray Booth</label>
                                    <input type="text" id="sprayBooth" name="sprayBooth" value="{{ $header->spray_booth_name }}" class="form-control" disabled />
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea id="note" name="note" class="form-control" rows="1" disabled>{{ $header->note }}</textarea>
                                </div>
                            </div>
                        </form>
                        <hr>
                        <h4 class="card-title">Article</h4>
                        <div class="table-responsive main-table">
                            <table class="table table-bordered w-100">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Urutan</th>
                                        <th>Article Code</th>
                                        <th>Article Desc</th>
                                        <th class="text-right">Qty Fresh</th>
                                        <th class="text-right">Qty Repaint</th>
                                        <th class="text-right">Qty Total</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($details as $item)
                                    <tr>
                                        <td>{{ $item->urutan }}</td>
                                        <td>{{ $item->article_alternative_code ?? $item->article_code }}</td>
                                        <td>{{ $item->article_desc }}</td>
                                        <td class="text-right">{{ number_format($item->qty_fresh) }}</td>
                                        <td class="text-right">{{ number_format($item->qty_repaint) }}</td>
                                        <td class="text-right">{{ number_format($item->qty) }}</td>
                                        <td>{{ $item->note }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <br>
                        <a href="{{ route('production.actualLoading.index') }}" class="btn btn-light">Back</a>
                        <a href="{{ route('production.actualLoading.print', ['id'=>Crypt::encryptString($header->id)]) }}" target="_blank" type="button" class="btn btn-primary">
                            <i data-feather="printer"></i>
                            <span>{{ __("Print") }}</span>
                        </a>
                    </div>
                    <hr>
                    <div class="form-row card-statistics">
                        @foreach($approvalHistory as $val)
                            @if($val->status == true)
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-success mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="check" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}</h4>
                                                <p class="card-text mb-0">{{ $val->name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-danger mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="x" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}</h4>
                                                <p class="card-text mb-0">{{ $val->petugas }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@section('styles')
<style>
    textarea {
        resize: none;
    }
</style>
@endsection
@section('scripts')
<script type="text/javascript">
    $(document).ready(function(){
        mask_thousand_digit(numberOfDecimalDigit);
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>
@endsection