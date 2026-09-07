@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<div class="content-body">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="card-title">{{ $title }}</h4>
      <a href="{{ route('conversion.priceList.create') }}" class="btn btn-primary">
        <i class="feather icon-plus"></i> Create
      </a>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped" id="tblPriceList" style="width:100%">
          <thead>
            <tr>
              <th>Action</th><th>No</th><th>Product Code</th><th>Product Name</th>
              <th class="text-right">Sales Price</th><th class="text-right">Material Price</th>
              <th class="text-right">Margin</th><th class="text-right">Conversion</th>
              <th>By</th><th>Updated Date</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- MODAL EDIT -->
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Price List</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="editId">
        <div class="alert alert-info py-1">
          Conversion Value: <b>{{ number_format($conversionValue,2) }}</b>
        </div>
        <div id="editContainer"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="btnUpdate">Update</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL DETAIL -->
<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Price List</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="detailBody">
        <div class="text-center py-3 text-muted">Loading...</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
$.ajaxSetup({
  headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});

const URL_DATA   = '{{ route("conversion.priceList.data") }}';
const URL_SHOW   = '{{ route("conversion.priceList.show") }}';
const URL_EDIT   = '{{ route("conversion.priceList.edit") }}';
const URL_UPDATE = '{{ route("conversion.priceList.update") }}';
const CONV_VALUE = {{ (float) $conversionValue }};

$(function () {
  $('#tblPriceList').DataTable({
    processing: true, serverSide: true, ajax: URL_DATA,
    columns: [
      { data: 'action', orderable: false, searchable: false },
  { data: null, orderable: false, searchable: false, render: (d,t,r,m) => m.row + m.settings._iDisplayStart + 1 },
  { data: 'article_alternative_code' },
  { data: 'article_desc' },
  { data: 'sales_price',       className: 'text-right' },
  { data: 'material_price',    className: 'text-right' },
  { data: 'margin',            className: 'text-right' },
  { data: 'conversion_result', className: 'text-right' },
  { data: 'created_by' },
  { data: 'pl_date' },
],
    drawCallback: function () { if (window.feather) feather.replace(); }
  });

  $('#tblPriceList').on('click', '.btn-detail', function () { showDetail($(this).data('id')); });
  $('#tblPriceList').on('click', '.btn-edit',   function () { loadEdit($(this).data('id')); });
});

/* ---------- DETAIL ---------- */
function showDetail(id) {
  $('#detailBody').html('<div class="text-center py-3 text-muted">Loading...</div>');
  $('#modalDetail').modal('show');
  $.ajax({ url: URL_SHOW, method: 'POST', dataType: 'json', data: { id: id } })
    .done(function (res) {
      if (res.status != 1) { $('#detailBody').html('<div class="text-danger">'+(res.message||'Gagal')+'</div>'); return; }
      renderDetail(res.fg, res.materials);
    })
    .fail(function (xhr) { $('#detailBody').html('<div class="text-danger">Error '+xhr.status+'</div>'); });
}

function renderDetail(fg, mats) {
  let rows = '';
  mats.forEach(m => {
    rows += `
      <tr>
        <td>${m.article_alternative_code ?? m.article_code}</td>
        <td>${m.article_desc ?? ''}</td>
        <td><span class="badge badge-${m.article_type==='RMNP'?'secondary':'success'}">${m.article_type ?? ''}</span></td>
        <td class="text-right">${fmt(m.qty)}</td>
        <td class="text-right">${fmt(m.unit_price)}</td>
        <td class="text-right">${fmt(m.line_total)}</td>
      </tr>`;
  });
  const html = `
    <div class="row mb-2">
      <div class="col-sm-6">
        <b>${fg.article_alternative_code ?? fg.article_code}</b><br>
        <small class="text-muted">${fg.article_desc ?? ''}</small>
      </div>
      <div class="col-sm-6 text-right">
        <div>Date: <b>${fmtDate(fg.pl_date)}</b></div>
        <div>Conv. Value: <b>${fmt(fg.conversion_value)}</b></div>
      </div>
    </div>
    <table class="table table-sm table-bordered">
      <thead class="thead-light">
        <tr><th>Code</th><th>Name</th><th>Type</th><th class="text-right">Qty</th>
            <th class="text-right">Unit Price</th><th class="text-right">Line Total</th></tr>
      </thead>
      <tbody>${rows || '<tr><td colspan="6" class="text-center text-muted">No material</td></tr>'}</tbody>
    </table>
    <div class="row text-right">
      <div class="col-sm-6 offset-sm-6">
        <div>Sales Price: <b>${fmt(fg.sales_price)}</b></div>
        <div>Material Price: <b>${fmt(fg.material_price)}</b></div>
        <div>Sales - Material: <b>${fmt(fg.margin)}</b></div>
        <div>Conversion: <b class="text-primary">${fmt(fg.conversion_result)}</b></div>
      </div>
    </div>`;
  $('#detailBody').html(html);
}

