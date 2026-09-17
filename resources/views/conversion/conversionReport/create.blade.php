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
          <input type="text" class="form-control" value="Auto-generated" disabled>
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
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Delivery Article List</h5>
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
        @include('conversion.conversionReport._summaryCards')

        <div class="table-responsive">
        <table class="table table-bordered table-sm">
          <thead class="thead-light">
            <tr>
              <th style="width:4%">No</th>
              <th>Article Code</th>
              <th>Article Desc</th>
              <th>Customer</th>
              <th class="text-right">Qty</th>
              <th class="text-right">Konversi Painting</th>
              <th class="text-right">Konversi Non Painting</th>
              <th style="width:6%">Action</th>
            </tr>
          </thead>
          <tbody id="previewRows"></tbody>
        </table>
        </div>
       {{-- <small class="text-muted">
          Konversi = ((Avg Selling Price &minus; Avg Purchase Price) &times; Qty) / Conversion Value.
          Avg Selling Price dihitung dari rata-rata (dibobot qty) harga Sales Order (price + service) tiap Delivery Note di periode ini,
          Avg Purchase Price dari average cost BOM/receiving (tanpa PPN) berjalan.
          Painting = artikel ber-UOM PCS/SET, selain itu Non Painting.
        </small>--}}
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

<div class="modal fade" id="mdlDetail" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-truncate pr-1">Detail DN <span id="mdlArticleLabel"></span></h5>
        <div class="d-flex align-items-center flex-shrink-0">
          <button type="button" class="btn btn-sm btn-outline-primary mr-1" id="btnExportDnDetail">
            <i data-feather="download" class="mr-25"></i> Export
          </button>
          <button type="button" class="close m-0 p-0" data-dismiss="modal">&times;</button>
        </div>
      </div>
      <div class="modal-body">
        <div class="table-responsive" style="max-height:60vh;overflow:auto;">
        <table class="table table-hover table-sm mb-0">
          <thead class="thead-light" style="position:sticky;top:0;z-index:1;">
            <tr>
              <th style="width:4%">No</th>
              <th>DN Number</th>
              <th>SO Number</th>
              <th>Customer</th>
              <th class="text-right">Qty</th>
              <th class="text-right">Price Unit</th>
              <th class="text-right">Price Total</th>
              <th class="text-right">Konversi</th>
            </tr>
          </thead>
          <tbody id="mdlDetailRows"></tbody>
          <tfoot class="thead-light font-weight-bolder" style="position:sticky;bottom:0;">
            <tr>
              <th colspan="4" class="text-right">Total</th>
              <th class="text-right" id="mdlTotalQty">0</th>
              <th></th>
              <th class="text-right" id="mdlTotalPrice">0</th>
              <th class="text-right" id="mdlTotalConv">0</th>
            </tr>
          </tfoot>
        </table>
        </div>
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
  let convValue = 0;

  function humanize(n) {
    n = parseFloat(n) || 0;
    return n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // Stempel tanggal+jam untuk nama file export: YYYYMMDD_HHmmss
  function exportStamp() {
    const d = new Date();
    const p = (x) => String(x).padStart(2, '0');
    return `${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}_${p(d.getHours())}${p(d.getMinutes())}${p(d.getSeconds())}`;
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
      convValue = parseFloat(res.conversionValue) || 0;

      const isPainting = (uom) => ['PCS', 'SET'].includes((uom || '').trim().toUpperCase());

      let html = '';
      previewRows.forEach((r, i) => {
        const painting = isPainting(r.uom);
        const conv = parseFloat(r.conversion) || 0;
        html += `<tr>
          <td class="text-center">${i + 1}</td>
          <td>${r.article_alternative_code}</td>
          <td>${r.article_desc}</td>
          <td>${r.customer_names}</td>
          <td class="text-right">${humanize(r.total_qty)} ${r.uom || ''}</td>
          <td class="text-right">${painting ? humanize(conv) : '-'}</td>
          <td class="text-right">${painting ? '-' : humanize(conv)}</td>
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
      const totalConvPainting = previewRows.reduce((sum, r) =>
        sum + (isPainting(r.uom) ? (parseFloat(r.conversion) || 0) : 0), 0);
      const totalConvNonPainting = previewRows.reduce((sum, r) =>
        sum + (isPainting(r.uom) ? 0 : (parseFloat(r.conversion) || 0)), 0);

      $('#sumTotalArticle').text(totalArticle);
      $('#sumTotalQty').text(humanize(totalQty));
      $('#sumTotalConversion').text(humanize(totalConversion));
      $('#sumConvPainting').text(humanize(totalConvPainting));
      $('#sumConvNonPainting').text(humanize(totalConvNonPainting));

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

  let mdlCurrentLines = [];
  let mdlCurrentLabel = '';
  let mdlCurrentArticle = null;

  const isPaintingUom = (uom) => ['PCS', 'SET'].includes((uom || '').trim().toUpperCase());

  // konversi per DN = ((price_unit - avg_purchase) * qty) / conversion_value
  function convPerDn(line, article) {
    if (!article || convValue <= 0) return 0;
    const avgPurchase = parseFloat(article.avg_purchase_price) || 0;
    return (((parseFloat(line.price_unit) || 0) - avgPurchase) * (parseFloat(line.qty) || 0)) / convValue;
  }

  $(document).on('click', '.btn-info-row', function () {
    const articleCode = $(this).data('article');
    const label = $(this).data('label');
    const lines = dnByArticle[articleCode] || [];
    const article = previewRows.find(r => r.article_code === articleCode);
    const painting = article ? isPaintingUom(article.uom) : false;

    mdlCurrentLines = lines;
    mdlCurrentLabel = label;
    mdlCurrentArticle = article;

    $('#mdlArticleLabel').text('| ' + label);

    let html = '';
    let tQty = 0, tPrice = 0, tConv = 0;
    lines.forEach((l, i) => {
      const dnCell = l.dn_url
        ? `<a href="${l.dn_url}" target="_blank">${l.dn_number}</a>`
        : (l.dn_number || '-');
      const soCell = l.so_url
        ? `<a href="${l.so_url}" target="_blank">${l.so_number}</a>`
        : (l.so_number || '-');
      const conv = convPerDn(l, article);
      tQty += parseFloat(l.qty) || 0;
      tPrice += parseFloat(l.price_total) || 0;
      tConv += conv;
      html += `<tr>
        <td class="text-center">${i + 1}</td>
        <td>${dnCell}</td>
        <td>${soCell}</td>
        <td>${l.customer_name || '-'}</td>
        <td class="text-right">${humanize(l.qty)}</td>
        <td class="text-right">${humanize(l.price_unit)}</td>
        <td class="text-right">${humanize(l.price_total)}</td>
        <td class="text-right">${humanize(conv)}</td>
      </tr>`;
    });
    $('#mdlDetailRows').html(html || '<tr><td colspan="8" class="text-center text-muted">Tidak ada data.</td></tr>');
    $('#mdlTotalQty').text(humanize(tQty));
    $('#mdlTotalPrice').text(humanize(tPrice));
    $('#mdlTotalConv').text(humanize(tConv));
    $('#mdlDetail').modal('show');
    if (window.feather) feather.replace({ width: 14, height: 14 });
  });

  $('#btnExportDnDetail').on('click', function () {
    if (!mdlCurrentLines.length) {
      Swal.fire('Info', 'Tidak ada data untuk diexport.', 'info');
      return;
    }
    const sep = ';';
    const esc = (v) => '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"';
    // Angka dibulatkan 2 desimal & pakai koma sebagai desimal (format Indonesia)
    // supaya nilai di Excel = nilai di modal, bukan kebaca sbg ribuan.
    const numId = (n) => (Math.round(((parseFloat(n) || 0) + Number.EPSILON) * 100) / 100)
      .toFixed(2).replace('.', ',');
    const article = mdlCurrentArticle;
    const header = ['No', 'DN Number', 'SO Number', 'Customer', 'Qty', 'Price Unit', 'Price Total', 'Konversi'];
    let csv = header.map(esc).join(sep) + '\r\n';
    let tQty = 0, tPrice = 0, tConv = 0;
    mdlCurrentLines.forEach((l, i) => {
      const conv = convPerDn(l, article);
      tQty += parseFloat(l.qty) || 0;
      tPrice += parseFloat(l.price_total) || 0;
      tConv += conv;
      csv += [
        i + 1,
        l.dn_number || '',
        l.so_number || '',
        l.customer_name || '',
        numId(l.qty),
        numId(l.price_unit),
        numId(l.price_total),
        numId(conv),
      ].map(esc).join(sep) + '\r\n';
    });
    csv += ['', '', '', 'TOTAL', numId(tQty), '', numId(tPrice), numId(tConv)].map(esc).join(sep) + '\r\n';

    const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'Detail_DN_' + mdlCurrentLabel.replace(/[^A-Za-z0-9]+/g, '_') + '_' + exportStamp() + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  });

  $('#frmCreate').on('submit', function () {
    if (previewRows.length === 0) {
      Swal.fire('Warning', 'Belum ada data delivery yang ditarik untuk periode ini.', 'warning');
      return false;
    }
  });
</script>
@endsection
