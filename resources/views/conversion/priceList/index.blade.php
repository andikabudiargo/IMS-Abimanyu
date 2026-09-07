@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="pricelist-index">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Filter</h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0">
          <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <form class="needs-validation" novalidate>
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="searchArticle">Article Code</label>
              <input type="text" class="form-control text-uppercase" id="searchArticle" name="searchArticle" placeholder="">
            </div>
            <div class="form-group col-md-4">
              <label for="searchDesc">Article Desc</label>
              <input type="text" class="form-control" id="searchDesc" name="searchDesc" placeholder="">
            </div>
            <div class="form-group col-md-4">
              <label class="form-label" for="searchCustomer">Customer</label>
              <select class="select2 form-control" id="searchCustomer" name="searchCustomer">
                <option value="">All</option>
                @foreach($customerList as $c)
                  <option value="{{ $c->kode }}">{{ $c->nama }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="col-12">
              <button type="button" class="btn btn-primary" id="btnSearch" name="btnSearch">Search</button>
              <a href="{{ route('conversion.priceList.create') }}" class="btn btn-info">
                <i class="fa fa-plus"></i> Create
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<section id="table-pricelist">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">{{ $title }} List</h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0">
          <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
          <li><a data-action="reload"><i data-feather="rotate-cw"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <div class="row">
          <div class="col-sm-12">
            <div class="card-datatable table-responsive pt-0">
              <table id="detailedTable" class="table">
                <thead class="thead-light"></thead>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

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
@include('partials.delete-modal')
@endsection

@section('scripts')
<script type="text/javascript">
$.ajaxSetup({
  headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});

const URL_SHOW   = '{{ route("conversion.priceList.show") }}';
const URL_EDIT   = '{{ route("conversion.priceList.edit") }}';
const URL_UPDATE = '{{ route("conversion.priceList.update") }}';
const CONV_VALUE = {{ (float) $conversionValue }};

let searchArticle  = document.querySelector('#searchArticle');
let searchDesc     = document.querySelector('#searchDesc');
let searchCustomer = document.querySelector('#searchCustomer');
let search  = document.querySelector('#btnSearch');
let refresh = document.querySelector('a[data-action="reload"]');

function dataSearch() {
  $(".loading-spinner-container").addClass("-show");
  showList(searchArticle.value, searchDesc.value, searchCustomer.value);
}

refresh.addEventListener("click", function () { dataSearch(); });
search.addEventListener("click", function () { dataSearch(); });

const showList = (searchArticle, searchDesc, searchCustomer) => {
  if ($('#detailedTable tr').length > 0) {
    let table = $('#detailedTable').DataTable();
    table.destroy();
    $('#detailedTable tbody > tr').remove();
    $('#detailedTable thead > tr').remove();
  }
  showDataTables({
    tableId: "detailedTable",
    route: "{{ route('conversion.priceList.list') }}",
    kolom: {!! $kolom !!},
    arrColPrint: [1,2,3,4,5,6,7,8,9],
    dataSearch: {
      searchArticle: searchArticle,
      searchDesc: searchDesc,
      searchCustomer: searchCustomer,
    },
    initComplete: function () {
      $(".loading-spinner-container").removeClass("-show");
    },
    orderColumn: [[ 1, 'asc' ]],
    excelFileName: 'price_list'
  });
}

$(function () {
  showList('', '', '');

  $(document).on('click', '.btn-detail', function () { showDetail($(this).data('id')); });
  $(document).on('click', '.btn-edit',   function () { loadEdit($(this).data('id')); });
});

/* ---------- DETAIL ---------- */
function showDetail(id) {
  $('#detailBody').html('<div class="text-center py-3 text-muted">Loading...</div>');
  $('#modalDetail').modal('show');
  $.ajax({ url: URL_SHOW, method: 'POST', dataType: 'json', data: { id: id } })
    .done(function (res) {
      if (res.status != 1) { $('#detailBody').html('<div class="text-danger">'+(res.message||'Gagal')+'</div>'); return; }
      renderDetail(res.fg, res.materials, res.history);
    })
    .fail(function (xhr) { $('#detailBody').html('<div class="text-danger">Error '+xhr.status+'</div>'); });
}

function renderDetail(fg, mats, history) {
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

  let histRows = '';
  (history || []).forEach(h => {
    histRows += `
      <tr>
        <td>${fmtDateTime(h.changed_at)}</td>
        <td>${h.changed_by}</td>
        <td class="text-right">${fmt(h.sales_price_old)}</td>
        <td class="text-right">${fmt(h.material_price_old)}</td>
        <td class="text-right">${fmt(h.margin_old)}</td>
        <td class="text-right">${fmt(h.conversion_value_old)}</td>
        <td class="text-right">${fmt(h.conversion_result_old)}</td>
      </tr>`;
  });

  const html = `
    <div class="row mb-2">
      <div class="col-sm-6">
        <b>${fg.article_alternative_code ?? fg.article_code}</b><br>
        <small class="text-muted">${fg.article_desc ?? ''}</small>
        ${fg.customer_name ? '<div class="text-muted small mt-25">Customer: <b>'+fg.customer_name+'</b></div>' : ''}
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
    <div class="row text-right mb-3">
      <div class="col-sm-6 offset-sm-6">
        <div>Sales Price: <b>${fmt(fg.sales_price)}</b></div>
        <div>Material Price: <b>${fmt(fg.material_price)}</b></div>
        <div>Sales - Material: <b>${fmt(fg.margin)}</b></div>
        <div>Conversion: <b class="text-primary">${fmt(fg.conversion_result)}</b></div>
      </div>
    </div>
    <hr>
    <h6 class="mb-1">Riwayat Perubahan Harga</h6>
    ${(history && history.length) ? `
    <table class="table table-sm table-bordered">
      <thead class="thead-light">
        <tr><th>Diubah Pada</th><th>Oleh</th><th class="text-right">Sales Price (lama)</th>
            <th class="text-right">Material Price (lama)</th><th class="text-right">Margin (lama)</th>
            <th class="text-right">Conv. Value (lama)</th><th class="text-right">Conversion (lama)</th></tr>
      </thead>
      <tbody>${histRows}</tbody>
    </table>` : '<div class="text-muted small">Belum pernah diedit sejak dibuat.</div>'}`;
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
        customer_code: res.fg.customer_code,
        customer_name: res.fg.customer_name,
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
        <label class="col-sm-3 col-form-label">Customer</label>
        <div class="col-sm-4 pt-1 text-muted">${fg.customer_name || fg.customer_code || '-'}</div>
      </div>
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
  $card.data('customer_code', fg.customer_code || null);
  $card.data('customer_name', fg.customer_name || null);
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
function fmtDateTime(d) {
  if (!d) return '-';
  const dt = new Date(String(d).replace(' ', 'T'));
  if (isNaN(dt.getTime())) return d;
  const pad = n => String(n).padStart(2, '0');
  return `${pad(dt.getDate())}-${pad(dt.getMonth()+1)}-${dt.getFullYear()} ${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
}

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
    article_code:  $c.data('fg'),
    bom_code:      $c.data('bom'),
    customer_code: $c.data('customer_code') || null,
    customer_name: $c.data('customer_name') || null,
    sales_price:   parseFloat($c.find('.sales-price').val()) || 0,
    materials:     mats,
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
        $('#detailedTable').DataTable().ajax.reload(null, false);
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