@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="dn-monitoring">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Outstanding Surat Jalan Kembali - {{ $monthLabel }}</h4>
      <form method="GET" class="form-inline"><label class="mr-1">Periode</label><input type="month" name="periode" value="{{ $periode }}" class="form-control form-control-sm mr-1"><select name="customer[]" class="select2 form-control form-control-sm mr-1" multiple data-placeholder="Semua Customer" style="min-width:300px">@foreach($customers as $c)<option value="{{ $c->kode }}" @if(in_array($c->kode, $selected)) selected @endif>{{ $c->kode }} - {{ $c->nama }}</option>@endforeach</select><button class="btn btn-primary btn-sm">Tampilkan</button></form>
    </div>
    <div class="card-body table-responsive">
      <table class="table table-bordered table-hover">
        <thead>
          <tr>
            <th>Customer</th><th>Cutt Off DN</th>
            <th class="text-center">W1 (1-7)</th><th class="text-center">W2 (8-14)</th>
            <th class="text-center">W3 (15-21)</th><th class="text-center">W4 (22-akhir)</th><th class="text-center">Total</th>
          </tr>
        </thead>
        <tbody>
          @forelse($summary as $kode => $row)
            <tr>
              <td>{{ $row['name'] }}</td>
              <td>@if($row['cutt_off']){{ $row['cutt_off'] }}@else<small><em class="text-muted">Belum Ada Cut Off Tanggal Kembali Surat Jalan</em></small>@endif</td>
              @foreach([1,2,3,4] as $w)
                <td class="text-center">
                  @if(!empty($row['w'][$w]))
                    <a href="#" class="font-weight-bold btn-dn" data-customer="{{ $kode }}" data-week="{{ $w }}" data-name="{{ $row['name'] }}">{{ $row['w'][$w] }}</a>
                  @else - @endif
                </td>
              @endforeach
              <td class="text-center font-weight-bold">{{ array_sum($row['w']) }}</td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted">Tidak ada DN yang belum di-invoice bulan ini</td></tr>
          @endforelse
        </tbody>
        @if($summary)
        <tfoot>
          <tr class="font-weight-bold">
            <td colspan="2" class="text-right">Total</td>
            @foreach([1,2,3,4] as $w)<td class="text-center">{{ collect($summary)->sum(fn($r) => $r['w'][$w] ?? 0) }}</td>@endforeach
            <td class="text-center">{{ collect($summary)->sum(fn($r) => array_sum($r['w'])) }}</td>
          </tr>
        </tfoot>
        @endif
      </table>
    </div>
  </div>
</section>

<div class="modal fade" id="dnModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="dnModalTitle"></h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body table-responsive">
        <table class="table table-sm table-bordered">
          <thead><tr><th>Nomor DN</th><th>Tipe</th><th>Delivery Date</th><th>Status</th><th>Created By</th><th>Created At</th></tr></thead>
          <tbody id="dnModalBody"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
@section('scripts')
<script type="text/javascript">
  $('.select2').select2({placeholder: 'Semua Customer', allowClear: true});
  const esc = s => $('<div>').text(s ?? '').html();
  $('.btn-dn').on('click', function (e) {
    e.preventDefault();
    const d = $(this).data();
    $('#dnModalTitle').text(d.name + ' - W' + d.week);
    $('#dnModalBody').html('<tr><td colspan="6">Loading...</td></tr>');
    $('#dnModal').modal('show');
    $.get("{{ route('dnMonitoring.detail') }}", {customer: d.customer, week: d.week, periode: '{{ $periode }}'}, function (rows) {
      $('#dnModalBody').html(rows.map(r => '<tr><td><a href="' + esc(r.url) + '" target="_blank">' + esc(r.dn_number) + '</a></td><td>'
        + esc(r.source) + '</td><td>' + esc(r.delivery_date) + '</td><td>' + esc(r.status) + '</td><td>'
        + esc(r.created_by) + '</td><td>' + esc(r.created_at) + '</td></tr>').join(''));
    });
  });
</script>
@endsection
