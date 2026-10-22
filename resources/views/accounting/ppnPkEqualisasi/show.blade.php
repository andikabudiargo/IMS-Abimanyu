@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="show-ppnPkEqualisasi">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Detail Equalisasi PPN PK</h4>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <div class="form-row">
          <div class="form-group col-md-4">
            <label class="form-label">Equal Number</label>
            <input type="text" class="form-control text-hitam disabled-el" value="{{ $header->equal_number }}" disabled>
          </div>
          <div class="form-group col-md-2">
            <label class="form-label">Periode</label>
            <input type="text" class="form-control text-hitam disabled-el" value="{{ $periodes[(int) $header->periode] ?? $header->periode }}" disabled>
          </div>
          <div class="form-group col-md-2">
            <label class="form-label">Tahun</label>
            <input type="text" class="form-control text-hitam disabled-el" value="{{ $header->year }}" disabled>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-8">
            <label class="form-label">Description</label>
            <input type="text" class="form-control text-hitam disabled-el" value="{{ $header->description }}" disabled>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Total Excel vs Buku Besar -- Buku Besar dihitung on the fly dari kas_det/kas_hdr
       saat halaman dibuka, tidak disimpan di DB, supaya selalu sinkron kalau ada posting baru. --}}
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Summary Equalisasi</h4>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-bordered bb2-info mb-0">
          <thead class="thead-light">
            <tr>
              <th>Keterangan</th>
              <th class="text-right">Excel (Faktur Keluaran)</th>
              <th class="text-right">Buku Besar (Akun {{ $header->coa_account }})</th>
              <th class="text-right">Selisih</th>
            </tr>
          </thead>
          <tbody>
            @php
              $selisih = round($header->total_ppn - $gl['mutasi_kredit'], 2);
              $match = abs($selisih) <= 0.01;
            @endphp
            <tr>
              <th>Total PPN Keluaran (Kredit)</th>
              <td class="text-right">{{ number_format($header->total_ppn, 2) }}</td>
              <td class="text-right">{{ number_format($gl['mutasi_kredit'], 2) }}</td>
              <td class="text-right {{ $match ? 'text-success' : 'text-danger font-weight-bold' }}">{{ number_format($selisih, 2) }}</td>
            </tr>
            <tr>
              <th>Status</th>
              <td colspan="3">
                <div class="badge badge-{{ $match ? 'success' : 'danger' }}">{{ $match ? 'MATCH' : 'NOT MATCH' }}</div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Detail Faktur Pajak Keluaran</h4>
    </div>
    <div class="card-body">
      <div class="card-datatable table-responsive pt-0">
        <table id="ppnPkEqualisasiDetailTable" class="table bankrecon-table">
          <thead class="thead-light"></thead>
        </table>
      </div>
    </div>
  </div>
</section>

@endsection

@section('styles')
<style>
  .bankrecon-table th, .bankrecon-table td { padding: 0.9rem 0.75rem; vertical-align: middle; }
  .bankrecon-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background-color: #f8f8f8;
  }
</style>
@endsection

@section('scripts')
<script type="text/javascript">

  function destroyTable() {
    if ($('#ppnPkEqualisasiDetailTable tr').length > 0) {
      let table = $('#ppnPkEqualisasiDetailTable').DataTable();
      table.destroy();
      $('#ppnPkEqualisasiDetailTable tbody > tr').remove();
      $('#ppnPkEqualisasiDetailTable thead > tr').remove();
    }
  }

  const showListDetail = () => {
    destroyTable();
    $(".loading-spinner-container").addClass("-show");
    showDataTables({
      tableId: "ppnPkEqualisasiDetailTable",
      route: "{{ route('ppnPkEqualisasi.list.detail', ['id' => $id]) }}",
      kolom: {!! $kolomDetail !!},
      arrColPrint: [1, 2, 3, 4, 5, 6, 7, 8],
      columnDefs: [
        { width: '4%', targets: 0 },
        { className: 'text-right', targets: [7, 8] },
      ],
      // dataSearch wajib diisi (walau kosong) -- tanpa ini draw/start/length dari
      // DataTables tidak terkirim dengan benar dan tabel macet di "Processing...".
      dataSearch: {},
      initComplete: function () {
        $(".loading-spinner-container").removeClass("-show");
      },
      orderColumn: [[1, 'asc']],
      excelFileName: "{{ $header->equal_number }}"
    });
  };

  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  $(document).ready(function () {
    showListDetail();
  });

</script>
@endsection
