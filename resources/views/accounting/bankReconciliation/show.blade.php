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
        <div class="col-md-2"><strong>Saldo Awal</strong><div>{{ $header->saldo_awal !== null ? number_format($header->saldo_awal, 2) : '-' }}</div></div>
        <div class="col-md-2"><strong>Saldo Akhir</strong><div>{{ $header->saldo_akhir !== null ? number_format($header->saldo_akhir, 2) : '-' }}</div></div>
        <div class="col-md-4"><strong>Description</strong><div>{{ $header->description ?: '-' }}</div></div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Detail Mutasi — PDF vs Buku Besar</h4>
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
      dataSearch: {},
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
              return { reconNumber: reconNumber, amount: amount, search: params.term };
            },
            processResults: function (rows) {
              return {
                results: rows.map(function (r) {
                  return {
                    id: r.id,
                    text: r.voucher_number + ' | ' + r.voucher_date + ' | ' + (r.description ?? '')
                      + ' | Dr ' + Number(r.debit).toLocaleString('id-ID', { minimumFractionDigits: 2 })
                      + ' Cr ' + Number(r.credit).toLocaleString('id-ID', { minimumFractionDigits: 2 }),
                  };
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
  });

</script>
@endsection
