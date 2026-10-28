@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="add-ppnPkEqualisasi">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Equalisasi PPN — Upload Faktur Pajak</h4>
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
            <div class="form-group col-md-8">
              <label class="form-label" for="description">Description</label>
              <input type="text" id="description" name="description" class="form-control" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-8">
              <label class="form-label" for="statement" id="statementFieldLabel">Data Faktur Pajak (Excel)*</label>
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
              <a href="{{ route('ppnPkEqualisasi.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

{{-- Ringkasan Excel vs Buku Besar -- muncul begitu preview berhasil, sebelum tabel detail --}}
<section id="info-ppnPkEqualisasi" class="d-none">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Summary Equalisasi</h4>
      <span id="i-status-badge" class="badge"></span>
    </div>
    <div class="card-body">
      <table class="table table-sm table-bordered bb2-info mb-0">
        <tbody>
          <tr><th width="30%">Total DPP (Excel)</th><td class="text-right" id="i-total-dpp"></td></tr>
          <tr><th>Total PPN (Excel)</th><td class="text-right" id="i-total-ppn"></td></tr>
          <tr><th id="i-gl-label">Mutasi Buku Besar</th><td class="text-right" id="i-gl-kredit"></td></tr>
          <tr><th>Selisih</th><td class="text-right font-weight-bold" id="i-selisih"></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

{{-- Hasil preview (parse + match per baris ke voucher penerbitnya lewat nomor faktur --
     Invoice/Debit Note untuk PK, AP Invoice untuk PM -- lalu ke posting COA PPN punya
     voucher itu) -- belum tersimpan sampai user klik Save manual. --}}
