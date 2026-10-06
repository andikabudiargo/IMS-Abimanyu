{{--
  Kartu ringkasan Conversion Report (dipakai create/show/edit).
  Var opsional (kalau tidak diisi -> tampil "0", biasanya diisi via JS by-id):
    $cArticle, $cQty, $cConversion, $cPainting, $cNonPainting
  Target & persentase dihitung dari $details (scope parent) bila ada; selain itu diisi
  via window.renderTargetCards(rows) (JS).
--}}
@php
  $tQty   = isset($details) ? (float) $details->sum('qty_target') : 0;
  $tConv  = isset($details) ? (float) $details->sum('target_conversion') : 0;
  $sQty   = isset($details) ? (float) $details->sum('total_qty') : 0;
  $sPaint = isset($details) ? (float) $details->filter(fn ($d) => $d->is_painting)->sum('conversion') : 0;
  $pQty   = $tQty > 0 ? number_format($sQty / $tQty * 100, 1) . '%' : '-';
  $pPaint = $tConv > 0 ? number_format($sPaint / $tConv * 100, 1) . '%' : '-';
  $sConv  = isset($details) ? (float) $details->sum('conversion') : 0;
  $dev    = fn ($a, $b) => $b > 0 ? (($a - $b) >= 0 ? '+' : '') . number_format(($a - $b) / $b * 100, 1) . '%' : '-';
  $devCls = fn ($a, $b) => $a >= $b ? 'text-success' : 'text-danger';
@endphp
<div class="row mb-1">
  <div class="col-sm-6 col-xl">
    <div class="card border shadow-none mb-1">
      <div class="card-body d-flex align-items-center p-1">
        <div class="avatar bg-light-primary p-50 mr-1" style="border-radius:8px;">
          <i data-feather="package" class="font-medium-3 text-primary"></i>
        </div>
        <div>
          <h4 class="mb-0 font-weight-bolder" id="sumTotalArticle">{{ $cArticle ?? '0' }}</h4>
          <small class="text-muted">Total Article</small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl">
    <div class="card border shadow-none mb-1">
      <div class="card-body d-flex align-items-center p-1">
        <div class="avatar bg-light-info p-50 mr-1" style="border-radius:8px;">
          <i data-feather="truck" class="font-medium-3 text-info"></i>
        </div>
        <div>
          <h4 class="mb-0 font-weight-bolder text-nowrap"><span id="sumTotalQty">{{ $cQty ?? '0' }}</span> <small class="text-info font-weight-bold" id="sumPctQty">({{ $pQty }})</small></h4>
          <small class="text-muted text-nowrap">Total Qty Kirim</small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl">
    <div class="card border shadow-none mb-1">
      <div class="card-body d-flex align-items-center p-1">
        <div class="avatar bg-light-dark p-50 mr-1" style="border-radius:8px;">
          <i data-feather="target" class="font-medium-3 text-dark"></i>
        </div>
        <div>
          <h4 class="mb-0 font-weight-bolder text-nowrap"><span id="sumTargetQty">{{ number_format($tQty, 2) }}</span> <small class="font-weight-bold {{ $devCls($sQty, $tQty) }}" id="sumDevQty">({{ $dev($sQty, $tQty) }})</small></h4>
          <small class="text-muted">Target Qty Kirim</small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl">
    <div class="card border shadow-none mb-1">
      <div class="card-body d-flex align-items-center p-1">
        <div class="avatar bg-light-secondary p-50 mr-1" style="border-radius:8px;">
          <i data-feather="volume-x" class="font-medium-3 text-secondary"></i>
        </div>
        <div>
          <h4 class="mb-0 font-weight-bolder" id="sumConvNonPainting">{{ $cNonPainting ?? '0' }}</h4>
          <small class="text-muted">Non Painting</small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl">
    <div class="card border shadow-none mb-1">
      <div class="card-body d-flex align-items-center p-1">
        <div class="avatar bg-light-warning p-50 mr-1" style="border-radius:8px;">
          <i data-feather="volume-2" class="font-medium-3 text-warning"></i>
        </div>
        <div>
          <h4 class="mb-0 font-weight-bolder text-nowrap"><span id="sumConvPainting">{{ $cPainting ?? '0' }}</span> <small class="text-warning font-weight-bold" id="sumPctPainting">({{ $pPaint }})</small></h4>
          <small class="text-muted text-nowrap">Painting</small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl">
    <div class="card border shadow-none mb-1">
      <div class="card-body d-flex align-items-center p-1">
        <div class="avatar bg-light-dark p-50 mr-1" style="border-radius:8px;">
          <i data-feather="flag" class="font-medium-3 text-dark"></i>
        </div>
        <div>
          <h4 class="mb-0 font-weight-bolder text-nowrap"><span id="sumTargetConversion">{{ number_format($tConv, 2) }}</span> <small class="font-weight-bold {{ $devCls($sConv, $tConv) }}" id="sumDevConv">({{ $dev($sConv, $tConv) }})</small></h4>
          <small class="text-muted">Target Konversi</small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl">
    <div class="card border shadow-none mb-1" style="border-color:#28c76f33 !important;background:#28c76f0d;">
      <div class="card-body d-flex align-items-center p-1">
        <div class="avatar bg-light-success p-50 mr-1" style="border-radius:8px;">
          <i data-feather="trending-up" class="font-medium-3 text-success"></i>
        </div>
        <div>
          <h4 class="mb-0 font-weight-bolder text-success" id="sumTotalConversion">{{ $cConversion ?? '0' }}</h4>
          <small class="text-muted">Total Konversi</small>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Isi kartu target + persentase dari rows (create/edit-NEW preview & filter range di show).
  window.renderTargetCards = function (rows) {
    const f = (n) => (parseFloat(n) || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const sum = (fn) => rows.reduce((a, r) => a + (parseFloat(fn(r)) || 0), 0);
    const tQty = sum(r => r.qty_target), tConv = sum(r => r.target_conversion);
    const qty = sum(r => r.total_qty), paint = sum(r => r.is_painting ? r.conversion : 0);
    const pct = (a, b) => '(' + (b > 0 ? f(a / b * 100) + '%' : '-') + ')';
    $('#sumTargetQty').text(f(tQty));
    $('#sumTargetConversion').text(f(tConv));
    $('#sumPctQty').text(pct(qty, tQty));
    $('#sumPctPainting').text(pct(paint, tConv));
    const conv = sum(r => r.conversion);
    const dev = (a, b, id) => $(id).text('(' + (b > 0 ? (a >= b ? '+' : '') + f((a - b) / b * 100) + '%' : '-') + ')')
      .toggleClass('text-success', a >= b).toggleClass('text-danger', a < b);
    dev(qty, tQty, '#sumDevQty');
    dev(conv, tConv, '#sumDevConv');
  };
</script>
