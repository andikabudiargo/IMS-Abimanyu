@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="edit-form">
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
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>AFG Number</label>
                                <input type="text" value="{{ $header->fg_code }}" class="form-control" disabled />
                            </div>
                            <div class="form-group col-md-3">
                                <label>Date</label>
                                <input type="text" value="{{ $header->fg_date_fmt }}" class="form-control" disabled />
                            </div>
                            <div class="form-group col-md-4">
                                <label>Location</label>
                                <input type="text" value="{{ $header->spray_booth_name }}" class="form-control" disabled />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-10">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" rows="1" disabled>{{ $header->note }}</textarea>
                            </div>
                        </div>

                        <hr>
                        <h4 class="card-title">Article</h4>
                        <div class="table-responsive main-table">
                            <table class="table table-bordered w-100">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Urutan</th>
                                        <th>Article Code</th>
                                        <th>Article Desc</th>
                                        <th class="text-right">Qty FG</th>
                                        <th class="text-right">Qty OT</th>
                                        <th class="text-right">Qty WIP</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($details as $item)
                                    <tr>
                                        <td>{{ $item->urutan }}</td>
                                        <td>{{ $item->article_alternative_code ?: $item->article_code }}</td>
                                        <td>{{ $item->article_desc }}</td>
                                        <td class="text-right">{{ number_format($item->qty_fg) }}</td>
                                        <td class="text-right">{{ number_format($item->qty_ot) }}</td>
                                        <td class="text-right">{{ number_format($item->qty_wip) }}</td>
                                        <td>{{ $item->note }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <br>
                        <a href="{{ route('production.actualFinishGoods.index') }}" class="btn btn-light">Back</a>

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
    </div>
</section>
@endsection
@section('styles')
<style>
    textarea { resize: none; }
</style>
@endsection
@section('scripts')
<script type="text/javascript">
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });
</script>
@endsection
