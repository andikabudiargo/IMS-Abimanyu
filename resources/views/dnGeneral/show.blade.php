@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status:
                        @php
                            $badgeMap = [
                                '1' => 'badge-primary',
                                '2' => 'badge-info',
                                '3' => 'badge-success',
                                '4' => 'badge-danger',
                            ];
                            $badge = $badgeMap[$dnHdr->status] ?? 'badge-secondary';
                        @endphp
                        <span class="badge {{ $badge }}">{{ $status }}</span>
                    </h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>
                </div>

                <div class="card-content collapse show">
                    <div class="card-body">

                        {{-- Header Info --}}
                        <form autocomplete="off">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>DN Number</label>
                                    <input type="text" class="form-control" value="{{ $dnHdr->tdn_number }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label>Type</label>
                                    @php
                                        $typeMap = [
                                            'rm'    => '<span class="badge badge-danger">RETURN NG RM</span>',
                                            'ot'    => '<span class="badge badge-info">RETURN OT</span>',
                                            'other' => '<span class="badge badge-warning">OTHER</span>',
                                        ];
                                    @endphp
                                    <div class="form-control" style="background:#f8f8f8">
                                        {!! $typeMap[$dnHdr->dn_type] ?? strtoupper($dnHdr->dn_type) !!}
                                    </div>
                                </div>
                                <div class="form-group col-md-2">
                                    <label>Delivery Date</label>
                                    <input type="text" class="form-control" value="{{ $dnHdr->delivery_date }}" disabled />
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label>Customer</label>
                                    <input type="text" class="form-control" value="{{ $dnHdr->customer_name }}" disabled />
                                </div>
                                {{--<div class="form-group col-md-6">
                                    <label>Alamat</label>
                                    <input type="text" class="form-control" value="{{ $dnHdr->alamat_kirim_1 }}" disabled />
                                </div>--}}
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label>Perihal</label>
                                    <textarea class="form-control" rows="2" disabled>{{ $dnHdr->perihal }}</textarea>
                                </div>
                                </div>
                                 <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label>Note</label>
                                    <textarea class="form-control" rows="2" disabled>{{ $dnHdr->note }}</textarea>
                                </div>
                            </div>

                           {{-- <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label>Created By</label>
                                    <input type="text" class="form-control" value="{{ $dnHdr->created_by }}" disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Created At</label>
                                    <input type="text" class="form-control" value="{{ $dnHdr->created_at }}" disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Updated By</label>
                                    <input type="text" class="form-control" value="{{ $dnHdr->updated_by }}" disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Updated At</label>
                                    <input type="text" class="form-control" value="{{ $dnHdr->updated_at }}" disabled />
                                </div>
                            </div>--}}
                        </form>

                        <hr>

                        {{-- Tabel Detail --}}
                        <div class="table-responsive main-table">
                            <table class="table table-bordered w-100" id="tableDetail">
                                <thead class="thead-dark">
                                    <tr>
                                        <th width="5%" class="text-center">No</th>
                                        <th width="15%">Article Code</th>
                                        <th width="45%">Description</th>
                                        <th width="10%" class="text-right">QTY</th>
                                        <th width="10%" class="text-left">UOM</th>
                                        <th width="15%" class="text-right">Stock Saat Kirim</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($details as $item)
                                        <tr>
                                            <td class="text-center">{{ ++$no }}</td>
                                            <td>{{ $item->article_alternative_code }}</td>
                                            <td>{{ $item->article_desc }}</td>
                                            <td class="text-right">{{ number_format($item->qty, 2) }}</td>
                                            <td>{{ $item->uom }}</td>
                                            <td class="text-right">{{ number_format($item->stock_on_send) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Tidak ada data detail.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-right font-weight-bold">Total</td>
                                        <td class="text-right font-weight-bold">
                                            {{ number_format($details->sum('qty'), 2) }}
                                        </td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-end mt-75">
                            <div class="col-md-4">
                                <span>ROW : {{ count($details) }}</span><br>
                                <span>QTY : {{ number_format($details->sum('qty')) }}</span>
                            </div>
                        </div>

                        <hr>

                        <div class="mt-75">
                            <a href="{{ route('dnGeneral.index') }}" class="btn btn-light">
                                <i data-feather="arrow-left"></i> Back
                            </a>
                            <a href="{{ route('dnGeneral.print', ['id' => Crypt::encryptString($dnHdr->id)]) }}"
                               target="_blank" class="btn btn-success">
                                <i data-feather="printer"></i> Print
                            </a>
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

    .main-table table {
        counter-reset: rowNumber;
    }

    #tableDetail th, #tableDetail td {
        padding: 0.4rem 0.6rem;
        vertical-align: middle;
    }
</style>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $('[data-feather]') && feather.replace();
    });
</script>
@endsection