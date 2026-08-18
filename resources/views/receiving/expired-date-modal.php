<div class="modal fade" id="chemicalUnitModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Expired Date - Chemical (CM1)</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="chemicalUnitRecNumber" class="mb-2 font-weight-bold"></div>
        <div id="chemicalUnitLoading" class="text-center py-3" style="display:none;">
          <i data-feather="loader"></i> Memuat data...
        </div>
        <div id="chemicalUnitAlert" class="alert alert-warning" style="display:none;"></div>
        <div id="chemicalUnitGroups"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" id="btnSaveChemicalUnit" style="display:none;">
          <i data-feather="save"></i> Simpan & Print
        </button>
      </div>
    </div>
  </div>
</div>

<script type="text/template" id="tplChemicalGroup">
 <div class="card chemical-unit-group mb-2" data-receiving-det-id="__RECEIVING_DET_ID__" data-article-code="__ARTICLE_CODE__">
  <div class="card-header py-2">
    <strong>__ARTICLE_ALT_CODE__</strong> - __ARTICLE_DESC__
    <span class="float-right text-muted">
      Min. Package: __MIN_PACKAGE__ __UOM__ | Total: __TOTAL_QTY__ __UOM__ | Set EP Date: __ALLOCATED_QTY__ __UOM__ | Belum ada EP Date: __REMAINING_QTY__ __UOM__
    </span>
  </div>
  <div class="card-body py-2 chemical-unit-rows">
    <!-- rows disuntik JS -->
  </div>
</div>
</script>

<script type="text/template" id="tplChemicalRow">
  <div class="row align-items-center mb-1 chemical-unit-row"
       data-receiving-det-id="__RECEIVING_DET_ID__"
       data-unit-sequence="__UNIT_SEQUENCE__"
       data-qty="__QTY__">
    <div class="col-md-2">
      <span class="badge badge-primary">Kaleng #__UNIT_SEQUENCE__</span>
    </div>
    <div class="col-md-3">
      Qty: <strong>__QTY__</strong> __UOM__
    </div>
    <div class="col-md-4">
      <input type="date" class="form-control form-control-sm input-expired-date" required />
    </div>
    <div class="col-md-3">
      <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input chk-print-barcode" id="chkPrint___RECEIVING_DET_ID_____UNIT_SEQUENCE__" checked>
        <label class="custom-control-label" for="chkPrint___RECEIVING_DET_ID_____UNIT_SEQUENCE__">Print Barcode</label>
      </div>
    </div>
  </div>
</script>