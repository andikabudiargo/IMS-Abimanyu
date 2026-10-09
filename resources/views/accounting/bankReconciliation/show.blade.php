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
        <div class="col-md-8"><strong>Description</strong><div>{{ $header->description ?: '-' }}</div></div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Detail Mutasi — PDF vs Buku Besar</h4>
    </div>
    <div class="card-body">
      <div class="card-datatable table-responsive pt-0">
        <table id="bankReconciliationDetailTable" class="table">
          <thead class="thead-light"></thead>
        </table>
      </div>
    </div>
  </div>
</section>

{{-- Modal Match Manual: baris PDF yang NOT MATCH dipasangkan manual lewat dropdown voucher --}}
<div class="modal fade" id="modalMatchManual" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Match Manual</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <p>Baris PDF: <strong id="mmDate"></strong> — <strong id="mmAmount"></strong> (<strong id="mmType"></strong>)</p>
        <label class="form-label" for="mmVoucher">Pilih Voucher Number</label>
        <select id="mmVoucher" class="form-control" style="width:100%"></select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="mmConfirm">Match</button>
      </div>
    </div>
  </div>
</div>

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
      arrColPrint: [1, 2, 3, 4, 5, 6, 7, 8, 9],
      columnDefs: [
        { className: 'text-right', targets: [4, 5, 9] },
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

  // Select2 voucher dropdown: AJAX search ke bankReconciliation.search.voucher,
  // hanya menampilkan kas_det akun yg sama & belum terpakai match lain (lihat controller).
  $('#mmVoucher').select2({
    dropdownParent: $('#modalMatchManual'),
    placeholder: 'Ketik voucher number / keterangan...',
    ajax: {
      url: "{{ route('bankReconciliation.search.voucher') }}",
      dataType: 'json',
      delay: 300,
      data: function (params) {
        return { reconNumber: reconNumber, search: params.term };
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

  function openManualMatch(detId, stmtDate, amount, type) {
    currentDetId = detId;
    $('#mmDate').text(stmtDate);
    $('#mmAmount').text(Number(amount).toLocaleString('id-ID', { minimumFractionDigits: 2 }));
    $('#mmType').text(type);
    $('#mmVoucher').val(null).trigger('change');
    $('#modalMatchManual').modal('show');
  }

  $('#mmConfirm').on('click', function () {
    let kasDetId = $('#mmVoucher').val();
    if (!kasDetId) {
      Swal.fire('Warning', 'Pilih voucher dulu.', 'warning');
      return;
    }
    $.ajax({
      url: "{{ route('bankReconciliation.match.manual') }}",
      method: "POST",
      data: { detId: currentDetId, kasDetId: kasDetId },
      success: function (data) {
        show_msg(data.title, data.message, data.alert);
        $('#modalMatchManual').modal('hide');
        showListDetail();
      }
    });
  });

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
