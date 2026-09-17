{{--
  Script preview periode (interaktif, "recalculate") dipakai bareng oleh
  Create & Edit (saat status masih NEW). Live-pull data Delivery terkini
  untuk Periode+Tahun yang dipilih -- setiap kali dipanggil ulang (ganti
  periode/tahun, atau reload halaman Edit), angka selalu representasi data
  Delivery TERBARU, bukan snapshot lama.

  Butuh sebelum di-include:
    const URL_PREVIEW_PERIOD = "{{ route('conversionReport.previewPeriod') }}";
    const URL_EXPORT_PREVIEW = "{{ route('conversionReport.exportPreview') }}";
    const EXCLUDE_ID = null | "<encrypted id>"   -- dipakai halaman Edit supaya
                                                      dokumen ini tidak dianggap
                                                      bentrok periode dgn dirinya sendiri.
  Elemen yang dibutuhkan di DOM: #periode #tahun #previewEmpty #previewLoading
  #previewDuplicate #previewWrap #previewRows #btnExport #btnSave (opsional)
  + kartu ringkasan dari _summaryCards.blade.php + modal dari _detailDnModal.blade.php.
--}}
<script type="text/javascript">
  let previewRows = [];
  let dnByArticle = {};
  let convValue = 0;
  let previewHasData = false;

  function humanize(n) {
    n = parseFloat(n) || 0;
    return n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // Stempel tanggal+jam untuk nama file export: YYYYMMDD_HHmmss
  function exportStamp() {
    const d = new Date();
    const p = (x) => String(x).padStart(2, '0');
    return `${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}_${p(d.getHours())}${p(d.getMinutes())}${p(d.getSeconds())}`;
  }

  const isPaintingUom = (uom) => ['PCS', 'SET'].includes((uom || '').trim().toUpperCase());

  function loadPreview() {
    const periode = $('#periode').val();
    const tahun = $('#tahun').val();
    previewHasData = false;

    if (!periode || !tahun) {
      $('#previewEmpty').show();
      $('#previewWrap, #previewLoading, #previewDuplicate').hide();
      $('#btnExport').addClass('d-none');
      $('#btnSave').prop('disabled', true);
      return;
    }

    $('#previewEmpty, #previewWrap, #previewDuplicate').hide();
    $('#previewLoading').show();
    $('#btnExport').addClass('d-none');
    $('#btnSave').prop('disabled', true);

    const params = { periode: periode, tahun: tahun };
    if (typeof EXCLUDE_ID !== 'undefined' && EXCLUDE_ID) params.excludeId = EXCLUDE_ID;

    $.get(URL_PREVIEW_PERIOD, params, function (res) {
      $('#previewLoading').hide();

      if (!res.status) {
        if (res.duplicate) {
          $('#previewDuplicate').text(res.message).show();
        } else {
          $('#previewEmpty').text(res.message || 'Periode belum lengkap.').show();
        }
        return;
      }

      if (!res.rows || res.rows.length === 0) {
        $('#previewEmpty').text('Tidak ada data Delivery pada periode ini.').show();
        return;
      }

      previewRows = res.rows;
      dnByArticle = res.dnByArticle || {};
      convValue = parseFloat(res.conversionValue) || 0;
      previewHasData = true;

      let html = '';
      previewRows.forEach((r, i) => {
        const painting = isPaintingUom(r.uom);
        const conv = parseFloat(r.conversion) || 0;
        html += `<tr>
          <td class="text-center">${i + 1}</td>
          <td>${r.article_alternative_code}</td>
          <td>${r.article_desc}</td>
          <td>${r.customer_names}</td>
          <td class="text-right">${humanize(r.total_qty)} ${r.uom || ''}</td>
          <td class="text-right">${painting ? humanize(conv) : '-'}</td>
          <td class="text-right">${painting ? '-' : humanize(conv)}</td>
          <td class="text-center">
            <button type="button" class="btn btn-icon btn-flat-primary btn-info-row" data-article="${r.article_code}" data-label="${r.article_alternative_code} - ${r.article_desc}">
              <i data-feather="info"></i>
            </button>
          </td>
        </tr>`;
      });

      $('#previewRows').html(html);

      const totalArticle    = previewRows.length;
      const totalQty        = previewRows.reduce((sum, r) => sum + (parseFloat(r.total_qty) || 0), 0);
      const totalConversion = previewRows.reduce((sum, r) => sum + (parseFloat(r.conversion) || 0), 0);
      const totalConvPainting = previewRows.reduce((sum, r) =>
        sum + (isPaintingUom(r.uom) ? (parseFloat(r.conversion) || 0) : 0), 0);
      const totalConvNonPainting = previewRows.reduce((sum, r) =>
        sum + (isPaintingUom(r.uom) ? 0 : (parseFloat(r.conversion) || 0)), 0);

      $('#sumTotalArticle').text(totalArticle);
      $('#sumTotalQty').text(humanize(totalQty));
      $('#sumTotalConversion').text(humanize(totalConversion));
      $('#sumConvPainting').text(humanize(totalConvPainting));
      $('#sumConvNonPainting').text(humanize(totalConvNonPainting));

      $('#previewWrap').show();
      $('#btnExport').removeClass('d-none');
      $('#btnSave').prop('disabled', false);
      if (window.feather) feather.replace({ width: 14, height: 14 });
    }).fail(function () {
      $('#previewLoading').hide();
      Swal.fire('Error', 'Gagal menarik data delivery periode ini.', 'error');
    });
  }

  $('#periode, #tahun').on('change', loadPreview);

  $('#btnExport').on('click', function () {
    const periode = $('#periode').val();
    const tahun = $('#tahun').val();
    if (!periode || !tahun) return;
    window.location.href = URL_EXPORT_PREVIEW + '?periode=' + periode + '&tahun=' + tahun;
  });

  let mdlCurrentLines = [];
  let mdlCurrentLabel = '';
  let mdlCurrentArticle = null;

  // konversi per DN = ((price_unit - avg_purchase) * qty) / conversion_value
  function convPerDn(line, article) {
    if (!article || convValue <= 0) return 0;
    const avgPurchase = parseFloat(article.avg_purchase_price) || 0;
    return (((parseFloat(line.price_unit) || 0) - avgPurchase) * (parseFloat(line.qty) || 0)) / convValue;
  }

  $(document).on('click', '.btn-info-row', function () {
    const articleCode = $(this).data('article');
    const label = $(this).data('label');
    const lines = dnByArticle[articleCode] || [];
    const article = previewRows.find(r => r.article_code === articleCode);

    mdlCurrentLines = lines;
    mdlCurrentLabel = label;
    mdlCurrentArticle = article;

    $('#mdlArticleLabel').text('| ' + label);

    let html = '';
    let tQty = 0, tPrice = 0, tConv = 0;
    lines.forEach((l, i) => {
      const dnCell = l.dn_url
        ? `<a href="${l.dn_url}" target="_blank">${l.dn_number}</a>`
        : (l.dn_number || '-');
      const soCell = l.so_url
        ? `<a href="${l.so_url}" target="_blank">${l.so_number}</a>`
        : (l.so_number || '-');
      const conv = convPerDn(l, article);
      tQty += parseFloat(l.qty) || 0;
      tPrice += parseFloat(l.price_total) || 0;
      tConv += conv;
      html += `<tr>
        <td class="text-center">${i + 1}</td>
        <td>${dnCell}</td>
        <td>${soCell}</td>
        <td>${l.customer_name || '-'}</td>
        <td class="text-right">${humanize(l.qty)}</td>
        <td class="text-right">${humanize(l.price_unit)}</td>
        <td class="text-right">${humanize(l.price_total)}</td>
        <td class="text-right">${humanize(conv)}</td>
      </tr>`;
    });
    $('#mdlDetailRows').html(html || '<tr><td colspan="8" class="text-center text-muted">Tidak ada data.</td></tr>');
    $('#mdlTotalQty').text(humanize(tQty));
    $('#mdlTotalPrice').text(humanize(tPrice));
    $('#mdlTotalConv').text(humanize(tConv));
    $('#mdlDetail').modal('show');
    if (window.feather) feather.replace({ width: 14, height: 14 });
  });

  $('#btnExportDnDetail').on('click', function () {
    if (!mdlCurrentLines.length) {
      Swal.fire('Info', 'Tidak ada data untuk diexport.', 'info');
      return;
    }
    const sep = ';';
    const esc = (v) => '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"';
    // Angka dibulatkan 2 desimal & pakai koma sebagai desimal (format Indonesia)
    // supaya nilai di Excel = nilai di modal, bukan kebaca sbg ribuan.
    const numId = (n) => (Math.round(((parseFloat(n) || 0) + Number.EPSILON) * 100) / 100)
      .toFixed(2).replace('.', ',');
    const article = mdlCurrentArticle;
    const header = ['No', 'DN Number', 'SO Number', 'Customer', 'Qty', 'Price Unit', 'Price Total', 'Konversi'];
    let csv = header.map(esc).join(sep) + '\r\n';
    let tQty = 0, tPrice = 0, tConv = 0;
    mdlCurrentLines.forEach((l, i) => {
      const conv = convPerDn(l, article);
      tQty += parseFloat(l.qty) || 0;
      tPrice += parseFloat(l.price_total) || 0;
      tConv += conv;
      csv += [
        i + 1,
        l.dn_number || '',
        l.so_number || '',
        l.customer_name || '',
        numId(l.qty),
        numId(l.price_unit),
        numId(l.price_total),
        numId(conv),
      ].map(esc).join(sep) + '\r\n';
    });
    csv += ['', '', '', 'TOTAL', numId(tQty), '', numId(tPrice), numId(tConv)].map(esc).join(sep) + '\r\n';

    const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'Detail_DN_' + mdlCurrentLabel.replace(/[^A-Za-z0-9]+/g, '_') + '_' + exportStamp() + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  });
</script>
