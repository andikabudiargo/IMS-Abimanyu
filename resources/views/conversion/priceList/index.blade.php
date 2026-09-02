@extends('layouts.app')
@section('content')
<div class="content-body">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="card-title">{{ $title }}</h4>
      <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">
        <i class="feather icon-plus"></i> Create
      </button>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped" id="tblPriceList" style="width:100%">
          <thead>
            <tr>
              <th>No</th><th>PL Number</th><th>Date</th><th>Total FG</th>
              <th>Conv. Value</th><th>Created By</th><th>Action</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- MODAL CREATE -->
<div class="modal fade" id="modalCreate" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <form id="formSave" action="{{ route('conversion.priceList.store') }}" method="POST">
        @csrf
        <input type="hidden" name="items" id="itemsJson">
        <div class="modal-header">
          <h5 class="modal-title">Create Price List</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Finish Goods (bisa pilih beberapa)</label>
           <select id="selFg" class="form-control select2" multiple>
  @foreach($fgList as $fg)
    <option value="{{ $fg->article_code }}">{{ $fg->alternative_code }} - {{ $fg->article_desc }}</option>
  @endforeach
</select>
          </div>
          <div class="alert alert-info py-1">
            Conversion Value: <b>{{ number_format($conversionValue,2) }}</b>
            <input type="hidden" id="convValue" value="{{ $conversionValue }}">
          </div>
          <div id="fgContainer"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" id="btnSave">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
const TOKEN      = '{{ csrf_token() }}';
const URL_DATA   = '{{ route("conversion.priceList.data") }}';
const URL_GETBOM = '{{ route("conversion.priceList.getBom") }}';
const CONV_VALUE = parseFloat($('#convValue').val()) || 0;

$(function () {
  $('#tblPriceList').DataTable({
    processing: true, serverSide: true, ajax: URL_DATA,
    columns: [
      { data: null, render: (d,t,r,m) => m.row + m.settings._iDisplayStart + 1 },
      { data: 'pl_number' },
      { data: 'pl_date' },
      { data: 'total_fg' },
      { data: 'conversion_value' },
      { data: 'created_by' },
      { data: 'action', orderable: false, searchable: false },
    ]
  });

  $('#selFg').select2({ dropdownParent: $('#modalCreate'), width: '100%', placeholder: 'Pilih FG' });

  $('#selFg').on('select2:select', e => loadBom(e.params.data.id));
  $('#selFg').on('select2:unselect', e => $('.fg-card[data-fg="'+e.params.data.id+'"]').remove());
});

function loadBom(code) {
  if ($('.fg-card[data-fg="'+code+'"]').length) return;
  $.post(URL_GETBOM, { _token: TOKEN, article_code: code }, function (res) {
    if (res.status != 1) {
      Swal.fire('Info', res.message, 'warning');
      let v = ($('#selFg').val() || []).filter(x => x != code);
      $('#selFg').val(v).trigger('change');
      return;
    }
    renderCard(res.fg, res.materials);
  }, 'json');
}

function renderCard(fg, mats) {
  let rows = '';
  mats.forEach(m => {
    rows += `
      <tr class="mat-row"
          data-code="${m.article_code}"
          data-alt="${m.alternative_code ?? ''}"
          data-type="${m.article_type}"
          data-source="${m.source}" data-qty="${m.qty}">
        <td>${m.alternative_code ?? ''}</td>
        <td>${m.article_name ?? ''}</td>
        <td><span class="badge badge-${m.article_type==='RMNP'?'secondary':'success'}">${m.article_type}</span></td>
        <td class="text-right">${m.qty}</td>
        <td><input type="number" step="any" class="form-control form-control-sm unit-price text-right"
             value="${m.unit_price}" ${m.article_type==='RMNP'?'readonly':''}></td>
        <td class="text-right line-total">0</td>
      </tr>`;
  });

  const html = `
  <div class="card fg-card border"
       data-fg="${fg.article_code}"
       data-alt="${fg.alternative_code ?? ''}"
       data-bom="${fg.bom_code}">
    <div class="card-header d-flex justify-content-between align-items-center py-1">
      <b>${fg.alternative_code ?? fg.article_code} - ${fg.article_name}</b>
      <button type="button" class="btn btn-sm btn-outline-danger btn-remove">&times;</button>
    </div>
    <div class="card-body">
      <div class="form-group row">
        <label class="col-sm-3 col-form-label">Sales Price (manual)</label>
        <div class="col-sm-4">
          <input type="number" step="any" class="form-control sales-price text-right" value="0">
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
  $('#fgContainer').append(html);
  const $card = $('#fgContainer .fg-card').last();
  recalc($card);
  $card.find('.unit-price, .sales-price').on('input', () => recalc($card));
  $card.find('.btn-remove').on('click', function () {
    const code = $card.data('fg');
    $card.remove();
    let v = ($('#selFg').val() || []).filter(x => x != code);
    $('#selFg').val(v).trigger('change');
  });
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

function fmt(n) { return (n || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }); }

$('#btnSave').on('click', function () {
  let items = [];
  $('.fg-card').each(function () {
    const $c = $(this), mats = [];
    $c.find('.mat-row').each(function () {
      mats.push({
        article_code: $(this).data('code'),
        article_type: $(this).data('type'),
        source:       $(this).data('source'),
        qty:          parseFloat($(this).data('qty')) || 0,
        unit_price:   parseFloat($(this).find('.unit-price').val()) || 0,
      });
    });
    items.push({
      article_code: $c.data('fg'),
      bom_code:     $c.data('bom'),
      sales_price:  parseFloat($c.find('.sales-price').val()) || 0,
      materials:    mats,
    });
  });
  if (!items.length) { Swal.fire('Info', 'Pilih minimal 1 FG', 'warning'); return; }
  $('#itemsJson').val(JSON.stringify(items));
  $('#formSave').submit();
});
</script>
@endsection