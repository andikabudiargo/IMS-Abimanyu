@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="show-unifikasiBppu">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Detail {{ $isBps ? 'Unifikasi BPS' : 'Unifikasi BPPU' }} — {{ $typeInfo['title'] }}</h4>
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
          <div class="form-group col-md-2">
            <label class="form-label">COA</label>
            <input type="text" class="form-control text-hitam disabled-el" value="{{ $header->coa_account }}" readonly>
          </div>
          <div class="form-group col-md-6">
            <label class="form-label">COA Description</label>
            <input type="text" class="form-control text-hitam disabled-el" value="{{ $typeInfo['coa_description'] }}" readonly>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-8">
            <label class="form-label">Description</label>
            <input type="text" class="form-control text-hitam disabled-el" value="{{ $header->description }}" disabled>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-8">
            <label class="form-label" for="statement">Excel {{ $isBps ? 'BPS' : 'BPPU' }} Terbaru <small class="text-muted">opsional — isi kalau ada perubahan di Excel</small></label>
            <div class="custom-file">
              <input type="file" class="custom-file-input" id="statement" accept=".xls,.xlsx" />
              <label class="custom-file-label" for="statement" id="statementLabel">Choose file</label>
            </div>
          </div>
        </div>
        <div class="form-row">
          <div class="col-12">
            <button class="btn btn-warning" type="button" id="cmdReconcile">
              <i data-feather="refresh-cw" class="align-middle mr-sm-25 mr-0"></i>
              <span class="align-middle d-sm-inline-block d-none">Rekonsiliasi</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

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
              <th class="text-right">Excel ({{ $isBps ? 'BPS' : 'BPPU' }})</th>
              <th class="text-right">Buku Besar (Akun {{ $header->coa_account }})</th>
              <th class="text-right">Selisih</th>
            </tr>
          </thead>
          <tbody>
            @php
              $selisih = round($header->total_pph - $glMutasi, 2);
              $match = abs($selisih) <= 0.01;
            @endphp
            <tr>
              <th>Total PPh (Mutasi {{ $isBps ? 'Debet' : 'Kredit' }})</th>
              <td class="text-right">{{ number_format($header->total_pph, 2) }}</td>
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

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
      <h4 class="card-title">Detail {{ $isBps ? 'BPS' : 'BPPU' }}</h4>
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
        <table id="unifikasiBppuDetailTable" class="table bankrecon-table">
          <thead class="thead-light"></thead>
        </table>
      </div>
      <div class="form-row mt-1 d-none" id="saveMatchesRow">
        <div class="col-12">
          <button class="btn btn-success" type="button" id="cmdSaveMatches">
            <i data-feather="save" class="align-middle mr-sm-25 mr-0"></i>
            <span class="align-middle d-sm-inline-block d-none">Save (<span class="save-count">0</span>)</span>
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
  const dppCol = {{ $dppCol }};
  let equalNumber = "{{ $header->equal_number }}";
  // Match/Unmatch ditampung dulu, baru disimpan saat klik Save (pola sama dengan Equalisasi PPN).
  let pendingChanges = {}; // detId -> {action: 'match'|'unmatch', voucherNumber?}

  function updateSaveButton() {
    let n = Object.keys(pendingChanges).length;
    $('#saveMatchesRow').toggleClass('d-none', n === 0);
    $('#saveMatchesRow .save-count').text(n);
  }

  function markRowPending(detId, label) {
    let row = $('[data-det-id="' + detId + '"]').closest('tr');
    let tds = row.addClass('table-warning').find('td');
    tds.eq(dppCol + 2).html('<span class="badge badge-warning">PENDING</span>');
    tds.eq(tds.length - 1).html(
      '<a href="javascript:;" class="btn btn-sm btn-outline-secondary" onclick="cancelPending(' + detId + ')">Batal</a>'
      + '<div><small class="text-muted">' + label + '</small></div>'
    );
  }

  function cancelPending(detId) {
    delete pendingChanges[detId];
    updateSaveButton();
    $('#unifikasiBppuDetailTable').DataTable().ajax.reload(null, false);
  }

  function destroyTable() {
    if ($('#unifikasiBppuDetailTable tr').length > 0) {
      let table = $('#unifikasiBppuDetailTable').DataTable();
      table.destroy();
      $('#unifikasiBppuDetailTable tbody > tr').remove();
      $('#unifikasiBppuDetailTable thead > tr').remove();
    }
  }

  const showListDetail = () => {
    destroyTable();
    $(".loading-spinner-container").addClass("-show");
    showDataTables({
      tableId: "unifikasiBppuDetailTable",
      route: "{{ route($route . '.list.detail', ['id' => $id]) }}",
      kolom: {!! $kolomDetail !!},
      arrColPrint: Array.from({ length: dppCol + 4 }, (_, i) => i + 1),
      columnDefs: [
        { width: '4%', targets: 0 },
        { className: 'text-right', targets: [dppCol, dppCol + 1, dppCol + 4] },
        { className: 'text-center', targets: [dppCol + 2, dppCol + 5] },
      ],
      dataSearch: { status: $('#statusFilter').val() },
      initComplete: function () {
        $(".loading-spinner-container").removeClass("-show");
      },
      orderColumn: [[1, 'asc']],
      excelFileName: "{{ $header->equal_number }}"
    });
  };

  function openManualMatch(detId, docNumber, pph) {
    currentDetId = detId;

    Swal.fire({
      title: 'Match Manual',
      html: `
        <p class="text-left">Nomor Pemotongan Excel: <strong>${docNumber}</strong> — PPh <strong>${Number(pph).toLocaleString('id-ID', { minimumFractionDigits: 2 })}</strong></p>
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
          placeholder: 'Ketik nomor voucher / nomor bukti potong...',
          ajax: {
            url: "{{ route($route . '.search.voucher') }}",
            dataType: 'json',
            delay: 300,
            data: function (params) {
              return { equalNumber: equalNumber, search: params.term };
            },
            processResults: function (rows) {
              return {
                results: rows.map(function (r) {
                  let text = r.voucher_number + ' | ' + r.voucher_date + ' | ' + (r.bukti_potong ?? '-')
                    + ' | BB ' + Number(r.gl_amount).toLocaleString('id-ID', { minimumFractionDigits: 2 });
                  return { id: r.voucher_number, text: text };
                })
              };
            }
          }
        });
      },
      preConfirm: () => {
        let voucherNumber = $('#mmVoucher').val();
        if (!voucherNumber) {
          Swal.showValidationMessage('Pilih voucher dulu.');
          return false;
        }
        return { voucherNumber: voucherNumber };
      }
    }).then((result) => {
      if (result.isConfirmed) {
        pendingChanges[currentDetId] = { action: 'match', voucherNumber: result.value.voucherNumber };
        markRowPending(currentDetId, 'Akan di-match ke ' + result.value.voucherNumber);
        updateSaveButton();
      }
    });
  }

  function unmatchRow(detId) {
    pendingChanges[detId] = { action: 'unmatch' };
    markRowPending(detId, 'Akan di-unmatch');
    updateSaveButton();
  }

  $(document).on('click', '#cmdSaveMatches', function () {
    let ids = Object.keys(pendingChanges);
    if (!ids.length) return;

    $(".loading-spinner-container").addClass("-show");
    $('#cmdSaveMatches').attr('disabled', 'disabled');

    let requests = ids.map(function (detId) {
      let c = pendingChanges[detId];
      if (c.action === 'match') {
        return $.ajax({ url: "{{ route($route . '.match.manual') }}", method: "POST", data: { detId: detId, voucherNumber: c.voucherNumber } });
      }
      return $.ajax({ url: "{{ route($route . '.unmatch') }}", method: "POST", data: { detId: detId } });
    });

    $.when.apply($, requests).always(function () {
      pendingChanges = {};
      $(".loading-spinner-container").removeClass("-show");
      $('#cmdSaveMatches').removeAttr('disabled');
      updateSaveButton();
      show_msg('Save', 'Perubahan berhasil disimpan.', 'success');
      showListDetail();
    });
  });

  $('#statement').on('change', function () {
    $('#statementLabel').text($(this).val().split('\\').pop() || 'Choose file');
  });

  // Rekonsiliasi: refresh dari transaksi IMS terbaru (+ Excel baru kalau dipilih).
  // Hasil Match Manual tidak dihapus.
  $('#cmdReconcile').on('click', function () {
    if (Object.keys(pendingChanges).length) {
      Swal.fire('Perhatian', 'Ada perubahan match manual yang belum disimpan. Save atau batalkan dulu.', 'warning');
      return;
    }
    let file = $('#statement')[0].files[0];
    Swal.fire({
      title: 'Rekonsiliasi?',
      text: file
        ? 'Data akan diperbarui dari Excel baru & transaksi IMS terbaru. Hasil Match Manual tetap dipertahankan.'
        : 'Data akan diperbarui dari transaksi IMS terbaru. Hasil Match Manual tetap dipertahankan.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, rekonsiliasi',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (!result.isConfirmed) return;
      let fd = new FormData();
      fd.append('id', "{{ $id }}");
      if (file) fd.append('statement', file);
      $(".loading-spinner-container").addClass("-show");
      $.ajax({
        url: "{{ route($route . '.reconcile') }}",
        method: "POST",
        data: fd,
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,
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
          Swal.fire('Error..', err.message ? JSON.stringify(err.message) : 'Failed to reconcile', 'error');
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
