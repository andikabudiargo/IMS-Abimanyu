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
      <a href="{{ route('budgeting.edit', $hdr->id) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
      <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRecalc">Recalculate</button>
      <a href="{{ route('budgeting.export.excel', $hdr->id) }}" class="btn btn-outline-success btn-sm">Export Excel</a>
      <a href="{{ route('budgeting.export.pdf', $hdr->id) }}" class="btn btn-outline-danger btn-sm" target="_blank">Export PDF</a>
    </div>
  </div>
  @if($hdr->description || $hdr->note)
  <div class="card-body py-2">
    @if($hdr->description)<div><strong>Description:</strong> {{ $hdr->description }}</div>@endif
    @if($hdr->note)<div><strong>Note:</strong> {{ $hdr->note }}</div>@endif
  </div>
  @endif
</div>

@include('accounting.budgeting._cards')

<div class="card">
  <div class="card-body pt-0">
    <small class="text-muted d-block mb-1">Klik nilai Debit / Realisasi untuk melihat rincian transaksi.</small>
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
  #bgTable th, #bgTable td { white-space:nowrap; vertical-align:middle; padding:.6rem .9rem; }
  #bgTable thead th { position:sticky; top:0; z-index:3; background:#f3f2f7; box-shadow:inset 0 -1px 0 #dee2e6; }
  .bg-acc { font-weight:600; color:#1f3a5f; }
  .bg-link { cursor:pointer; text-decoration:underline; }
  .bg-pos { color:#28c76f; font-weight:600; }
  .bg-neg { color:#ea5455; font-weight:600; }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
  const DEPT_CODE = {!! json_encode($hdr->dept_code) !!};
  const PREVIOUS_FROM = {!! json_encode($hdr->previous_from) !!};
  const PREVIOUS_TO = {!! json_encode($hdr->previous_to) !!};
  let months = {!! json_encode($months) !!};
  let rows = {!! json_encode($rows) !!};

  const nf = (v) => new Intl.NumberFormat('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2}).format(v || 0);
  const esc = (s) => $('<div>').text(s === null || s === undefined ? '' : s).html();
  const monthLabel = (ym) => { const [y, m] = ym.split('-'); return ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][parseInt(m,10)-1] + ' ' + y; };
  const ymToDate = (ym, end) => end ? ym + '-' + new Date(ym.split('-')[0], ym.split('-')[1], 0).getDate() : ym + '-01';

  function buildHead() {
    let r1 = '<tr>'
      + '<th rowspan="2">Account</th><th rowspan="2">Name</th>'
      + '<th rowspan="2" class="text-right">Debit</th><th rowspan="2" class="text-right">Average</th>'
      + '<th rowspan="2" class="text-right">Cost Reduction</th>'
      + '<th rowspan="2" class="text-right">Proposed Budget</th>'
      + '<th rowspan="2" class="text-right">Final Budget</th>'
      + '<th colspan="' + (months.length + 3) + '" class="text-center">Budget Period</th></tr>';
    let r2 = '<tr>';
    months.forEach((m) => r2 += '<th class="text-right">' + monthLabel(m) + '</th>');
    r2 += '<th class="text-right">Total</th><th class="text-right">Selisih</th><th class="text-right">Realisasi %</th></tr>';
    $('#bg-thead').html(r1 + r2);
  }

  function render() {
    let html = '';
    if (!rows.length) {
      html = '<tr><td colspan="' + (7 + months.length + 3) + '" class="text-center text-muted py-2">Tidak ada data.</td></tr>';
    }
    rows.forEach(function (r, ri) {
      html += '<tr class="bg-row" data-r="' + ri + '">'
            + '<td class="bg-acc">' + esc(r.account) + '</td><td>' + esc(r.nama_akun) + '</td>'
            + '<td class="text-right"><a class="bg-link bg-debit">' + nf(r.debit) + '</a></td>'
            + '<td class="text-right">' + nf(r.average) + '</td>'
            + '<td class="text-right">' + r.cost_reduction + '%</td>'
            + '<td class="text-right">' + nf(r.budget) + '</td>'
            + '<td class="text-right">' + nf(r.final_budget) + '</td>';
      months.forEach(function (m) {
        html += '<td class="text-right"><a class="bg-link bg-real" data-m="' + m + '">' + nf((r.realisasi || {})[m] || 0) + '</a></td>';
      });
      html += '<td class="text-right">' + nf(r.realisasi_total) + '</td>'
            + '<td class="text-right ' + (r.selisih >= 0 ? 'bg-pos' : 'bg-neg') + '">' + nf(r.selisih) + '</td>'
            + '<td class="text-right ' + (r.realisasi_pct <= 100 ? 'bg-pos' : 'bg-neg') + '">' + r.realisasi_pct + '%</td></tr>';
    });
    $('#bg-body').html(html);
  }

  let cards = {!! json_encode($cards) !!};
  function renderAll() { buildHead(); render(); paintCards(cards); if (window.feather) feather.replace({ width: 14, height: 14 }); }
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

  $('#btnRecalc').click(function () {
    if (!confirm('Tarik ulang data Debit, Average dan Realisasi dari transaksi terbaru?')) return;
    $('.loading-spinner-container').addClass('-show');
    $.ajax({
      url: "{{ route('budgeting.recalculate', $hdr->id) }}", type: 'POST',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    }).done(function (res) {
      months = res.months;
      rows = res.rows;
      cards = res.cards;
      renderAll();
      alert(res.message);
    }).fail(function (xhr) {
      alert((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menarik ulang data.');
    }).always(function () {
      $('.loading-spinner-container').removeClass('-show');
    });
  });
</script>
@endsection
