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
      <table class="table table-bordered table-sm">
        <thead>
          <tr>
            <th>File</th>
            <th>Mulai</th>
            <th>Selesai</th>
            <th>Status</th>
            <th>Durasi</th>
            <th>Ukuran</th>
            <th>Berlaku sampai</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $row)
          <tr>
            <td>
              @if($row->status === 'success')
                <a href="{{ route('file.download', ['file' => $row->file]) }}">{{ $row->file }}</a>
              @else
                {{ $row->file }}
              @endif
            </td>
            {{-- backup lama tanpa log: waktu file = waktu selesai, waktu mulai tidak diketahui --}}
            @php $start = \Carbon\Carbon::parse($row->started_at); @endphp
            <td>{{ $row->duration === null ? '-' : $start->format('d-m-Y H:i:s') }}</td>
            <td>{{ $start->copy()->addSeconds($row->duration ?? 0)->format('d-m-Y H:i:s') }}</td>
            <td>
              @if($row->status === 'success')
                <span class="badge badge-success">Sukses</span>
              @else
                <span class="badge badge-danger">Gagal</span>
                <div class="text-danger small" style="white-space: pre-wrap">{{ $row->error }}</div>
              @endif
            </td>
            <td>{{ $row->duration === null ? '-' : gmdate('H:i:s', $row->duration) }}</td>
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
  tickBackupCountdown();
  setInterval(tickBackupCountdown, 1000);
</script>
@endsection
