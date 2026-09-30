<div class="msp-line-panel border rounded p-3 mb-2" data-item-no="{{ $itemNo }}" data-item-name="{{ $item['name'] }}" data-stage-group="{{ $item['stage'] }}">
  <input type="hidden" name="item_no[]" value="{{ $itemNo }}">
  <input type="hidden" name="item_name[]" value="{{ $item['name'] }}">
  <input type="hidden" name="stage_group[]" value="{{ $item['stage'] }}">
  <input type="hidden" name="dtl_id[]" value="{{ $line->id ?? '' }}">

  <div class="form-row align-items-end">
    <div class="form-group col-md-4 mb-2">
      <label class="msp-field-label">Document / Report</label>
      <div class="input-group input-group-sm">
        <div class="input-group-prepend"><span class="input-group-text msp-line-no">{{ $itemNo }}.1</span></div>
        <input type="text" name="document_report[]" class="form-control document-report" value="{{ $line->document_report ?? '' }}">
      </div>
    </div>
    <div class="form-group col-md-2 mb-2">
      <label class="msp-field-label">PIC</label>
      <input type="text" name="pic[]" class="form-control form-control-sm pic-input" value="{{ $line->pic ?? '' }}">
    </div>
    <div class="form-group col-md-2 mb-2">
      <label class="msp-field-label">Progress %</label>
      <input type="number" name="progress[]" min="0" max="100" class="form-control form-control-sm" value="{{ $line->progress ?? 0 }}">
    </div>
    <div class="form-group col-md-4 mb-2 text-md-right">
      <span class="badge badge-danger msp-late-badge mr-1" style="display:none;">Telat, belum reschedule</span>
      <button type="button" class="btn btn-sm btn-outline-secondary msp-toggle-milestone" title="Milestone D/AI/AE"><i data-feather="flag"></i></button>
      <button type="button" class="btn btn-sm btn-outline-primary msp-add-line" title="Tambah document/report line"><i data-feather="plus"></i></button>
      <button type="button" class="btn btn-sm btn-outline-danger msp-remove-line" title="Hapus baris ini"><i data-feather="minus"></i></button>
    </div>
  </div>

  <div class="form-row">
    <div class="form-group col-6 col-md-3 mb-2">
      <label class="msp-field-label">Plan Start</label>
      <input type="date" name="plan_start[]" class="form-control form-control-sm plan-start" value="{{ $line->plan_start_date ?? '' }}">
    </div>
    <div class="form-group col-6 col-md-3 mb-2">
      <label class="msp-field-label">Plan End</label>
      <input type="date" name="plan_end[]" class="form-control form-control-sm plan-end" value="{{ $line->plan_end_date ?? '' }}">
    </div>
    <div class="form-group col-6 col-md-3 mb-2">
      <label class="msp-field-label">Actual Start</label>
      <input type="date" name="actual_start[]" class="form-control form-control-sm actual-start" value="{{ $line->actual_start_date ?? '' }}">
    </div>
    <div class="form-group col-6 col-md-3 mb-2">
      <label class="msp-field-label">Actual End</label>
      <input type="date" name="actual_end[]" class="form-control form-control-sm actual-end" value="{{ $line->actual_end_date ?? '' }}">
    </div>
  </div>

  <div class="form-group mb-1">
    <label class="msp-field-label">Notes <small class="text-muted font-weight-normal">(catatan progress, ikut muncul di chart)</small></label>
    <input type="text" name="notes[]" class="form-control form-control-sm notes-input" placeholder="mis. OK HPM 15 Des 2025" value="{{ $line->notes ?? '' }}">
  </div>

  <div class="msp-milestone-row" style="display:none;">
    <div class="d-flex flex-wrap border-top pt-2 mt-2" style="gap:24px;">
      <div>
        <small class="text-muted d-block mb-1">Plan</small>
        <div class="d-flex" style="gap:6px;">
          <div><label class="msp-field-label">D</label><input type="date" name="plan_draft[]" class="form-control form-control-sm" value="{{ $line->plan_draft_date ?? '' }}"></div>
          <div><label class="msp-field-label">AI</label><input type="date" name="plan_ai[]" class="form-control form-control-sm" value="{{ $line->plan_ai_date ?? '' }}"></div>
          <div><label class="msp-field-label">AE</label><input type="date" name="plan_ae[]" class="form-control form-control-sm" value="{{ $line->plan_ae_date ?? '' }}"></div>
        </div>
      </div>
      <div>
        <small class="text-muted d-block mb-1">Actual</small>
        <div class="d-flex" style="gap:6px;">
          <div><label class="msp-field-label">D</label><input type="date" name="actual_draft[]" class="form-control form-control-sm" value="{{ $line->actual_draft_date ?? '' }}"></div>
          <div><label class="msp-field-label">AI</label><input type="date" name="actual_ai[]" class="form-control form-control-sm" value="{{ $line->actual_ai_date ?? '' }}"></div>
          <div><label class="msp-field-label">AE</label><input type="date" name="actual_ae[]" class="form-control form-control-sm" value="{{ $line->actual_ae_date ?? '' }}"></div>
        </div>
      </div>
      <div>
        <small class="text-muted d-block mb-1">Reschedule</small>
        <div class="d-flex" style="gap:6px;">
          <div><label class="msp-field-label">RD</label><input type="date" name="resch_draft[]" class="form-control form-control-sm resch-input" value="{{ $line->resch_draft_date ?? '' }}"></div>
          <div><label class="msp-field-label">RAI</label><input type="date" name="resch_ai[]" class="form-control form-control-sm resch-input" value="{{ $line->resch_ai_date ?? '' }}"></div>
          <div><label class="msp-field-label">RAE</label><input type="date" name="resch_ae[]" class="form-control form-control-sm resch-input" value="{{ $line->resch_ae_date ?? '' }}"></div>
        </div>
      </div>
    </div>
  </div>
</div>
