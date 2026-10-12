{{-- Modal breakdown DN per artikel, versi Planning (tanpa harga asli). --}}
<div class="modal fade" id="mdlDetail" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-truncate pr-1">Detail DN <span id="mdlArticleLabel"></span></h5>
        <button type="button" class="close m-0 p-0" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="table-responsive" style="max-height:60vh;overflow:auto;">
        <table class="table table-hover table-sm mb-0">
          <thead class="thead-light" style="position:sticky;top:0;z-index:1;">
            <tr>
              <th style="width:4%">No</th>
              <th>DN Number</th>
              <th>SO Number</th>
              <th>Customer</th>
              <th class="text-right">Qty</th>
              <th class="text-right">Konversi</th>
            </tr>
          </thead>
          <tbody id="mdlDetailRows"></tbody>
          <tfoot class="thead-light font-weight-bolder" style="position:sticky;bottom:0;">
            <tr>
              <th colspan="4" class="text-right">Total</th>
              <th class="text-right" id="mdlTotalQty">0</th>
              <th class="text-right" id="mdlTotalConv">0</th>
            </tr>
          </tfoot>
        </table>
        </div>
      </div>
    </div>
  </div>
</div>
