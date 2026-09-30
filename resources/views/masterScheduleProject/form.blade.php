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
      <div class="mt-2" style="font-size:12px;">
        <span style="display:inline-block;width:14px;height:8px;border:1.5px solid #5a4fcf;border-radius:2px;margin-right:4px;"></span> Plan
        <span style="display:inline-block;width:14px;height:8px;background:#5a4fcf;border-radius:2px;margin-left:14px;margin-right:4px;"></span> Actual
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
</style>
@endsection

@section('scripts')
<script>
// document/report line numbering: "<item no>.<line index within that item>",
// e.g. item 2's 2nd document line shows "2.2" — recomputed after every add/remove.
function mspRenumberLines() {
  const counters = {};
  document.querySelectorAll('#mspLineTable tbody tr').forEach(function (row) {
    const itemNo = row.dataset.itemNo;
    counters[itemNo] = (counters[itemNo] || 0) + 1;
    const badge = row.querySelector('.msp-line-no');
    if (badge) badge.textContent = itemNo + '.' + counters[itemNo];
  });
}

// per-row "+" duplicates that row (same item), keeping hidden item_no/item_name/stage_group
document.addEventListener('click', function (e) {
  if (e.target.closest('.msp-add-line')) {
    const row = e.target.closest('tr');
    const clone = row.cloneNode(true);
    clone.querySelectorAll('input').forEach(function (input) {
      if (input.name === 'dtl_id[]') { input.value = ''; }
      else if (input.type !== 'hidden') { input.value = ''; }
    });
    row.parentNode.insertBefore(clone, row.nextSibling);
    if (window.feather) feather.replace();
    mspRenumberLines();
  } else if (e.target.closest('.msp-remove-line')) {
    const row = e.target.closest('tr');
    const sameItemRows = document.querySelectorAll('tr[data-item-no="' + row.dataset.itemNo + '"]');
    if (sameItemRows.length > 1) row.remove();
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

  const rows = document.querySelectorAll('#mspLineTable tbody tr');
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

    [ps, pe, as, ae].filter(Boolean).forEach(function (d) {
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
      ps: ps, pe: pe, as: as, ae: ae,
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

  lines.forEach(function (line, idx) {
    const row = bodyRows[idx];
    addBar(row, colIndex(line.ps), colIndex(line.pe), 'msp-bar-plan', 4);
    addBar(row, colIndex(line.as), colIndex(line.ae), 'msp-bar-actual', 16);
  });
}

document.addEventListener('DOMContentLoaded', function () {
  if (document.getElementById('mspGanttChart')) mspRenderGantt();
});
</script>
@endsection