/* ---------- EDIT ---------- */
function loadEdit(idEnc) {
  $('#editContainer').html('<div class="text-center py-3 text-muted">Loading...</div>');
  $('#modalEdit').modal('show');
  $.ajax({ url: URL_EDIT, method: 'POST', dataType: 'json', data: { id: idEnc } })
    .done(function (res) {
      if (res.status != 1) { $('#editContainer').html('<div class="text-danger">'+(res.message||'Gagal')+'</div>'); return; }
      $('#editId').val(idEnc);
      $('#editContainer').empty();
      const fg = {
        article_code: res.fg.article_code,
        article_alternative_code: res.fg.article_alternative_code,
        article_name: res.fg.article_desc,
        bom_code: res.fg.bom_code,
      };
      renderCard('#editContainer', fg, res.materials, true, res.fg.sales_price);
    })
    .fail(function (xhr) { $('#editContainer').html('<div class="text-danger">Error '+xhr.status+'</div>'); });
}

/* ---------- CARD (dipakai edit) ---------- */
function renderCard(container, fg, mats, isEdit, salesVal) {
  let rows = '';
  mats.forEach(m => {
    const alt  = m.article_alternative_code ?? '';
    const name = m.article_name ?? m.article_desc ?? '';
    rows += `
      <tr class="mat-row"
          data-code="${m.article_code}"
          data-type="${m.article_type}"
          data-source="${m.source}" data-qty="${m.qty}">
        <td>${alt}</td>
        <td>${name}</td>
        <td><span class="badge badge-${m.article_type==='RMNP'?'secondary':'success'}">${m.article_type}</span></td>
        <td class="text-right">${m.qty}</td>
        <td><input type="number" step="any" class="form-control form-control-sm unit-price text-right"
             value="${m.unit_price}" ${m.article_type==='RMNP'?'readonly':''}></td>
        <td class="text-right line-total">0</td>
      </tr>`;
  });

  const html = `
  <div class="card fg-card border" data-fg="${fg.article_code}" data-bom="${fg.bom_code}">
    <div class="card-header d-flex justify-content-between align-items-center py-1">
      <b>${fg.article_alternative_code ?? fg.article_code} - ${fg.article_name}</b>
    </div>
    <div class="card-body">
      <div class="form-group row">
        <label class="col-sm-3 col-form-label">Sales Price (manual)</label>
        <div class="col-sm-4">
          <input type="number" step="any" class="form-control sales-price text-right" value="${salesVal ?? 0}">
        </div>
      </div>
      <table class="table table-sm table-bordered mb-2">
        <thead class="thead-light">
          <tr><th>Code</th><th>Name</th><th>Type</th><th class="text-right">Qty</th>
              <th style="width:140px">Unit Price</th><th class="text-right">Line Total</th></tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
      <div class="row text-right">
        <div class="col-sm-6 offset-sm-6">
          <div>Material Price: <b class="mat-price">0</b></div>
          <div>Sales - Material: <b class="margin">0</b></div>
          <div>Conversion: <b class="conv-result text-primary">0</b></div>
        </div>
      </div>
    </div>
  </div>`;
  $(container).append(html);
  const $card = $(container + ' .fg-card').last();
  recalc($card);
  $card.find('.unit-price, .sales-price').on('input', () => recalc($card));
}

function recalc($card) {
  let matPrice = 0;
  $card.find('.mat-row').each(function () {
    const qty = parseFloat($(this).data('qty')) || 0;
    const up  = parseFloat($(this).find('.unit-price').val()) || 0;
    const lt  = qty * up;
    $(this).find('.line-total').text(fmt(lt));
    matPrice += lt;
  });
  const sales  = parseFloat($card.find('.sales-price').val()) || 0;
  const margin = sales - matPrice;
  const conv   = CONV_VALUE > 0 ? margin / CONV_VALUE : 0;
  $card.find('.mat-price').text(fmt(matPrice));
  $card.find('.margin').text(fmt(margin));
  $card.find('.conv-result').text(fmt(conv));
}

function fmt(n) { return (parseFloat(n) || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }); }
function fmtDate(d) { if (!d) return '-'; const p = String(d).substr(0,10).split('-'); return p.length===3 ? p[2]+'-'+p[1]+'-'+p[0] : d; }

function collectCard($c) {
  const mats = [];
  $c.find('.mat-row').each(function () {
    mats.push({
      article_code: $(this).data('code'),
      article_type: $(this).data('type'),
      source:       $(this).data('source'),
      qty:          parseFloat($(this).data('qty')) || 0,
      unit_price:   parseFloat($(this).find('.unit-price').val()) || 0,
    });
  });
  return {
    article_code: $c.data('fg'),
    bom_code:     $c.data('bom'),
    sales_price:  parseFloat($c.find('.sales-price').val()) || 0,
    materials:    mats,
  };
}

/* ---------- UPDATE (edit) ---------- */
$('#btnUpdate').on('click', function () {
  const $c = $('#editContainer .fg-card').first();
  if (!$c.length) { Swal.fire('Info', 'Data kosong', 'warning'); return; }
  const items = [collectCard($c)];
  $.ajax({
    url: URL_UPDATE, method: 'POST', dataType: 'json',
    data: { id: $('#editId').val(), items: JSON.stringify(items) }
  })
  .done(function (res) {
    if (res.status == 1) {
      Swal.fire('Success', res.message, 'success').then(() => {
        $('#modalEdit').modal('hide');
        $('#tblPriceList').DataTable().ajax.reload(null, false);
      });
    } else {
      Swal.fire('Warning', res.message || 'Gagal update', 'warning');
    }
  })
  .fail(function (xhr) {
    console.error('update error', xhr.status, xhr.responseText);
    Swal.fire('Error', 'Update gagal ('+xhr.status+'). Cek console.', 'error');
  });
});
</script>
@endsection