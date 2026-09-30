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
        <span style="display:inline-block;width:14px;height:8px;border:1px solid #333;margin-right:4px;"></span> Plan
        <span style="display:inline-block;width:14px;height:8px;background:#000;margin-left:14px;margin-right:4px;"></span> Actual
      </div>
    </div>
  </div>
  @endif
</section>

@endsection

@section('scripts')
<script>
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
  } else if (e.target.closest('.msp-remove-line')) {
    const row = e.target.closest('tr');
    const sameItemRows = document.querySelectorAll('tr[data-item-no="' + row.dataset.itemNo + '"]');
    if (sameItemRows.length > 1) row.remove();
  }
});

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

  rows.forEach(function (row) {
    const ps = row.querySelector('.plan-start').value;
    const pe = row.querySelector('.plan-end').value;
    const as = row.querySelector('.actual-start').value;
    const ae = row.querySelector('.actual-end').value;
    if (!ps && !pe && !as && !ae) return;

    const doc = row.querySelector('.document-report').value;
    const itemNo = row.dataset.itemNo;
    const label = itemNo + '. ' + (doc || row.dataset.itemName);

    [ps, pe, as, ae].filter(Boolean).forEach(function (d) {
      const dt = new Date(d);
      if (!minDate || dt < minDate) minDate = dt;
      if (!maxDate || dt > maxDate) maxDate = dt;
    });
    lines.push({ label: label, ps: ps, pe: pe, as: as, ae: ae });
  });

  if (!lines.length) {
    container.innerHTML = '<p class="text-muted">Isi tanggal plan/actual untuk melihat chart.</p>';
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

  let html = '<div style="display:grid;grid-template-columns:240px repeat(' + totalCols + ',24px);font-size:11px;">';
  html += '<div style="border:1px solid #ddd;"></div>';
  months.forEach(function (m) {
    html += '<div style="grid-column: span ' + weeksPerMonth + ';text-align:center;font-weight:600;border:1px solid #ddd;padding:2px;">' + mspMonthName(m.month) + ' ' + m.year + '</div>';
  });
  html += '<div style="border:1px solid #eee;"></div>';
  months.forEach(function () {
    for (let w = 1; w <= weeksPerMonth; w++) {
      html += '<div style="text-align:center;border:1px solid #eee;">' + mspRoman(w) + '</div>';
    }
  });

  lines.forEach(function (line) {
    html += '<div style="border:1px solid #eee;padding:2px 4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="' + line.label + '">' + line.label + '</div>';
    const psCol = colIndex(line.ps), peCol = colIndex(line.pe);
    const asCol = colIndex(line.as), aeCol = colIndex(line.ae);
    for (let c = 1; c <= totalCols; c++) {
      let inner = '';
      if (psCol && peCol && c >= psCol && c <= peCol) {
        inner += '<div style="position:absolute;top:2px;left:1px;right:1px;height:7px;border:1px solid #333;"></div>';
      }
      if (asCol && aeCol && c >= asCol && c <= aeCol) {
        inner += '<div style="position:absolute;bottom:2px;left:1px;right:1px;height:7px;background:#000;"></div>';
      }
      html += '<div style="border:1px solid #f4f4f4;position:relative;height:22px;">' + inner + '</div>';
    }
  });

  html += '</div>';
  container.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function () {
  if (document.getElementById('mspGanttChart')) mspRenderGantt();
});
</script>
@endsection
