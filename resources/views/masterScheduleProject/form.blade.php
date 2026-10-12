@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="msp-form">

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <form method="POST" action="{{ $hdr ? route('msp.update', $hdr->id) : route('msp.store') }}">
    @csrf
    @if ($hdr) @method('PUT') @endif

    <div class="card msp-card">
      <div class="card-header">
        <h4 class="card-title">
          <i data-feather="file-text" class="font-medium-2 mr-1 text-primary"></i>
          {{ $hdr ? 'Edit' : 'New' }} Master Schedule Project
        </h4>
        <div class="heading-elements">
          @if ($hdr)
            <span class="badge msp-status-badge badge-pill mr-2" data-status="{{ $hdr->status }}">{{ ucfirst($hdr->status) }}</span>
            <a href="{{ route('msp.history', $hdr->id) }}" class="btn btn-sm btn-outline-secondary"><i data-feather="clock" class="mr-50"></i>History</a>
          @endif
        </div>
      </div>
      <div class="card-body">
        <div class="form-row">
          @if ($hdr)
          <div class="form-group col-md-3">
            <label class="msp-label">MSP No</label>
            <input type="text" class="form-control" value="{{ $hdr->msp_no }}" readonly>
          </div>
          @endif
          <div class="form-group col-md-3">
            <label class="msp-label">Customer</label>
            <input type="text" name="customer" class="form-control" value="{{ old('customer', $hdr->customer ?? '') }}">
          </div>
          <div class="form-group col-md-3">
            <label class="msp-label">Part Name <span class="text-danger">*</span></label>
            <input type="text" name="part_name" class="form-control" required value="{{ old('part_name', $hdr->part_name ?? '') }}">
          </div>
          <div class="form-group col-md-3">
            <label class="msp-label">Model</label>
            <input type="text" name="model" class="form-control" value="{{ old('model', $hdr->model ?? '') }}">
          </div>
          <div class="form-group col-md-3">
            <label class="msp-label">Part Number</label>
            <input type="text" name="part_number" class="form-control" value="{{ old('part_number', $hdr->part_number ?? '') }}">
          </div>
          <div class="form-group col-md-3">
            <label class="msp-label">Project Type <span class="text-danger">*</span></label>
            <select name="project_type" id="mspProjectType" class="form-control" required>
              @foreach ($projectTypes as $value => $label)
                <option value="{{ $value }}" {{ old('project_type', $hdr->project_type ?? 'regular') == $value ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group col-md-3">
            <label class="msp-label">Issue Date</label>
            <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date', $hdr->issue_date ?? now()->toDateString()) }}">
          </div>
          @if ($hdr)
          <div class="form-group col-md-3">
            <label class="msp-label">Status</label>
            <select name="status" class="form-control">
              <option value="ongoing" {{ $hdr->status == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
              <option value="done" {{ $hdr->status == 'done' ? 'selected' : '' }}>Done</option>
              <option value="cancelled" {{ $hdr->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
          </div>
          <div class="form-group col-md-3">
            <label class="msp-label">Revision No</label>
            <input type="text" class="form-control" value="{{ $hdr->revision_no }}" readonly>
          </div>
          @endif
        </div>
        @if ($hdr)
        <div class="form-group mb-0">
          <label class="msp-label">Catatan Revisi <small class="text-muted font-weight-normal">(diisi bila ada perubahan schedule pada penyimpanan ini, akan tercatat di history)</small></label>
          <input type="text" name="revision_reason" class="form-control" placeholder="mis. Reschedule T1 mundur karena material telat">
        </div>
        @endif
      </div>
    </div>

    {{-- Progress overview, derived live from the items below --}}
    <div class="row match-height mb-1">
      <div class="col-sm-6 col-lg-3">
        <div class="card msp-stat-card mb-0">
          <div class="card-body d-flex align-items-center justify-content-between py-2">
            <div>
              <h6 class="text-muted mb-25">Items Berlaku</h6>
              <h2 class="font-weight-bolder mb-0" id="mspStatVisible">-</h2>
            </div>
            <div class="avatar bg-light-primary p-50"><span class="avatar-content"><i data-feather="list" class="font-medium-5"></i></span></div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="card msp-stat-card mb-0">
          <div class="card-body d-flex align-items-center justify-content-between py-2">
            <div>
              <h6 class="text-muted mb-25">Done</h6>
              <h2 class="font-weight-bolder mb-0" id="mspStatDone">-</h2>
            </div>
            <div class="avatar bg-light-success p-50"><span class="avatar-content"><i data-feather="check-circle" class="font-medium-5"></i></span></div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="card msp-stat-card mb-0">
          <div class="card-body d-flex align-items-center justify-content-between py-2">
            <div>
              <h6 class="text-muted mb-25">On Progress</h6>
              <h2 class="font-weight-bolder mb-0" id="mspStatProgress">-</h2>
            </div>
            <div class="avatar bg-light-warning p-50"><span class="avatar-content"><i data-feather="loader" class="font-medium-5"></i></span></div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="card msp-stat-card mb-0">
          <div class="card-body d-flex align-items-center justify-content-between py-2">
            <div>
              <h6 class="text-muted mb-25">Telat</h6>
              <h2 class="font-weight-bolder mb-0" id="mspStatLate">-</h2>
            </div>
            <div class="avatar bg-light-danger p-50"><span class="avatar-content"><i data-feather="alert-triangle" class="font-medium-5"></i></span></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card msp-card">
      <div class="card-header">
        <h4 class="card-title"><i data-feather="bar-chart-2" class="font-medium-2 mr-1 text-primary"></i>Timeline Chart</h4>
        <div class="heading-elements">
          <button type="button" class="btn btn-sm btn-outline-primary" onclick="mspRenderGantt()"><i data-feather="refresh-cw" class="mr-50"></i>Refresh Chart</button>
        </div>
      </div>
      <div class="card-body" style="overflow-x:auto;">
        <div id="mspGanttChart"></div>
        <div class="mt-2 msp-legend">
          <span class="msp-legend-swatch" style="border:1.5px solid #5a4fcf;border-radius:2px;"></span> Plan (bar)
          <span class="msp-legend-swatch" style="background:#5a4fcf;border-radius:2px;"></span> Actual (bar)
          <span class="msp-legend-swatch" style="border:1.5px solid #dc3545;border-radius:2px;"></span> Telat, belum di-reschedule
          <span class="msp-legend-swatch" style="width:2px;height:12px;background:#dc3545;"></span> Hari ini
          <span class="msp-legend-swatch" style="background:#fffbe6;border:1px solid #e6d47a;border-radius:2px;"></span> Notes
          <br>
          <span class="msp-marker msp-marker-plan" style="position:static;display:inline-block;margin-right:2px;">D</span>
          <span class="msp-marker msp-marker-plan" style="position:static;display:inline-block;margin-right:2px;">AI</span>
          <span class="msp-marker msp-marker-plan" style="position:static;display:inline-block;margin-right:8px;">AE</span>
          Plan Draft / Approval Internal / Approval External
          <br>
          <span class="msp-marker msp-marker-actual" style="position:static;display:inline-block;margin-right:2px;">D</span>
          <span class="msp-marker msp-marker-actual" style="position:static;display:inline-block;margin-right:2px;">AI</span>
          <span class="msp-marker msp-marker-actual" style="position:static;display:inline-block;margin-right:8px;">AE</span>
          Actual Draft / Approval Internal / Approval External
          <br>
          <span class="msp-marker msp-marker-resch" style="position:static;display:inline-block;margin-right:2px;">RD</span>
          <span class="msp-marker msp-marker-resch" style="position:static;display:inline-block;margin-right:2px;">RAI</span>
          <span class="msp-marker msp-marker-resch" style="position:static;display:inline-block;margin-right:8px;">RAE</span>
          Reschedule Draft / Approval Internal / Approval External
          <br>
          <small class="text-muted">Isi tanggal D/AI/AE lewat tombol <i data-feather="flag"></i> di tiap baris di bawah. Chart otomatis update dari isian di bawah, tekan "Refresh Chart" kalau belum berubah.</small>
        </div>
      </div>
    </div>

    <div class="card msp-card">
      <div class="card-header">
        <h4 class="card-title"><i data-feather="layers" class="font-medium-2 mr-1 text-primary"></i>Schedule Items</h4>
        <div class="heading-elements">
          <span class="badge badge-light-primary" id="mspTypeBadge">Regular Order</span>
        </div>
      </div>
      <div class="card-body">
        <small class="text-muted d-block mb-2">Klik judul item untuk buka/tutup. Tombol <i data-feather="plus"></i> menambah document/report line pada item yang sama, <i data-feather="flag"></i> untuk isi tanggal Draft/Approval (D/AI/AE). Item yang tidak berlaku untuk Project Type yang dipilih otomatis disembunyikan.</small>

        <div id="mspScheduleWrapper">
          @foreach ($items as $itemNo => $item)
            @php
              $lines = $dtlByItem[$itemNo] ?? collect();
              $hiddenFor = collect($hiddenItemsByType)->filter(fn ($list) => in_array($itemNo, $list))->keys()->implode(',');
            @endphp
            <div class="card msp-item-card mb-2" data-item-no="{{ $itemNo }}" data-hidden-for="{{ $hiddenFor }}">
              <div class="card-header py-2 msp-item-toggle">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <span class="badge msp-stage-badge mr-2">{{ $item['stage'] }}</span>
                    <strong>{{ $itemNo }}. {{ $item['name'] }}</strong>
                    <span class="badge badge-pill msp-item-status ml-2" data-status="not_started">Belum Mulai</span>
                  </div>
                  <i data-feather="chevron-down"></i>
                </div>
              </div>
              <div class="card-content collapse show">
                <div class="card-body py-2">
                  @if ($lines->isEmpty())
                    @include('masterScheduleProject._line', ['itemNo' => $itemNo, 'item' => $item, 'line' => null])
                  @else
                    @foreach ($lines as $line)
                      @include('masterScheduleProject._line', ['itemNo' => $itemNo, 'item' => $item, 'line' => $line])
                    @endforeach
                  @endif
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <button type="submit" class="btn btn-primary"><i data-feather="save" class="mr-50"></i>Save</button>
    <a href="{{ route('msp.index') }}" class="btn btn-outline-secondary">Cancel</a>
  </form>
</section>

@endsection

@section('styles')
<style>
/* ── Enterprise card language (shared with other modules) ───────── */
.msp-card { border: 1px solid #e4e7ec; box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06); border-radius: 8px; }
.msp-card > .card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #f1f1f4; }
.msp-card > .card-header .card-title { font-size: 1rem; font-weight: 600; color: #1d2939; margin-bottom: 0; display: flex; align-items: center; }

.msp-stat-card { border: 1px solid #e4e7ec; box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06); border-radius: 8px; }
.msp-stat-card .avatar { border-radius: 8px; }
.msp-stat-card h2 { font-size: 1.6rem; font-weight: 700; color: #101828; }
.msp-stat-card h6 { font-size: 0.75rem; font-weight: 600; letter-spacing: 0.02em; text-transform: uppercase; color: #667085; }

.msp-label { font-size: 12px; font-weight: 600; color: #475467; margin-bottom: 4px; display: block; }
.msp-field-label { font-size: 11px; color: #6e6b7b; margin-bottom: 2px; display: block; }
.msp-line-panel { background: #fff; border-color: #ebe9f1 !important; }

.msp-stage-badge { background: #e0def7; color: #5a4fcf; font-weight: 600; }

/* status badge on header (ongoing/done/cancelled) */
.msp-status-badge[data-status="ongoing"] { background: #fff3cd; color: #946200; }
.msp-status-badge[data-status="done"] { background: #d1f7df; color: #0a7a3c; }
.msp-status-badge[data-status="cancelled"] { background: #eceff1; color: #52606d; }

/* per-item live status badge, driven by JS from progress/date inputs */
.msp-item-status[data-status="not_started"] { background: #eceff1; color: #52606d; }
.msp-item-status[data-status="in_progress"] { background: #fff3cd; color: #946200; }
.msp-item-status[data-status="done"] { background: #d1f7df; color: #0a7a3c; }
.msp-item-status[data-status="late"] { background: #fde0e0; color: #c62828; }

.msp-item-card { border: 1px solid #e4e7ec; border-radius: 8px; overflow: hidden; }
.msp-item-card.d-none { display: none !important; }
.msp-item-toggle { cursor: pointer; background: #fbfafe; }
.msp-item-toggle:hover { background: #f5f3fb; }
.msp-item-toggle svg { transition: transform .15s ease; color: #667085; }
.msp-item-toggle.msp-collapsed svg { transform: rotate(-90deg); }

.msp-legend { font-size: 12px; line-height: 2; color: #475467; }
.msp-legend-swatch { display: inline-block; width: 14px; height: 8px; margin-right: 4px; vertical-align: middle; }

.msp-gantt-table { border-collapse: collapse; font-size: 11px; table-layout: fixed; }
.msp-gantt-table th, .msp-gantt-table td { border: 1px solid #dcdcdc; text-align: center; padding: 0; height: 28px; }
.msp-gantt-table thead th { background: #f3f2f7; font-weight: 600; padding: 4px 2px; }
.msp-gantt-label {
  text-align: left !important; padding: 4px 8px !important; white-space: nowrap;
  overflow: hidden; text-overflow: ellipsis; background: #fafafa;
}
.msp-gantt-odd td { background: #fbfbfd; }
.msp-bar-plan, .msp-bar-actual {
  position: absolute; height: 8px; border-radius: 2px; pointer-events: none;
}
.msp-bar-plan { border: 1.5px solid #5a4fcf; background: #fff; }
.msp-bar-plan.msp-late { border-color: #dc3545; }
.msp-bar-actual { background: #5a4fcf; }
.msp-gantt-note {
  position: absolute; white-space: nowrap; font-size: 10px; color: #444;
  background: #fffbe6; border: 1px solid #e6d47a; border-radius: 3px;
  padding: 1px 5px; pointer-events: none; z-index: 2;
}
.msp-marker {
  position: absolute; width: 22px; height: 14px; line-height: 13px;
  font-size: 8px; font-weight: 700; text-align: center; border-radius: 2px;
  pointer-events: none; z-index: 3;
}
.msp-marker-plan { background: #fff; border: 1.5px solid #333; color: #333; }
.msp-marker-actual { background: #333; color: #fff; }
.msp-marker-resch { background: #1e88e5; color: #fff; }
.msp-today-line { position: absolute; width: 2px; background: #dc3545; z-index: 4; pointer-events: none; }
.msp-today-tag {
  position: absolute; font-size: 9px; color: #dc3545; font-weight: 700;
  white-space: nowrap; z-index: 4; pointer-events: none;
}
</style>
@endsection

@section('scripts')
<script>
const mspHiddenItemsByType = @json($hiddenItemsByType);
const mspProjectTypeLabels = @json($projectTypes);

// ---- Project Type -> show/hide item cards (client-side, no reload) ----
function mspApplyProjectType() {
  const type = document.getElementById('mspProjectType').value;
  const hidden = mspHiddenItemsByType[type] || [];
  document.querySelectorAll('#mspScheduleWrapper .msp-item-card').forEach(function (card) {
    const itemNo = parseInt(card.dataset.itemNo, 10);
    card.classList.toggle('d-none', hidden.includes(itemNo));
  });
  const badge = document.getElementById('mspTypeBadge');
  if (badge) badge.textContent = mspProjectTypeLabels[type] || type;
  mspUpdateStats();
}
document.getElementById('mspProjectType').addEventListener('change', mspApplyProjectType);

// ---- accordion open/close for each item card ----
document.addEventListener('click', function (e) {
  const toggle = e.target.closest('.msp-item-toggle');
  if (!toggle) return;
  const content = toggle.parentElement.querySelector('.card-content');
  if (content) content.classList.toggle('show');
  toggle.classList.toggle('msp-collapsed');
});

// ---- document/report line numbering: "<item no>.<line index within item>" ----
function mspRenumberLines() {
  const counters = {};
  document.querySelectorAll('#mspScheduleWrapper .msp-line-panel').forEach(function (panel) {
    const itemNo = panel.dataset.itemNo;
    counters[itemNo] = (counters[itemNo] || 0) + 1;
    const badge = panel.querySelector('.msp-line-no');
    if (badge) badge.textContent = itemNo + '.' + counters[itemNo];
  });
}

// ---- red "Telat, belum reschedule" badge per line ----
function mspIsLate(panel) {
  const planEnd = panel.querySelector('.plan-end').value;
  const actualEnd = panel.querySelector('.actual-end').value;
  if (!planEnd || actualEnd) return false;
  const hasResch = Array.from(panel.querySelectorAll('.resch-input')).some(function (i) { return i.value; });
  if (hasResch) return false;
  const today = new Date(); today.setHours(0, 0, 0, 0);
  return new Date(planEnd) < today;
}
function mspUpdateLateBadge(panel) {
  const badge = panel.querySelector('.msp-late-badge');
  if (badge) badge.style.display = mspIsLate(panel) ? '' : 'none';
}
function mspUpdateAllLateBadges() {
  document.querySelectorAll('#mspScheduleWrapper .msp-line-panel').forEach(mspUpdateLateBadge);
}

// ---- per-item status badge + top stat tiles, derived from progress/dates already on screen ----
function mspItemStatus(card) {
  const panels = card.querySelectorAll('.msp-line-panel');
  let anyLate = false, anyStarted = false, allDone = true, hasAny = false;
  panels.forEach(function (panel) {
    const progress = parseInt(panel.querySelector('input[name="progress[]"]').value, 10) || 0;
    const hasDoc = !!panel.querySelector('.document-report').value;
    if (hasDoc) hasAny = true;
    if (progress > 0) anyStarted = true;
    if (progress < 100) allDone = false;
    if (mspIsLate(panel)) anyLate = true;
  });
  if (!hasAny) return 'not_started';
  if (anyLate) return 'late';
  if (allDone) return 'done';
  if (anyStarted) return 'in_progress';
  return 'not_started';
}
const mspStatusLabels = { not_started: 'Belum Mulai', in_progress: 'On Progress', done: 'Done', late: 'Telat' };
function mspUpdateStats() {
  let visible = 0, done = 0, progress = 0, late = 0;
  document.querySelectorAll('#mspScheduleWrapper .msp-item-card').forEach(function (card) {
    if (card.classList.contains('d-none')) return;
    visible++;
    const status = mspItemStatus(card);
    const badge = card.querySelector('.msp-item-status');
    if (badge) { badge.dataset.status = status; badge.textContent = mspStatusLabels[status]; }
    if (status === 'done') done++;
    else if (status === 'late') late++;
    else if (status === 'in_progress') progress++;
  });
  document.getElementById('mspStatVisible').textContent = visible;
  document.getElementById('mspStatDone').textContent = done;
  document.getElementById('mspStatProgress').textContent = progress;
  document.getElementById('mspStatLate').textContent = late;
}
document.addEventListener('change', function (e) {
  if (e.target.matches('.plan-end, .actual-end, .resch-input, input[name="progress[]"], .document-report')) {
    const panel = e.target.closest('.msp-line-panel');
    if (panel) mspUpdateLateBadge(panel);
    mspUpdateStats();
  }
});

// ---- add / remove / toggle-milestone per line panel ----
document.addEventListener('click', function (e) {
  if (e.target.closest('.msp-toggle-milestone')) {
    const panel = e.target.closest('.msp-line-panel');
    const milestoneBox = panel.querySelector('.msp-milestone-row');
    if (milestoneBox) milestoneBox.style.display = milestoneBox.style.display === 'none' ? '' : 'none';
  } else if (e.target.closest('.msp-add-line')) {
    const panel = e.target.closest('.msp-line-panel');
    const clone = panel.cloneNode(true);
    clone.querySelectorAll('input').forEach(function (input) {
      if (input.name === 'dtl_id[]') { input.value = ''; }
      else if (input.type !== 'hidden') { input.value = ''; }
    });
    const milestoneBox = clone.querySelector('.msp-milestone-row');
    if (milestoneBox) milestoneBox.style.display = 'none';
    const badge = clone.querySelector('.msp-late-badge');
    if (badge) badge.style.display = 'none';
    panel.parentNode.insertBefore(clone, panel.nextSibling);
    if (window.feather) feather.replace();
    mspRenumberLines();
    mspUpdateStats();
  } else if (e.target.closest('.msp-remove-line')) {
    const panel = e.target.closest('.msp-line-panel');
    const sameItemPanels = document.querySelectorAll('.msp-line-panel[data-item-no="' + panel.dataset.itemNo + '"]');
    if (sameItemPanels.length > 1) panel.remove();
    mspRenumberLines();
    mspUpdateStats();
  }
});

document.addEventListener('DOMContentLoaded', function () {
  mspRenumberLines();
  mspUpdateAllLateBadges();
  mspApplyProjectType();
});

// ---- Timeline chart ----
function mspMonthName(m) {
  return ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][m];
}
function mspRoman(n) { return ['I','II','III','IV','V'][n - 1]; }

function mspRenderGantt() {
  const container = document.getElementById('mspGanttChart');
  if (!container) return;

  const milestoneFields = ['plan_draft','plan_ai','plan_ae','actual_draft','actual_ai','actual_ae','resch_draft','resch_ai','resch_ae'];
  const panels = document.querySelectorAll('#mspScheduleWrapper .msp-item-card:not(.d-none) .msp-line-panel');
  const lines = [];
  const today = new Date(); today.setHours(0, 0, 0, 0);
  let minDate = today, maxDate = today;
  const lineNoCounters = {};

  // every visible panel (items hidden by Project Type are excluded) always
  // shows up as a row, even with no dates yet — only the date range used for
  // the header is driven by whichever rows actually have dates (plus "today").
  panels.forEach(function (panel) {
    const ps = panel.querySelector('.plan-start').value;
    const pe = panel.querySelector('.plan-end').value;
    const as = panel.querySelector('.actual-start').value;
    const ae = panel.querySelector('.actual-end').value;

    const milestones = {};
    milestoneFields.forEach(function (f) {
      const input = panel.querySelector('input[name="' + f + '[]"]');
      milestones[f] = input ? input.value : '';
    });

    const allDates = [ps, pe, as, ae].concat(milestoneFields.map(function (f) { return milestones[f]; }));
    allDates.filter(Boolean).forEach(function (d) {
      const dt = new Date(d);
      if (dt < minDate) minDate = dt;
      if (dt > maxDate) maxDate = dt;
    });

    const itemNo = panel.dataset.itemNo;
    lineNoCounters[itemNo] = (lineNoCounters[itemNo] || 0) + 1;
    const late = mspIsLate(panel);

    lines.push({
      stage: panel.dataset.stageGroup,
      itemNo: itemNo,
      itemName: panel.dataset.itemName,
      lineNo: itemNo + '.' + lineNoCounters[itemNo],
      doc: panel.querySelector('.document-report').value,
      pic: panel.querySelector('.pic-input').value,
      note: panel.querySelector('.notes-input').value,
      ps: ps, pe: pe, as: as, ae: ae,
      milestones: milestones,
      late: late,
    });
  });

  if (!lines.length) {
    container.innerHTML = '<p class="text-muted">Belum ada baris schedule.</p>';
    return;
  }

  const months = [];
  const cur = new Date(minDate.getFullYear(), minDate.getMonth(), 1);
  const end = new Date(maxDate.getFullYear(), maxDate.getMonth(), 1);
  while (cur <= end) {
    months.push({ year: cur.getFullYear(), month: cur.getMonth() });
    cur.setMonth(cur.getMonth() + 1);
  }

  const weeksPerMonth = 5;
  const totalCols = months.length * weeksPerMonth;

  function colIndex(dateStr) {
    if (!dateStr) return null;
    const d = new Date(dateStr);
    const mi = months.findIndex(function (m) { return m.year === d.getFullYear() && m.month === d.getMonth(); });
    if (mi === -1) return null;
    const week = Math.min(weeksPerMonth, Math.ceil(d.getDate() / 7));
    return mi * weeksPerMonth + week;
  }

  let thead = '<tr>'
    + '<th class="msp-gantt-label" rowspan="2">Stage</th>'
    + '<th rowspan="2">No</th>'
    + '<th class="msp-gantt-label" rowspan="2">Item</th>'
    + '<th class="msp-gantt-label" rowspan="2">Document / Report</th>'
    + '<th rowspan="2">PIC</th>';
  months.forEach(function (m) {
    thead += '<th colspan="' + weeksPerMonth + '" class="msp-gantt-month">' + mspMonthName(m.month) + ' ' + m.year + '</th>';
  });
  thead += '</tr><tr>';
  months.forEach(function () {
    for (let w = 1; w <= weeksPerMonth; w++) {
      thead += '<th class="msp-gantt-week">' + mspRoman(w) + '</th>';
    }
  });
  thead += '</tr>';

  // group consecutive rows sharing the same stage / item so those columns can
  // be rendered as merged (rowspan) cells, same as the Excel template.
  const stageSpan = new Array(lines.length).fill(0);
  const itemSpan = new Array(lines.length).fill(0);
  for (let i = 0; i < lines.length; i++) {
    if (i === 0 || lines[i].stage !== lines[i - 1].stage) {
      let span = 1;
      for (let j = i + 1; j < lines.length && lines[j].stage === lines[i].stage; j++) span++;
      stageSpan[i] = span;
    }
    if (i === 0 || lines[i].itemNo !== lines[i - 1].itemNo) {
      let span = 1;
      for (let j = i + 1; j < lines.length && lines[j].itemNo === lines[i].itemNo; j++) span++;
      itemSpan[i] = span;
    }
  }

  let tbody = '';
  lines.forEach(function (line, idx) {
    tbody += '<tr class="' + (idx % 2 ? 'msp-gantt-odd' : '') + '">';
    if (stageSpan[idx]) tbody += '<td class="msp-gantt-label" rowspan="' + stageSpan[idx] + '">' + line.stage + '</td>';
    if (itemSpan[idx]) {
      tbody += '<td rowspan="' + itemSpan[idx] + '">' + line.itemNo + '</td>';
      tbody += '<td class="msp-gantt-label" rowspan="' + itemSpan[idx] + '">' + line.itemName + '</td>';
    }
    const docLabel = line.doc ? (line.lineNo + ' ' + line.doc) : '';
    const docStyle = line.late ? ' style="color:#dc3545;font-weight:600;"' : '';
    tbody += '<td class="msp-gantt-label" title="' + docLabel + '"' + docStyle + '>' + docLabel + '</td>';
    tbody += '<td>' + (line.pic || '') + '</td>';
    for (let c = 1; c <= totalCols; c++) {
      tbody += '<td class="msp-gantt-cell"></td>';
    }
    tbody += '</tr>';
  });

  const colgroup = '<colgroup><col style="width:110px"><col style="width:36px"><col style="width:180px"><col style="width:180px"><col style="width:90px">'
    + '<col style="width:26px">'.repeat(totalCols) + '</colgroup>';
  container.style.position = 'relative';
  container.innerHTML = '<table class="msp-gantt-table">' + colgroup + '<thead>' + thead + '</thead><tbody>' + tbody + '</tbody></table>';

  const containerRect = container.getBoundingClientRect();
  const bodyRows = container.querySelectorAll('tbody tr');

  // draw plan/actual as one continuous bar per line (spanning start..end
  // column) instead of a separate little box per week cell.
  function addBar(row, startCol, endCol, cls, top) {
    if (!startCol || !endCol) return;
    const cells = row.querySelectorAll('td.msp-gantt-cell');
    const startTd = cells[startCol - 1];
    const endTd = cells[endCol - 1];
    if (!startTd || !endTd) return;
    const sRect = startTd.getBoundingClientRect();
    const eRect = endTd.getBoundingClientRect();
    const bar = document.createElement('div');
    bar.className = cls;
    bar.style.left = (sRect.left - containerRect.left + 1) + 'px';
    bar.style.width = Math.max(2, eRect.right - sRect.left - 2) + 'px';
    bar.style.top = (sRect.top - containerRect.top + top) + 'px';
    container.appendChild(bar);
  }

  // free-text note per line, shown as a callout right after its bar.
  function addNote(row, col, text) {
    if (!text || !col) return;
    const cells = row.querySelectorAll('td.msp-gantt-cell');
    const td = cells[col - 1];
    if (!td) return;
    const rect = td.getBoundingClientRect();
    const label = document.createElement('div');
    label.className = 'msp-gantt-note';
    label.textContent = text;
    label.style.left = (rect.right - containerRect.left + 4) + 'px';
    label.style.top = (rect.top - containerRect.top + 5) + 'px';
    container.appendChild(label);
  }

  // single-date approval-workflow markers (D/AI/AE legend).
  const markerDefs = [
    { field: 'plan_draft', text: 'D', cls: 'msp-marker-plan' },
    { field: 'plan_ai', text: 'AI', cls: 'msp-marker-plan' },
    { field: 'plan_ae', text: 'AE', cls: 'msp-marker-plan' },
    { field: 'actual_draft', text: 'D', cls: 'msp-marker-actual' },
    { field: 'actual_ai', text: 'AI', cls: 'msp-marker-actual' },
    { field: 'actual_ae', text: 'AE', cls: 'msp-marker-actual' },
    { field: 'resch_draft', text: 'RD', cls: 'msp-marker-resch' },
    { field: 'resch_ai', text: 'RAI', cls: 'msp-marker-resch' },
    { field: 'resch_ae', text: 'RAE', cls: 'msp-marker-resch' },
  ];
  function addMarker(row, col, text, cls) {
    if (!col) return;
    const cells = row.querySelectorAll('td.msp-gantt-cell');
    const td = cells[col - 1];
    if (!td) return;
    const rect = td.getBoundingClientRect();
    const marker = document.createElement('div');
    marker.className = 'msp-marker ' + cls;
    marker.textContent = text;
    marker.title = text;
    marker.style.left = (rect.left - containerRect.left + 1) + 'px';
    marker.style.top = (rect.top - containerRect.top + 7) + 'px';
    container.appendChild(marker);
  }

  lines.forEach(function (line, idx) {
    const row = bodyRows[idx];
    const peCol = colIndex(line.pe), aeCol = colIndex(line.ae);
    addBar(row, colIndex(line.ps), peCol, 'msp-bar-plan' + (line.late ? ' msp-late' : ''), 4);
    addBar(row, colIndex(line.as), aeCol, 'msp-bar-actual', 16);
    addNote(row, aeCol || peCol, line.note);
    markerDefs.forEach(function (m) {
      addMarker(row, colIndex(line.milestones[m.field]), m.text, m.cls);
    });
  });

  // vertical "today" marker across the whole chart height.
  const todayCol = colIndex(today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0'));
  if (todayCol && bodyRows.length) {
    const cell = bodyRows[0].querySelectorAll('td.msp-gantt-cell')[todayCol - 1];
    const table = container.querySelector('table');
    if (cell && table) {
      const cRect = cell.getBoundingClientRect();
      const tRect = table.getBoundingClientRect();
      const line = document.createElement('div');
      line.className = 'msp-today-line';
      line.style.left = (cRect.left - containerRect.left) + 'px';
      line.style.top = (tRect.top - containerRect.top) + 'px';
      line.style.height = tRect.height + 'px';
      container.appendChild(line);

      const tag = document.createElement('div');
      tag.className = 'msp-today-tag';
      tag.textContent = 'Hari ini';
      tag.style.left = (cRect.left - containerRect.left) + 'px';
      tag.style.top = (tRect.top - containerRect.top - 14) + 'px';
      container.appendChild(tag);
    }
  }
}

document.addEventListener('DOMContentLoaded', function () {
  if (document.getElementById('mspGanttChart')) mspRenderGantt();
});
</script>
@endsection
