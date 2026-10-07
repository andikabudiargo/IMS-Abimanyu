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
      <table class="table table-sm table-bordered bb2-info mb-0">
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
          <table id="bb2Table" class="table"><thead class="thead-light"></thead></table>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@section('styles')
<style>
  .bb2-info th { background:#f3f4f6; font-weight:600; white-space:nowrap; }
  .bb2-info td, .bb2-info th { padding:.4rem .6rem; font-size:.85rem; }

  #bb2Table th, #bb2Table td { white-space:nowrap; vertical-align:middle; }
  #bb2Table td.bb2-desc { white-space:normal; min-width:240px; }

  /* Banner: hanya muncul kalau COA yang dipilih adalah HEADER */
  #bb2Table tbody tr.bb2-banner > td {
    background:#dfe3e8; color:#1f2937; white-space:normal;
    border-top:2px solid #9ca3af; padding:.65rem .75rem;
  }
  /* Sub header per COA */
  #bb2Table tbody tr.bb2-group > td {
    background:#f1f3f5; color:#1f2937; white-space:normal;
    border-top:1px solid #cfd4da; padding:.6rem .75rem;
  }
  .bb2-acc  { font-weight:700; color:#1f3a5f; margin-right:.75rem; }
  .bb2-name { font-weight:600; }
  .bb2-meta { font-size:.75rem; color:#6b7280; margin-top:.15rem; }
  .bb2-tag  { font-size:.7rem; font-weight:600; letter-spacing:.04em; color:#374151;
              border:1px solid #9ca3af; border-radius:3px; padding:.05rem .35rem; margin-right:.5rem; }

  /* Saldo awal: baris biasa, hanya dibedakan garis bawah */
  #bb2Table tbody tr.bb2-opening > td { background:#fff; font-weight:600; border-bottom:1px solid #dee2e6; }
  /* Total mutasi & saldo akhir: abu-abu netral */
  #bb2Table tbody tr.bb2-total > td,
  #bb2Table tbody tr.bb2-closing > td { background:#f1f3f5; font-weight:600; }
  #bb2Table tbody tr.bb2-total > td { border-top:1px solid #9ca3af; }
  #bb2Table tbody tr.bb2-closing > td { border-bottom:2px solid #9ca3af; font-weight:700; }
  .bb2-note { font-weight:400; color:#6b7280; margin-left:.5rem; font-size:.8rem; }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
  const KOLOM = {!! $kolom !!};
  const nf = (v) => (v === null || v === '' || v === undefined || v === 0)
    ? ''
    : new Intl.NumberFormat('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2}).format(v);
  // Baris ringkasan: nol tetap ditampilkan, null (sisi yang tidak dipakai) dikosongkan.
  const nfz = (v) => (v === null || v === undefined)
    ? ''
    : new Intl.NumberFormat('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2}).format(v);
  const esc = (s) => $('<div>').text(s === null || s === undefined ? '' : s).html();

  // Label baris ringkasan di-merge sampai sebelum kolom Debet.
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
   * Dipanggil sekali per baris saat DataTables membuat <tr>-nya.
   * - banner/group : satu cell selebar tabel (nomor + nama akun, range bila HEADER)
   * - opening/total/closing : label di-merge sampai sebelum kolom Debet
   * Export Excel membaca data (bukan DOM), jadi cell yang di-merge tidak mengganggu.
   */
  const styleSpecialRow = (tr, d) => {
    if (!d || d.row_type === 'trx') return;

    const $tr = $(tr).addClass('bb2-' + d.row_type);
    const $cells = $tr.children('td');
    let span, html;

    if (d.row_type === 'banner') {
      span = $cells.length;
      html = "<span class='bb2-tag'>HEADER</span>"
           + "<span class='bb2-acc'>" + esc(d.account) + "</span><span class='bb2-name'>" + esc(d.nama_akun) + "</span>"
           + (d.g_range ? "<div class='bb2-meta'>Rincian: " + esc(d.g_range) + "</div>" : '');
    } else if (d.row_type === 'group') {
      span = $cells.length;
      const meta = ['Kelompok: ' + esc(d.g_kelompok), 'Saldo normal: ' + esc(d.g_normal)];
      if (d.g_range) meta.push('Rincian: ' + esc(d.g_range));
      html = (d.g_range ? "<span class='bb2-tag'>HEADER</span>" : '')
           + "<span class='bb2-acc'>" + esc(d.account) + "</span><span class='bb2-name'>" + esc(d.nama_akun) + "</span>"
           + "<div class='bb2-meta'>" + meta.join(' &nbsp;·&nbsp; ') + "</div>";
    } else {
      span = MERGE_STOP;
      html = esc(d.s_label) + (d.s_note ? "<span class='bb2-note'>" + esc(d.s_note) + "</span>" : '');
    }

    $cells.slice(1, span).remove();
    $cells.first().attr('colspan', span).addClass('text-left').html(html);
  };

  const renderTable = (h, rows) => {
    if (bb2Table) {
      bb2Table.destroy();
      $('#bb2Table tbody').remove();
      $('#bb2Table thead > tr').remove();
    }

    const amountIdx = KOLOM.map((c, i) => ['debit', 'credit'].includes(c.data) ? i : -1).filter(i => i >= 0);
    const descIdx = KOLOM.findIndex(c => c.data === 'description');

    bb2Table = $('#bb2Table').DataTable({
      data: rows,
      columns: KOLOM,
      // Buku besar kronologis per COA, dan baris saldo harus tetap di tempatnya:
      // sorting, pencarian, dan paging dimatikan. Lebar tabel ditangani oleh
      // wrapper .table-responsive (bukan scrollX, supaya baris colspan tidak
      // merusak sinkronisasi lebar kolom).
      ordering: false,
      searching: false,
      paging: false,
      info: false,
      columnDefs: [
        {
          targets: amountIdx,
          className: 'text-right',
          render: function (v, type, row) {
            if (type !== 'display') return v === null ? '' : v;
            return row.is_summary ? nfz(v) : nf(v);
          }
        },
        { targets: descIdx, className: 'bb2-desc' }
      ],
      dom: '<"d-flex justify-content-end align-items-center mx-1 mt-75"B>t',
      buttons: [{
        extend: 'excel',
        className: 'btn btn-outline-secondary ml-1',
        text: 'Excel',
        title: null,
        filename: 'buku_besar_v2_' + h.account + '_' + h.tahun
      }],
      createdRow: function (tr, d) { styleSpecialRow(tr, d); }
    });
  };

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection