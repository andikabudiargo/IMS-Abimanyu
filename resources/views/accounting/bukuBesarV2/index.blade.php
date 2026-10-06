@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

<section id="bb2-filter">
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
        {{-- Wajib ada <form>: app.js memasang $('.select2').on('change', () => $(this).valid()),
             dan jquery.validate melempar error kalau elemennya tidak berada di dalam form --
             exception itu juga yang bikin dropdown select2 tidak mau menutup. --}}
        <form class="needs-validation" novalidate onsubmit="return false;">
        <div class="form-row">
          <div class="form-group col-md-6">
            <label class="form-label" for="account">COA <span class="text-danger">*</span></label>
            <select class="select2 form-control" id="account" name="account">
              <option value=""></option>
              @foreach($accounts as $val)
                <option value="{{ $val->account }}" data-header="{{ $val->acc_header }}">
                  {{ $val->account }} - {{ $val->description }}@if($val->acc_header == 'HEADER') [HEADER]@endif
                </option>
              @endforeach
            </select>
            <small class="text-muted">Pilih COA header untuk menarik transaksi seluruh COA di bawahnya.</small>
          </div>
          <div class="form-group col-md-2">
            <label class="form-label" for="tahun">Tahun</label>
            <select class="select2 form-control" id="tahun" name="tahun">
              @for ($i = $tahunIni + 1; $i >= $tahunAwal; $i--)
                <option value="{{ $i }}" {{ $i == $tahunIni ? 'selected' : '' }}>{{ $i }}</option>
              @endfor
            </select>
          </div>
          <div class="form-group col-md-2">
            <label class="form-label" for="period1">Periode Awal</label>
            <select class="select2 form-control" id="period1" name="period1">
              @for ($i = 1; $i <= 12; $i++)
                <option value="{{ $i }}">{{ $i }}</option>
              @endfor
            </select>
          </div>
          <div class="form-group col-md-2">
            <label class="form-label" for="period2">Periode Akhir</label>
            <select class="select2 form-control" id="period2" name="period2">
              @for ($i = 1; $i <= 12; $i++)
                <option value="{{ $i }}" {{ $i == (int) date('n') ? 'selected' : '' }}>{{ $i }}</option>
              @endfor
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-4">
            <label class="form-label" for="vcDate">Tanggal <small class="text-muted">(opsional, menimpa periode)</small></label>
            <input type="text" id="vcDate" name="vcDate" class="form-control flatpickr-range" placeholder="dd-mm-yyyy to dd-mm-yyyy" />
          </div>
          <div class="form-group col-md-2">
            <label class="form-label" for="searchStatus">Status</label>
            <select class="select2 form-control" id="searchStatus" name="searchStatus">
              <option value="">All</option>
              @foreach($status as $index => $val)
                <option value="{{ $index }}">{{ $val }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group col-md-6">
            <label class="form-label" for="dept">Departemen</label>
            <select class="select2 form-control" id="dept" name="dept" multiple>
              @foreach($depts as $val)
                <option value="{{ $val->code }}">{{ $val->name }}</option>
              @endforeach
            </select>
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

<section id="bb2-result" style="display:none">
  <div class="card">
    <div class="card-body">
      <h4 class="mb-1" id="bb2-title"></h4>
      <table class="table table-sm table-bordered bb2-summary mb-0">
        <tbody>
          <tr>
            <th width="12%">Kode COA</th><td width="14%" id="h-account"></td>
            <th width="12%">Nama Akun</th><td id="h-description"></td>
            <th width="14%">Saldo Awal</th><td width="16%" class="text-right" id="h-opening"></td>
          </tr>
          <tr>
            <th>Kelompok</th><td id="h-kelompok"></td>
            <th>Saldo Normal</th><td id="h-normal"></td>
            <th>Total Mutasi Debet</th><td class="text-right" id="h-debit"></td>
          </tr>
          <tr>
            <th>Tahun</th><td id="h-tahun"></td>
            <th>Periode</th><td id="h-periode"></td>
            <th>Total Mutasi Kredit</th><td class="text-right" id="h-credit"></td>
          </tr>
          <tr>
            <th>Rentang</th><td colspan="3" id="h-range"></td>
            <th>Saldo Akhir</th><td class="text-right font-weight-bold" id="h-closing"></td>
          </tr>
          <tr>
            <th>Jumlah Transaksi</th><td colspan="5" id="h-trx"></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h4 class="card-title">@yield('title') List</h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0">
          <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <div class="card-datatable table-responsive pt-0">
          <table id="bb2Table" class="table table-sm"><thead class="thead-light"></thead></table>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@section('styles')
<style>
  .bb2-summary th { background:#f3f2f7; font-weight:600; white-space:nowrap; }
  .bb2-summary td, .bb2-summary th { padding:.4rem .6rem; font-size:.85rem; }
  #bb2Table td, #bb2Table th { font-size:.8rem; white-space:nowrap; }

  /* baris SALDO AWAL / AKHIR -- mengikuti gaya movement2 di articlev2 */
  #bb2Table tbody tr.bb2-summary > td {
    background:#fafbfc !important;
    border-top:1px solid rgba(47,51,73,.08);
    border-bottom:1px solid rgba(47,51,73,.08);
    padding-top:.75rem; padding-bottom:.75rem;
    color:#2f3349; font-weight:600;
  }
  #bb2Table tbody tr.row-saldo-awal  > td { background:#f6f5ff !important; }
  #bb2Table tbody tr.row-saldo-akhir > td { background:#f2fbf6 !important; }
  #bb2Table tbody tr.bb2-summary > td:first-child { border-left:3px solid transparent; }
  #bb2Table tbody tr.row-saldo-awal  > td:first-child { border-left-color:#7367f0; }
  #bb2Table tbody tr.row-saldo-akhir > td:first-child { border-left-color:#28c76f; }
  .bb2-summary-badge {
    display:inline-flex; align-items:center; gap:.4rem;
    font-size:.72rem; font-weight:700; letter-spacing:.06em;
    padding:.28rem .6rem; border-radius:6px;
  }
  .bb2-summary-badge svg { width:13px; height:13px; }
  .row-saldo-awal  .bb2-summary-badge { color:#5e50ee; background:rgba(115,103,240,.12); }
  .row-saldo-akhir .bb2-summary-badge { color:#1f9d57; background:rgba(40,199,111,.12); }
  #bb2Table tbody tr.bb2-summary .bb2-amount { font-size:.95rem; font-weight:700; }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
  const KOLOM = {!! $kolom !!};
  const nf = (v) => (v === null || v === '' || v === undefined || v === 0)
    ? ''
    : new Intl.NumberFormat('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2}).format(v);

  // Baris SALDO AWAL/AKHIR di-merge sampai sebelum kolom Debet, angkanya
  // tetap sejajar di kolom Debet/Kredit sesuai tanda saldo.
  const MERGE_STOP = KOLOM.findIndex(c => c.data === 'debit');

  let bb2Table = null;

  const rangePickr = $('.flatpickr-range');
  if (rangePickr.length) {
    rangePickr.flatpickr({ dateFormat: "d-m-Y", mode: 'range' });
  }

  $("#btnReset").click(function () {
    $("#account,#searchStatus,#dept").val(null).trigger('change');
    if (rangePickr.length) rangePickr[0]._flatpickr.clear();
    $("#bb2-result").hide();
  });

  $("#btnSearch").click(function () {
    if (!$("#account").val()) {
      alert('COA wajib dipilih.');
      return;
    }
    $(".loading-spinner-container").addClass("-show");

    $.get("{{ route('bukuBesarV2.data') }}", {
      account: $("#account").val(),
      tahun: $("#tahun").val(),
      period1: $("#period1").val(),
      period2: $("#period2").val(),
      vcDate: $("#vcDate").val(),
      searchStatus: $("#searchStatus").val(),
      dept: $("#dept").val()
    }).done(function (res) {
      renderHeader(res.header);
      renderTable(res.header, res.rows);
      $("#bb2-result").show();
    }).fail(function (xhr) {
      alert((xhr.responseJSON && xhr.responseJSON.error) || 'Gagal memuat data.');
    }).always(function () {
      $(".loading-spinner-container").removeClass("-show");
    });
  });

  const renderHeader = (h) => {
    $("#bb2-title").text(h.account + ' — ' + h.description + '  |  Periode: ' + h.periode_text);
    $("#h-account").text(h.account + (h.is_header ? '  [HEADER]' : ''));
    $("#h-description").text(h.description);
    $("#h-kelompok").text(h.kelompok);
    $("#h-normal").text(h.saldo_normal);
    $("#h-tahun").text(h.tahun);
    $("#h-periode").text(h.periode);
    $("#h-range").text(h.date_from + ' s/d ' + h.date_to);
    $("#h-opening").text(nf(Math.abs(h.opening)) || '0,00');
    $("#h-debit").text(nf(h.total_debit) || '0,00');
    $("#h-credit").text(nf(h.total_credit) || '0,00');
    $("#h-closing").text(nf(Math.abs(h.closing)) || '0,00');
    $("#h-trx").text(h.jumlah_trx + (h.is_header ? '  (gabungan ' + h.coa_count + ' COA)' : ''));
  };

  /**
   * Gabungkan kolom Dept..Period jadi satu cell label pada baris ringkasan.
   * API-nya diterima sebagai argumen, bukan dibaca dari variabel bb2Table --
   * drawCallback dipanggil DI DALAM konstruktor DataTable, jadi saat draw
   * pertama bb2Table masih null.
   */
  const mergeSummaryRows = (api) => {
    $(api.table().body()).find('> tr').each(function () {
      const $tr = $(this);
      if ($tr.hasClass('bb2-merged')) return;
      const row = api.row(this);
      const d = row.length ? row.data() : null;
      if (!d || !d.is_summary) return;

      $tr.addClass('bb2-merged bb2-summary')
         .addClass(d.summary_type === 'OPENING' ? 'row-saldo-awal' : 'row-saldo-akhir');

      const $cells = $tr.children('td').slice(0, MERGE_STOP);
      if ($cells.length) {
        const icon = d.summary_type === 'OPENING' ? 'log-in' : 'flag';
        $cells.slice(1).remove();
        $cells.first()
          .attr('colspan', $cells.length)
          .addClass('text-left')
          .html("<span class='bb2-summary-badge'><i data-feather='" + icon + "'></i>"
                + d.summary_label + "</span> <span class='text-muted'>" + d.summary_note + "</span>");
      }
    });
    if (window.feather) feather.replace();
  };

  const renderTable = (h, rows) => {
    if (bb2Table) {
      bb2Table.destroy();
      $('#bb2Table tbody').remove();
      $('#bb2Table thead > tr').remove();
    }

    const amountIdx = KOLOM.map((c, i) => ['debit', 'credit'].includes(c.data) ? i : -1).filter(i => i >= 0);

    bb2Table = $('#bb2Table').DataTable({
      data: rows,
      columns: KOLOM,
      // Buku besar itu kronologis, dan baris SALDO AWAL/AKHIR harus tetap di
      // ujung atas/bawah -- jadi sorting kolom dimatikan.
      ordering: false,
      // scrollY sengaja TIDAK dipakai: scrollX + scrollY sama-sama memecah
      // tabel jadi header/body terpisah, dan baris colspan (SALDO AWAL/AKHIR)
      // bikin lebar kolom keduanya tidak sinkron.
      scrollX: true,
      lengthMenu: [[-1, 25, 50, 100], ['all', '25', '50', '100']],
      pageLength: -1,
      columnDefs: [
        {
          targets: amountIdx,
          className: 'text-right',
          render: function (v, type, row) {
            if (type !== 'display') return v === null ? 0 : v;
            const s = nf(v);
            return row.is_summary && s ? "<span class='bb2-amount'>" + s + "</span>" : s;
          }
        }
      ],
      dom: '<"d-flex justify-content-between align-items-center mx-1 row mt-75"<"col-md-6"l><"col-md-6 text-right"<"d-inline-flex"f>B>>t<"d-flex justify-content-between mx-2 row mb-1"<"col-md-6"i><"col-md-6"p>>',
      buttons: [{
        extend: 'excel',
        className: 'btn btn-outline-secondary ml-1',
        text: 'Excel',
        title: null,
        filename: 'buku_besar_v2_' + h.account + '_' + h.tahun
      }],
      drawCallback: function () { mergeSummaryRows(this.api()); }
    });
  };

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection
