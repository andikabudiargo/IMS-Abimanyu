<tr data-item-no="{{ $itemNo }}" data-item-name="{{ $item['name'] }}" data-stage-group="{{ $item['stage'] }}">
  <td>{{ $item['stage'] }}</td>
  <td class="text-center">{{ $itemNo }}</td>
  <td>{{ $item['name'] }}</td>
  <td>
    <input type="hidden" name="item_no[]" value="{{ $itemNo }}">
    <input type="hidden" name="item_name[]" value="{{ $item['name'] }}">
    <input type="hidden" name="stage_group[]" value="{{ $item['stage'] }}">
    <input type="hidden" name="dtl_id[]" value="{{ $line->id ?? '' }}">
    <input type="text" name="document_report[]" class="form-control form-control-sm document-report" value="{{ $line->document_report ?? '' }}">
  </td>
  <td><input type="text" name="pic[]" class="form-control form-control-sm" value="{{ $line->pic ?? '' }}"></td>
  <td><input type="date" name="plan_start[]" class="form-control form-control-sm plan-start" value="{{ $line->plan_start_date ?? '' }}"></td>
  <td><input type="date" name="plan_end[]" class="form-control form-control-sm plan-end" value="{{ $line->plan_end_date ?? '' }}"></td>
  <td><input type="date" name="actual_start[]" class="form-control form-control-sm actual-start" value="{{ $line->actual_start_date ?? '' }}"></td>
  <td><input type="date" name="actual_end[]" class="form-control form-control-sm actual-end" value="{{ $line->actual_end_date ?? '' }}"></td>
  <td><input type="number" name="progress[]" min="0" max="100" class="form-control form-control-sm" value="{{ $line->progress ?? 0 }}"></td>
  <td class="text-nowrap">
    <button type="button" class="btn btn-sm btn-outline-primary msp-add-line" title="Add line"><i data-feather="plus"></i></button>
    <button type="button" class="btn btn-sm btn-outline-danger msp-remove-line" title="Remove line"><i data-feather="minus"></i></button>
  </td>
</tr>
