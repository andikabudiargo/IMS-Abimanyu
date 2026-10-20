<script type="text/javascript">
  function fmtRp(n){
    return (n || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function openVoucherModal(account, month, bucket, side){
    const monthName = {!! json_encode(array_combine(range(1,12), array_map(fn($m) => \DateTime::createFromFormat('n',$m)->format('F'), range(1,12)))) !!}[month];
    $('#voucherModalTitle').text(`Voucher List - ${account} - ${monthName} ${window.tbYear} (${side})`);
    $('#voucherModalBody').html('<tr><td colspan="5" class="text-center">Loading...</td></tr>');
    $('#voucherModal').modal('show');

    $.get("{{ route('trialBalance.saved.voucherList') }}", { account: account, year: window.tbYear, month: month, bucket: bucket, side: side })
      .done(function(res){
        let html = '';
        if(!res.rows || res.rows.length === 0){
          html += '<tr><td colspan="5" class="text-center">Tidak ada voucher</td></tr>';
        }else{
          res.rows.forEach(r => {
            const link = r.edit_url ? `<a href="${r.edit_url}" target="_blank">${r.voucher_number}</a>` : r.voucher_number;
            html += `<tr><td>${link}</td><td>${r.voucher_date}</td><td>${r.cost_center_name ?? ''}</td><td class="text-right">${fmtRp(r.debit)}</td><td class="text-right">${fmtRp(r.credit)}</td></tr>`;
          });
        }
        $('#voucherModalBody').html(html);
        $('#voucherModalTotalDebit').text(fmtRp(res.total_debit));
        $('#voucherModalTotalCredit').text(fmtRp(res.total_credit));
      })
      .fail(function(){
        $('#voucherModalBody').html('<tr><td colspan="5" class="text-center text-danger">Gagal memuat data</td></tr>');
      });
  }

  $(document).on('click', '.cell-value', function(){
    const field = $(this).data('field');
    if(!field.startsWith('mutasi')) return; // saldo awal/akhir bukan satu transaksi, gak ada vouchernya sendiri
    const account = $(this).closest('tr').attr('data-account');
    const month = $(this).data('month');
    const side = field.endsWith('_credit') ? 'credit' : 'debit';
    openVoucherModal(account, month, 'mutasi', side);
  });
</script>
