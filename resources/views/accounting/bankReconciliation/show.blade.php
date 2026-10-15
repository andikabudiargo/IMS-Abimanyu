@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="show-bankReconciliation">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">{{ $header->recon_number }}</h4>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-2"><strong>Type</strong><div>{{ $header->type }}</div></div>
        <div class="col-md-2"><strong>Periode</strong><div>{{ $header->periode }} / {{ $header->year }}</div></div>
        <div class="col-md-3"><strong>Akun COA</strong><div>{{ $header->coa_account ? $header->coa_account . ' - ' . $header->coa_account_name : '-' }}</div></div>
        <div class="col-md-2"><strong>No. Rekening</strong><div>{{ $header->account_number ?: '-' }}</div></div>
        <div class="col-md-3"><strong>Nama Rekening</strong><div>{{ $header->account_name ?: '-' }}</div></div>
      </div>
      @if ($header->description)
        <div class="row mt-1">
          <div class="col-12"><strong>Description</strong><div>{{ $header->description }}</div></div>
        </div>
      @endif
    </div>
  </div>

  {{-- Rekening Koran vs Buku Besar -- Buku Besar dihitung on the fly dari kas_det/kas_hdr
       saat halaman dibuka, tidak disimpan di DB, supaya selalu sinkron kalau ada posting baru. --}}
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Saldo &amp; Mutasi — Rekening Koran vs Buku Besar</h4>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-bordered bb2-info mb-0">
          <thead class="thead-light">
            <tr>
              <th>Keterangan</th>
              <th class="text-right">Rekening Koran</th>
              <th class="text-right">Buku Besar</th>
              <th class="text-right">Selisih</th>
            </tr>
          </thead>
          <tbody>
            @php
              $glRows = [
                ['Saldo Awal', $header->saldo_awal, $gl['saldo_awal'] ?? null],
                ['Saldo Akhir', $header->saldo_akhir, $gl['saldo_akhir'] ?? null],
                ['Mutasi Debet', $stmtMutasiDebet, $gl['mutasi_debet'] ?? null],
                ['Mutasi Kredit', $stmtMutasiKredit, $gl['mutasi_kredit'] ?? null],
              ];
            @endphp
            @foreach ($glRows as [$label, $koran, $buku])
              @php $selisih = ($koran !== null && $buku !== null) ? round($koran - $buku, 2) : null; @endphp
              <tr>
                <th>{{ $label }}</th>
                <td class="text-right">{{ $koran !== null ? number_format($koran, 2) : '-' }}</td>
                <td class="text-right">{{ $buku !== null ? number_format($buku, 2) : '-' }}</td>
                <td class="text-right {{ $selisih !== null && abs($selisih) > 0.01 ? 'text-danger font-weight-bold' : 'text-success' }}">
                  {{ $selisih !== null ? number_format($selisih, 2) : '-' }}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
      <h4 class="card-title">Detail Mutasi — PDF vs Buku Besar</h4>
      <div class="form-group mb-0" style="min-width:200px">
        <select id="statusFilter" class="form-control form-control-sm">
          <option value="">Semua Status</option>
          <option value="MATCHED">MATCH</option>
          <option value="UNMATCHED">NOT MATCH</option>
        </select>
      </div>
    </div>
    <div class="card-body">
      <div class="card-datatable table-responsive pt-0">
        <table id="bankReconciliationDetailTable" class="table bankrecon-table">
          <thead class="thead-light"></thead>
        </table>
      </div>
    </div>
  </div>
</section>

@endsection

