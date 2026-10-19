{{-- Modal drill-down: klik 1 cell saldo awal/pergerakan/saldo akhir -> list voucher sumbernya. --}}
<div class="modal fade" id="voucherModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="voucherModalTitle">Voucher List</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <table class="table table-sm table-bordered">
          <thead>
            <tr>
              <th>Voucher Number</th>
              <th>Date</th>
              <th>Cost Center</th>
              <th class="text-right">Debit</th>
              <th class="text-right">Credit</th>
            </tr>
          </thead>
          <tbody id="voucherModalBody"></tbody>
          <tfoot>
            <tr>
              <td colspan="3">Total</td>
              <td class="text-right" id="voucherModalTotalDebit">0</td>
              <td class="text-right" id="voucherModalTotalCredit">0</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript">
  function fmtRp(n){
    return (n || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  }

  function openVoucherModal(account, month, bucket){
    const monthName = {!! json_encode(array_combine(range(1,12), array_map(fn($m) => \DateTime::createFromFormat('n',$m)->format('F'), range(1,12)))) !!}[month];
    $('#voucherModalTitle').text(`Voucher List - ${account} - ${monthName} ${window.tbYear}`);
    $('#voucherModalBody').html('<tr><td colspan="5" class="text-center">Loading...</td></tr>');
    $('#voucherModal').modal('show');

    $.get("{{ route('trialBalance.saved.voucherList') }}", { account: account, year: window.tbYear, month: month, bucket: bucket })
      .done(function(res){
        if(!res.rows || res.rows.length === 0){
          $('#voucherModalBody').html('<tr><td colspan="5" class="text-center">Tidak ada voucher</td></tr>');
        }else{
          let html = '';
          res.rows.forEach(r => {
            const link = r.edit_url ? `<a href="${r.edit_url}" target="_blank">${r.voucher_number}</a>` : r.voucher_number;
            html += `<tr><td>${link}</td><td>${r.voucher_date}</td><td>${r.cost_center_name ?? ''}</td><td class="text-right">${fmtRp(r.debit)}</td><td class="text-right">${fmtRp(r.credit)}</td></tr>`;
          });
          $('#voucherModalBody').html(html);
        }
        $('#voucherModalTotalDebit').text(fmtRp(res.total_debit));
        $('#voucherModalTotalCredit').text(fmtRp(res.total_credit));
      })
      .fail(function(){
        $('#voucherModalBody').html('<tr><td colspan="5" class="text-center text-danger">Gagal memuat data</td></tr>');
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
