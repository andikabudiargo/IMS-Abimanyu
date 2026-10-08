@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

<div class="card">
  <div class="card-header">
    <div>
      <h4 class="card-title">{{ $hdr->budgeting_number }} <small class="text-muted">FY {{ $hdr->fiscal_year }}</small></h4>
      <small class="text-muted">
        Department: {{ $hdr->dept_name ?: $hdr->dept_code }} |
        Previous: {{ date('d M Y', strtotime($hdr->previous_from)) }} - {{ date('d M Y', strtotime($hdr->previous_to)) }} |
        Budget Period: {{ date('d M Y', strtotime($hdr->budget_from)) }} - {{ date('d M Y', strtotime($hdr->budget_to)) }}
      </small>
    </div>
    <div class="heading-elements">
      <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRecalc">Recalculate</button>
      <button type="button" class="btn btn-success btn-sm" id="btnSave">Save</button>
    </div>
  </div>
  <div class="card-body">
    <div class="form-row">
      <div class="form-group col-md-6">
        <label class="form-label" for="description">Description</label>
        <input type="text" id="description" class="form-control" value="{{ $hdr->description }}" />
      </div>
      <div class="form-group col-md-6">
        <label class="form-label" for="note">Note</label>
        <input type="text" id="note" class="form-control" value="{{ $hdr->note }}" />
      </div>
    </div>
  </div>
</div>

@include('accounting.budgeting._cards')

<div class="card">
  <div class="card-body pt-0">
    <small class="text-muted d-block mb-1">
      Ubah Cost Reduction / Final Budget lalu klik Save. <strong>Final Budget adalah TOTAL untuk seluruh Budget Period</strong> (nilai per bulan mengikuti otomatis).
      Recalculate menarik ulang Debit, Average dan Realisasi dari transaksi terbaru. Klik nilai Debit / Realisasi untuk melihat rincian transaksi.
    </small>
    <div class="table-responsive bg-scroll">
      <table id="bgTable" class="table table-sm">
        <thead class="thead-light" id="bg-thead"></thead>
        <tbody id="bg-body"></tbody>
      </table>
    </div>
  </div>
</div>
@include('accounting.budgeting._txmodal')
@endsection

