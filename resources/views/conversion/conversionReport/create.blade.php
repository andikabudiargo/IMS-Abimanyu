@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">{{ $subtitle }}</h4>
  </div>
  <div class="card-body">
    <form id="frmCreate" method="POST" action="{{ route('conversionReport.store') }}">
      @csrf

      <div class="form-row">
        <div class="form-group col-md-3">
          <label>Nomor Conversion</label>
          <input type="text" class="form-control" value="Auto-generated (CVR-ASN-YYYY-MONTH-XXXX)" disabled>
        </div>
         <div class="form-group col-md-2">
          <label for="periode">Periode (Bulan) <span class="text-danger">*</span></label>
          <select class="select2 form-control" id="periode" name="periode" required>
            <option value="">-- Bulan --</option>
            @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $m)
              <option value="{{ $i + 1 }}">{{ $m }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-2">
          <label for="tahun">Tahun <span class="text-danger">*</span></label>
          <select class="select2 form-control" id="tahun" name="tahun" required>
            <option value="">-- Tahun --</option>
            @for ($y = 2023; $y <= date('Y'); $y++)
              <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
          </select>
        </div>
      </div>
       <div class="form-row">
        <div class="form-group col-md-7">
          <label for="reportName">Nama Conversion <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="reportName" name="reportName" required>
        </div>
       
      </div>

      <div class="form-row">
        <div class="form-group col-md-7">
          <label for="note">Note</label>
          <textarea class="form-control" id="note" name="note" rows="4"></textarea>
        </div>
      </div>

      <hr>
      <div class="d-flex justify-content-between align-items-center">
        <h5>Article dari Delivery pada periode terpilih</h5>
        <button type="button" class="btn btn-outline-success btn-sm d-none" id="btnExport">
          <i data-feather="download" class="align-middle mr-50"></i>
          <span class="align-middle">Export Excel</span>
        </button>
      </div>
      <div id="previewEmpty" class="text-muted mb-2">Pilih Periode (Bulan) &amp; Tahun untuk menarik data delivery.</div>
      <div id="previewLoading" class="text-muted mb-2" style="display:none">
        <i data-feather="loader" class="mr-50"></i> Memuat data delivery...
      </div>

      <div id="previewWrap" style="display:none">
        <div class="row mb-2">
          <div class="col-md-4">
            <div class="card mb-0"><div class="card-body py-1 px-2">
              <small class="text-muted d-block">Total Article</small>
              <h5 class="mb-0" id="sumTotalArticle">0</h5>
            </div></div>
          </div>
          <div class="col-md-4">
            <div class="card mb-0"><div class="card-body py-1 px-2">
              <small class="text-muted d-block">Total Qty Kirim</small>
              <h5 class="mb-0" id="sumTotalQty">0</h5>
            </div></div>
          </div>
          <div class="col-md-4">
            <div class="card mb-0"><div class="card-body py-1 px-2">
              <small class="text-muted d-block">Total Conversion</small>
              <h5 class="mb-0" id="sumTotalConversion">0</h5>
            </div></div>
          </div>
        </div>

        <div class="table-responsive">
        <table class="table table-bordered table-sm">
          <thead class="thead-light">
            <tr>
              <th style="width:4%">No</th>
              <th>Article Code</th>
              <th>Article Desc</th>
              <th>Customer</th>
              <th class="text-right">Qty</th>
              <th class="text-right">Conversion</th>
              <th style="width:6%">Action</th>
            </tr>
          </thead>
          <tbody id="previewRows"></tbody>
        </table>
        </div>
        <small class="text-muted">
          Conversion = (Avg Selling Price &minus; Avg Purchase Price) / Conversion Value.
          Avg Selling Price dihitung dari rata-rata (dibobot qty) harga Sales Order tiap Delivery Note di periode ini,
          Avg Purchase Price dari average cost BOM/receiving berjalan.
        </small>
      </div>

      <hr>
      <div class="form-row mt-1">
        <div class="col-12">
          <a href="{{ route('conversionReport.index') }}" class="btn btn-light">Back</a>
          <button type="submit" class="btn btn-primary" id="btnSave" disabled>Save</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="mdlDetail" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail DN <span id="mdlArticleLabel"></span></h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-sm">
          <thead class="thead-light">
            <tr>
              <th>DN Number</th>
              <th>Customer</th>
              <th class="text-right">Qty</th>
              <th class="text-right">Price Unit</th>
              <th class="text-right">Price Total</th>
            </tr>
          </thead>
          <tbody id="mdlDetailRows"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  let previewRows = [];
  let dnByArticle = {};

  function humanize(n) {
    n = parseFloat(n) || 0;
    return n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function loadPreview() {
    const periode = $('#periode').val();
    const tahun = $('#tahun').val();

    if (!periode || !tahun) {
      $('#previewEmpty').show();
      $('#previewWrap, #previewLoading').hide();
      $('#btnExport').addClass('d-none');
      $('#btnSave').prop('disabled', true);
      return;
    }

    $('#previewEmpty, #previewWrap').hide();
    $('#previewLoading').show();
    $('#btnExport').addClass('d-none');
    $('#btnSave').prop('disabled', true);

    $.get("{{ route('conversionReport.previewPeriod') }}", { periode: periode, tahun: tahun }, function (res) {
      $('#previewLoading').hide();

      if (!res.status || !res.rows || res.rows.length === 0) {
        $('#previewEmpty').text('Tidak ada data Delivery pada periode ini.').show();
        return;
      }

      previewRows = res.rows;
      dnByArticle = res.dnByArticle || {};

      let html = '';
      previewRows.forEach((r, i) => {
        html += `<tr>
          <td class="text-center">${i + 1}</td>
          <td>${r.article_alternative_code}</td>
          <td>${r.article_desc}</td>
          <td>${r.customer_names}</td>
          <td class="text-right">${humanize(r.total_qty)} ${r.uom || ''}</td>
          <td class="text-right">${humanize(r.conversion)}</td>
          <td class="text-center">
            <button type="button" class="btn btn-icon btn-flat-primary btn-info-row" data-article="${r.article_code}" data-label="${r.article_alternative_code} - ${r.article_desc}">
              <i data-feather="info"></i>
            </button>
          </td>
        </tr>`;
      });

      $('#previewRows').html(html);

      const totalArticle    = previewRows.length;
      const totalQty        = previewRows.reduce((sum, r) => sum + (parseFloat(r.total_qty) || 0), 0);
      const totalConversion = previewRows.reduce((sum, r) => sum + (parseFloat(r.conversion) || 0), 0);

      $('#sumTotalArticle').text(totalArticle);
      $('#sumTotalQty').text(humanize(totalQty));
      $('#sumTotalConversion').text(humanize(totalConversion));

      $('#previewWrap').show();
      $('#btnExport').removeClass('d-none');
      $('#btnSave').prop('disabled', false);
      if (window.feather) feather.replace({ width: 14, height: 14 });
    }).fail(function () {
      $('#previewLoading').hide();
      Swal.fire('Error', 'Gagal menarik data delivery periode ini.', 'error');
    });
  }

  $('#periode, #tahun').on('change', loadPreview);

  $('#btnExport').on('click', function () {
    const periode = $('#periode').val();
    const tahun = $('#tahun').val();
    if (!periode || !tahun) return;
    window.location.href = "{{ route('conversionReport.exportPreview') }}?periode=" + periode + "&tahun=" + tahun;
  });

  $(document).on('click', '.btn-info-row', function () {
    const articleCode = $(this).data('article');
    const label = $(this).data('label');
    const lines = dnByArticle[articleCode] || [];

    $('#mdlArticleLabel').text('| ' + label);

    let html = '';
    lines.forEach(l => {
      const dnCell = l.dn_url
        ? `<a href="${l.dn_url}" target="_blank">${l.dn_number}</a>`
        : (l.dn_number || '-');
      html += `<tr>
        <td>${dnCell}</td>
        <td>${l.customer_name || '-'}</td>
        <td class="text-right">${humanize(l.qty)}</td>
        <td class="text-right">${humanize(l.price_unit)}</td>
        <td class="text-right">${humanize(l.price_total)}</td>
      </tr>`;
    });
    $('#mdlDetailRows').html(html || '<tr><td colspan="5" class="text-center text-muted">Tidak ada data.</td></tr>');
    $('#mdlDetail').modal('show');
  });

  $('#frmCreate').on('submit', function () {
    if (previewRows.length === 0) {
      Swal.fire('Warning', 'Belum ada data delivery yang ditarik untuk periode ini.', 'warning');
      return false;
    }
  });
</script>
@endsection
