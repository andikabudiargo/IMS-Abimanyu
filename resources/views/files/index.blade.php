@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="list-backup">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Backup file list</h4>
    </div>
    <div class="card-body table-responsive">
      <div class="form-row mb-1">
        <div class="col-md-2">
          <label for="filter-from">Dari tanggal</label>
          <input type="date" id="filter-from" class="form-control form-control-sm backup-filter">
        </div>
        <div class="col-md-2">
          <label for="filter-to">Sampai tanggal</label>
          <input type="date" id="filter-to" class="form-control form-control-sm backup-filter">
        </div>
        <div class="col-md-2">
          <label for="filter-status">Status</label>
          <select id="filter-status" class="form-control form-control-sm backup-filter">
            <option value="">Semua</option>
            <option value="success">Sukses</option>
            <option value="failed">Gagal</option>
          </select>
        </div>
      </div>
      <table class="table table-bordered table-sm" id="backup-table">
        <thead>
          <tr>
            <th>No</th>
            <th>File</th>
            <th>Tanggal</th>
            <th>Jam Mulai</th>
            <th>Jam Selesai</th>
            <th>Durasi</th>
            <th>Status</th>
            <th>Ukuran</th>
            <th>Berlaku sampai</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $row)
          <tr data-date="{{ substr($row->started_at, 0, 10) }}" data-status="{{ $row->status }}">
            <td class="row-no">{{ $loop->iteration }}</td>
            <td>
              @if($row->status === 'success')
                <a href="{{ route('file.download', ['file' => $row->file]) }}">{{ $row->file }}</a>
              @else
                {{ $row->file }}
              @endif
            </td>
            {{-- backup lama tanpa log: waktu file = waktu selesai, waktu mulai tidak diketahui --}}
            @php $start = \Carbon\Carbon::parse($row->started_at); @endphp
            <td>{{ $start->format('d-m-Y') }}</td>
            <td>{{ $row->duration === null ? '-' : $start->format('H:i:s') }}</td>
            <td>{{ $start->copy()->addSeconds($row->duration ?? 0)->format('H:i:s') }}</td>
            <td>{{ $row->duration === null ? '-' : gmdate('H:i:s', $row->duration) }}</td>
            <td>
              @if($row->status === 'success')
                <span class="badge badge-success">Sukses</span>
              @else
                <span class="badge badge-danger">Gagal</span>
                <div class="text-danger small" style="white-space: pre-wrap">{{ $row->error }}</div>
              @endif
            </td>
            <td>{{ $row->size === null ? '-' : number_format($row->size / 1048576, 1) . ' MB' }}</td>
            <td>
              @if($row->monthly)
                <span class="badge badge-info">Bulanan (permanen)</span>
              @elseif($row->expires_at)
                {{ date('d-m-Y H:i', $row->expires_at) }}
                <div class="small text-muted countdown" data-expires="{{ $row->expires_at }}"></div>
              @else
                -
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</section>
@endsection
@section('scripts')
<script type="text/javascript">
  function tickBackupCountdown() {
    var now = Date.now() / 1000;
    document.querySelectorAll('.countdown').forEach(function (el) {
      var left = el.dataset.expires - now;
      if (left <= 0) { el.textContent = 'Akan dihapus pada backup berikutnya'; return; }
      var d = Math.floor(left / 86400), h = Math.floor(left % 86400 / 3600),
          m = Math.floor(left % 3600 / 60), s = Math.floor(left % 60);
      el.textContent = 'Sisa ' + d + ' hari ' + h + ' jam ' + m + ' menit ' + s + ' detik';
    });
  }
  function filterBackupRows() {
    var from = document.getElementById('filter-from').value,
        to = document.getElementById('filter-to').value,
        status = document.getElementById('filter-status').value,
        no = 0;
    document.querySelectorAll('#backup-table tbody tr').forEach(function (tr) {
      var d = tr.dataset.date;
      var show = (!from || d >= from) && (!to || d <= to) && (!status || tr.dataset.status === status);
      tr.style.display = show ? '' : 'none';
      if (show) tr.querySelector('.row-no').textContent = ++no;
    });
  }
  document.querySelectorAll('.backup-filter').forEach(function (el) {
    el.addEventListener('change', filterBackupRows);
  });

  tickBackupCountdown();
  setInterval(tickBackupCountdown, 1000);
</script>
@endsection
