@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

<section id="bg-filter">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Filter</h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0">
          <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        {{-- Wajib ada <form>: app.js memasang $('.select2').on('change', () => $(this).valid()) --}}
        <form class="needs-validation" novalidate onsubmit="return false;">
        <div class="form-row">
          <div class="form-group col-md-4">
            <label class="form-label" for="periode">Periode</label>
            <input type="text" id="periode" name="periode" class="form-control" placeholder="MM-YYYY to MM-YYYY" autocomplete="off" />
          </div>
          <div class="form-group col-md-8">
            <label class="form-label" for="dept">Department</label>
            <select class="select2 form-control" id="dept" name="dept" multiple data-placeholder="Semua department">
              @foreach($depts as $val)
                <option value="{{ $val->code }}">{{ $val->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-12">
            <label class="form-label" for="coa">COA</label>
            <select class="select2 form-control" id="coa" name="coa" multiple data-placeholder="Semua COA (1000, 5000, 8000)">
              @foreach($accounts as $val)
                <option value="{{ $val->account }}">
                  {{ $val->account }} - {{ $val->description }}@if(strtoupper($val->acc_header) == 'HEADER') [HEADER]@endif
                </option>
              @endforeach
            </select>
            <small class="text-muted">Kosong = semua COA range 1000, 5000, dan 8000. Memilih COA header menarik seluruh COA di bawahnya.</small>
          </div>
        </div>
        <div class="form-row">
          <div class="col-12">
            <button type="button" class="btn btn-primary" id="btnSearch">Tampilkan</button>
            <button type="button" class="btn btn-outline-secondary" id="btnReset">Reset</button>
          </div>
        </div>
        </form>
      </div>
    </div>
  </div>
</section>

<section id="bg-result" style="display:none">
  <div class="card">
    <div class="card-header">
      <div>
        <h4 class="card-title">@yield('title')</h4>
        <small class="text-muted" id="bg-title"></small>
      </div>
    </div>
    <div class="card-body pt-0">
      <small class="text-muted d-block mb-1">
        Average = total debit &divide; jumlah bulan yang debitnya tidak nol. Budget = Average &minus; Cost Reduction.
        Final Budget mengikuti Budget sampai diedit manual (gunakan koma untuk desimal).
      </small>
      <div class="table-responsive bg-scroll">
        <table id="bgTable" class="table table-sm">
          <thead class="thead-light">
            <tr>
              <th>Department</th>
              <th>Nomor COA</th>
              <th>Nama COA</th>
              <th class="text-right">Debit</th>
              <th class="text-right">Average</th>
              <th class="text-right" style="width:140px">Cost Reduction Value</th>
              <th class="text-right">Proposed Monthly Budget</th>
              <th class="text-right" style="width:190px">Final Budget</th>
            </tr>
          </thead>
          <tbody id="bg-body"></tbody>
          <tfoot>
            <tr class="bg-grand">
              <td colspan="3">TOTAL</td>
              <td class="text-right" id="bg-t-debit"></td>
              <td class="text-right" id="bg-t-avg"></td>
              <td></td>
              <td class="text-right" id="bg-t-budget"></td>
              <td class="text-right" id="bg-t-final"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</section>
@endsection

@section('styles')
<style>
  .bg-scroll { max-height:70vh; overflow:auto; }
  #bgTable th, #bgTable td { white-space:nowrap; vertical-align:middle; }
  #bgTable thead th {
    position:sticky; top:0; z-index:3; background:#f3f2f7;
    box-shadow:inset 0 -1px 0 #dee2e6;
  }
  /* Sub header per department + subtotal */
  #bgTable tbody tr.bg-group > td {
    background:#e9ecef; color:#1f2937; font-weight:700;
    border-top:2px solid #9ca3af;
  }
  .bg-gmeta { font-weight:400; color:#6b7280; font-size:.8rem; margin-left:.5rem; }
  .bg-acc { font-weight:600; color:#1f3a5f; }
  #bgTable tbody tr.bg-row td.bg-dept { color:#6b7280; }
  #bgTable tbody tr.bg-row input { min-width:90px; }
  #bgTable tfoot tr.bg-grand > td {
    position:sticky; bottom:0; z-index:2; background:#f1f3f5; font-weight:700;
    box-shadow:inset 0 1px 0 #9ca3af, inset 0 -2px 0 #9ca3af;
  }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
  const CR_DEFAULT = {{ (float) $crDefault }};
  const PERIODE_DEFAULT = {!! json_encode($periodeDefault) !!};

  const nf = (v) => new Intl.NumberFormat('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2}).format(v || 0);
  const esc = (s) => $('<div>').text(s === null || s === undefined ? '' : s).html();
  const round2 = (v) => Math.round((v + Number.EPSILON) * 100) / 100;
  // "1.234,56" / "1234,56" -> 1234.56 ; null kalau bukan angka
  const parseId = (s) => {
    const t = String(s).trim().replace(/[^\d,.\-]/g, '').replace(/\./g, '').replace(',', '.');
    const n = parseFloat(t);
    return isNaN(n) ? null : n;
  };

  let groups = [];

  // Periode: range bulan (MM-YYYY to MM-YYYY)
  $('#periode').flatpickr({
    mode: 'range',
    dateFormat: 'm-Y',
    defaultDate: PERIODE_DEFAULT.split(' to '),
    locale: { rangeSeparator: ' to ' }
  });

  $("#btnReset").click(function () {
    $("#dept,#coa").val(null).trigger('change');
    $('#periode')[0]._flatpickr.setDate(PERIODE_DEFAULT.split(' to '), false);
    $("#bg-result").hide();
  });

  $("#btnSearch").click(function () {
    $(".loading-spinner-container").addClass("-show");

    $.get("{{ route('budgeting.data') }}", {
      periode: $("#periode").val(),
      dept: $("#dept").val(),
      coa: $("#coa").val()
    }).done(function (res) {
      $("#bg-title").text('Periode: ' + res.header.periode_text + '  |  ' + res.header.jumlah_periode + ' bulan');
      groups = res.groups.map(function (g) {
        return {
          dept_name: g.dept_name,
          dept_code: g.dept_code,
          rows: g.rows.map(function (r) {
            const row = { account: r.account, nama: r.nama_akun, debit: r.debit, avg: r.average,
                          active: r.active_months, cr: CR_DEFAULT, manual: false };
            row.budget = budgetOf(row);
            row.final = row.budget;
            return row;
          })
        };
      });
      render();
      $("#bg-result").show();
      if (window.feather) feather.replace({ width: 14, height: 14 });
    }).fail(function (xhr) {
      alert((xhr.responseJSON && xhr.responseJSON.error) || 'Gagal memuat data.');
    }).always(function () {
      $(".loading-spinner-container").removeClass("-show");
    });
  });

  const budgetOf = (r) => r.avg * (1 - (r.cr || 0) / 100);

  const render = () => {
    let html = '';
    if (!groups.length) {
      html = '<tr><td colspan="8" class="text-center text-muted py-2">Tidak ada data debit pada filter ini.</td></tr>';
    }
    groups.forEach(function (g, gi) {
      html += '<tr class="bg-group" data-g="' + gi + '">'
            + '<td colspan="3">' + esc(g.dept_name) + '<span class="bg-gmeta">' + g.rows.length + ' COA</span></td>'
            + '<td class="text-right g-debit"></td><td class="text-right g-avg"></td><td></td>'
            + '<td class="text-right g-budget"></td><td class="text-right g-final"></td></tr>';

      g.rows.forEach(function (r, ri) {
        html += '<tr class="bg-row" data-g="' + gi + '" data-r="' + ri + '">'
              + '<td class="bg-dept">' + esc(g.dept_name) + '</td>'
              + '<td class="bg-acc">' + esc(r.account) + '</td>'
              + '<td>' + esc(r.nama) + '</td>'
              + '<td class="text-right">' + nf(r.debit) + '</td>'
              + '<td class="text-right" title="Dibagi ' + r.active + ' bulan (bulan debit 0 tidak dihitung)">' + nf(r.avg) + '</td>'
              + '<td><div class="input-group input-group-sm">'
              +   '<input type="number" class="form-control text-right bg-cr" min="0" max="100" step="0.01" value="' + r.cr + '">'
              +   '<div class="input-group-append"><span class="input-group-text">%</span></div></div></td>'
              + '<td class="text-right bg-budget">' + nf(r.budget) + '</td>'
              + '<td><input type="text" inputmode="decimal" class="form-control form-control-sm text-right bg-final" value="' + nf(r.final) + '"></td>'
              + '</tr>';
      });
    });
    $('#bg-body').html(html);
    paintTotals();
  };

  const paintTotals = () => {
    const G = { debit: 0, avg: 0, budget: 0, final: 0 };
    groups.forEach(function (g, gi) {
      const s = { debit: 0, avg: 0, budget: 0, final: 0 };
      g.rows.forEach(function (r) {
        s.debit += r.debit; s.avg += r.avg; s.budget += r.budget; s.final += r.final;
      });
      const $g = $('#bg-body tr.bg-group[data-g="' + gi + '"]');
      $g.find('.g-debit').text(nf(s.debit));
      $g.find('.g-avg').text(nf(s.avg));
      $g.find('.g-budget').text(nf(s.budget));
      $g.find('.g-final').text(nf(s.final));
      G.debit += s.debit; G.avg += s.avg; G.budget += s.budget; G.final += s.final;
    });
    $('#bg-t-debit').text(nf(G.debit));
    $('#bg-t-avg').text(nf(G.avg));
    $('#bg-t-budget').text(nf(G.budget));
    $('#bg-t-final').text(nf(G.final));
  };

  const rowOf = (el) => {
    const $tr = $(el).closest('tr');
    return { r: groups[$tr.data('g')].rows[$tr.data('r')], $tr: $tr };
  };

  // Cost Reduction diubah -> hitung ulang Budget (dan Final Budget bila belum diedit manual)
  $('#bg-body').on('input', '.bg-cr', function () {
    const o = rowOf(this);
    let v = parseFloat(this.value);
    o.r.cr = isNaN(v) ? 0 : Math.min(100, Math.max(0, v));
    o.r.budget = budgetOf(o.r);
    if (!o.r.manual) o.r.final = o.r.budget;
    o.$tr.find('.bg-budget').text(nf(o.r.budget));
    if (!o.r.manual) o.$tr.find('.bg-final').val(nf(o.r.final));
    paintTotals();
  });

  // Final Budget: tampil angka mentah saat fokus, terformat saat selesai
  $('#bg-body').on('focus', '.bg-final', function () {
    const o = rowOf(this);
    this.value = String(round2(o.r.final)).replace('.', ',');
    this.select();
  });
  $('#bg-body').on('blur', '.bg-final', function () {
    const o = rowOf(this);
    const v = parseId(this.value);
    if (v !== null) {
      o.r.final = v;
      o.r.manual = Math.abs(v - o.r.budget) > 0.004;
    }
    this.value = nf(o.r.final);
    paintTotals();
  });
  $('#bg-body').on('keydown', '.bg-final, .bg-cr', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); this.blur(); }
  });

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection