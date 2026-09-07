@extends('layouts.app')
@section('title', 'Create Price List')
@section('content')
@include('layouts.breadcrumb')

<style>
  .fg-row { border:1px solid #e0e0e5; border-left:4px solid #ff9f43; border-radius:.5rem; margin-bottom:1rem; }
  .fg-row.complete { border-left-color:#28c76f; }
  .fg-row .row-label { font-size:11px; text-transform:uppercase; color:#b9b9c3; margin-bottom:.25rem; display:block; }
  .fg-row .readonly-figure {
    background:#f8f8f8; border-radius:.357rem; padding:.438rem 1rem; text-align:right; font-weight:600;
  }
  .fg-row .conv-result { color:#7367f0; }
  .mat-tbl th, .mat-tbl td { font-size:13px; }
  .unit-price[readonly] { background:#f8f8f8; }

  .fg-empty-state { border:2px dashed #d8d6de; border-radius:.5rem; padding:2.5rem 1rem; text-align:center; color:#b9b9c3; }

  .conv-info-box { background:#f1effe; border:1px solid #d8d3fc; border-radius:.5rem; padding:.75rem 1rem; }
</style>

<div class="content-body">
  <div class="card">
    <div class="card-body">

      <div class="conv-info-box mb-3 d-flex justify-content-between align-items-center">
        <div>
          <i class="feather icon-info mr-1"></i>
          Conversion Value yang dipakai untuk perhitungan: <b>{{ number_format($conversionValue,2) }}</b>
        </div>
        <input type="hidden" id="convValue" value="{{ $conversionValue }}">
      </div>

      <form id="formSave" action="{{ route('conversion.priceList.store') }}" method="POST">
        @csrf
        <input type="hidden" name="items" id="itemsJson">

        <div id="fgRowContainer"></div>

        <div id="fgEmptyState" class="fg-empty-state">
          <i class="feather icon-package" style="font-size:32px;"></i>
          <p class="mt-2 mb-3">Belum ada artikel ditambahkan.</p>
          <button type="button" class="btn btn-primary btn-sm" id="btnAddRowEmpty">
            <i class="feather icon-plus mr-50"></i> Add Article
          </button>
        </div>

        <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddRow" style="display:none">
          <i class="feather icon-plus mr-50"></i> Add Article
        </button>
      </form>

      <hr>
      <div class="form-row mt-75">
        <div class="col-md-12">
          <a href="{{ route('conversion.priceList.index') }}" class="btn btn-light">Back</a>
          <button class="btn btn-primary" type="button" id="btnSave">Save</button>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- template option list, dipakai tiap row select -->
<template id="fgOptionsTemplate">
  @foreach($fgList as $fg)
    <option value="{{ $fg->article_code }}" data-name="{{ $fg->article_alternative_code }} - {{ $fg->article_desc }}">
      {{ $fg->article_alternative_code }} - {{ $fg->article_desc }}
    </option>
  @endforeach
</template>

<template id="customerOptionsTemplate">
  @foreach($customerList as $c)
    <option value="{{ $c->kode }}" data-name="{{ $c->nama }}">
      {{ $c->nama }}
    </option>
  @endforeach
</template>
@endsection

@section('scripts')
<script type="text/javascript">
$.ajaxSetup({
  headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});

const URL_GETBOM = '{{ route("conversion.priceList.getBom") }}';
const CONV_VALUE = parseFloat($('#convValue').val()) || 0;

let rowSeq = 0;

$(function () {
  $('#btnAddRow, #btnAddRowEmpty').on('click', addRow);
  $('#btnSave').on('click', doSave);
  addRow(); // mulai dengan 1 baris kosong
});

/* ---------- ADD ROW ---------- */
function addRow() {
  rowSeq++;
  const rowId = 'row' + rowSeq;
  const optionsHtml = $('#fgOptionsTemplate').html();
  const customerOptionsHtml = $('#customerOptionsTemplate').html();

  const html = `
  <div class="fg-row" id="${rowId}" data-code="">
    <div class="card-body py-3">
      <div class="row mb-2">
        <div class="col-lg-6 mb-2 mb-lg-0">
          <span class="row-label">Article</span>
          <select class="form-control fg-select" style="width:100%">
            <option value=""></option>
            ${optionsHtml}
          </select>
        </div>
        <div class="col-lg-6">
          <span class="row-label">Customer</span>
          <select class="form-control customer-select" style="width:100%">
            <option value=""></option>
            ${customerOptionsHtml}
          </select>
        </div>
      </div>
      <div class="row align-items-end">
        <div class="col-lg-3 col-6 mb-2 mb-lg-0">
          <span class="row-label">Sales Price</span>
          <input type="number" step="any" class="form-control sales-price text-right" value="0" disabled>
        </div>
        <div class="col-lg-3 col-6 mb-2 mb-lg-0">
          <span class="row-label">Material Price</span>
          <div class="readonly-figure mat-price">0</div>
        </div>
        <div class="col-lg-2 col-6">
          <span class="row-label">Margin</span>
          <div class="readonly-figure margin">0</div>
        </div>
        <div class="col-lg-2 col-4">
          <span class="row-label">Conversion</span>
          <div class="readonly-figure conv-result">0</div>
        </div>
        <div class="col-lg-2 col-2 text-right">
          <span class="row-label d-none d-lg-block">&nbsp;</span>
          <button type="button" class="btn btn-icon btn-flat-danger btn-remove-row" title="Hapus baris">
            <i class="feather icon-trash-2"></i>
          </button>
        </div>
      </div>

      <div class="mat-table-wrap mt-3" style="display:none">
        <table class="table table-sm table-bordered mat-tbl mb-1">
          <thead class="thead-light">
            <tr><th>Code</th><th>Name</th><th>Type</th><th class="text-right">Qty</th>
                <th style="width:130px">Unit Price</th><th class="text-right">Line Total</th></tr>
          </thead>
          <tbody class="mat-tbody"></tbody>
        </table>
      </div>
      <div class="empty-row-hint text-muted small mt-2">Pilih artikel di atas untuk memuat komposisi material.</div>
    </div>
  </div>`;

  $('#fgRowContainer').append(html);
  const $row = $('#' + rowId);

  $row.find('.fg-select').select2({
    dropdownParent: $row,
    width: '100%',
    placeholder: 'Cari kode / nama artikel...'
  }).on('select2:select', function (e) {
    onArticleSelected($row, e.params.data.id);
  });

  $row.find('.customer-select').select2({
    dropdownParent: $row,
    width: '100%',
    placeholder: 'Pilih customer (opsional)'
  });

  $row.find('.btn-remove-row').on('click', function () {
    $row.remove();
    updateEmptyState();
  });

  $row.find('.sales-price').on('input', function () {
    recalcRow($row);
  });

  updateEmptyState();
}

/* ---------- ARTICLE SELECTED -> LOAD BOM ---------- */
function onArticleSelected($row, code) {
  if (!code) return;

  const $selectedOpt = $row.find('.fg-select option:selected');
  const label = $selectedOpt.length ? $selectedOpt.text().split(' - ')[0] : code;

  // cegah artikel yang sama dipilih di baris lain
  const used = [];
  $('.fg-row').not($row).each(function () { if ($(this).data('code')) used.push(String($(this).data('code'))); });
  if (used.includes(String(code))) {
    Swal.fire('Info', 'Artikel ' + label + ' sudah ditambahkan di baris lain.', 'warning');
    $row.find('.fg-select').val(null).trigger('change');
    return;
  }

  $row.data('code', code);
  $row.find('.mat-tbody').html('<tr><td colspan="6" class="text-center text-muted py-2">Memuat...</td></tr>');
  $row.find('.mat-table-wrap').show();
  $row.find('.empty-row-hint').hide();

  $.ajax({ url: URL_GETBOM, method: 'POST', dataType: 'json', data: { article_code: code } })
    .done(function (res) {
      if (res.status != 1) {
        Swal.fire('Info', res.message || 'BOM tidak ditemukan untuk artikel ini', 'warning');
        resetRow($row);
        return;
      }
      $row.data('fg', res.fg);
      $row.data('materials', res.materials);
      renderMaterials($row, res.materials);
      $row.find('.sales-price').prop('disabled', false);
      recalcRow($row);
    })
    .fail(function (xhr) {
      console.error('getBom error', xhr.status, xhr.responseText);
      Swal.fire('Error', 'Gagal mengambil data BOM (' + xhr.status + '). Cek console.', 'error');
      resetRow($row);
    });
}

function resetRow($row) {
  $row.data('code', '');
  $row.data('fg', null);
  $row.data('materials', null);
  $row.find('.fg-select').val(null).trigger('change');
  $row.find('.mat-table-wrap').hide();
  $row.find('.mat-tbody').empty();
  $row.find('.empty-row-hint').show();
  $row.find('.sales-price').prop('disabled', true).val(0);
  recalcRow($row);
}

function renderMaterials($row, mats) {
  let rows = '';
  mats.forEach(m => {
    const alt  = m.article_alternative_code ?? '';
    const name = m.article_name ?? m.article_desc ?? '';
    const lastRecText = m.article_type === 'RMNP'
      ? 'Non Purchase (RMNP)'
      : (m.last_receiving_date ? 'Last: ' + fmtDate(m.last_receiving_date) : 'Belum pernah receiving');
    rows += `
      <tr class="mat-row"
          data-code="${m.article_code}"
          data-type="${m.article_type}"
          data-source="${m.source}" data-qty="${m.qty}">
        <td>${alt}</td>
        <td>${name}</td>
        <td><span class="badge badge-${m.article_type==='RMNP'?'secondary':'success'}">${m.article_type}</span></td>
        <td class="text-right">${m.qty}</td>
        <td>
          <input type="number" step="any" class="form-control form-control-sm unit-price text-right"
               value="${m.unit_price}" ${m.article_type==='RMNP'?'readonly':''}>
          <small class="text-muted d-block mt-25">${lastRecText}</small>
        </td>
        <td class="text-right line-total">0</td>
      </tr>`;
  });
  $row.find('.mat-tbody').html(rows || '<tr><td colspan="6" class="text-center text-muted py-2">Tidak ada material</td></tr>');
  $row.find('.unit-price').on('input', function () { recalcRow($row); });
}

/* ---------- CALC ---------- */
function recalcRow($row) {
  let matPrice = 0;
  $row.find('.mat-row').each(function () {
    const qty = parseFloat($(this).data('qty')) || 0;
    const up  = parseFloat($(this).find('.unit-price').val()) || 0;
    const lt  = qty * up;
    $(this).find('.line-total').text(fmt(lt));
    matPrice += lt;
  });
  const sales  = parseFloat($row.find('.sales-price').val()) || 0;
  const margin = sales - matPrice;
  const conv   = CONV_VALUE > 0 ? margin / CONV_VALUE : 0;

  $row.find('.mat-price').text(fmt(matPrice));
  $row.find('.margin').text(fmt(margin));
  $row.find('.conv-result').text(fmt(conv));

  const complete = !!$row.data('code') && sales > 0;
  $row.toggleClass('complete', complete);
}

/* ---------- EMPTY STATE ---------- */
function updateEmptyState() {
  const has = $('.fg-row').length > 0;
  $('#fgEmptyState').toggle(!has);
  $('#btnAddRow').toggle(has);
}

/* ---------- HELPERS ---------- */
function fmt(n) { return (parseFloat(n) || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }); }
function fmtDate(d) { if (!d) return '-'; const p = String(d).substr(0,10).split('-'); return p.length===3 ? p[2]+'-'+p[1]+'-'+p[0] : d; }

function collectRow($row) {
  const mats = [];
  $row.find('.mat-row').each(function () {
    mats.push({
      article_code: $(this).data('code'),
      article_type: $(this).data('type'),
      source:       $(this).data('source'),
      qty:          parseFloat($(this).data('qty')) || 0,
      unit_price:   parseFloat($(this).find('.unit-price').val()) || 0,
    });
  });
  const fg = $row.data('fg');
  const $custOpt = $row.find('.customer-select option:selected');
  return {
    article_code:  fg.article_code,
    bom_code:      fg.bom_code,
    customer_code: $row.find('.customer-select').val() || null,
    customer_name: $custOpt.data('name') || null,
    sales_price:   parseFloat($row.find('.sales-price').val()) || 0,
    materials:     mats,
  };
}

/* ---------- SAVE ---------- */
function doSave() {
  const $rows = $('.fg-row').filter(function () { return !!$(this).data('code'); });

  if (!$rows.length) {
    Swal.fire('Info', 'Tambahkan minimal 1 artikel', 'warning');
    return;
  }

  let incomplete = 0;
  const items = [];
  $rows.each(function () {
    const item = collectRow($(this));
    if (item.sales_price <= 0) incomplete++;
    items.push(item);
  });

  if (incomplete > 0) {
    Swal.fire('Belum Lengkap', incomplete + ' artikel belum diisi Sales Price.', 'warning');
    return;
  }

  Swal.fire({
    title: 'Simpan Price List?',
    text: items.length + ' artikel akan disimpan.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, simpan',
    cancelButtonText: 'Batal'
  }).then((result) => {
    if (result.isConfirmed) {
      $('#itemsJson').val(JSON.stringify(items));
      $('#formSave').submit();
    }
  });
}
</script>
@endsection