@section('styles')
<style>
  .bankrecon-scroll { max-height: 32rem; overflow-y: auto; }
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

  let currentDetId = null;
  let reconNumber = "{{ $header->recon_number }}";

  function destroyTable() {
    if ($('#bankReconciliationDetailTable tr').length > 0) {
      let table = $('#bankReconciliationDetailTable').DataTable();
      table.destroy();
      $('#bankReconciliationDetailTable tbody > tr').remove();
      $('#bankReconciliationDetailTable thead > tr').remove();
    }
  }

  const showListDetail = () => {
    destroyTable();
    $(".loading-spinner-container").addClass("-show");
    showDataTables({
      tableId: "bankReconciliationDetailTable",
      route: "{{ route('bankReconciliation.list.detail', ['id' => $id]) }}",
      kolom: {!! $kolomDetail !!},
      arrColPrint: [1, 2, 3, 4, 5, 6, 7, 8],
      columnDefs: [
        { width: '4%', targets: 0 },
        { className: 'text-right', targets: [3, 4, 8] },
      ],
      // dataSearch wajib diisi (walau kosong) -- tanpa ini draw/start/length dari
      // DataTables tidak terkirim dengan benar dan tabel macet di "Processing...".
      dataSearch: { status: $('#statusFilter').val() },
      initComplete: function () {
        $(".loading-spinner-container").removeClass("-show");
      },
      orderColumn: [[1, 'asc']],
      excelFileName: reconNumber
    });
  };

  // Match Manual dipindah dari modal Bootstrap ke SweetAlert -- modal Bootstrap punya
  // fitur "enforce focus" yang terus menarik fokus balik ke dirinya, bentrok sama
  // search box select2 (ketikan ke-interupsi, ajax pencarian voucher nggak pernah
  // jalan). SweetAlert tidak punya masalah itu.
  function openManualMatch(detId, stmtDate, amount, type) {
    currentDetId = detId;

    Swal.fire({
      title: 'Match Manual',
      html: `
        <p class="text-left">Baris PDF: <strong>${stmtDate}</strong> — <strong>${Number(amount).toLocaleString('id-ID', { minimumFractionDigits: 2 })}</strong> (<strong>${type}</strong>)</p>
        <label class="text-left d-block" for="mmVoucher">Pilih Voucher Number</label>
        <select id="mmVoucher" class="form-control" style="width:100%"></select>
      `,
      width: 650,
      showCancelButton: true,
      confirmButtonText: 'Match',
      cancelButtonText: 'Batal',
      focusConfirm: false,
      didOpen: () => {
        $('#mmVoucher').select2({
          dropdownParent: $(Swal.getPopup()),
          placeholder: 'Ketik voucher number / keterangan...',
          ajax: {
            url: "{{ route('bankReconciliation.search.voucher') }}",
            dataType: 'json',
            delay: 300,
            data: function (params) {
              return { reconNumber: reconNumber, amount: amount, mutationType: type, search: params.term };
            },
            processResults: function (rows) {
              return {
                results: rows.map(function (r) {
                  let text = r.voucher_number + ' | ' + r.voucher_date + ' | ' + (r.description ?? '')
                    + ' | Dr ' + Number(r.debit).toLocaleString('id-ID', { minimumFractionDigits: 2 })
                    + ' Cr ' + Number(r.credit).toLocaleString('id-ID', { minimumFractionDigits: 2 });
                  // used_count > 0 -> voucher ini sudah dipakai buat baris statement lain di
                  // recon yang sama (jurnal gabungan, many-to-one) -- tampilkan running total.
                  if (r.used_count > 0) {
                    text += ' [sudah match ' + r.used_count + ' baris, total '
                      + Number(r.used_amount).toLocaleString('id-ID', { minimumFractionDigits: 2 }) + ']';
                  }
                  return { id: r.id, text: text };
                })
              };
            }
          }
        });
      },
      preConfirm: () => {
        let kasDetId = $('#mmVoucher').val();
        if (!kasDetId) {
          Swal.showValidationMessage('Pilih voucher dulu.');
          return false;
        }
        return kasDetId;
      }
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: "{{ route('bankReconciliation.match.manual') }}",
          method: "POST",
          data: { detId: currentDetId, kasDetId: result.value },
          success: function (data) {
            show_msg(data.title, data.message, data.alert);
            showListDetail();
          }
        });
      }
    });
  }

  function unmatchRow(detId) {
    $.ajax({
      url: "{{ route('bankReconciliation.unmatch') }}",
      method: "POST",
      data: { detId: detId },
      success: function (data) {
        show_msg(data.title, data.message, data.alert);
        showListDetail();
      }
    });
  }

  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  $(document).ready(function () {
    showListDetail();
    $('#statusFilter').on('change', showListDetail);
  });

</script>
@endsection
