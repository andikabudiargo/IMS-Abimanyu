@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="show-ppnPkEqualisasi">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Detail Equalisasi PPN — {{ $typeInfo['title'] }}</h4>
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
        <div class="form-row">
          <div class="col-12">
            <button class="btn btn-warning" type="button" id="cmdRecalculate">
              <i data-feather="refresh-cw" class="align-middle mr-sm-25 mr-0"></i>
              <span class="align-middle d-sm-inline-block d-none">Recalculate</span>
            </button>
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
              <th class="text-right">Excel ({{ $typeInfo['file_label'] }})</th>
              <th class="text-right">Buku Besar (Akun {{ $header->coa_account }})</th>
              <th class="text-right">Selisih</th>
            </tr>
          </thead>
          <tbody>
            @php
              $selisih = round($header->total_ppn - $glMutasi, 2);
              $match = abs($selisih) <= 0.01;
            @endphp
            <tr>
              <th>Total PPN {{ $typeInfo['label'] }} ({{ $typeInfo['mutasi_label'] }})</th>
              <td class="text-right">{{ number_format($header->total_ppn, 2) }}</td>
              <td class="text-right">{{ number_format($glMutasi, 2) }}</td>
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

  {{-- Tiap baris Excel di-match langsung ke voucher GL-nya lewat nomor faktur -- PK ke
       invoice_hdr/debit_note_hdr (posting kredit), PM ke ap_invoice (posting debet) --
       kolom Status/Voucher BB/Kredit-Debit BB ada di tabel ini, bukan daftar Buku Besar
       terpisah. Baris yang NOT MATCH bisa di-match manual ke voucher lain lewat tombol di
       kolom action (sama pola dengan Bank Reconciliation). --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
      <h4 class="card-title">Detail {{ $typeInfo['file_label'] }}</h4>
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
        <table id="ppnPkEqualisasiDetailTable" class="table bankrecon-table">
          <thead class="thead-light"></thead>
        </table>
      </div>
      <div class="form-row mt-1 d-none" id="saveMatchesRow">
        <div class="col-12">
          <button class="btn btn-success" type="button" id="cmdSaveMatches">
            <i data-feather="save" class="align-middle mr-sm-25 mr-0"></i>
            <span class="align-middle d-sm-inline-block d-none">Simpan Perubahan (<span class="save-count">0</span>)</span>
          </button>
        </div>
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

  let currentDetId = null;
  let equalNumber = "{{ $header->equal_number }}";
  // Match/Unmatch manual tidak langsung disimpan -- supaya tabel tidak reload tiap klik
  // (bikin baris hilang dari view kalau sedang difilter status). Perubahan ditampung di
  // sini, baru benar-benar disimpan ke DB saat klik tombol "Simpan Perubahan" di bawah tabel.
  // Sama pola dengan bankReconciliation/show.blade.php.
  let pendingChanges = {}; // detId -> {action: 'match'|'unmatch', voucherType?, voucherId?}

  function updateSaveButton() {
    let n = Object.keys(pendingChanges).length;
    $('#saveMatchesRow').toggleClass('d-none', n === 0);
    $('#saveMatchesRow .save-count').text(n);
  }

  function markRowPending(detId, label) {
    let row = $('[data-det-id="' + detId + '"]').closest('tr');
    let tds = row.addClass('table-warning').find('td');
    tds.eq(9).html('<span class="badge badge-warning">PENDING</span>');
    tds.eq(tds.length - 1).html(
      '<a href="javascript:;" class="btn btn-sm btn-outline-secondary" onclick="cancelPending(' + detId + ')">Batal</a>'
      + '<div><small class="text-muted">' + label + '</small></div>'
    );
  }

  function cancelPending(detId) {
    delete pendingChanges[detId];
    updateSaveButton();
    $('#ppnPkEqualisasiDetailTable').DataTable().ajax.reload(null, false);
  }

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
      arrColPrint: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
      columnDefs: [
        { width: '4%', targets: 0 },
        { className: 'text-right', targets: [6, 7, 8, 11] },
        { className: 'text-center', targets: [9, 12] },
      ],
      // dataSearch wajib diisi (walau kosong) -- tanpa ini draw/start/length dari
      // DataTables tidak terkirim dengan benar dan tabel macet di "Processing...".
      dataSearch: { status: $('#statusFilter').val() },
      initComplete: function () {
        $(".loading-spinner-container").removeClass("-show");
      },
      orderColumn: [[1, 'asc']],
      excelFileName: "{{ $header->equal_number }}"
    });
  };

  // Match Manual dipindah ke SweetAlert (bukan modal Bootstrap) -- sama alasan dengan
  // bankReconciliation/show.blade.php: modal Bootstrap "enforce focus" bentrok sama
  // select2 di dalamnya.
  function openManualMatch(detId, docNumber, ppn) {
    currentDetId = detId;

    Swal.fire({
      title: 'Match Manual',
      html: `
        <p class="text-left">Nomor Faktur Excel: <strong>${docNumber}</strong> — PPN <strong>${Number(ppn).toLocaleString('id-ID', { minimumFractionDigits: 2 })}</strong></p>
        <label class="text-left d-block" for="mmVoucher">Pilih Voucher</label>
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
          placeholder: 'Ketik nomor voucher / nomor faktur...',
          ajax: {
            url: "{{ route('ppnPkEqualisasi.search.voucher') }}",
            dataType: 'json',
            delay: 300,
            data: function (params) {
              return { equalNumber: equalNumber, search: params.term };
            },
            processResults: function (rows) {
              return {
                results: rows.map(function (r) {
                  let text = r.voucher_number + ' | ' + r.voucher_date + ' | ' + (r.faktur_pajak ?? '-')
                    + ' | BB ' + Number(r.gl_amount).toLocaleString('id-ID', { minimumFractionDigits: 2 });
                  return { id: r.id, text: text, voucherType: r.voucher_type };
                })
              };
            }
          }
        });
      },
      preConfirm: () => {
        let voucherId = $('#mmVoucher').val();
        if (!voucherId) {
          Swal.showValidationMessage('Pilih voucher dulu.');
          return false;
        }
        let data = $('#mmVoucher').select2('data');
        return { voucherId: voucherId, voucherType: data[0].voucherType };
      }
    }).then((result) => {
      if (result.isConfirmed) {
        pendingChanges[currentDetId] = { action: 'match', voucherType: result.value.voucherType, voucherId: result.value.voucherId };
        markRowPending(currentDetId, 'Akan di-match');
        updateSaveButton();
      }
    });
  }

  function unmatchRow(detId) {
    pendingChanges[detId] = { action: 'unmatch' };
    markRowPending(detId, 'Akan di-unmatch');
    updateSaveButton();
  }

  // Kirim semua perubahan yang ditampung (match + unmatch) sekaligus, baru reload tabel
  // sekali di akhir.
  $(document).on('click', '#cmdSaveMatches', function () {
    let ids = Object.keys(pendingChanges);
    if (!ids.length) return;

    $(".loading-spinner-container").addClass("-show");
    $('#cmdSaveMatches').attr('disabled', 'disabled');

    let requests = ids.map(function (detId) {
      let c = pendingChanges[detId];
      if (c.action === 'match') {
        return $.ajax({ url: "{{ route('ppnPkEqualisasi.match.manual') }}", method: "POST", data: { detId: detId, voucherType: c.voucherType, voucherId: c.voucherId } });
      }
      return $.ajax({ url: "{{ route('ppnPkEqualisasi.unmatch') }}", method: "POST", data: { detId: detId } });
    });

    $.when.apply($, requests).always(function () {
      pendingChanges = {};
      $(".loading-spinner-container").removeClass("-show");
      $('#cmdSaveMatches').removeAttr('disabled');
      updateSaveButton();
      show_msg('Simpan Perubahan', 'Perubahan berhasil disimpan.', 'success');
      showListDetail();
    });
  });

  // Cocokkan ulang semua baris dari nol (hasil Match Manual sebelumnya ikut ditimpa kalau
  // auto-match sekarang dapat hasil berbeda) -- lalu reload halaman supaya section summary
  // & tabel detail sama-sama kebaca ulang dari DB.
  $('#cmdRecalculate').on('click', function () {
    if (Object.keys(pendingChanges).length) {
      Swal.fire('Perhatian', 'Ada perubahan match manual yang belum disimpan. Simpan atau batalkan dulu.', 'warning');
      return;
    }
    Swal.fire({
      title: 'Recalculate?',
      text: 'Semua baris akan dicocokkan ulang dari awal (hasil Match Manual sebelumnya ikut ditimpa kalau hasil otomatisnya beda).',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, cocokkan ulang',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (!result.isConfirmed) return;
      $(".loading-spinner-container").addClass("-show");
      $.ajax({
        url: "{{ route('ppnPkEqualisasi.recalculate') }}",
        method: "POST",
        data: { id: "{{ $id }}" },
        success: function (data) {
          show_msg(data.title, data.message, data.alert);
          if (data.status == 1) {
            setTimeout(function () { window.location.reload(); }, 1200);
          } else {
            $(".loading-spinner-container").removeClass("-show");
          }
        },
        error: function (xhr) {
          $(".loading-spinner-container").removeClass("-show");
          let err = JSON.parse(xhr.responseText);
          Swal.fire('Error..', err.message ? JSON.stringify(err.message) : 'Failed to recalculate', 'error');
        }
      });
    });
  });

  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  $(document).ready(function () {
    showListDetail();
    $('#statusFilter').on('change', showListDetail);
  });

</script>
@endsection
