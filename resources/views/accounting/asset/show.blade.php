@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="accountPayable-edit">
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12">
            <div class="card">
                <div class="card-header">
                    {{-- <h4 class="card-title">Status: <span id="statusText">{{ $status }}</span></h4> --}}
                    <h4 class="card-title">Detail</h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>    
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <table class="table table-bordered" id="header" width="100%">
                            <tbody>
                                <tr><td width="25%">Nama Asset</td><td >{{ $header->asset_desc }}</td></tr>
                                <tr><td >Nomor Asset</td><td >{{ $header->asset_number }}</td></tr>
                                <tr><td >Nomor Invoice</td><td >{{ $header->invoice_number }}</td></tr>
                                <tr><td >Harga Beli</td><td >{{ number_format($header->buying_price) }}</td></tr>
                                <tr><td >Qty</td><td >{{ $header->qty }}</td></tr>
                                <tr><td >Departement</td><td >{{ $header->dept_name }}</td></tr>
                                <tr><td >Supplier</td><td >{{ $header->supplier_name }}</td></tr>
                                <tr><td >Akun Asset Tetap</td><td >{{ $header->akun_aset_tetap_name }}</td></tr>
                                <tr><td >Status</td><td >{{ ucfirst($header->status_beli) }}</td></tr>
                            </tbody>
                        </table>
                        <br>
                        @if( $header->penyusutan == '1')
                            <h4 class="card-title">Penyusutan</h4>
                            <table class="table table-bordered" id="header1" width="100%">
                                <tbody>
                                    <tr><td width="20%">Metode Penyusutan</td><td >{{ $header->metode_penyusutan }}</td></tr>
                                    <tr><td >Nilai Penyusutan (Thn)</td><td >{{ $header->nilai_penyusutan }}%</td></tr>
                                    <tr><td >Masa Manfaat</td><td >{{ $header->masa_manfaat }} Tahun</td></tr>
                                    <tr><td >Tanggal Awal Penyusutan</td><td >{{ date("d F Y", strtotime($header->tanggal_awal_penyusutan)) }}</td></tr>
                                    <tr><td >Tanggal Akhir Penyusutan</td><td >{{ date("d F Y", strtotime($header->tanggal_akhir_penyusutan))  }}</td></tr>
                                    <tr><td >Penyusutan</td><td >{{ number_format($header->akumulai_penyusutan) }}</td></tr>
                                    <tr><td >Akun Akumulasi Penyusutan</td><td >{{ $header->akun_akumulasi_penyusutan_name }}</td></tr>
                                </tbody>
                            </table>
                        @endif
                        {{-- <table>
                            <table class="table table-bordered" id="details" width="100%">
                                <tbody>
                                    @foreach ($details as $val )
                                        <tr><td width="20%">Nama Asset</td><td >{{ $header->asset_desc }}</td></tr>
                                        <tr><td width="20%">Nomor Asset</td><td >{{ $header->asset_number }}</td></tr>
                                        <tr><td width="20%">Nomor Invoice</td><td >{{ $header->invoice_number }}</td></tr>
                                        <tr><td width="20%">Harga Beli</td><td >{{ number_format($header->buying_price) }}</td></tr>
                                        <tr><td width="20%">Qty</td><td >{{ $header->qty }}</td></tr>
                                        <tr><td width="20%">Departement</td><td >{{ $header->departement }}</td></tr>
                                        <tr><td width="20%">Supplier</td><td >{{ $header->supplier }}</td></tr>
                                        <tr><td width="20%">Akun Asset Tetap</td><td >{{ $header->akun_aset_tetap }}</td></tr>
                                        <tr><td width="20%">Status</td><td >{{ $header->status_beli }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </table> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if( $header->penyusutan == '1')
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Simulasi Penyusutan</h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>    
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <div class="form-row">
                            <table class="table table-bordered" id="details" width="100%">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Nilai Asset</th>
                                        <th>Penyusutan</th>
                                        <th>Nilai Buku</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($details as $val )
                                        <tr>
                                            <td width="20%">{{ date("d-m-Y", strtotime($val->tanggal_asset)) }}</td>
                                            <td >{{ number_format($val->nilai_asset) }}</td>
                                            <td >{{ number_format($val->penyusutan) }}</td>
                                            <td >{{ number_format($val->nilai_buku) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <br>
                        <div class="form-row">
                            <div class="col-md-12">
                                <a href="{{ route('asset.index') }}" class="btn btn-light">Back</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</section>
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script type="text/javascript">
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>
@endsection
