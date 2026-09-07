@extends('layouts.app')
@section('title', 'Create Price List')
@section('content')
@include('layouts.breadcrumb')

<style>
  .step-nav { display:flex; align-items:center; margin-bottom:1.5rem; }
  .step-nav .step { display:flex; align-items:center; color:#b9b9c3; font-weight:600; }
  .step-nav .step.active { color:#7367f0; }
  .step-nav .step.done { color:#28c76f; }
  .step-nav .circle {
    width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    border:2px solid #b9b9c3; margin-right:.5rem; font-size:13px; flex-shrink:0;
  }
  .step-nav .step.active .circle { border-color:#7367f0; background:#7367f0; color:#fff; }
  .step-nav .step.done .circle { border-color:#28c76f; background:#28c76f; color:#fff; }
  .step-nav .line { flex:1; height:2px; background:#e0e0e5; margin:0 1rem; }

  .fg-empty-state { border:2px dashed #d8d6de; border-radius:.5rem; padding:2.5rem 1rem; text-align:center; color:#b9b9c3; }

  .fg-card { border:1px solid #e0e0e5; border-radius:.5rem; margin-bottom:1rem; overflow:hidden; }
  .fg-card .fg-card-header {
    padding:.75rem 1rem; background:#f8f8f8; cursor:pointer;
    display:flex; justify-content:between; align-items:center;
  }
  .fg-card .fg-card-header:hover { background:#f1f1f2; }
  .fg-card.incomplete .fg-card-header { border-left:3px solid #ff9f43; }
  .fg-card.complete .fg-card-header { border-left:3px solid #28c76f; }
  .fg-card-body { padding:1rem; }
  .fg-status-badge { font-size:11px; }

  .summary-bar {
    position: sticky; bottom:0; left:0; right:0; background:#fff;
    border-top:1px solid #e0e0e5; padding:.85rem 1.25rem; margin:0 -1.5rem -1.5rem;
    display:flex; justify-content:space-between; align-items:center; z-index:10;
    box-shadow: 0 -2px 8px rgba(0,0,0,.04);
  }
  .summary-bar .summary-count { font-weight:600; }
  .summary-bar .summary-count .num { color:#7367f0; font-size:1.1rem; }

  .conv-info-box { background:#f1effe; border:1px solid #d8d3fc; border-radius:.5rem; padding:.75rem 1rem; }

  .unit-price[readonly] { background:#f8f8f8; }
</style>

<div class="content-body">

  <div class="d-flex align-items-center mb-1">
    <a href="{{ route('conversion.priceList.index') }}" class="btn btn-icon btn-flat-secondary mr-1">
      <i class="feather icon-arrow-left"></i>
    </a>
    <h4 class="mb-0">Create Price List</h4>
  </div>
  <p class="text-muted mb-2">Pilih Finish Goods, lalu atur harga jual tiap FG sebelum disimpan.</p>

  <div class="card">
    <div class="card-body">

      <!-- STEP INDICATOR -->
      <div class="step-nav">
        <div class="step active" id="stepNav1">
          <div class="circle">1</div> Pilih Finish Goods
        </div>
        <div class="line"></div>
        <div class="step" id="stepNav2">
          <div class="circle">2</div> Atur Harga &amp; Simpan
        </div>
      </div>

      <form id="formSave" action="{{ route('conversion.priceList.store') }}" method="POST">
        @csrf
        <input type="hidden" name="items" id="itemsJson">

        <!-- ===================== STEP 1: PILIH FG ===================== -->
        <div id="panelStep1">
          <div class="form-group">
            <label class="font-weight-bold">Cari &amp; Pilih Finish Goods</label>
            <select id="selFg" class="form-control select2" multiple style="width:100%">
              @foreach($fgList as $fg)
                <option value="{{ $fg->article_code }}">{{ $fg->article_alternative_code }} - {{ $fg->article_desc }}</option>
              @endforeach
            </select>
            <small class="text-muted">Ketik kode atau nama produk. Bisa pilih lebih dari satu.</small>
          </div>

          <div class="conv-info-box mb-3 d-flex justify-content-between align-items-center">
            <div>
              <i class="feather icon-info mr-1"></i>
              Conversion Value yang dipakai untuk perhitungan: <b>{{ number_format($conversionValue,2) }}</b>
            </div>
            <input type="hidden" id="convValue" value="{{ $conversionValue }}">
          </div>

          <div id="fgSelectedList"></div>

          <div id="fgEmptyState" class="fg-empty-state">
            <i class="feather icon-package" style="font-size:32px;"></i>
            <p class="mt-2 mb-0">Belum ada Finish Goods yang dipilih.<br>Gunakan kolom pencarian di atas untuk mulai menambahkan.</p>
          </div>

          <div class="text-right mt-3">
            <button type="button" class="btn btn-primary" id="btnToStep2" disabled>
              Lanjut ke Atur Harga <i class="feather icon-arrow-right ml-50"></i>
            </button>
          </div>
        </div>

        <!-- ===================== STEP 2: ATUR HARGA ===================== -->
        <div id="panelStep2" style="display:none">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <button type="button" class="btn btn-flat-secondary btn-sm" id="btnBackToStep1">
              <i class="feather icon-arrow-left mr-50"></i> Kembali pilih FG
            </button>
            <div>
              <button type="button" class="btn btn-flat-primary btn-sm" id="btnExpandAll">Buka Semua</button>
              <button type="button" class="btn btn-flat-secondary btn-sm" id="btnCollapseAll">Tutup Semua</button>
            </div>
          </div>

          <div class="alert alert-warning py-2 px-3" id="incompleteWarning" style="display:none">
            <i class="feather icon-alert-triangle mr-1"></i>
            <span id="incompleteWarningText"></span>
          </div>

          <div id="fgContainer"></div>
        </div>
      </form>
    </div>

    <!-- STICKY SUMMARY / ACTION BAR -->
    <div class="summary-bar">
      <div class="summary-count">
        <span class="num" id="sumCount">0</span> Finish Goods dipilih
        <span class="text-muted font-weight-normal ml-2" id="sumMargin"></span>
      </div>
      <div>
        <a href="{{ route('conversion.priceList.index') }}" class="btn btn-outline-secondary mr-1">Batal</a>
        <button type="button" class="btn btn-primary" id="btnSave" style="display:none">
          <i class="feather icon-save mr-50"></i> Simpan Price List
        </button>
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

const URL_GETBOM = '{{ route("conversion.priceList.getBom") }}';
const CONV_VALUE = parseFloat($('#convValue').val()) || 0;

/* fgState: code -> { fg, materials, loaded, complete } used to know save-readiness */
const fgState = {};

$(function () {
  $('#selFg').select2({ width: '100%', placeholder: 'Cari kode / nama Finish Goods...' });
  $('#selFg').on('select2:select', e => addFg(e.params.data.id));
  $('#selFg').on('select2:unselect', e => removeFg(e.params.data.id));

  $('#btnToStep2').on('click', goToStep2);
  $('#btnBackToStep1').on('click', goToStep1);

  $('#btnExpandAll').on('click', () => $('.fg-card-body').slideDown(150));
  $('#btnCollapseAll').on('click', () => $('.fg-card-body').slideUp(150));

  $('#btnSave').on('click', doSave);
});

/* ---------- STEP NAVIGATION ---------- */
function goToStep2() {
  if (!Object.keys(fgState).length) return;
  $('#panelStep1').hide();
  $('#panelStep2').show();
  $('#btnSave').show();
  $('#stepNav1').removeClass('active').addClass('done');
  $('#stepNav2').addClass('active');
  refreshIncompleteWarning();
}
function goToStep1() {
  $('#panelStep2').hide();
  $('#panelStep1').show();
  $('#btnSave').hide();
  $('#stepNav2').removeClass('active');
  $('#stepNav1').addClass('active').removeClass('done');
}

/* ---------- ADD / REMOVE FG (Step 1 preview list) ---------- */
function addFg(code) {
  if (fgState[code]) return;
  fgState[code] = { loading: true };
  updateEmptyState();
  renderStep1Preview();

  $.ajax({ url: URL_GETBOM, method: 'POST', dataType: 'json', data: { article_code: code } })
    .done(function (res) {
      if (res.status != 1) {
        Swal.fire('Info', res.message || 'BOM tidak ditemukan untuk FG ini', 'warning');
        delete fgState[code];
        let v = ($('#selFg').val() || []).filter(x => x != code);
        $('#selFg').val(v).trigger('change.select2');
        renderStep1Preview();
        updateEmptyState();
        return;
      }
      fgState[code] = { fg: res.fg, materials: res.materials, loading: false, salesPrice: 0 };
      renderStep1Preview();
      renderCard(code);
      updateSummary();
    })
    .fail(function (xhr) {
      console.error('getBom error', xhr.status, xhr.responseText);
      Swal.fire('Error', 'Gagal mengambil data BOM (' + xhr.status + '). Cek console.', 'error');
      delete fgState[code];
      let v = ($('#selFg').val() || []).filter(x => x != code);
      $('#selFg').val(v).trigger('change.select2');
      renderStep1Preview();
      updateEmptyState();
    });
}

function removeFg(code) {
  delete fgState[code];
  $('.fg-card[data-fg="' + code + '"]').remove();
  renderStep1Preview();
  updateEmptyState();
  updateSummary();
  if (!Object.keys(fgState).length) goToStep1();
}

function updateEmptyState() {
  const has = Object.keys(fgState).length > 0;
  $('#fgEmptyState').toggle(!has);
  $('#btnToStep2').prop('disabled', !has);
}

function renderStep1Preview() {
  let html = '';
  Object.keys(fgState).forEach(code => {
    const s = fgState[code];
    if (s.loading) {
      html += `<div class="d-flex align-items-center border rounded p-2 mb-2">
                 <div class="spinner-border spinner-border-sm text-primary mr-2"></div> Memuat BOM untuk ${code}...
               </div>`;
    } else {
      html += `<div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2">
                 <div><b>${s.fg.article_alternative_code ?? s.fg.article_code}</b> - ${s.fg.article_desc ?? ''}
                   <span class="badge badge-light-secondary ml-1">${s.materials.length} material</span>
                 </div>
                 <i class="feather icon-check-circle text-success"></i>
               </div>`;
    }
  });
  $('#fgSelectedList').html(html);
}

/* ---------- STEP 2: CARD PER FG ---------- */
function renderCard(code) {
  const s = fgState[code];
  const fg = s.fg;
  let rows = '';
  s.materials.forEach(m => {
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
  <div class="fg-card incomplete" data-fg="${fg.article_code}" data-bom="${fg.bom_code}">
    <div class="fg-card-header" data-toggle-body>
      <div class="flex-grow-1">
        <b>${fg.article_alternative_code ?? fg.article_code}</b> - ${fg.article_desc ?? fg.article_name ?? ''}
        <span class="badge badge-light-warning fg-status-badge ml-1">Belum ada harga jual</span>
      </div>
      <i class="feather icon-chevron-down"></i>
    </div>
    <div class="fg-card-body">
      <div class="form-group row align-items-center">
        <label class="col-sm-3 col-form-label font-weight-bold">Sales Price <span class="text-danger">*</span></label>
        <div class="col-sm-4">
          <input type="number" step="any" class="form-control sales-price text-right" placeholder="Masukkan harga jual" value="0">
        </div>
        <div class="col-sm-5 text-muted small">Wajib diisi sebelum disimpan.</div>
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

  const $card = $('#fgContainer .fg-card[data-fg="' + code + '"]');
  recalc($card);
  $card.find('.unit-price, .sales-price').on('input', () => { recalc($card); refreshIncompleteWarning(); updateSummary(); });
  $card.find('[data-toggle-body]').on('click', function () {
    $card.find('.fg-card-body').slideToggle(150);
    $(this).find('.feather').toggleClass('icon-chevron-down icon-chevron-up');
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

  const complete = sales > 0;
  $card.toggleClass('incomplete', !complete).toggleClass('complete', complete);
  $card.find('.fg-status-badge')
    .toggleClass('badge-light-warning', !complete)
    .toggleClass('badge-light-success', complete)
    .text(complete ? 'Siap disimpan' : 'Belum ada harga jual');
}

/* ---------- WARNINGS / SUMMARY ---------- */
function refreshIncompleteWarning() {
  const incomplete = $('#fgContainer .fg-card.incomplete').length;
  $('#incompleteWarning').toggle(incomplete > 0);
  if (incomplete > 0) {
    $('#incompleteWarningText').text(incomplete + ' Finish Goods belum diisi Sales Price. Lengkapi sebelum menyimpan.');
  }
}

function updateSummary() {
  const codes = Object.keys(fgState).filter(c => !fgState[c].loading);
  $('#sumCount').text(codes.length);
  let totalMargin = 0;
  $('#fgContainer .fg-card').each(function () {
    totalMargin += parseFloat($(this).find('.margin').text().replace(/\./g, '').replace(',', '.')) || 0;
  });
  $('#sumMargin').text(codes.length ? '• Total margin: ' + fmt(totalMargin) : '');
}

/* ---------- HELPERS ---------- */
function fmt(n) { return (parseFloat(n) || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }); }

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

/* ---------- SAVE ---------- */
function doSave() {
  let items = [];
  let incomplete = 0;
  $('#fgContainer .fg-card').each(function () {
    const item = collectCard($(this));
    if (item.sales_price <= 0) incomplete++;
    items.push(item);
  });

  if (!items.length) {
    Swal.fire('Info', 'Pilih minimal 1 Finish Goods', 'warning');
    return;
  }
  if (incomplete > 0) {
    Swal.fire('Belum Lengkap', incomplete + ' Finish Goods belum diisi Sales Price.', 'warning');
    return;
  }

  Swal.fire({
    title: 'Simpan Price List?',
    text: items.length + ' Finish Goods akan disimpan.',
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