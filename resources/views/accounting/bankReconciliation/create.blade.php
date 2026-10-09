@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="add-bankReconciliation">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Reconciliation Kas & Bank — Upload Rekening Koran</h4>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <form id="frmAdd" name="frmAdd" autocomplete="off" enctype="multipart/form-data">
          @csrf
          <div class="form-row">
            <div class="form-group col-md-3">
              <label class="form-label" for="type">Type*</label>
              <select class="select2 form-control" id="type" name="type" required>
                <option value=""></option>
                <option value="KAS">Kas</option>
                <option value="BANK">Bank</option>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label class="form-label" for="periode">Periode*</label>
              <select class="select2 form-control" id="periode" name="periode" required>
                <option value=""></option>
                @foreach ($periodes as $val => $label)
                  <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-2">
              <label class="form-label" for="year">Tahun*</label>
              <input type="number" id="year" name="year" class="form-control" value="{{ date('Y') }}" required />
            </div>
            <div class="form-group col-md-5">
              <label class="form-label" for="description">Description</label>
              <input type="text" id="description" name="description" class="form-control" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label class="form-label" for="statement">Rekening Koran (CSV)*</label>
              <div class="custom-file">
                <input type="file" class="custom-file-input" name="statement" id="statement" accept=".csv" required />
                <label class="custom-file-label" for="statement" id="statementLabel">Choose file</label>
              </div>
            </div>
          </div>
          <div class="form-row mt-1">
            <div class="col-12">
              <button class="btn btn-primary" type="button" id="cmdPreview">
                <i data-feather="upload" class="align-middle mr-sm-25 mr-0"></i>
                <span class="align-middle d-sm-inline-block d-none">Upload &amp; Reconcile</span>
              </button>
              <a href="{{ route('bankReconciliation.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

{{-- Hasil preview (parse + match) -- belum tersimpan sampai user klik Save manual --}}
<section id="preview-bankReconciliation" class="d-none">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Preview Hasil Parse &amp; Match</h4>
      <span id="previewSummary" class="badge badge-light-info"></span>
    </div>
    <div class="card-body">
      <div class="table-responsive" style="max-height: 28rem; overflow-y: auto;">
        <table class="table table-sm table-striped">
          <thead class="thead-light">
            <tr>
              <th>No</th>
              <th>Tanggal</th>
              <th>Keterangan</th>
              <th class="text-right">Debit</th>
              <th class="text-right">Kredit</th>
              <th class="text-right">Saldo</th>
              <th>Status</th>
              <th>Voucher GL</th>
              <th class="text-right">Debit GL</th>
              <th class="text-right">Kredit GL</th>
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

@section('scripts')
<script type="text/javascript">

  let previewFilePath = null;

  $(document).ready(function () {
    validateFormToast("frmAdd");

    $('#statement').on('change', function () {
      let name = $(this).val().split('\\').pop() || 'Choose file';
      $('#statementLabel').text(name);
      // Ganti file -> preview lama (kalau ada) jadi tidak valid lagi.
      previewFilePath = null;
      $('#preview-bankReconciliation').addClass('d-none');
    });
  });

  function fmtNumber(n) {
    return Number(n).toLocaleString('id-ID', { minimumFractionDigits: 2 });
  }

  function renderPreview(data) {
    let rows = data.rows.map(function (r, i) {
      let statusBadge = r.status === 'MATCHED'
        ? '<span class="badge badge-success">MATCH</span>'
        : '<span class="badge badge-danger">NOT MATCH</span>';
      let debit = r.mutation_type === 'DB' ? fmtNumber(r.amount) : '-';
      let kredit = r.mutation_type === 'CR' ? fmtNumber(r.amount) : '-';
      let glDebit = r.gl_debit !== null && r.gl_debit > 0 ? fmtNumber(r.gl_debit) : '-';
      let glKredit = r.gl_kredit !== null && r.gl_kredit > 0 ? fmtNumber(r.gl_kredit) : '-';
      let voucherCell = r.voucher_number
        ? (r.voucher_url ? `<a href="${r.voucher_url}" target="_blank">${r.voucher_number}</a>` : r.voucher_number)
        : '-';
      return `<tr>
        <td>${i + 1}</td>
        <td>${r.stmt_date}</td>
        <td>${r.description}</td>
        <td class="text-right">${debit}</td>
        <td class="text-right">${kredit}</td>
        <td class="text-right">${r.saldo !== null ? fmtNumber(r.saldo) : '-'}</td>
        <td>${statusBadge}</td>
        <td>${voucherCell}</td>
        <td class="text-right">${glDebit}</td>
        <td class="text-right">${glKredit}</td>
      </tr>`;
    }).join('');

    $('#previewRows').html(rows);
    let saldoInfo = (data.saldoAwal !== null ? ` | Saldo Awal: ${fmtNumber(data.saldoAwal)}` : '')
      + (data.saldoAkhir !== null ? ` | Saldo Akhir: ${fmtNumber(data.saldoAkhir)}` : '');
    $('#previewSummary').text(`${data.totalRows} baris, ${data.matchedCount} otomatis match${saldoInfo}`);
    $('#preview-bankReconciliation').removeClass('d-none');
    document.getElementById('preview-bankReconciliation').scrollIntoView({ behavior: 'smooth' });
  }

  $('#cmdPreview').on('click', function () {
    if (!$('#frmAdd')[0].checkValidity()) {
      $('#frmAdd')[0].reportValidity();
      return;
    }

    $(".loading-spinner-container").addClass("-show");
    $('#cmdPreview').attr('disabled', 'disabled');
    $('#preview-bankReconciliation').addClass('d-none');

    $.ajax({
      url: "{{ route('bankReconciliation.preview') }}",
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
      url: "{{ route('bankReconciliation.store') }}",
      method: "POST",
      data: {
        periode: $('#periode').val(),
        year: $('#year').val(),
        type: $('#type').val(),
        description: $('#description').val(),
        filePath: previewFilePath,
      },
      dataType: "json",
      success: function (data) {
        $('#cmdSave').removeAttr('disabled');
        $(".loading-spinner-container").removeClass("-show");
        show_msg(data.title, data.message, data.alert);
        if (data.status == 1) {
          setTimeout(function () {
            window.location.href = "{{ route('bankReconciliation.index') }}";
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
