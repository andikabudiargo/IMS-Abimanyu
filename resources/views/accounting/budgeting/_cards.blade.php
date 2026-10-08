{{-- Summary cards. JS mengisi #bg-c-* . --}}
<div class="row mb-2">
  <div class="col-6 col-md-3">
    <div class="card">
      <div class="card-body py-2 px-3">
        <small class="text-muted d-block">Previous Expenses</small>
        <h5 class="mb-0" id="bg-c-previous">0</h5>
        <small class="text-muted" id="bg-c-previous-pct"></small>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card">
      <div class="card-body py-2 px-3">
        <small class="text-muted d-block">Total Budget</small>
        <h5 class="mb-0" id="bg-c-budget">0</h5>
        <small class="text-muted" id="bg-c-budget-pct"></small>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card">
      <div class="card-body py-2 px-3">
        <small class="text-muted d-block">Actual Expenses</small>
        <h5 class="mb-0" id="bg-c-actual">0</h5>
        <small class="text-muted" id="bg-c-actual-pct"></small>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card">
      <div class="card-body py-2 px-3">
        <small class="text-muted d-block">Margin</small>
        <h5 class="mb-0" id="bg-c-margin">0</h5>
        <small class="text-muted" id="bg-c-margin-pct"></small>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  // cards: {previous_expenses, previous_pct, total_budget, budget_growth_pct, actual_expenses, actual_pct, margin, margin_pct}
  function paintCards(c) {
    $('#bg-c-previous').text(nf(c.previous_expenses));
    $('#bg-c-previous-pct').text(c.previous_pct + '% dari total budget');
    $('#bg-c-budget').text(nf(c.total_budget));
    $('#bg-c-budget-pct').text((c.budget_growth_pct >= 0 ? '+' : '') + c.budget_growth_pct + '% vs previous');
    $('#bg-c-actual').text(nf(c.actual_expenses));
    $('#bg-c-actual-pct').text(c.actual_pct + '% dari budget terpakai');
    $('#bg-c-margin').text(nf(c.margin));
    $('#bg-c-margin').toggleClass('text-danger', c.margin < 0);
    $('#bg-c-margin-pct').text(c.margin_pct + '% sisa budget');
  }
</script>
