@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="add-unifikasiBps">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">{{ $isBps ? 'Unifikasi BPS' : 'Unifikasi BPPU' }} — Upload Bukti Potong</h4>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <form id="frmAdd" name="frmAdd" autocomplete="off" enctype="multipart/form-data">
          @csrf
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="equalNumber" class="form-label">
                Equal Number
                <small class="text-muted">automatic</small>
              </label>
              <input type="text" id="equalNumber" name="equalNumber" class="form-control text-hitam disabled-el" disabled>
            </div>
            <div class="form-group col-md-2">
              <label class="form-label" for="type">Type*</label>
              <select class="select2 form-control" id="type" name="type" required>
                <option value=""></option>
                @foreach ($types as $val => $t)
                  <option value="{{ $val }}">{{ $t['title'] }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-1">
              <label class="form-label" for="periode">Periode*</label>
              <select class="select2 form-control" id="periode" name="periode" required>
                <option value=""></option>
                @foreach ($periodes as $val => $label)
                  <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-1">
              <label class="form-label" for="year">Tahun*</label>
              <input type="number" id="year" name="year" class="form-control" value="{{ date('Y') }}" required />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-2">
              <label class="form-label" for="coaAccount">COA</label>
              <input type="text" id="coaAccount" class="form-control text-hitam disabled-el" readonly>
            </div>
            <div class="form-group col-md-6">
              <label class="form-label" for="coaDescription">COA Description</label>
              <input type="text" id="coaDescription" class="form-control text-hitam disabled-el" readonly>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-8">
              <label class="form-label" for="description">Description</label>
              <input type="text" id="description" name="description" class="form-control" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-8">
              <label class="form-label" for="statement">Data {{ $isBps ? 'BPS' : 'BPPU' }} (Excel)*</label>
              <div class="custom-file">
                <input type="file" class="custom-file-input" name="statement" id="statement" accept=".xls,.xlsx" required />
                <label class="custom-file-label" for="statement" id="statementLabel">Choose file</label>
              </div>
            </div>
          </div>
          <div class="form-row mt-1">
            <div class="col-12">
              <button class="btn btn-primary" type="button" id="cmdPreview">
                <i data-feather="upload" class="align-middle mr-sm-25 mr-0"></i>
                <span class="align-middle d-sm-inline-block d-none">Upload &amp; Equalisasi</span>
              </button>
              <a href="{{ route($route . '.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<section id="info-unifikasiBps" class="d-none">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Summary Equalisasi</h4>
      <span id="i-status-badge" class="badge"></span>
    </div>
    <div class="card-body">
      <table class="table table-sm table-bordered bb2-info mb-0">
        <tbody>
          <tr><th width="30%">Total DPP (Excel)</th><td class="text-right" id="i-total-dpp"></td></tr>
          <tr><th>Total PPh (Excel)</th><td class="text-right" id="i-total-pph"></td></tr>
          <tr><th>Mutasi {{ $isBps ? 'Debet' : 'Kredit' }} COA (Buku Besar)</th><td class="text-right" id="i-gl"></td></tr>
          <tr><th>Selisih</th><td class="text-right font-weight-bold" id="i-selisih"></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section id="preview-unifikasiBps" class="d-none">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Preview Hasil Parse &amp; Match</h4>
      <span id="previewSummary" class="badge badge-light-info"></span>
      <div class="form-group mb-0" style="min-width:200px">
        <select id="previewStatusFilter" class="form-control form-control-sm">
          <option value="">Semua Status</option>
          <option value="MATCHED">MATCH</option>
          <option value="UNMATCHED">NOT MATCH</option>
        </select>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive bankrecon-scroll">
        <table class="table table-striped bankrecon-table">
          <thead class="thead-light">
            <tr>
              <th>No</th>
              <th>Masa Pajak</th>
              <th>Nomor Pemotongan</th>
              @if ($isBps)
              <th>Nama Pemotong</th>
              <th>Kode Objek Pajak</th>
              @else
              <th>Status</th>
              <th>Jenis Pajak</th>
              <th>Kode Objek Pajak</th>
              <th>Nama</th>
              @endif
              <th class="text-right">DPP (Rp)</th>
              <th class="text-right">PPh (Rp)</th>
              <th>Match</th>
              <th>Voucher BB</th>
              <th>No Bukti Potong</th>
              <th class="text-right">Nilai BB</th>
            </tr>
          </thead>
          <tbody id="previewRows"></tbody>
        </table>
      </div>
      <div class="form-row mt-1">
        <div class="col-12">
          <button class="btn btn-success" type="button" id="cmdSave">
            <i data-feather="save" class="align-middle mr-sm-25 mr-0"></i>
            <span class="align-middle d-sm-inline-block d-none">Save</span>
          </button>
        </div>
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

  let previewFilePath = null;
  let manualMatches = {}; // Nomor Pemotongan -> voucher_number dipilih manual di preview
  const equalTypes = {!! json_encode($types) !!};
  const isBps = {{ $isBps ? 'true' : 'false' }};

  function resetPreview() {
    previewFilePath = null;
    manualMatches = {};
    $('#preview-unifikasiBps').addClass('d-none');
    $('#info-unifikasiBps').addClass('d-none');
  }

  $(document).ready(function () {
    validateFormToast("frmAdd");

    $('#statement').on('change', function () {
      $('#statementLabel').text($(this).val().split('\\').pop() || 'Choose file');
      resetPreview();
    });

    $('#type').on('change', function () {
      let t = equalTypes[$(this).val()];
      $('#coaAccount').val(t ? t.coa_account : '');
      $('#coaDescription').val(t ? t.coa_description : '');
      resetPreview();
    });
  });

  function fmtNumber(n) {
    return Number(n).toLocaleString('id-ID', { minimumFractionDigits: 2 });
  }

  function esc(s) {
    return $('<div>').text(s ?? '').html();
  }

  // Filter di sisi browser saja (baris disembunyikan, bukan dibuang) supaya pilihan Match
  // Manual di dropdown baris yang sedang tersembunyi tidak hilang.
  function applyPreviewFilter() {
    let status = $('#previewStatusFilter').val();
    $('#previewRows tr').each(function () {
      $(this).toggle(!status || $(this).attr('data-status') === status);
    });
  }
  $(document).on('change', '#previewStatusFilter', applyPreviewFilter);

  function renderPreview(data) {
    let rows = data.rows.map(function (r, i) {
      let statusBadge = r.match_status === 'MATCHED'
        ? '<span class="badge badge-success">MATCH</span>' + (Math.abs(r.pph - r.gl_amount) >= 0.005 ? ' <small class="text-muted">(pembulatan)</small>' : '')
        : '<span class="badge badge-danger">NOT MATCH</span>';
      return `<tr data-status="${r.match_status}">
        <td>${i + 1}</td>
        <td>${esc(r.masa_pajak)}</td>
        <td>${esc(r.doc_number)}</td>
        ${isBps
          ? `<td>${esc(r.nama)}</td><td>${esc(r.kode_objek_pajak)}</td>`
          : `<td>${esc(r.status)}</td><td>${esc(r.jenis_pajak)}</td><td>${esc(r.kode_objek_pajak)}</td><td>${esc(r.nama)}</td>`}
        <td class="text-right">${fmtNumber(r.dpp)}</td>
        <td class="text-right">${fmtNumber(r.pph)}</td>
        <td>${statusBadge}</td>
        <td>${r.match_status === 'MATCHED'
          ? (r.voucher_link ? `<a href="${r.voucher_link}" target="_blank">${esc(r.matched_voucher_number)}</a>` : esc(r.matched_voucher_number))
          : `<select class="form-control form-control-sm manual-voucher-select" data-doc-number="${esc(r.doc_number)}" style="min-width:260px"></select>`
            + (r.matched_voucher_number ? `<small class="text-muted">Auto: ${esc(r.matched_voucher_number)}</small>` : '')}</td>
        <td>${esc(r.voucher_bukti_potong) || '-'}</td>
        <td class="text-right">${r.gl_amount !== null ? fmtNumber(r.gl_amount) : '-'}</td>
      </tr>`;
    }).join('');

    $('#previewRows').html(rows);
    applyPreviewFilter();
    $('#previewSummary').text(`${data.totalRows} baris, ${data.matchedCount} otomatis match`);
    $('#i-total-dpp').text(fmtNumber(data.totalDpp));
    $('#i-total-pph').text(fmtNumber(data.totalPph));
    $('#i-gl').text(fmtNumber(data.glMutasi));
    $('#i-selisih').text(fmtNumber(data.selisih)).toggleClass('text-danger', !data.match).toggleClass('text-success', data.match);
    $('#i-status-badge').text(data.match ? 'MATCH' : 'NOT MATCH').toggleClass('badge-success', data.match).toggleClass('badge-danger', !data.match);
    $('#info-unifikasiBps').removeClass('d-none');
    $('#preview-unifikasiBps').removeClass('d-none');
    document.getElementById('info-unifikasiBps').scrollIntoView({ behavior: 'smooth' });
    initManualVoucherSelects();
  }

  // Dropdown Match Manual untuk baris NOT MATCH di preview (sama pola Bank Reconciliation) --
  // belum ada equal number, jadi search pakai type dari form.
  function initManualVoucherSelects() {
    $('.manual-voucher-select').select2({
      placeholder: 'Ketik nomor voucher / bukti potong...',
      allowClear: true,
      ajax: {
        url: "{{ route($route . '.search.voucher') }}",
        dataType: 'json',
        delay: 300,
        data: function (params) {
          return { type: $('#type').val(), search: params.term };
        },
        processResults: function (rows) {
          return {
            results: rows.map(function (r) {
              return {
                id: r.voucher_number,
                text: r.voucher_number + ' | ' + r.voucher_date + ' | ' + (r.bukti_potong ?? '-') + ' | BB ' + fmtNumber(r.gl_amount),
              };
            })
          };
        }
      }
    }).on('change', function () {
      let doc = $(this).attr('data-doc-number'); // attr, bukan data(): data() mengubah nomor angka jadi number
      let val = $(this).val();
      if (val) {
        manualMatches[doc] = val;
      } else {
        delete manualMatches[doc];
      }
    });
  }

  function ajaxError(btn, fallback) {
    return function (xhr) {
      $(btn).removeAttr('disabled');
      $(".loading-spinner-container").removeClass("-show");
      let err = JSON.parse(xhr.responseText);
      Swal.fire('Error..', err.message ? JSON.stringify(err.message) : fallback, 'error');
    };
  }

  $('#cmdPreview').on('click', function () {
    if (!$('#frmAdd')[0].checkValidity()) {
      $('#frmAdd')[0].reportValidity();
      return;
    }
    $(".loading-spinner-container").addClass("-show");
    $('#cmdPreview').attr('disabled', 'disabled');
    resetPreview();

    $.ajax({
      url: "{{ route($route . '.preview') }}",
      method: "POST",
      data: new FormData(document.getElementById('frmAdd')),
      dataType: "json",
      contentType: false,
      cache: false,
      processData: false,
      success: function (data) {
        $('#cmdPreview').removeAttr('disabled');
        $(".loading-spinner-container").removeClass("-show");
        show_msg(data.title, data.message, data.alert);
        if (data.status == 1) {
          previewFilePath = data.filePath;
          renderPreview(data);
        }
      },
      error: ajaxError('#cmdPreview', 'Failed to preview')
    });
  });

  $('#cmdSave').on('click', function () {
    if (!previewFilePath) {
      Swal.fire('Warning', 'Preview dulu sebelum Save.', 'warning');
      return;
    }
    $(".loading-spinner-container").addClass("-show");
    $('#cmdSave').attr('disabled', 'disabled');

    $.ajax({
      url: "{{ route($route . '.store') }}",
      method: "POST",
      data: {
        type: $('#type').val(),
        periode: $('#periode').val(),
        year: $('#year').val(),
        description: $('#description').val(),
        filePath: previewFilePath,
        manualMatches: manualMatches,
      },
      dataType: "json",
      success: function (data) {
        $('#cmdSave').removeAttr('disabled');
        $(".loading-spinner-container").removeClass("-show");
        show_msg(data.title, data.message, data.alert);
        if (data.status == 1) {
          setTimeout(function () { window.location.href = "{{ route($route . '.index') }}"; }, 1500);
        }
      },
      error: ajaxError('#cmdSave', 'Failed to save')
    });
  });

  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

</script>
@endsection