@section('styles')
<style>
  .bg-scroll { max-height:65vh; overflow:auto; }
  #bgTable { table-layout:auto; }
  #bgTable th, #bgTable td { white-space:nowrap; vertical-align:middle; padding:.6rem .9rem; }
  #bgTable thead th { position:sticky; top:0; z-index:3; background:#f3f2f7; box-shadow:inset 0 -1px 0 #dee2e6; }
  .bg-acc { font-weight:600; color:#1f3a5f; }
  #bgTable th.col-cr, #bgTable td.col-cr { min-width:120px; }
  #bgTable th.col-final, #bgTable td.col-final { min-width:170px; }
  #bgTable tbody tr.bg-row .bg-cr { min-width:60px; }
  #bgTable tbody tr.bg-row .bg-final { min-width:140px; font-weight:600; }
  .bg-link { cursor:pointer; color:#7367f0; text-decoration:underline; }
  .bg-link:hover { color:#5e50ee; }
  .bg-pos { color:#28c76f; font-weight:600; }
  .bg-neg { color:#ea5455; font-weight:600; }
  .bg-final-monthly { display:block; font-weight:400; }

  /* Account + Name sticky di kiri (pakai class, bukan nth-child -- header row 2 kolomnya beda) */
  .col-sticky1, .col-sticky2 { position:sticky; z-index:2; }
  .col-sticky1 { left:0; min-width:110px; }
  .col-sticky2 { left:110px; min-width:220px; box-shadow:2px 0 4px rgba(0,0,0,.08); }
  #bgTable thead .col-sticky1, #bgTable thead .col-sticky2 { z-index:4; background:#f3f2f7; }

  /* Zebra + hover (ikut mewarnai kolom sticky) */
  #bgTable tbody tr.bg-row:nth-child(odd) { background:#fff; }
  #bgTable tbody tr.bg-row:nth-child(even) { background:#f8f9fc; }
  #bgTable tbody tr.bg-row:nth-child(odd) td.col-sticky1, #bgTable tbody tr.bg-row:nth-child(odd) td.col-sticky2 { background:#fff; }
  #bgTable tbody tr.bg-row:nth-child(even) td.col-sticky1, #bgTable tbody tr.bg-row:nth-child(even) td.col-sticky2 { background:#f8f9fc; }
  #bgTable tbody tr.bg-row:hover { background:#eef1fd; }
  #bgTable tbody tr.bg-row:hover td.col-sticky1, #bgTable tbody tr.bg-row:hover td.col-sticky2 { background:#eef1fd; }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
  const DEPT_CODE = {!! json_encode($hdr->dept_code) !!};
  const BUDGET_FROM = {!! json_encode($hdr->budget_from) !!};
  const BUDGET_TO = {!! json_encode($hdr->budget_to) !!};
  const PREVIOUS_FROM = {!! json_encode($hdr->previous_from) !!};
  const PREVIOUS_TO = {!! json_encode($hdr->previous_to) !!};
  let months = {!! json_encode($months) !!};
  let rows = {!! json_encode($rows) !!};

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

  function buildHead() {
    let r1 = '<tr>'
      + '<th rowspan="2" class="col-sticky1">Account</th><th rowspan="2" class="col-sticky2">Name</th>'
      + '<th rowspan="2" class="text-right">Debit</th><th rowspan="2" class="text-right">Average</th>'
      + '<th rowspan="2" class="col-cr">Cost Reduction</th>'
      + '<th rowspan="2" class="text-right">Proposed Budget<br><small class="text-muted">(Monthly)</small></th>'
      + '<th rowspan="2" class="col-final" title="Total untuk seluruh Budget Period">Final Budget<br><small class="text-muted">(Total ' + months.length + ' bln)</small></th>'
      + '<th colspan="' + (months.length + 3) + '" class="text-center">Budget Period</th></tr>';
    let r2 = '<tr>';
    months.forEach((m) => r2 += '<th class="text-right">' + monthLabel(m) + '</th>');
    r2 += '<th class="text-right">Total Realisasi</th><th class="text-right">Selisih</th><th class="text-right">Realisasi %</th></tr>';
    $('#bg-thead').html(r1 + r2);
  }

  function rowSelisih(r) { return round2(r.final_budget - r.realisasi_total); }
  function rowPct(r) { return r.final_budget > 0 ? round2(r.realisasi_total / r.final_budget * 100) : 0; }

  function render() {
    let html = '';
    if (!rows.length) {
      html = '<tr><td colspan="' + (7 + months.length + 3) + '" class="text-center text-muted py-2">Tidak ada data.</td></tr>';
    }
    rows.forEach(function (r, ri) {
      html += '<tr class="bg-row" data-r="' + ri + '">'
            + '<td class="bg-acc col-sticky1">' + esc(r.account) + '</td><td class="col-sticky2">' + esc(r.nama_akun) + '</td>'
            + '<td class="text-right"><a href="javascript:void(0)" class="bg-link bg-debit"></a></td>'
            + '<td class="text-right">' + nf(r.average) + '</td>'
            + '<td class="col-cr"><div class="input-group input-group-sm"><input type="number" class="form-control text-right bg-cr" min="0" max="100" step="0.01" value="' + r.cost_reduction + '"><div class="input-group-append"><span class="input-group-text">%</span></div></div></td>'
            + '<td class="text-right bg-budget">' + nf(r.budget) + '</td>'
            + '<td class="col-final"><input type="text" inputmode="decimal" class="form-control form-control-sm text-right bg-final" value="' + nf(r.final_budget) + '"><small class="text-muted bg-final-monthly"></small></td>';
      months.forEach(function (m) {
        html += '<td class="text-right"><a href="javascript:void(0)" class="bg-link bg-real" data-m="' + m + '"></a></td>';
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
    $tr.find('.bg-final-monthly').text('≈ ' + nf(months.length > 0 ? r.final_budget / months.length : 0) + ' /bln');
    $tr.find('.bg-total').text(nf(r.realisasi_total));
    const selisih = rowSelisih(r), pct = rowPct(r);
    $tr.find('.bg-selisih').text(nf(selisih)).removeClass('bg-pos bg-neg').addClass(selisih >= 0 ? 'bg-pos' : 'bg-neg');
    $tr.find('.bg-pct').text(pct + '%').removeClass('bg-pos bg-neg').addClass(pct <= 100 ? 'bg-pos' : 'bg-neg');
    $tr.find('.bg-real').each(function () {
      const m = $(this).data('m');
      $(this).text(nf((r.realisasi || {})[m] || 0));
    });
  }

  function computeCards() {
    const previous = rows.reduce((s, r) => s + r.debit, 0);
    const totalBudget = rows.reduce((s, r) => s + r.final_budget, 0);
    const actual = rows.reduce((s, r) => s + r.realisasi_total, 0);
    const margin = totalBudget - actual;
    return {
      previous_expenses: round2(previous), previous_pct: totalBudget > 0 ? round2(previous / totalBudget * 100) : 0,
      total_budget: round2(totalBudget), budget_growth_pct: previous > 0 ? round2((totalBudget - previous) / previous * 100) : 0,
      actual_expenses: round2(actual), actual_pct: totalBudget > 0 ? round2(actual / totalBudget * 100) : 0,
      margin: round2(margin), margin_pct: totalBudget > 0 ? round2(margin / totalBudget * 100) : 0,
    };
  }

  function renderAll() { buildHead(); render(); paintCards(computeCards()); if (window.feather) feather.replace({ width: 14, height: 14 }); }
  renderAll();

  $('#bg-body').on('click', '.bg-debit', function () {
    const r = rows[$(this).closest('tr').data('r')];
    openBgTxModal(DEPT_CODE, r.account, PREVIOUS_FROM, PREVIOUS_TO, r.account + ' - Previous Period');
  });
  $('#bg-body').on('click', '.bg-real', function () {
    const r = rows[$(this).closest('tr').data('r')];
    const m = $(this).data('m');
    openBgTxModal(DEPT_CODE, r.account, ymToDate(m, false), ymToDate(m, true), r.account + ' - ' + monthLabel(m));
  });

  $('#bg-body').on('input', '.bg-cr', function () {
    const ri = $(this).closest('tr').data('r');
    const r = rows[ri];
    let v = parseFloat(this.value);
    r.cost_reduction = isNaN(v) ? 0 : Math.min(100, Math.max(0, v));
    r.budget = round2(r.average * (1 - r.cost_reduction / 100));
    r.final_budget = round2(r.budget * months.length);
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

  $('#btnRecalc').click(function () {
    if (!confirm('Tarik ulang data Debit, Average dan Realisasi dari transaksi terbaru?')) return;
    $('.loading-spinner-container').addClass('-show');
    $.post("{{ route('budgeting.recalculate', $hdr->id) }}").done(function (res) {
      months = res.months;
      rows = res.rows;
      renderAll();
      alert(res.message);
    }).fail(function (xhr) {
      alert((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menarik ulang data.');
    }).always(function () {
      $('.loading-spinner-container').removeClass('-show');
    });
  });

  $('#btnSave').click(function () {
    const payload = rows.map((r) => ({ account: r.account, cost_reduction: r.cost_reduction, final_budget: r.final_budget }));
    $('.loading-spinner-container').addClass('-show');
    $.ajax({
      url: "{{ route('budgeting.update', $hdr->id) }}", type: 'PUT',
      data: { rows: JSON.stringify(payload), description: $('#description').val(), note: $('#note').val() },
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    }).done(function (res) {
      alert(res.message);
    }).fail(function (xhr) {
      alert((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan.');
    }).always(function () {
      $('.loading-spinner-container').removeClass('-show');
    });
  });

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection
