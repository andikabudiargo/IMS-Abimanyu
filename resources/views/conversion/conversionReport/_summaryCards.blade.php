{{--
  Kartu ringkasan Conversion Report (dipakai create/show/edit).
  Var opsional (kalau tidak diisi -> tampil "0", biasanya diisi via JS by-id):
    $cArticle, $cQty, $cConversion, $cPainting, $cNonPainting
--}}
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
          <h4 class="mb-0 font-weight-bolder" id="sumTotalQty">{{ $cQty ?? '0' }}</h4>
          <small class="text-muted">Total Qty Kirim</small>
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
          <small class="text-muted">Total Conversion</small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl">
    <div class="card border shadow-none mb-1">
      <div class="card-body d-flex align-items-center p-1">
        <div class="avatar bg-light-warning p-50 mr-1" style="border-radius:8px;">
          <i data-feather="edit-3" class="font-medium-3 text-warning"></i>
        </div>
        <div>
          <h4 class="mb-0 font-weight-bolder" id="sumConvPainting">{{ $cPainting ?? '0' }}</h4>
          <small class="text-muted">Konversi Painting <span class="text-muted">(PCS/SET)</span></small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl">
    <div class="card border shadow-none mb-1">
      <div class="card-body d-flex align-items-center p-1">
        <div class="avatar bg-light-secondary p-50 mr-1" style="border-radius:8px;">
          <i data-feather="box" class="font-medium-3 text-secondary"></i>
        </div>
        <div>
          <h4 class="mb-0 font-weight-bolder" id="sumConvNonPainting">{{ $cNonPainting ?? '0' }}</h4>
          <small class="text-muted">Konversi Non Painting</small>
        </div>
      </div>
    </div>
  </div>
</div>
