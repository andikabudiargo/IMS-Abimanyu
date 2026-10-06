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
        <div class="form-row">
          <div class="form-group col-md-6">
            <label class="form-label" for="account">COA <span class="text-danger">*</span></label>
            <select class="select2 form-control" id="account" name="account">
              <option value=""></option>
              @foreach($accounts as $val)
                <option value="{{ $val->account }}">{{ $val->account }} - {{ $val->description }}</option>
              @endforeach
            </select>
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
            <label class="form-label" for="bulan">Bulan</label>
            <select class="select2 form-control" id="bulan" name="bulan">
              @foreach($bulan as $i => $nm)
                <option value="{{ $i }}" {{ $i == (int) date('n') ? 'selected' : '' }}>{{ $nm }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group col-md-2">
            <label class="form-label" for="mode">Mode</label>
            <select class="select2 form-control" id="mode" name="mode">
              <option value="ytd">Januari s/d bulan</option>
              <option value="month">Bulan terpilih saja</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-2">
            <label class="form-label" for="dateFrom">Tanggal Dari <small class="text-muted">(opsional)</small></label>
            <input type="date" id="dateFrom" name="dateFrom" class="form-control" />
          </div>
          <div class="form-group col-md-2">
            <label class="form-label" for="dateTo">Tanggal Sampai <small class="text-muted">(opsional)</small></label>
            <input type="date" id="dateTo" name="dateTo" class="form-control" />
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
            <th width="12%">Kode COA</th><td width="10%" id="h-account"></td>
            <th width="12%">Nama Akun</th><td id="h-description"></td>
            <th width="14%">Saldo Awal</th><td width="16%" class="text-right" id="h-opening"></td>
          </tr>
          <tr>
            <th>Bulan</th><td id="h-bulan"></td>
            <th>Kelompok</th><td id="h-kelompok"></td>
            <th>Total Mutasi Debet</th><td class="text-right" id="h-debit"></td>
          </tr>
          <tr>
            <th>Mode</th><td id="h-mode"></td>
            <th>Saldo Normal</th><td id="h-normal"></td>
            <th>Total Mutasi Kredit</th><td class="text-right" id="h-credit"></td>
          </tr>
          <tr>
            <th>Tahun</th><td id="h-tahun"></td>
            <th>Periode</th><td id="h-periode"></td>
            <th>Saldo Akhir</th><td class="text-right font-weight-bold" id="h-closing"></td>
          </tr>
          <tr>
            <th>Rentang</th><td colspan="3" id="h-range"></td>
            <th>Jumlah Transaksi</th><td class="text-right" id="h-trx"></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Detail Transaksi</h4>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <div class="card-datatable table-responsive pt-0">
          <table id="bb2Table" class="table table-sm">
            <thead class="thead-light">
              <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Periode</th>
                <th>No. Voucher</th>
                <th>Referensi</th>
                <th>Keterangan</th>
                <th>Dept</th>
                <th class="text-right">Debet</th>
                <th class="text-right">Kredit</th>
                <th class="text-right">Saldo Debet</th>
                <th class="text-right">Saldo Kredit</th>
                <th>Status</th>
              </tr>
            </thead>
          </table>
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
  #bb2Table tbody tr.bb2-opening { background:#fffbdd; font-weight:600; font-style:italic; }
  #bb2Table td, #bb2Table th { font-size:.8rem; white-space:nowrap; }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
  const nf = (v) => (v === null || v === '' || v === undefined || v === 0)
    ? '-'
    : new Intl.NumberFormat('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2}).format(v);

  let bb2Table = null;

  $("#btnReset").click(function () {
    $("#account,#searchStatus,#dept").val(null).trigger('change');
    $("#dateFrom,#dateTo").val('');
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
      bulan: $("#bulan").val(),
      mode: $("#mode").val(),
      dateFrom: $("#dateFrom").val(),
      dateTo: $("#dateTo").val(),
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
    $("#h-account").text(h.account);
    $("#h-description").text(h.description);
    $("#h-kelompok").text(h.kelompok);
    $("#h-normal").text(h.saldo_normal);
    $("#h-bulan").text(h.bulan);
    $("#h-mode").text(h.mode);
    $("#h-tahun").text(h.tahun);
    $("#h-periode").text(h.periode_text);
    $("#h-range").text(h.date_from + ' s/d ' + h.date_to);
    $("#h-opening").text(nf(h.opening) + ' ' + h.opening_side);
    $("#h-debit").text(nf(h.total_debit));
    $("#h-credit").text(nf(h.total_credit));
    $("#h-closing").text(nf(h.closing) + ' ' + h.closing_side);
    $("#h-trx").text(h.jumlah_trx);
  };

  const renderTable = (h, rows) => {
    const opening = {
      no: '', tanggal: '', period: '', voucher_number: '', reference: '',
      description: 'SALDO AWAL ' + h.periode_text.split(' s/d ')[0],
      dept: '', debit: null, credit: null,
      saldo_debit: h.opening_side === 'D' ? h.opening : null,
      saldo_credit: h.opening_side === 'K' ? h.opening : null,
      status: '', _opening: true
    };

    if (bb2Table) { bb2Table.destroy(); $('#bb2Table tbody').remove(); }

    bb2Table = $('#bb2Table').DataTable({
      data: [opening].concat(rows),
      // Saldo berjalan dihitung urut di server, jadi sorting kolom dimatikan.
      ordering: false,
      paging: true,
      pageLength: 50,
      lengthMenu: [[25, 50, 100, -1], ['25', '50', '100', 'all']],
      scrollX: true,
      createdRow: function (row, data) {
        if (data._opening) $(row).addClass('bb2-opening');
      },
      columns: [
        { data: 'no' },
        { data: 'tanggal' },
        { data: 'period' },
        { data: 'voucher_number' },
        { data: 'reference' },
        { data: 'description' },
        { data: 'dept' },
        { data: 'debit', className: 'text-right', render: (d) => nf(d) },
        { data: 'credit', className: 'text-right', render: (d) => nf(d) },
        { data: 'saldo_debit', className: 'text-right', render: (d) => nf(d) },
        { data: 'saldo_credit', className: 'text-right', render: (d) => nf(d) },
        { data: 'status' }
      ],
      dom: '<"d-flex justify-content-between align-items-center mx-1 row mt-75"<"col-md-6"l><"col-md-6 text-right"<"d-inline-flex"f>B>>t<"d-flex justify-content-between mx-2 row mb-1"<"col-md-6"i><"col-md-6"p>>',
      buttons: [{
        extend: 'excel',
        className: 'btn btn-outline-secondary ml-1',
        text: 'Excel',
        title: null,
        filename: 'buku_besar_v2_' + h.account + '_' + h.tahun
      }]
    });
  };

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection
