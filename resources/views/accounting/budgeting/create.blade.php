@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

<div class="card">
  <div class="card-header"><h4 class="card-title">{{ $title }}</h4></div>
  <div class="card-body">
    <form class="needs-validation" novalidate onsubmit="return false;">
      <div class="form-row">
        <div class="form-group col-md-5">
          <label class="form-label" for="budgetingNumber">Budgeting Number</label>
          <input type="text" id="budgetingNumber" class="form-control" disabled placeholder="Auto-generate (BGT-ASN-{{ $fiscalYearDefault }}-...)" />
        </div>
        <div class="form-group col-md-3">
          <label class="form-label" for="fiscalYear">Fiscal Year</label>
          <select class="form-control" id="fiscalYear">
            @foreach($fiscalYears as $fy)
              <option value="{{ $fy }}" {{ $fy == $fiscalYearDefault ? 'selected' : '' }}>{{ $fy }}</option>
            @endforeach
          </select>
        </div>
      </div>
        <div class="form-row">
            <div class="form-group col-md-8">
          <label class="form-label" for="description">Description</label>
          <input type="text" id="description" class="form-control" />
        </div>
      </div>
      <div class="form-row">
 <div class="form-group col-md-"3>
          <label class="form-label" for="dept">Department</label>
          <select class="select2 form-control" id="dept" data-placeholder="Pilih department">
            <option value=""></option>
            @foreach($depts as $val)
              <option value="{{ $val->code }}">{{ $val->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-5">
          <label class="form-label" for="coa">COA</label>
          <select class="select2 form-control" id="coa" multiple data-placeholder="Semua COA (5000 - 8000)">
            @foreach($accounts as $val)
              <option value="{{ $val->account }}">
                {{ $val->account }} - {{ $val->description }}@if(strtoupper($val->acc_header) == 'HEADER') [HEADER]@endif
              </option>
            @endforeach
          </select>
        </div>
</div>
<div class="form-row">
        <div class="form-group col-md-4">
          <label class="form-label" for="previous">Previous Period</label>
          <input type="text" id="previous" class="form-control" placeholder="MM-YYYY to MM-YYYY" autocomplete="off" />
        </div>
        <div class="form-group col-md-4">
          <label class="form-label" for="budget">Budget Period</label>
          <input type="text" id="budget" class="form-control" placeholder="MM-YYYY to MM-YYYY" autocomplete="off" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="form-label" for="note">Note</label>
          <textarea id="note" rows="4" class="form-control"></textarea>
        </div>
      </div>
      <div class="form-row">
        <div class="col-12">
          <button type="button" class="btn btn-primary" id="btnPull">Generate</button>
        </div>
      </div>
    </form>
  </div>
</div>

<section id="bg-result" style="display:none">
  @include('accounting.budgeting._cards')

  <div class="card">
    <div class="card-header">
      <div>
        <h4 class="card-title">Detail Budgeting</h4>
        <small class="text-muted" id="bg-title"></small>
      </div>
      <div class="heading-elements">
        <button type="button" class="btn btn-success btn-sm" id="btnSave">Save Budgeting</button>
      </div>
    </div>
    <div class="card-body pt-0">
      <small class="text-muted d-block mb-1">
        Average = total debit &divide; jumlah bulan yang debitnya tidak nol. Budget = Average &minus; Cost Reduction.
        Final Budget mengikuti Budget sampai diedit manual. Klik nilai Debit / Realisasi untuk melihat rincian transaksi.
      </small>
      <div class="table-responsive bg-scroll">
        <table id="bgTable" class="table table-sm">
          <thead class="thead-light" id="bg-thead"></thead>
          <tbody id="bg-body"></tbody>
        </table>
      </div>
    </div>
  </div>
</section>
@include('accounting.budgeting._txmodal')
@endsection

@section('styles')
<style>
  .bg-scroll { max-height:65vh; overflow:auto; }
  #bgTable { table-layout:auto; }
  #bgTable th, #bgTable td { white-space:nowrap; vertical-align:middle; padding:.6rem .9rem; }
  #bgTable thead th { position:sticky; top:0; z-index:3; background:#f3f2f7; box-shadow:inset 0 -1px 0 #dee2e6; }
  #bgTable tbody tr.bg-group > td { background:#e9ecef; color:#1f2937; font-weight:700; border-top:2px solid #9ca3af; }
  .bg-acc { font-weight:600; color:#1f3a5f; }
  #bgTable th.col-cr, #bgTable td.col-cr { min-width:120px; }
  #bgTable th.col-final, #bgTable td.col-final { min-width:170px; }
  #bgTable tbody tr.bg-row .bg-cr { min-width:60px; }
  #bgTable tbody tr.bg-row .bg-final { min-width:140px; font-weight:600; }
  .bg-link { cursor:pointer; text-decoration:underline; }
  .bg-pos { color:#28c76f; font-weight:600; }
  .bg-neg { color:#ea5455; font-weight:600; }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
  const CR_DEFAULT = {{ (float) $crDefault }};
  const PREVIOUS_DEFAULT = {!! json_encode($previousDefault) !!};
  const BUDGET_DEFAULT = {!! json_encode($budgetDefault) !!};

  const nf = (v) => new Intl.NumberFormat('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2}).format(v || 0);
  const esc = (s) => $('<div>').text(s === null || s === undefined ? '' : s).html();
  const round2 = (v) => Math.round((v + Number.EPSILON) * 100) / 100;
  const parseId = (s) => {
    const t = String(s).trim().replace(/[^\d,.\-]/g, '').replace(/\./g, '').replace(',', '.');
    const n = parseFloat(t);
    return isNaN(n) ? null : n;
  };
  const monthLabel = (ym) => { const [y, m] = ym.split('-'); return ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][parseInt(m,10)-1] + ' ' + y; };
  const ymToDate = (ym, end) => end ? ym + '-' + new Date(ym.split('-')[0], ym.split('-')[1], 0).getDate() : ym + '-01';

  let rows = [];
  let months = [];
  let deptCode = '';
  let previousRange = null, budgetRange = null;

  $('#previous').flatpickr({ mode: 'range', dateFormat: 'm-Y', defaultDate: PREVIOUS_DEFAULT.split(' to '), locale: { rangeSeparator: ' to ' } });
  $('#budget').flatpickr({ mode: 'range', dateFormat: 'm-Y', defaultDate: BUDGET_DEFAULT.split(' to '), locale: { rangeSeparator: ' to ' } });

  const updateNumberPlaceholder = () => {
    const fy = $('#fiscalYear').val();
    const dc = $('#dept').val();
    $('#budgetingNumber').attr('placeholder', 'Auto-generate (BGT-ASN-' + fy + '-' + (dc || '...') + ')');
  };
  $('#fiscalYear, #dept').on('change', updateNumberPlaceholder);
  updateNumberPlaceholder();

  $('#btnPull').click(function () {
    const dept = $('#dept').val();
    if (!dept) { alert('Department wajib dipilih.'); return; }

    $('.loading-spinner-container').addClass('-show');
    $.get("{{ route('budgeting.data') }}", {
      previous: $('#previous').val(), budget: $('#budget').val(), dept: dept, coa: $('#coa').val()
    }).done(function (res) {
      deptCode = dept;
      months = res.months;
      rows = res.rows.map(function (r) {
        r.cost_reduction = CR_DEFAULT;
        r.budget = round2(r.average * (1 - r.cost_reduction / 100));
        r.final_budget = r.budget;
        return r;
      });
      $('#bg-title').text('Department: ' + res.dept_name + '  |  Previous: ' + res.previous_text + '  |  Budget Period: ' + res.budget_text);
      buildHead();
      render();
      paintCards(computeCards());
      $('#bg-result').show();
      if (window.feather) feather.replace({ width: 14, height: 14 });
    }).fail(function (xhr) {
      alert((xhr.responseJSON && xhr.responseJSON.error) || 'Gagal memuat data.');
    }).always(function () {
      $('.loading-spinner-container').removeClass('-show');
    });
  });

  function buildHead() {
    let r1 = '<tr>'
      + '<th rowspan="2">Account</th><th rowspan="2">Name</th>'
      + '<th rowspan="2" class="text-right">Debit</th><th rowspan="2" class="text-right">Average</th>'
      + '<th rowspan="2" class="col-cr">Cost Reduction</th>'
      + '<th rowspan="2" class="text-right">Proposed Budget</th>'
      + '<th rowspan="2" class="col-final">Final Budget</th>'
      + '<th colspan="' + (months.length + 3) + '" class="text-center">Budget Period</th></tr>';
    let r2 = '<tr>';
    months.forEach((m) => r2 += '<th class="text-right">' + monthLabel(m) + '</th>');
    r2 += '<th class="text-right">Total</th><th class="text-right">Selisih</th><th class="text-right">Realisasi %</th></tr>';
    $('#bg-thead').html(r1 + r2);
  }

  function rowBudgetTotal(r) { return round2(r.final_budget * months.length); }
  function rowSelisih(r) { return round2(rowBudgetTotal(r) - r.realisasi_total); }
  function rowPct(r) { const bt = rowBudgetTotal(r); return bt > 0 ? round2(r.realisasi_total / bt * 100) : 0; }

  function render() {
    let html = '';
    if (!rows.length) {
      html = '<tr><td colspan="' + (7 + months.length + 3) + '" class="text-center text-muted py-2">Tidak ada data pada filter ini.</td></tr>';
    }
    rows.forEach(function (r, ri) {
      html += '<tr class="bg-row" data-r="' + ri + '">'
            + '<td class="bg-acc">' + esc(r.account) + '</td><td>' + esc(r.nama_akun) + '</td>'
            + '<td class="text-right"><a class="bg-link bg-debit"></a></td>'
            + '<td class="text-right">' + nf(r.average) + '</td>'
            + '<td class="col-cr"><div class="input-group input-group-sm"><input type="number" class="form-control text-right bg-cr" min="0" max="100" step="0.01" value="' + r.cost_reduction + '"><div class="input-group-append"><span class="input-group-text">%</span></div></div></td>'
            + '<td class="text-right bg-budget">' + nf(r.budget) + '</td>'
            + '<td class="col-final"><input type="text" inputmode="decimal" class="form-control form-control-sm text-right bg-final" value="' + nf(r.final_budget) + '"></td>';
      months.forEach(function (m) {
        html += '<td class="text-right"><a class="bg-link bg-real" data-m="' + m + '"></a></td>';
      });
      html += '<td class="text-right bg-total"></td><td class="text-right bg-selisih"></td><td class="text-right bg-pct"></td></tr>';
    });
    $('#bg-body').html(html);
    rows.forEach((r, ri) => paintRow(ri));
  }

  function paintRow(ri) {
    const r = rows[ri];
    const $tr = $('#bg-body tr.bg-row[data-r="' + ri + '"]');
    $tr.find('.bg-debit').text(nf(r.debit));
    $tr.find('.bg-budget').text(nf(r.budget));
    $tr.find('.bg-total').text(nf(r.realisasi_total));
    const selisih = rowSelisih(r), pct = rowPct(r);
    $tr.find('.bg-selisih').text(nf(selisih)).removeClass('bg-pos bg-neg').addClass(selisih >= 0 ? 'bg-pos' : 'bg-neg');
    $tr.find('.bg-pct').text(pct + '%').removeClass('bg-pos bg-neg').addClass(pct <= 100 ? 'bg-pos' : 'bg-neg');
    $tr.find('.bg-real').each(function () {
      const m = $(this).data('m');
      $(this).text(nf(r.realisasi[m] || 0));
    });
  }

  $('#bg-body').on('click', '.bg-debit', function () {
    const r = rows[$(this).closest('tr').data('r')];
    const [pf, pt] = previousFromTo();
    openBgTxModal(deptCode, r.account, pf, pt, r.account + ' - Previous Period');
  });
  $('#bg-body').on('click', '.bg-real', function () {
    const r = rows[$(this).closest('tr').data('r')];
    const m = $(this).data('m');
    openBgTxModal(deptCode, r.account, ymToDate(m, false), ymToDate(m, true), r.account + ' - ' + monthLabel(m));
  });

  function previousFromTo() {
    const parts = $('#previous').val().split(' to ').map((s) => s.trim());
    const [m1, y1] = parts[0].split('-');
    const [m2, y2] = (parts[1] || parts[0]).split('-');
    return [y1 + '-' + m1 + '-01', ymToDate(y2 + '-' + m2, true)];
  }

  $('#bg-body').on('input', '.bg-cr', function () {
    const ri = $(this).closest('tr').data('r');
    const r = rows[ri];
    let v = parseFloat(this.value);
    r.cost_reduction = isNaN(v) ? 0 : Math.min(100, Math.max(0, v));
    r.budget = round2(r.average * (1 - r.cost_reduction / 100));
    r.final_budget = r.budget;
    $(this).closest('tr').find('.bg-final').val(nf(r.final_budget));
    paintRow(ri);
    paintCards(computeCards());
  });

  $('#bg-body').on('focus', '.bg-final', function () {
    const r = rows[$(this).closest('tr').data('r')];
    this.value = String(round2(r.final_budget)).replace('.', ',');
    this.select();
  });
  $('#bg-body').on('blur', '.bg-final', function () {
    const ri = $(this).closest('tr').data('r');
    const r = rows[ri];
    const v = parseId(this.value);
    if (v !== null) r.final_budget = v;
    this.value = nf(r.final_budget);
    paintRow(ri);
    paintCards(computeCards());
  });
  $('#bg-body').on('keydown', '.bg-final, .bg-cr', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); this.blur(); }
  });

  function computeCards() {
    const previous = rows.reduce((s, r) => s + r.debit, 0);
    const totalBudget = rows.reduce((s, r) => s + rowBudgetTotal(r), 0);
    const actual = rows.reduce((s, r) => s + r.realisasi_total, 0);
    const margin = totalBudget - actual;
    return {
      previous_expenses: round2(previous), previous_pct: totalBudget > 0 ? round2(previous / totalBudget * 100) : 0,
      total_budget: round2(totalBudget), budget_growth_pct: previous > 0 ? round2((totalBudget - previous) / previous * 100) : 0,
      actual_expenses: round2(actual), actual_pct: totalBudget > 0 ? round2(actual / totalBudget * 100) : 0,
      margin: round2(margin), margin_pct: totalBudget > 0 ? round2(margin / totalBudget * 100) : 0,
    };
  }

  $('#btnSave').click(function () {
    if (!rows.length) { alert('Tidak ada data untuk disimpan.'); return; }

    const payload = rows.map((r) => ({ account: r.account, cost_reduction: r.cost_reduction, final_budget: r.final_budget }));
    $('.loading-spinner-container').addClass('-show');

    $.ajax({
      url: "{{ route('budgeting.store') }}", type: 'POST',
      data: {
        previous: $('#previous').val(), budget: $('#budget').val(), dept: deptCode, coa: $('#coa').val(),
        fiscal_year: $('#fiscalYear').val(), description: $('#description').val(), note: $('#note').val(),
        rows: JSON.stringify(payload)
      },
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    }).done(function (res) {
      alert(res.message);
      if (res.redirect) window.location.href = res.redirect;
    }).fail(function (xhr) {
      alert((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan.');
    }).always(function () {
      $('.loading-spinner-container').removeClass('-show');
    });
  });

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection
