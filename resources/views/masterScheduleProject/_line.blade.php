<tr class="msp-line-row" data-item-no="{{ $itemNo }}" data-item-name="{{ $item['name'] }}" data-stage-group="{{ $item['stage'] }}">
  <td>{{ $item['stage'] }}</td>
  <td class="text-center">{{ $itemNo }}</td>
  <td>{{ $item['name'] }}</td>
  <td>
    <input type="hidden" name="item_no[]" value="{{ $itemNo }}">
    <input type="hidden" name="item_name[]" value="{{ $item['name'] }}">
    <input type="hidden" name="stage_group[]" value="{{ $item['stage'] }}">
    <input type="hidden" name="dtl_id[]" value="{{ $line->id ?? '' }}">
    <div class="input-group input-group-sm">
      <div class="input-group-prepend"><span class="input-group-text msp-line-no">{{ $itemNo }}.1</span></div>
      <input type="text" name="document_report[]" class="form-control form-control-sm document-report" value="{{ $line->document_report ?? '' }}">
    </div>
  </td>
  <td><input type="text" name="pic[]" class="form-control form-control-sm pic-input" value="{{ $line->pic ?? '' }}"></td>
  <td><input type="date" name="plan_start[]" class="form-control form-control-sm plan-start" value="{{ $line->plan_start_date ?? '' }}"></td>
  <td><input type="date" name="plan_end[]" class="form-control form-control-sm plan-end" value="{{ $line->plan_end_date ?? '' }}"></td>
  <td><input type="date" name="actual_start[]" class="form-control form-control-sm actual-start" value="{{ $line->actual_start_date ?? '' }}"></td>
  <td><input type="date" name="actual_end[]" class="form-control form-control-sm actual-end" value="{{ $line->actual_end_date ?? '' }}"></td>
  <td><input type="number" name="progress[]" min="0" max="100" class="form-control form-control-sm" value="{{ $line->progress ?? 0 }}"></td>
  <td><input type="text" name="notes[]" class="form-control form-control-sm notes-input" placeholder="mis. OK HPM 15 Des 2025" value="{{ $line->notes ?? '' }}"></td>
  <td class="text-nowrap">
    <button type="button" class="btn btn-sm btn-outline-secondary msp-toggle-milestone" title="Milestone D/AI/AE"><i data-feather="flag"></i></button>
    <button type="button" class="btn btn-sm btn-outline-primary msp-add-line" title="Add line"><i data-feather="plus"></i></button>
    <button type="button" class="btn btn-sm btn-outline-danger msp-remove-line" title="Remove line"><i data-feather="minus"></i></button>
  </td>
</tr>
<tr class="msp-milestone-row" style="display:none;">
  <td colspan="12" class="bg-light">
    <div class="d-flex flex-wrap" style="gap:24px;">
      <div>
        <small class="text-muted d-block mb-1">Plan</small>
        <div class="d-flex" style="gap:6px;">
          <div><label class="mb-0" style="font-size:10px;">D</label><input type="date" name="plan_draft[]" class="form-control form-control-sm" value="{{ $line->plan_draft_date ?? '' }}"></div>
          <div><label class="mb-0" style="font-size:10px;">AI</label><input type="date" name="plan_ai[]" class="form-control form-control-sm" value="{{ $line->plan_ai_date ?? '' }}"></div>
          <div><label class="mb-0" style="font-size:10px;">AE</label><input type="date" name="plan_ae[]" class="form-control form-control-sm" value="{{ $line->plan_ae_date ?? '' }}"></div>
        </div>
      </div>
      <div>
        <small class="text-muted d-block mb-1">Actual</small>
        <div class="d-flex" style="gap:6px;">
          <div><label class="mb-0" style="font-size:10px;">D</label><input type="date" name="actual_draft[]" class="form-control form-control-sm" value="{{ $line->actual_draft_date ?? '' }}"></div>
          <div><label class="mb-0" style="font-size:10px;">AI</label><input type="date" name="actual_ai[]" class="form-control form-control-sm" value="{{ $line->actual_ai_date ?? '' }}"></div>
          <div><label class="mb-0" style="font-size:10px;">AE</label><input type="date" name="actual_ae[]" class="form-control form-control-sm" value="{{ $line->actual_ae_date ?? '' }}"></div>
        </div>
      </div>
      <div>
        <small class="text-muted d-block mb-1">Reschedule</small>
        <div class="d-flex" style="gap:6px;">
          <div><label class="mb-0" style="font-size:10px;">RD</label><input type="date" name="resch_draft[]" class="form-control form-control-sm" value="{{ $line->resch_draft_date ?? '' }}"></div>
          <div><label class="mb-0" style="font-size:10px;">RAI</label><input type="date" name="resch_ai[]" class="form-control form-control-sm" value="{{ $line->resch_ai_date ?? '' }}"></div>
          <div><label class="mb-0" style="font-size:10px;">RAE</label><input type="date" name="resch_ae[]" class="form-control form-control-sm" value="{{ $line->resch_ae_date ?? '' }}"></div>
        </div>
      </div>
    </div>
  </td>
</tr>
