{{-- Script modal breakdown DN (show & edit). Butuh var JS: URL_DETAIL_DN. --}}
<script type="text/javascript">
  function humanizeDn(n) {
    n = parseFloat(n) || 0;
    return n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // Stempel tanggal+jam untuk nama file export: YYYYMMDD_HHmmss
  function exportStamp() {
    const d = new Date();
    const p = (x) => String(x).padStart(2, '0');
    return `${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}_${p(d.getHours())}${p(d.getMinutes())}${p(d.getSeconds())}`;
  }

  let mdlRows = [];
  let mdlLabel = '';

  function loadDetailDnModal(detId, label) {
    mdlLabel = label || '';
    $('#mdlArticleLabel').text('| ' + mdlLabel);
    $('#mdlDetailRows').html('<tr><td colspan="8" class="text-center text-muted">Memuat data...</td></tr>');
    $('#mdlTotalQty, #mdlTotalPrice, #mdlTotalConv').text('0');
    $('#mdlDetail').modal('show');

    $.get(URL_DETAIL_DN, { reportDetId: detId }, function (res) {
      mdlRows = (res && res.rows) ? res.rows : [];
      const totals = (res && res.totals) ? res.totals : { qty: 0, price_total: 0, conversion: 0 };

      let html = '';
      mdlRows.forEach((r) => {
        const dnCell = r.dn_url ? `<a href="${r.dn_url}" target="_blank">${r.dn_number}</a>` : (r.dn_number || '-');
        const soCell = r.so_url ? `<a href="${r.so_url}" target="_blank">${r.so_number}</a>` : (r.so_number || '-');
        html += `<tr>
          <td class="text-center">${r.no}</td>
          <td>${dnCell}</td>
          <td>${soCell}</td>
          <td>${r.customer_name || '-'}</td>
          <td class="text-right">${humanizeDn(r.qty)}</td>
          <td class="text-right">${humanizeDn(r.price_unit)}</td>
          <td class="text-right">${humanizeDn(r.price_total)}</td>
          <td class="text-right">${humanizeDn(r.conversion)}</td>
        </tr>`;
      });
      $('#mdlDetailRows').html(html || '<tr><td colspan="8" class="text-center text-muted">Tidak ada data.</td></tr>');
      $('#mdlTotalQty').text(humanizeDn(totals.qty));
      $('#mdlTotalPrice').text(humanizeDn(totals.price_total));
      $('#mdlTotalConv').text(humanizeDn(totals.conversion));
      if (window.feather) feather.replace({ width: 14, height: 14 });
    }).fail(function () {
      $('#mdlDetailRows').html('<tr><td colspan="8" class="text-center text-danger">Gagal memuat data.</td></tr>');
    });
  }

  $('#btnExportDnDetail').on('click', function () {
    if (!mdlRows.length) {
      Swal.fire('Info', 'Tidak ada data untuk diexport.', 'info');
      return;
    }
    const sep = ';';
    const esc = (v) => '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"';
    const numId = (n) => (Math.round(((parseFloat(n) || 0) + Number.EPSILON) * 100) / 100).toFixed(2).replace('.', ',');
    const header = ['No', 'DN Number', 'SO Number', 'Customer', 'Qty', 'Price Unit', 'Price Total', 'Konversi'];
    let csv = header.map(esc).join(sep) + '\r\n';
    let tQty = 0, tPrice = 0, tConv = 0;
    mdlRows.forEach((r) => {
      tQty += parseFloat(r.qty) || 0;
      tPrice += parseFloat(r.price_total) || 0;
      tConv += parseFloat(r.conversion) || 0;
      csv += [
        r.no, r.dn_number || '', r.so_number || '', r.customer_name || '',
        numId(r.qty), numId(r.price_unit), numId(r.price_total), numId(r.conversion),
      ].map(esc).join(sep) + '\r\n';
    });
    csv += ['', '', '', 'TOTAL', numId(tQty), '', numId(tPrice), numId(tConv)].map(esc).join(sep) + '\r\n';

    const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'Detail_DN_' + mdlLabel.replace(/[^A-Za-z0-9]+/g, '_') + '_' + exportStamp() + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  });
</script>