<section id="preview-ppnPkEqualisasi" class="d-none">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Preview Hasil Parse &amp; Match</h4>
      <span id="previewSummary" class="badge badge-light-info"></span>
    </div>
    <div class="card-body">
      <div class="table-responsive bankrecon-scroll">
        <table class="table table-striped bankrecon-table">
          <thead class="thead-light">
            <tr>
              <th>No</th>
              <th>Nomor Faktur</th>
              <th>Nama</th>
              <th>Tanggal</th>
              <th class="text-right">DPP</th>
              <th class="text-right">PPN (Excel)</th>
              <th>Status</th>
              <th id="previewVoucherLabel">Voucher BB</th>
              <th class="text-right" id="previewGlLabel">Kredit BB</th>
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
  let manualMatches = {}; // Nomor Faktur -> "TIPE|id voucher" dipilih manual di preview
  const equalTypes = {!! json_encode($types) !!};

  $(document).ready(function () {
    validateFormToast("frmAdd");

    $('#statement').on('change', function () {
      let name = $(this).val().split('\\').pop() || 'Choose file';
      $('#statementLabel').text(name);
      // Ganti file -> preview lama (kalau ada) jadi tidak valid lagi.
      previewFilePath = null;
      manualMatches = {};
      $('#preview-ppnPkEqualisasi').addClass('d-none');
      $('#info-ppnPkEqualisasi').addClass('d-none');
    });

    $('#type').on('change', function () {
      let t = equalTypes[$(this).val()];
      $('#statementFieldLabel').text(t ? `Data ${t.file_label} (Excel)*` : 'Data Faktur Pajak (Excel)*');
      $('#i-gl-label').text(t ? `${t.mutasi_label} COA (Buku Besar)` : 'Mutasi Buku Besar');
      $('#previewVoucherLabel').text('Voucher BB');
      $('#previewGlLabel').text(t ? t.gl_label : 'Kredit BB');
    });
  });

  function fmtNumber(n) {
    return Number(n).toLocaleString('id-ID', { minimumFractionDigits: 2 });
  }

  function renderPreview(data) {
    let rows = data.rows.map(function (r, i) {
      let statusBadge = r.match_status === 'MATCHED'
        ? '<span class="badge badge-success">MATCH</span>'
        : '<span class="badge badge-danger">NOT MATCH</span>';
      let voucherCell = r.match_status !== 'MATCHED'
        ? `<select class="form-control form-control-sm manual-voucher-select" data-doc-number="${$('<div>').text(r.doc_number).html()}" style="min-width:260px"></select>`
          + (r.matched_voucher_number ? `<small class="text-muted">Auto: ${r.matched_voucher_number}</small>` : '')
        : r.matched_voucher_number
        ? (r.voucher_link
            ? `<a href="${r.voucher_link}" target="_blank">${r.matched_voucher_number}</a> (${r.matched_voucher_type})`
            : `${r.matched_voucher_number} (${r.matched_voucher_type})`)
        : '-';
      return `<tr>
        <td>${i + 1}</td>
        <td>${r.doc_number}</td>
        <td>${r.nama}</td>
        <td>${r.tanggal}</td>
        <td class="text-right">${fmtNumber(r.dpp)}</td>
        <td class="text-right">${fmtNumber(r.ppn)}</td>
        <td>${statusBadge}</td>
        <td>${voucherCell}</td>
        <td class="text-right">${r.gl_credit !== null ? fmtNumber(r.gl_credit) : '-'}</td>
      </tr>`;
    }).join('');

    $('#previewRows').html(rows);
    $('#previewSummary').text(`${data.totalRows} baris, ${data.matchedCount} otomatis match`);

    $('#i-total-dpp').text(fmtNumber(data.totalDpp));
    $('#i-total-ppn').text(fmtNumber(data.totalPpn));
    $('#i-gl-kredit').text(fmtNumber(data.glMutasiKredit));
    $('#i-selisih')
      .text(fmtNumber(data.selisih))
      .toggleClass('text-danger', !data.match)
      .toggleClass('text-success', data.match);
    $('#i-status-badge')
      .text(data.match ? 'MATCH' : 'NOT MATCH')
      .toggleClass('badge-success', data.match)
      .toggleClass('badge-danger', !data.match);
    $('#info-ppnPkEqualisasi').removeClass('d-none');

    $('#preview-ppnPkEqualisasi').removeClass('d-none');
    document.getElementById('info-ppnPkEqualisasi').scrollIntoView({ behavior: 'smooth' });
    initManualVoucherSelects();
  }

  // Dropdown Match Manual untuk baris NOT MATCH di preview (sama pola Bank Reconciliation) --
  // belum ada equal number, jadi search pakai type dari form.
  function initManualVoucherSelects() {
    $('.manual-voucher-select').select2({
      placeholder: 'Ketik nomor voucher / nomor faktur...',
      allowClear: true,
      ajax: {
        url: "{{ route('ppnPkEqualisasi.search.voucher') }}",
        dataType: 'json',
        delay: 300,
        data: function (params) {
          return { type: $('#type').val(), search: params.term };
        },
        processResults: function (rows) {
          return {
            results: rows.map(function (r) {
              return {
                id: r.voucher_type + '|' + r.id,
                text: r.voucher_number + ' | ' + r.voucher_date + ' | ' + (r.faktur_pajak ?? '-') + ' | BB ' + fmtNumber(r.gl_amount),
              };
            })
          };
        }
      }
    }).on('change', function () {
      let doc = $(this).attr('data-doc-number');
      let val = $(this).val();
      if (val) {
        manualMatches[doc] = val;
      } else {
        delete manualMatches[doc];
      }
    });
  }

  $('#cmdPreview').on('click', function () {
    if (!$('#frmAdd')[0].checkValidity()) {
      $('#frmAdd')[0].reportValidity();
      return;
    }

    $(".loading-spinner-container").addClass("-show");
    $('#cmdPreview').attr('disabled', 'disabled');
    $('#preview-ppnPkEqualisasi').addClass('d-none');

    $.ajax({
      url: "{{ route('ppnPkEqualisasi.preview') }}",
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
      error: function (xhr) {
        $('#cmdPreview').removeAttr('disabled');
        $(".loading-spinner-container").removeClass("-show");
        let err = JSON.parse(xhr.responseText);
        Swal.fire('Error..', err.message ? JSON.stringify(err.message) : 'Failed to preview', 'error');
      }
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
      url: "{{ route('ppnPkEqualisasi.store') }}",
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
          setTimeout(function () {
            window.location.href = "{{ route('ppnPkEqualisasi.index') }}";
          }, 1500);
        }
      },
      error: function (xhr) {
        $('#cmdSave').removeAttr('disabled');
        $(".loading-spinner-container").removeClass("-show");
        let err = JSON.parse(xhr.responseText);
        Swal.fire('Error..', err.message ? JSON.stringify(err.message) : 'Failed to save', 'error');
      }
    });
  });

  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

</script>
@endsection
