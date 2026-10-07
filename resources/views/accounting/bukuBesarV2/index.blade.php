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
           <div class="form-group col-md-4">
            <label class="form-label" for="type_code">Account Type <small class="text-muted">(alternatif dari COA)</small></label>
            <select class="select2 form-control" id="type_code" name="type_code"
                    data-placeholder="Pilih Account Type" data-allow-clear="true">
              <option value=""></option>
              @foreach($accTypes as $t)
                <option value="{{ $t->code }}">{{ $t->code }} - {{ $t->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Menarik seluruh COA detail bertipe ini.</small>
          </div>
          <div class="form-group col-md-4">
            <label class="form-label" for="account">COA</label>
            <select class="select2 form-control" id="account" name="account"
                    data-placeholder="Pilih COA" data-allow-clear="true">
              <option value=""></option>
              @foreach($accounts as $val)
                <option value="{{ $val->account }}" data-header="{{ $val->acc_header }}">
                  {{ $val->account }} - {{ $val->description }}@if($val->acc_header == 'HEADER') [HEADER]@endif
                </option>
              @endforeach
            </select>
            <small class="text-muted">Pilih COA header untuk menarik transaksi seluruh COA di bawahnya.</small>
          </div>

        </div>
        <div class="form-row">
          <div class="form-group col-md-4">
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
            <label class="form-label" for="dept">Cost Center</label>
            <select class="select2 form-control" id="dept" name="dept" multiple>
              @foreach($depts as $val)
                <option value="{{ $val->code }}">{{ $val->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group col-md-4">
            <label class="form-label" for="vcDate">Date <small class="text-muted">(opsional, menimpa periode)</small></label>
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

        {{-- Toolbar: toggle By Date / By Account (kiri), Search + Export (kanan) --}}
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-1" id="bb2-toolbar">
          <div class="btn-group" id="bb2-mode" role="group">
            <button type="button" class="btn btn-primary" data-mode="date">By Date</button>
            <button type="button" class="btn btn-outline-primary" data-mode="account">By Account</button>
          </div>
          <div class="d-flex align-items-center">
            <label class="bb2-search mb-0 mr-1">Search:
              <input type="search" id="bb2-search" class="form-control ml-50" autocomplete="off" />
            </label>
            <div class="dropdown">
              <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i data-feather="share" class="font-small-4 mr-50"></i>Export
              </button>
              <div class="dropdown-menu dropdown-menu-right">
                <a class="dropdown-item" href="#" id="bb2-export-excel"><i data-feather="file" class="font-small-4 mr-50"></i>Excel</a>
                <a class="dropdown-item" href="#" id="bb2-export-pdf"><i data-feather="file-text" class="font-small-4 mr-50"></i>PDF</a>
              </div>
            </div>
          </div>
        </div>

        <div class="card-datatable table-responsive pt-0 bb2-scroll">
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

  .bb2-search { display:flex; align-items:center; white-space:nowrap; }
  .bb2-search input { width:200px; }

  /* Tanpa wrap; area scroll sendiri supaya header kolom bisa menempel di atas */
  .bb2-scroll { max-height:70vh; overflow:auto; }
  #bb2Table th, #bb2Table td { white-space:nowrap; vertical-align:middle; }
  #bb2Table thead th {
    position:sticky; top:0; z-index:3; background:#f3f2f7;
    box-shadow:inset 0 -1px 0 #dee2e6;
  }

  /* Banner: hanya muncul kalau COA yang dipilih adalah HEADER */
  #bb2Table tbody tr.bb2-banner > td {
    background:#dfe3e8; color:#1f2937; white-space:normal;
    border-top:2px solid #9ca3af; padding:.65rem .75rem;
  }
  /* Sub header per COA (mode By Account) */
  #bb2Table tbody tr.bb2-group > td {
    background:#f1f3f5; color:#1f2937; white-space:normal;
    border-top:2px solid #cfd4da; padding:.6rem .75rem;
  }
  .bb2-acc  { font-weight:700; color:#1f3a5f; margin-right:.75rem; }
  .bb2-name { font-weight:600; }
  .bb2-meta { font-size:.75rem; color:#6b7280; margin-top:.15rem; }
  .bb2-tag  { font-size:.7rem; font-weight:600; letter-spacing:.04em; color:#374151;
              border:1px solid #9ca3af; border-radius:3px; padding:.05rem .35rem; margin-right:.5rem; }

  /* Saldo awal: baris biasa, hanya dibedakan garis bawah */
  #bb2Table tbody tr.bb2-opening > td { background:#fff; font-weight:600; border-bottom:1px solid #dee2e6; }
  /* Saldo akhir: abu-abu netral. Mode By Date: menempel di bawah area scroll */
  #bb2Table tbody tr.bb2-closing > td {
    background:#f1f3f5; font-weight:700;
    box-shadow:inset 0 1px 0 #9ca3af, inset 0 -2px 0 #9ca3af;
  }
  #bb2Table.bb2-by-date tbody tr.bb2-closing > td { position:sticky; bottom:0; z-index:2; }
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
  const DATE_IDX = ['voucher_date', 'created_at', 'approval_at']
    .map(n => KOLOM.findIndex(c => c.data === n)).filter(i => i >= 0);

  let bb2Table = null;
  let lastParams = null;      // parameter pencarian terakhir (dipakai ulang saat toggle mode)
  let bb2Mode = 'date';       // 'date' | 'account'
  let bb2Term = '';           // kata kunci search

  const rangePickr = $('.flatpickr-range');
  if (rangePickr.length) {
    rangePickr.flatpickr({ dateFormat: "d-m-Y", mode: 'range' });
  }

  // COA dan Tipe Akun saling menggantikan.
  $("#account").on('change', function () { if ($(this).val()) $("#type_code").val(null).trigger('change'); });
  $("#type_code").on('change', function () { if ($(this).val()) $("#account").val(null).trigger('change'); });

  $("#btnReset").click(function () {
    $("#account,#type_code,#searchStatus,#dept").val(null).trigger('change');
    if (rangePickr.length) rangePickr[0]._flatpickr.clear();
    lastParams = null;
    $("#bb2-result").hide();
  });

  const loadData = () => {
    if (!lastParams) return;
    $(".loading-spinner-container").addClass("-show");

    $.get("{{ route('bukuBesarV2.data') }}", Object.assign({}, lastParams, { group_by: bb2Mode }))
      .done(function (res) {
        renderHeader(res.header);
        renderTable(res.header, res.rows);
        $("#bb2-result").show();
        if (window.feather) feather.replace({ width: 14, height: 14 });
      })
      .fail(function (xhr) {
        alert((xhr.responseJSON && xhr.responseJSON.error) || 'Gagal memuat data.');
      })
      .always(function () {
        $(".loading-spinner-container").removeClass("-show");
      });
  };

  $("#btnSearch").click(function () {
    if (!$("#account").val() && !$("#type_code").val()) {
      alert('Pilih COA atau Tipe Akun.');
      return;
    }
    lastParams = {
      account: $("#account").val(),
      type_code: $("#type_code").val(),
      tahun: $("#tahun").val(),
      period1: $("#period1").val(),
      period2: $("#period2").val(),
      vcDate: $("#vcDate").val(),
      searchStatus: $("#searchStatus").val(),
      dept: $("#dept").val()
    };
    loadData();
  });

  // Toggle By Date / By Account -> ambil ulang data dengan parameter filter terakhir.
  $("#bb2-mode button").on('click', function () {
    const m = $(this).data('mode');
    if (m === bb2Mode) return;
    bb2Mode = m;
    $("#bb2-mode button").each(function () {
      const on = $(this).data('mode') === bb2Mode;
      $(this).toggleClass('btn-primary', on).toggleClass('btn-outline-primary', !on);
    });
    loadData();
  });

  // Search: hanya menyaring baris transaksi; baris sub header/saldo selalu tampil.
  $.fn.dataTable.ext.search.push(function (settings, searchData, dataIndex, rowData) {
    if (settings.nTable.id !== 'bb2Table' || !bb2Term) return true;
    if (!rowData || rowData.row_type !== 'trx') return true;
    const hay = KOLOM.map(c => rowData[c.data] == null ? '' : rowData[c.data]).join(' ').toLowerCase();
    return hay.indexOf(bb2Term) !== -1;
  });
  $("#bb2-search").on('input', function () {
    bb2Term = $.trim($(this).val()).toLowerCase();
    if (bb2Table) bb2Table.draw();
  });

  $("#bb2-export-excel").on('click', function (e) { e.preventDefault(); if (bb2Table) bb2Table.button('excel:name').trigger(); });
  $("#bb2-export-pdf").on('click', function (e) { e.preventDefault(); if (bb2Table) bb2Table.button('pdf:name').trigger(); });

  const renderHeader = (h) => {
    $("#bb2-title").text(h.account + ' — ' + h.description + '  |  Periode: ' + h.periode_text);
    $("#h-account").text(h.account + (h.tag ? '  [' + h.tag + ']' : ''));
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
   */
  const styleSpecialRow = (tr, d) => {
    if (!d || d.row_type === 'trx') return;

    const $tr = $(tr).addClass('bb2-' + d.row_type);
    const $cells = $tr.children('td');
    let span, html;

    if (d.row_type === 'banner' || d.row_type === 'group') {
      span = $cells.length;
      html = (d.g_range ? "<span class='bb2-tag'>" + esc(d.g_tag || 'HEADER') + "</span>" : '')
           + "<span class='bb2-acc'>" + esc(d.account) + "</span><span class='bb2-name'>" + esc(d.nama_akun) + "</span>"
           + (d.g_range ? "<div class='bb2-meta'>Rincian: " + esc(d.g_range) + "</div>" : '');
    } else {
      span = MERGE_STOP;
      html = esc(d.s_label) + (d.s_note ? "<span class='bb2-note'>" + esc(d.s_note) + "</span>" : '');

      if (d.row_type === 'closing') {
        // Sisa kolom setelah Kredit digabung untuk hasil (Debet - Kredit).
        const $rest = $cells.slice(MERGE_STOP + 2);
        if ($rest.length) {
          const n = $rest.length;
          $rest.slice(1).remove();
          $rest.first().attr('colspan', n).addClass('text-left');
        }
      }
    }

    $cells.slice(1, span).remove();
    $cells.first().attr('colspan', span).addClass('text-left').html(html);
  };

  // Nilai untuk sorting: tanggal "dd-mm-yyyy [hh:mm]" -> yyyymmddhhmm, kode akun -> dipadding per segmen.
  const sortDate = (v) => {
    const m = /^(\d{2})-(\d{2})-(\d{4})(?: (\d{2}):(\d{2}))?$/.exec(v || '');
    return m ? m[3] + m[2] + m[1] + (m[4] || '00') + (m[5] || '00') : (v || '');
  };
  const sortAccount = (v) => v ? String(v).split('.').map(p => p.padStart(6, '0')).join('.') : '';
  const POS = { banner: 0, group: 0, opening: 1, trx: 2, closing: 3 };

  /* ---------------------------------------------------------------- Export helpers */

  // Baris sesuai urutan & filter yang sedang tampil (sama dengan urutan baris di file export).
  const exportedRows = () => bb2Table.rows({ order: 'applied', search: 'applied' }).data().toArray();

  const specialLabel = (d) => {
    if (d.row_type === 'banner' || d.row_type === 'group') {
      return (d.g_range ? '[' + (d.g_tag || 'HEADER') + '] ' : '')
           + (d.account || '') + ' — ' + (d.nama_akun || '')
           + (d.g_range ? '   |   Rincian: ' + d.g_range : '');
    }
    return (d.s_label || '') + (d.s_note ? '   ' + d.s_note : '');
  };

  const colLetter = (i) => {
    let s = '', n = i + 1;
    while (n > 0) { const m = (n - 1) % 26; s = String.fromCharCode(65 + m) + s; n = Math.floor((n - 1) / 26); }
    return s;
  };

  // "dd-mm-yyyy [hh:mm]" -> serial date Excel
  const toSerial = (v) => {
    const m = /^(\d{2})-(\d{2})-(\d{4})(?: (\d{2}):(\d{2}))?$/.exec((v || '').trim());
    if (!m) return null;
    const ms = Date.UTC(+m[3], +m[2] - 1, +m[1], +(m[4] || 0), +(m[5] || 0));
    return { serial: Math.round((ms / 86400000 + 25569) * 1e8) / 1e8, hasTime: !!m[4] };
  };

  // Tambah style tanggal ke styles.xml, kembalikan index style-nya.
  const addDateStyles = (xlsx) => {
    const st = xlsx.xl['styles.xml'];
    const ns = st.documentElement.namespaceURI;
    let numFmts = st.getElementsByTagName('numFmts')[0];
    if (!numFmts) {
      numFmts = st.createElementNS(ns, 'numFmts');
      st.documentElement.insertBefore(numFmts, st.documentElement.firstChild);
    }
    [[200, 'dd-mm-yyyy'], [201, 'dd-mm-yyyy hh:mm']].forEach(([id, code]) => {
      const e = st.createElementNS(ns, 'numFmt');
      e.setAttribute('numFmtId', id);
      e.setAttribute('formatCode', code);
      numFmts.appendChild(e);
    });
    numFmts.setAttribute('count', numFmts.getElementsByTagName('numFmt').length);

    const xfs = st.getElementsByTagName('cellXfs')[0];
    const base = xfs.getElementsByTagName('xf').length;
    [200, 201].forEach(id => {
      const x = st.createElementNS(ns, 'xf');
      x.setAttribute('numFmtId', id);
      x.setAttribute('fontId', 0);
      x.setAttribute('fillId', 0);
      x.setAttribute('borderId', 0);
      x.setAttribute('xfId', 0);
      x.setAttribute('applyNumberFormat', 1);
      xfs.appendChild(x);
    });
    xfs.setAttribute('count', xfs.getElementsByTagName('xf').length);
    return { date: base, datetime: base + 1 };
  };

  const customizeExcel = (xlsx) => {
    const sheet = xlsx.xl.worksheets['sheet1.xml'];
    const ns = sheet.documentElement.namespaceURI;
    const sty = addDateStyles(xlsx);
    const dateLetters = DATE_IDX.map(colLetter);
    const data = exportedRows();
    const trs = $('sheetData > row', sheet).toArray();   // [0] = header
    const merges = [];
    const letterOf = (c) => c.getAttribute('r').replace(/\d+/, '');

    data.forEach((d, i) => {
      const tr = trs[i + 1];
      if (!tr) return;
      const rn = tr.getAttribute('r');
      const cells = $(tr).children('c').toArray();

      if (d.row_type === 'trx') {
        // Tanggal: teks -> nilai date sungguhan
        cells.forEach(c => {
          if (dateLetters.indexOf(letterOf(c)) < 0) return;
          const p = toSerial($('t', c).text());
          if (!p) return;
          $(c).children().remove();
          c.removeAttribute('t');
          const v = sheet.createElementNS(ns, 'v');
          v.textContent = p.serial;
          c.appendChild(v);
          c.setAttribute('s', p.hasTime ? sty.datetime : sty.date);
        });
        return;
      }

      // Baris khusus: sub header / saldo awal / total / saldo akhir
      const isBlock = d.row_type === 'banner' || d.row_type === 'group';
      const toIdx = isBlock ? KOLOM.length - 1 : MERGE_STOP - 1;
      cells.forEach((c, k) => { if (k > 0 && k <= toIdx) tr.removeChild(c); });

      const c0 = cells[0];
      if (!c0) return;
      $(c0).children().remove();
      c0.setAttribute('t', 'inlineStr');
      c0.setAttribute('s', '2');   // bold
      const is = sheet.createElementNS(ns, 'is');
      const t = sheet.createElementNS(ns, 't');
      t.textContent = specialLabel(d);
      is.appendChild(t);
      c0.appendChild(is);
      if (toIdx > 0) merges.push('A' + rn + ':' + colLetter(toIdx) + rn);
      cells.slice(toIdx + 1).forEach(c => c.setAttribute('s', '2'));
    });

    if (merges.length) {
      const mc = sheet.createElementNS(ns, 'mergeCells');
      mc.setAttribute('count', merges.length);
      merges.forEach(ref => {
        const m = sheet.createElementNS(ns, 'mergeCell');
        m.setAttribute('ref', ref);
        mc.appendChild(m);
      });
      const sd = sheet.getElementsByTagName('sheetData')[0];
      sd.parentNode.insertBefore(mc, sd.nextSibling);
    }
  };

  const customizePdf = (doc) => {
    doc.pageMargins = [20, 20, 20, 20];
    doc.defaultStyle.fontSize = 7;
    const tbl = doc.content.filter(c => c.table)[0];
    if (!tbl) return;
    const body = tbl.table.body;
    const n = body[0].length;
    const data = exportedRows();

    const merged = (text, span, fill) => [{ text: text, colSpan: span, bold: true, alignment: 'left', fillColor: fill }]
      .concat(Array.from({ length: span - 1 }, () => ({})));

    data.forEach((d, i) => {
      const row = body[i + 1];
      if (!row || d.row_type === 'trx') return;
      if (d.row_type === 'banner') {
        body[i + 1] = merged(specialLabel(d), n, '#dfe3e8');
      } else if (d.row_type === 'group') {
        body[i + 1] = merged(specialLabel(d), n, '#eceef1');
      } else {
        const rest = row.slice(MERGE_STOP).map(c => Object.assign({}, c, { bold: true, fillColor: '#f1f3f5' }));
        body[i + 1] = merged(specialLabel(d), MERGE_STOP, '#f1f3f5').concat(rest);
      }
    });
    tbl.table.widths = Array(n).fill('*');
    tbl.layout = 'lightHorizontalLines';
  };

  /* ---------------------------------------------------------------- Table */

  const renderTable = (h, rows) => {
    if (bb2Table) {
      bb2Table.destroy();
      $('#bb2Table tbody').remove();
      $('#bb2Table thead > tr').remove();
    }

    // Kolom tersembunyi untuk orderFixed:
    //  _grp = nomor blok COA (naik tiap sub header "group"), _pos = urutan tipe baris.
    // Dengan ini sub header, saldo awal, dan saldo akhir tetap di tempatnya dan baris
    // transaksi hanya berpindah di dalam blok COA-nya sendiri saat disort.
    let g = 0;
    rows.forEach(r => {
      if (r.row_type === 'group') g++;
      r._grp = g;
      r._pos = POS[r.row_type] ?? 2;
    });
    const GRP_IDX = KOLOM.length;
    const POS_IDX = KOLOM.length + 1;
    const kolomDt = KOLOM.concat([
      { data: '_grp', visible: false, searchable: false, orderable: false },
      { data: '_pos', visible: false, searchable: false, orderable: false }
    ]);
    const idxOf = (name) => KOLOM.findIndex(c => c.data === name);

    const amountIdx = KOLOM.map((c, i) => ['debit', 'credit'].includes(c.data) ? i : -1).filter(i => i >= 0);

    $('#bb2Table').toggleClass('bb2-by-date', bb2Mode === 'date');

    bb2Table = $('#bb2Table').DataTable({
      data: rows,
      columns: kolomDt,
      // Semua kolom bisa disort (default: urutan kronologis dari server).
      // Search memakai input di toolbar (lihat ext.search), paging dimatikan.
      ordering: true,
      order: [],
      orderFixed: { pre: [[GRP_IDX, 'asc'], [POS_IDX, 'asc']] },
      searching: true,
      paging: false,
      info: false,
      columnDefs: [
        {
          targets: DATE_IDX,
          render: function (v, type) { return (type === 'sort' || type === 'type') ? sortDate(v) : v; }
        },
        {
          targets: idxOf('account'),
          render: function (v, type) { return (type === 'sort' || type === 'type') ? sortAccount(v) : v; }
        },
        {
          targets: amountIdx,
          className: 'text-right',
          render: function (v, type, row) {
            if (type !== 'display') return v === null ? '' : v;
            return row.is_summary ? nfz(v) : nf(v);
          }
        }
      ],
      // Tombol export disembunyikan; dipicu dari dropdown Export di toolbar.
      dom: '<"d-none"B>t',
      buttons: [
        {
          extend: 'excel',
          name: 'excel',
          title: null,
          filename: function () { return 'buku_besar_v2_' + h.account + '_' + h.tahun + '_' + bb2Mode; },
          customize: customizeExcel
        },
        {
          extend: 'pdf',
          name: 'pdf',
          title: null,
          orientation: 'landscape',
          pageSize: 'A4',
          filename: function () { return 'buku_besar_v2_' + h.account + '_' + h.tahun + '_' + bb2Mode; },
          customize: customizePdf
        }
      ],
      createdRow: function (tr, d) { styleSpecialRow(tr, d); }
    });
  };

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection