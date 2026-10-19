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
              <th class="text-right">Balance</th>
            </tr>
          </thead>
          <tbody id="voucherModalBody"></tbody>
          <tfoot>
            <tr>
              <td colspan="3">Total</td>
              <td class="text-right" id="voucherModalTotalDebit">0</td>
              <td class="text-right" id="voucherModalTotalCredit">0</td>
              <td class="text-right" id="voucherModalEndingBalance">0</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>
