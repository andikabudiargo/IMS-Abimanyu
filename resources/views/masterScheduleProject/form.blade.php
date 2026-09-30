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

    <div class="card">
      <div class="card-header">
        <h4 class="card-title">{{ $hdr ? 'Edit' : 'New' }} Master Schedule Project</h4>
        @if ($hdr)
          <a href="{{ route('msp.history', $hdr->id) }}" class="btn btn-sm btn-outline-secondary">View History</a>
        @endif
      </div>
      <div class="card-body">
        <div class="form-row">
          @if ($hdr)
          <div class="form-group col-md-3">
            <label>MSP No</label>
            <input type="text" class="form-control" value="{{ $hdr->msp_no }}" readonly>
          </div>
          @endif
          <div class="form-group col-md-3">
            <label>Customer</label>
            <input type="text" name="customer" class="form-control" value="{{ old('customer', $hdr->customer ?? '') }}">
          </div>
          <div class="form-group col-md-3">
            <label>Part Name <span class="text-danger">*</span></label>
            <input type="text" name="part_name" class="form-control" required value="{{ old('part_name', $hdr->part_name ?? '') }}">
          </div>
          <div class="form-group col-md-3">
            <label>Model</label>
            <input type="text" name="model" class="form-control" value="{{ old('model', $hdr->model ?? '') }}">
          </div>
          <div class="form-group col-md-3">
            <label>Part Number</label>
            <input type="text" name="part_number" class="form-control" value="{{ old('part_number', $hdr->part_number ?? '') }}">
          </div>
          <div class="form-group col-md-3">
            <label>Issue Date</label>
            <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date', $hdr->issue_date ?? now()->toDateString()) }}">
          </div>
          @if ($hdr)
          <div class="form-group col-md-3">
            <label>Status</label>
            <select name="status" class="form-control">
              <option value="ongoing" {{ $hdr->status == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
              <option value="done" {{ $hdr->status == 'done' ? 'selected' : '' }}>Done</option>
              <option value="cancelled" {{ $hdr->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
          </div>
          <div class="form-group col-md-3">
            <label>Revision No</label>
            <input type="text" class="form-control" value="{{ $hdr->revision_no }}" readonly>
          </div>
          @endif
        </div>
        @if ($hdr)
        <div class="form-group">
          <label>Catatan Revisi <small class="text-muted">(diisi bila ada perubahan schedule pada penyimpanan ini, akan tercatat di history)</small></label>
          <input type="text" name="revision_reason" class="form-control" placeholder="mis. Reschedule T1 mundur karena material telat">
        </div>
        @endif
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h4 class="card-title">Schedule (13 Item Baku)</h4>
      </div>
      <div class="card-body">
        <table class="table table-bordered table-sm" id="mspLineTable">
          <thead>
            <tr>
              <th style="width:110px">Stage</th>
              <th style="width:40px">No</th>
              <th>Item</th>
              <th>Document / Report</th>
              <th style="width:130px">PIC</th>
              <th style="width:130px">Plan Start</th>
              <th style="width:130px">Plan End</th>
              <th style="width:130px">Actual Start</th>
              <th style="width:130px">Actual End</th>
              <th style="width:70px">Progress %</th>
              <th style="width:160px">Notes</th>
              <th style="width:36px"></th>
            </tr>
          </thead>
          <tbody>
            @foreach ($items as $itemNo => $item)
              @php $lines = $dtlByItem[$itemNo] ?? collect(); @endphp
              @if ($lines->isEmpty())
                @include('masterScheduleProject._line', ['itemNo' => $itemNo, 'item' => $item, 'line' => null])
              @else
                @foreach ($lines as $line)
                  @include('masterScheduleProject._line', ['itemNo' => $itemNo, 'item' => $item, 'line' => $line])
                @endforeach
              @endif
            @endforeach
          </tbody>
        </table>
        <small class="text-muted">Gunakan tombol <i data-feather="plus"></i> di tiap baris untuk menambah document/report line pada item yang sama.</small>
      </div>
    </div>

    <button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('msp.index') }}" class="btn btn-secondary">Cancel</a>
  </form>

  @if ($hdr)
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Timeline Chart</h4>
      <div class="heading-elements">
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="mspRenderGantt()">Refresh Chart</button>
      </div>
    </div>
    <div class="card-body" style="overflow-x:auto;">
      <div id="mspGanttChart"></div>
      <div class="mt-2" style="font-size:12px;line-height:2;">
        <span style="display:inline-block;width:14px;height:8px;border:1.5px solid #5a4fcf;border-radius:2px;margin-right:4px;"></span> Plan (bar)
        <span style="display:inline-block;width:14px;height:8px;background:#5a4fcf;border-radius:2px;margin-left:14px;margin-right:4px;"></span> Actual (bar)
        <span style="display:inline-block;width:14px;height:8px;background:#fffbe6;border:1px solid #e6d47a;border-radius:2px;margin-left:14px;margin-right:4px;"></span> Notes
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
        <small class="text-muted">Isi tanggal D/AI/AE lewat tombol <i data-feather="flag"></i> di tiap baris schedule.</small>
      </div>
    </div>
  </div>
  @endif
</section>

@endsection

@section('styles')
<style>
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
</style>
@endsection

@section('scripts')
<script>
// document/report line numbering: "<item no>.<line index within that item>",
// e.g. item 2's 2nd document line shows "2.2" — recomputed after every add/remove.
function mspRenumberLines() {
  const counters = {};
  document.querySelectorAll('#mspLineTable tbody tr.msp-line-row').forEach(function (row) {
    const itemNo = row.dataset.itemNo;
    counters[itemNo] = (counters[itemNo] || 0) + 1;
    const badge = row.querySelector('.msp-line-no');
    if (badge) badge.textContent = itemNo + '.' + counters[itemNo];
  });
}

// each schedule line is a pair of rows: the visible .msp-line-row and a
// hidden .msp-milestone-row (D/AI/AE dates) right after it — always move/
// clone/remove them together so the array indices submitted to the server
// stay aligned across both rows.
document.addEventListener('click', function (e) {
  if (e.target.closest('.msp-toggle-milestone')) {
    const row = e.target.closest('tr');
    const milestoneRow = row.nextElementSibling;
    if (milestoneRow && milestoneRow.classList.contains('msp-milestone-row')) {
      milestoneRow.style.display = milestoneRow.style.display === 'none' ? '' : 'none';
    }
  } else if (e.target.closest('.msp-add-line')) {
    const row = e.target.closest('tr');
    const milestoneRow = row.nextElementSibling && row.nextElementSibling.classList.contains('msp-milestone-row') ? row.nextElementSibling : null;

    const rowClone = row.cloneNode(true);
    rowClone.querySelectorAll('input').forEach(function (input) {
      if (input.name === 'dtl_id[]') { input.value = ''; }
      else if (input.type !== 'hidden') { input.value = ''; }
    });
    row.parentNode.insertBefore(rowClone, row.nextSibling);

    if (milestoneRow) {
      const milestoneClone = milestoneRow.cloneNode(true);
      milestoneClone.querySelectorAll('input').forEach(function (input) { input.value = ''; });
      milestoneClone.style.display = 'none';
      rowClone.parentNode.insertBefore(milestoneClone, rowClone.nextSibling);
    }

    if (window.feather) feather.replace();
    mspRenumberLines();
  } else if (e.target.closest('.msp-remove-line')) {
    const row = e.target.closest('tr');
    const sameItemRows = document.querySelectorAll('tr.msp-line-row[data-item-no="' + row.dataset.itemNo + '"]');
    if (sameItemRows.length > 1) {
      const milestoneRow = row.nextElementSibling && row.nextElementSibling.classList.contains('msp-milestone-row') ? row.nextElementSibling : null;
      if (milestoneRow) milestoneRow.remove();
      row.remove();
    }
    mspRenumberLines();
  }
});

document.addEventListener('DOMContentLoaded', mspRenumberLines);

function mspMonthName(m) {
  return ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][m];
}
function mspRoman(n) { return ['I','II','III','IV','V'][n - 1]; }

function mspRenderGantt() {
  const container = document.getElementById('mspGanttChart');
  if (!container) return;

  const milestoneFields = ['plan_draft','plan_ai','plan_ae','actual_draft','actual_ai','actual_ae','resch_draft','resch_ai','resch_ae'];
  const rows = document.querySelectorAll('#mspLineTable tbody tr.msp-line-row');
  const lines = [];
  let minDate = null, maxDate = null;
  const lineNoCounters = {};

  // every row (all 13 fixed items + any extra lines) always shows up as a row,
  // even with no dates yet — only the date range used for the header is driven
  // by whichever rows actually have dates.
  rows.forEach(function (row) {
    const ps = row.querySelector('.plan-start').value;
    const pe = row.querySelector('.plan-end').value;
    const as = row.querySelector('.actual-start').value;
    const ae = row.querySelector('.actual-end').value;

    const milestoneRow = row.nextElementSibling;
    const milestones = {};
    milestoneFields.forEach(function (f) {
      const input = milestoneRow ? milestoneRow.querySelector('input[name="' + f + '[]"]') : null;
      milestones[f] = input ? input.value : '';
    });

    const allDates = [ps, pe, as, ae].concat(milestoneFields.map(function (f) { return milestones[f]; }));
    allDates.filter(Boolean).forEach(function (d) {
      const dt = new Date(d);
      if (!minDate || dt < minDate) minDate = dt;
      if (!maxDate || dt > maxDate) maxDate = dt;
    });

    const itemNo = row.dataset.itemNo;
    lineNoCounters[itemNo] = (lineNoCounters[itemNo] || 0) + 1;

    lines.push({
      stage: row.dataset.stageGroup,
      itemNo: itemNo,
      itemName: row.dataset.itemName,
      lineNo: itemNo + '.' + lineNoCounters[itemNo],
      doc: row.querySelector('.document-report').value,
      pic: row.querySelector('.pic-input').value,
      note: row.querySelector('.notes-input').value,
      ps: ps, pe: pe, as: as, ae: ae,
      milestones: milestones,
    });
  });

  if (!lines.length) {
    container.innerHTML = '<p class="text-muted">Belum ada baris schedule.</p>';
    return;
  }

  if (!minDate) {
    minDate = new Date();
    maxDate = new Date();
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
    tbody += '<td class="msp-gantt-label" title="' + docLabel + '">' + docLabel + '</td>';
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

  // draw plan/actual as one continuous bar per line (spanning start..end column)
  // instead of a separate little box per week cell.
  const containerRect = container.getBoundingClientRect();
  const bodyRows = container.querySelectorAll('tbody tr');

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

  // free-text note per line, shown as a callout right after its bar — this is
  // the "panduan/keterangan" per progress point, e.g. "OK HPM 15 Des 2025".
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

  // single-date approval-workflow markers (D/AI/AE legend): small square with
  // a short label, placed on its own date's column instead of a bar range.
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
    addBar(row, colIndex(line.ps), peCol, 'msp-bar-plan', 4);
    addBar(row, colIndex(line.as), aeCol, 'msp-bar-actual', 16);
    addNote(row, aeCol || peCol, line.note);
    markerDefs.forEach(function (m) {
      addMarker(row, colIndex(line.milestones[m.field]), m.text, m.cls);
    });
  });
}

document.addEventListener('DOMContentLoaded', function () {
  if (document.getElementById('mspGanttChart')) mspRenderGantt();
});
</script>
@endsection
