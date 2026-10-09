@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

@include('accounting.budgeting._detailHeader', ['hdr' => $hdr, 'locked' => true])

@include('accounting.budgeting._cards')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">Detail</h4>
    <div class="heading-elements">
      <a href="{{ route('budgeting.export.excel', $hdr->id) }}" class="btn btn-success btn-sm"><i data-feather="file-text"></i> Export Excel</a>
      <a href="{{ route('budgeting.export.pdf', $hdr->id) }}" class="btn btn-danger btn-sm" target="_blank"><i data-feather="file"></i> Export PDF</a>
    </div>
  </div>
  <div class="card-body pt-0">
    <small class="text-muted d-block mb-1">Klik nilai Debit / Realisasi untuk melihat rincian transaksi.</small>
    <div class="table-responsive bg-scroll">
      <table id="bgTable" class="table table-sm">
        <thead class="thead-light" id="bg-thead"></thead>
        <tbody id="bg-body"></tbody>
      </table>
    </div>
    <div class="d-flex flex-wrap mt-2" style="gap:.5rem">
      <a href="{{ route('budgeting.index') }}" class="btn btn-light">Back</a>
      <a href="{{ route('budgeting.edit', $hdr->id) }}" class="btn btn-warning"><i data-feather="edit-2"></i> Edit</a>
      <button type="button" class="btn btn-primary" id="btnRecalc"><i data-feather="refresh-cw"></i> Recalculate</button>
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
  .bg-link { cursor:pointer; color:#7367f0; text-decoration:underline; }
  .bg-link:hover { color:#5e50ee; }
  .bg-pos { color:#28c76f; font-weight:600; }
  .bg-neg { color:#ea5455; font-weight:600; }

  /* Unbudget + Account + Name sticky di kiri (pakai class, bukan nth-child -- header row 2 kolomnya beda) */
  .col-sticky0, .col-sticky1, .col-sticky2 { position:sticky; z-index:2; }
  .col-sticky0 { left:0; min-width:60px; text-align:center; }
  .col-sticky1 { left:60px; min-width:110px; }
  .col-sticky2 { left:170px; min-width:220px; box-shadow:2px 0 4px rgba(0,0,0,.08); }
  #bgTable thead .col-sticky0, #bgTable thead .col-sticky1, #bgTable thead .col-sticky2 { z-index:4; background:#f3f2f7; }

  /* Zebra + hover (ikut mewarnai kolom sticky) */
  #bgTable tbody tr.bg-row:nth-child(odd) { background:#fff; }
  #bgTable tbody tr.bg-row:nth-child(even) { background:#f8f9fc; }
  #bgTable tbody tr.bg-row:nth-child(odd) td.col-sticky0, #bgTable tbody tr.bg-row:nth-child(odd) td.col-sticky1, #bgTable tbody tr.bg-row:nth-child(odd) td.col-sticky2 { background:#fff; }
  #bgTable tbody tr.bg-row:nth-child(even) td.col-sticky0, #bgTable tbody tr.bg-row:nth-child(even) td.col-sticky1, #bgTable tbody tr.bg-row:nth-child(even) td.col-sticky2 { background:#f8f9fc; }
  #bgTable tbody tr.bg-row:hover { background:#eef1fd; }
  #bgTable tbody tr.bg-row:hover td.col-sticky0, #bgTable tbody tr.bg-row:hover td.col-sticky1, #bgTable tbody tr.bg-row:hover td.col-sticky2 { background:#eef1fd; }
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
  const toast = (icon, title) => Swal.fire({ toast: true, position: 'top-end', icon: icon, title: title, showConfirmButton: false, timer: 2500 });

  // Bandingkan rows sebelum & sesudah Recalculate, kembalikan baris yang Debit/Average/Realisasi-nya berubah.
  function diffRecalc(oldRows, newRows) {
    const oldMap = {};
    oldRows.forEach((r) => { oldMap[r.account] = r; });
    const changed = [];
    newRows.forEach((r) => {
      const o = oldMap[r.account];
      const oldDebit = o ? o.debit : 0, oldAvg = o ? o.average : 0, oldReal = o ? o.realisasi_total : 0;
      if (Math.abs(oldDebit - r.debit) > 0.01 || Math.abs(oldAvg - r.average) > 0.01 || Math.abs(oldReal - r.realisasi_total) > 0.01) {
        changed.push({ account: r.account, nama: r.nama_akun, oldDebit, newDebit: r.debit, oldAvg, newAvg: r.average, oldReal, newReal: r.realisasi_total });
      }
    });
    return changed;
  }

  function showRecalcDiff(changed) {
    if (!changed.length) { toast('info', 'Data sudah up to date, tidak ada perubahan.'); return; }
    let html = '<div class="table-responsive text-left" style="max-height:50vh;overflow:auto;">'
      + '<table class="table table-sm"><thead><tr><th>Account</th><th class="text-right">Debit</th><th class="text-right">Average</th><th class="text-right">Realisasi</th></tr></thead><tbody>';
    changed.forEach((c) => {
      html += '<tr><td>' + esc(c.account) + '<br><small class="text-muted">' + esc(c.nama) + '</small></td>'
        + '<td class="text-right">' + nf(c.oldDebit) + ' &rarr; <b>' + nf(c.newDebit) + '</b></td>'
        + '<td class="text-right">' + nf(c.oldAvg) + ' &rarr; <b>' + nf(c.newAvg) + '</b></td>'
        + '<td class="text-right">' + nf(c.oldReal) + ' &rarr; <b>' + nf(c.newReal) + '</b></td></tr>';
    });
    html += '</tbody></table></div>';
    Swal.fire({ title: changed.length + ' akun berubah', html: html, icon: 'info', confirmButtonText: 'OK', width: 700 });
  }

  function buildHead() {
    let r1 = '<tr>'
      + '<th rowspan="2" class="text-center">Unbudget</th>'
      + '<th rowspan="2" class="col-sticky1">Account</th><th rowspan="2" class="col-sticky2">Name</th>'
      + '<th rowspan="2" class="text-right">Debit</th><th rowspan="2" class="text-right">Average</th>'
      + '<th rowspan="2" class="text-right">Inflasi</th>'
      + '<th rowspan="2" class="text-right">Cost Reduction</th>'
      + '<th rowspan="2" class="text-right" title="Final Budget dibagi jumlah bulan Budget Period">Monthly Budget</th>'
      + '<th rowspan="2" class="text-right" title="Tambahan budget yang disetujui kalau dept ini over-budget">Additional Budget</th>'
      + '<th rowspan="2" class="text-right" title="Total untuk seluruh Budget Period">Final Budget<br><small class="text-muted">(Total ' + months.length + ' bln)</small></th>'
      + '<th colspan="' + (months.length + 3) + '" class="text-center">Budget Period</th></tr>';
    let r2 = '<tr>';
    months.forEach((m) => r2 += '<th class="text-right">' + monthLabel(m) + '</th>');
    r2 += '<th class="text-right">Total Realisasi</th><th class="text-right">Selisih</th><th class="text-right">Realisasi %</th></tr>';
    $('#bg-thead').html(r1 + r2);
  }

  function render() {
    let html = '';
    if (!rows.length) {
      html = '<tr><td colspan="' + (10 + months.length + 3) + '" class="text-center text-muted py-2">Tidak ada data.</td></tr>';
    }
    rows.forEach(function (r, ri) {
      html += '<tr class="bg-row" data-r="' + ri + '">'
            + '<td class="col-sticky0">' + (r.is_unbudget ? '<i data-feather="check-square" style="width:14px;height:14px"></i>' : '') + '</td>'
            + '<td class="bg-acc col-sticky1">' + esc(r.account) + '</td><td class="col-sticky2">' + esc(r.nama_akun) + '</td>'
            + '<td class="text-right"><a href="javascript:void(0)" class="bg-link bg-debit">' + nf(r.debit) + '</a></td>'
            + '<td class="text-right">' + nf(r.average) + '</td>'
            + '<td class="text-right">' + r.inflation + '%</td>'
            + '<td class="text-right">' + r.cost_reduction + '%</td>'
            + '<td class="text-right">' + (r.is_unbudget ? '-' : nf(r.final_budget_monthly)) + '</td>'
            + '<td class="text-right">' + nf(r.additional_budget) + '</td>'
            + '<td class="text-right">' + (r.is_unbudget ? '-' : nf(r.final_budget)) + '</td>';
      months.forEach(function (m) {
        html += '<td class="text-right"><a href="javascript:void(0)" class="bg-link bg-real" data-m="' + m + '">' + nf((r.realisasi || {})[m] || 0) + '</a></td>';
      });
      html += '<td class="text-right">' + (r.is_unbudget ? '-' : nf(r.realisasi_total)) + '</td>'
            + '<td class="text-right ' + (r.selisih !== null && r.selisih >= 0 ? 'bg-pos' : (r.selisih !== null ? 'bg-neg' : '')) + '">' + (r.selisih === null ? '-' : nf(r.selisih)) + '</td>'
            + '<td class="text-right ' + (r.selisih !== null && r.selisih >= 0 ? 'bg-pos' : (r.selisih !== null ? 'bg-neg' : '')) + '">' + (r.realisasi_pct === null ? '-' : r.realisasi_pct + '%') + '</td></tr>';
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
    Swal.fire({
      title: 'Yakin?',
      text: 'Tarik ulang data Debit, Average dan Realisasi dari transaksi terbaru?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, tarik ulang',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#7367F0',
      reverseButtons: true,
    }).then((result) => {
      if (!result.isConfirmed) return;
      $('.loading-spinner-container').addClass('-show');
      const oldRows = rows;
      $.ajax({
        url: "{{ route('budgeting.recalculate', $hdr->id) }}", type: 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
      }).done(function (res) {
        months = res.months;
        rows = res.rows;
        cards = res.cards;
        renderAll();
        showRecalcDiff(diffRecalc(oldRows, rows));
      }).fail(function (xhr) {
        toast('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menarik ulang data.');
      }).always(function () {
        $('.loading-spinner-container').removeClass('-show');
      });
    });
  });
</script>
@endsection
