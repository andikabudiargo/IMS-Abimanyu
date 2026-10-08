{{-- Summary cards (gaya conversion report). JS mengisi lewat paintCards(cards). --}}
<div class="row mb-1">
  <div class="col-sm-6 col-xl-3">
    <div class="card border shadow-none mb-1">
      <div class="card-body d-flex align-items-center p-1">
        <div class="avatar bg-light-secondary p-50 mr-1" style="border-radius:8px;">
          <i data-feather="archive" class="font-medium-3 text-secondary"></i>
        </div>
        <div>
          <h4 class="mb-0 font-weight-bolder text-nowrap" id="bg-c-previous">0</h4>
          <small class="text-muted text-nowrap">Previous Expenses</small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card border shadow-none mb-1">
      <div class="card-body d-flex align-items-center p-1">
        <div class="avatar bg-light-dark p-50 mr-1" style="border-radius:8px;">
          <i data-feather="target" class="font-medium-3 text-dark"></i>
        </div>
        <div>
          <h4 class="mb-0 font-weight-bolder text-nowrap" id="bg-c-budget">0</h4>
          <small class="text-muted text-nowrap">Total Budget</small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card border shadow-none mb-1">
      <div class="card-body d-flex align-items-center p-1">
        <div class="avatar bg-light-info p-50 mr-1" style="border-radius:8px;">
          <i data-feather="credit-card" class="font-medium-3 text-info"></i>
        </div>
        <div>
          <h4 class="mb-0 font-weight-bolder text-nowrap" id="bg-c-actual">0</h4>
          <small class="text-muted text-nowrap">Actual Expenses <span id="bg-c-actual-pct" class="font-weight-bold"></span></small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card border shadow-none mb-1" style="border-color:#28c76f33 !important;background:#28c76f0d;">
      <div class="card-body d-flex align-items-center p-1">
        <div class="avatar bg-light-success p-50 mr-1" style="border-radius:8px;">
          <i data-feather="trending-up" class="font-medium-3 text-success"></i>
        </div>
        <div>
          <h4 class="mb-0 font-weight-bolder text-nowrap" id="bg-c-margin">0</h4>
          <small class="text-muted text-nowrap">Margin <span id="bg-c-margin-pct" class="font-weight-bold"></span></small>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  // cards: {previous_expenses, previous_pct, total_budget, budget_growth_pct, actual_expenses, actual_pct, margin, margin_pct}
  function paintCards(c) {
    $('#bg-c-previous').text(nf(c.previous_expenses));
    $('#bg-c-budget').text(nf(c.total_budget));

    $('#bg-c-actual').text(nf(c.actual_expenses));
    $('#bg-c-actual-pct').text('(' + c.actual_pct + '% terpakai)')
      .toggleClass('text-danger', c.actual_pct > 100).toggleClass('text-success', c.actual_pct <= 100);

    $('#bg-c-margin').text(nf(c.margin)).toggleClass('text-danger', c.margin < 0).toggleClass('text-success', c.margin >= 0);
    $('#bg-c-margin-pct').text('(' + c.margin_pct + '% sisa)')
      .toggleClass('text-danger', c.margin_pct < 0).toggleClass('text-success', c.margin_pct >= 0);
  }
</script>
