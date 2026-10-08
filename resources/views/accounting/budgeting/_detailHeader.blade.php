{{--
  Header form budgeting, dipakai edit & show. Field yang memang tidak boleh berubah
  setelah dibuat selalu disabled (Budgeting Number, Fiscal Year, Department, Previous/Budget Period).
  $locked (default true): true = semua field disabled (show), false = Description/Note masih bisa diedit (edit).
--}}
@php($locked = $locked ?? true)

<div class="card">
  <div class="card-header">
    <h4 class="card-title">{{ $hdr->budgeting_number }} <span class="badge badge-pill badge-light-primary">FY {{ $hdr->fiscal_year }}</span></h4>
  </div>
  <div class="card-body">
    <div class="form-row">
      <div class="form-group col-md-3">
        <label class="form-label">Budgeting Number</label>
        <input type="text" class="form-control" value="{{ $hdr->budgeting_number }}" disabled>
      </div>
      <div class="form-group col-md-2">
        <label class="form-label" for="fiscalYear">Fiscal Year</label>
        @if($locked)
          <input type="text" class="form-control" value="{{ $hdr->fiscal_year }}" disabled>
        @else
          <select id="fiscalYear" class="form-control">
            @foreach($fiscalYears as $fy)
              <option value="{{ $fy }}" {{ $fy == $hdr->fiscal_year ? 'selected' : '' }}>{{ $fy }}</option>
            @endforeach
          </select>
        @endif
      </div>
      <div class="form-group col-md-3">
        <label class="form-label">Department</label>
        <input type="text" class="form-control" value="{{ $hdr->dept_name ?: $hdr->dept_code }}" disabled>
      </div>
      <div class="form-group col-md-2">
        <label class="form-label">Previous Period</label>
        <input type="text" class="form-control" value="{{ date('d-m-Y', strtotime($hdr->previous_from)) }} to {{ date('d-m-Y', strtotime($hdr->previous_to)) }}" disabled>
      </div>
      <div class="form-group col-md-2">
        <label class="form-label">Budget Period</label>
        <input type="text" class="form-control" value="{{ date('d-m-Y', strtotime($hdr->budget_from)) }} to {{ date('d-m-Y', strtotime($hdr->budget_to)) }}" disabled>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label class="form-label" for="description">Description</label>
        <input type="text" id="description" class="form-control" value="{{ $hdr->description }}" @if($locked) disabled @endif>
      </div>
      <div class="form-group col-md-6">
        <label class="form-label" for="note">Note</label>
        <input type="text" id="note" class="form-control" value="{{ $hdr->note }}" @if($locked) disabled @endif>
      </div>
    </div>
  </div>
</div>
