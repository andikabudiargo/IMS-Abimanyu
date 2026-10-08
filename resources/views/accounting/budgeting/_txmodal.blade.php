{{-- Modal list transaksi, dipicu oleh hyperlink Debit / Realisasi. Butuh fungsi JS: nf(), esc(). --}}
<div class="modal fade" id="bgTxModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bgTxModalTitle">Daftar Transaksi</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive" style="max-height:60vh; overflow:auto;">
          <table class="table table-sm">
            <thead class="thead-light">
              <tr><th>Tanggal</th><th>Voucher</th><th>Description</th><th class="text-right">Debit</th></tr>
            </thead>
            <tbody id="bgTxBody"><tr><td colspan="4" class="text-center text-muted">Memuat...</td></tr></tbody>
            <tfoot>
              <tr class="font-weight-bold"><td colspan="3">TOTAL</td><td class="text-right" id="bgTxTotal"></td></tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  // dept, account, from, to: 'YYYY-MM-DD'. label untuk judul modal.
  function openBgTxModal(dept, account, from, to, label) {
    $('#bgTxModalTitle').text(label);
    $('#bgTxBody').html('<tr><td colspan="4" class="text-center text-muted">Memuat...</td></tr>');
    $('#bgTxTotal').text('');
    $('#bgTxModal').modal('show');

    $.get('{{ route("budgeting.transactions") }}', { dept: dept, account: account, from: from, to: to })
      .done(function (res) {
        if (!res.rows || !res.rows.length) {
          $('#bgTxBody').html('<tr><td colspan="4" class="text-center text-muted">Tidak ada transaksi.</td></tr>');
          $('#bgTxTotal').text(nf(0));
          return;
        }
        let html = '';
        res.rows.forEach(function (r) {
          html += '<tr><td>' + esc(r.voucher_date) + '</td><td>' + esc(r.voucher_number) + '</td>'
                + '<td>' + esc(r.description) + '</td><td class="text-right">' + nf(r.debit) + '</td></tr>';
        });
        $('#bgTxBody').html(html);
        $('#bgTxTotal').text(nf(res.total));
      })
      .fail(function () {
        $('#bgTxBody').html('<tr><td colspan="4" class="text-center text-danger">Gagal memuat transaksi.</td></tr>');
      });
  }
</script>
