<script type="text/javascript">
  function fmtRp(n){
    return (n || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  }

  function openVoucherModal(account, month, bucket){
    const monthName = {!! json_encode(array_combine(range(1,12), array_map(fn($m) => \DateTime::createFromFormat('n',$m)->format('F'), range(1,12)))) !!}[month];
    $('#voucherModalTitle').text(`Voucher List - ${account} - ${monthName} ${window.tbYear}`);
    $('#voucherModalBody').html('<tr><td colspan="6" class="text-center">Loading...</td></tr>');
    $('#voucherModal').modal('show');

    $.get("{{ route('trialBalance.saved.voucherList') }}", { account: account, year: window.tbYear, month: month, bucket: bucket })
      .done(function(res){
        let html = '';
        if(res.starting_balance){
          html += `<tr class="table-light"><td colspan="5"><em>Saldo Awal</em></td><td class="text-right"><em>${fmtRp(res.starting_balance)}</em></td></tr>`;
        }
        if(!res.rows || res.rows.length === 0){
          html += '<tr><td colspan="6" class="text-center">Tidak ada voucher</td></tr>';
        }else{
          res.rows.forEach(r => {
            const link = r.edit_url ? `<a href="${r.edit_url}" target="_blank">${r.voucher_number}</a>` : r.voucher_number;
            html += `<tr><td>${link}</td><td>${r.voucher_date}</td><td>${r.cost_center_name ?? ''}</td><td class="text-right">${fmtRp(r.debit)}</td><td class="text-right">${fmtRp(r.credit)}</td><td class="text-right">${fmtRp(r.balance)}</td></tr>`;
          });
        }
        $('#voucherModalBody').html(html);
        $('#voucherModalTotalDebit').text(fmtRp(res.total_debit));
        $('#voucherModalTotalCredit').text(fmtRp(res.total_credit));
        $('#voucherModalEndingBalance').text(fmtRp(res.ending_balance));
      })
      .fail(function(){
        $('#voucherModalBody').html('<tr><td colspan="6" class="text-center text-danger">Gagal memuat data</td></tr>');
      });
  }

  $(document).on('click', '.cell-value', function(){
    const account = $(this).closest('tr').data('account');
    const month = $(this).data('month');
    const field = $(this).data('field');
    const bucket = field.startsWith('open') ? 'open' : (field.startsWith('mutasi') ? 'mutasi' : 'saldo');
    openVoucherModal(account, month, bucket);
  });
</script>
