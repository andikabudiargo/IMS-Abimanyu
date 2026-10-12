{{-- Script modal breakdown DN, versi Planning (tanpa harga asli). Butuh var JS: URL_DETAIL_DN. --}}
<script type="text/javascript">
  function humanizeDn(n) {
    n = parseFloat(n) || 0;
    return n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function loadDetailDnModal(detId, label) {
    $('#mdlArticleLabel').text('| ' + (label || ''));
    $('#mdlDetailRows').html('<tr><td colspan="6" class="text-center text-muted">Memuat data...</td></tr>');
    $('#mdlTotalQty, #mdlTotalConv').text('0');
    $('#mdlDetail').modal('show');

    $.get(URL_DETAIL_DN, { reportDetId: detId }, function (res) {
      const rows = (res && res.rows) ? res.rows : [];
      const totals = (res && res.totals) ? res.totals : { qty: 0, conversion: 0 };

      let html = '';
      rows.forEach((r) => {
        const dnCell = r.dn_url ? `<a href="${r.dn_url}" target="_blank">${r.dn_number}</a>` : (r.dn_number || '-');
        const soCell = r.so_url ? `<a href="${r.so_url}" target="_blank">${r.so_number}</a>` : (r.so_number || '-');
        html += `<tr>
          <td class="text-center">${r.no}</td>
          <td>${dnCell}</td>
          <td>${soCell}</td>
          <td>${r.customer_name || '-'}</td>
          <td class="text-right">${humanizeDn(r.qty)}</td>
          <td class="text-right">${humanizeDn(r.conversion)}</td>
        </tr>`;
      });
      $('#mdlDetailRows').html(html || '<tr><td colspan="6" class="text-center text-muted">Tidak ada data.</td></tr>');
      $('#mdlTotalQty').text(humanizeDn(totals.qty));
      $('#mdlTotalConv').text(humanizeDn(totals.conversion));
      if (window.feather) feather.replace({ width: 14, height: 14 });
    }).fail(function () {
      $('#mdlDetailRows').html('<tr><td colspan="6" class="text-center text-danger">Gagal memuat data.</td></tr>');
    });
  }
</script>
