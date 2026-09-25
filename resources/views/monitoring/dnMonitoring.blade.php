@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="dn-monitoring">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">DN Belum Dibuatkan Invoice - {{ $monthLabel }}</h4>
    </div>
    <div class="card-body table-responsive">
      <table class="table table-bordered table-hover">
        <thead>
          <tr>
            <th>Customer</th><th>Cutt Off DN</th>
            <th class="text-center">W1 (1-7)</th><th class="text-center">W2 (8-14)</th>
            <th class="text-center">W3 (15-21)</th><th class="text-center">W4 (22-akhir)</th>
          </tr>
        </thead>
        <tbody>
          @forelse($summary as $kode => $row)
            <tr>
              <td>{{ $row['name'] }}</td>
              <td>{{ $row['cutt_off'] }}</td>
              @foreach([1,2,3,4] as $w)
                <td class="text-center">
                  @if(!empty($row['w'][$w]))
                    <a href="#" class="font-weight-bold btn-dn" data-customer="{{ $kode }}" data-week="{{ $w }}" data-name="{{ $row['name'] }}">{{ $row['w'][$w] }}</a>
                  @else - @endif
                </td>
              @endforeach
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted">Tidak ada DN yang belum di-invoice bulan ini</td></tr>
          @endforelse
        </tbody>
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
  const esc = s => $('<div>').text(s ?? '').html();
  $('.btn-dn').on('click', function (e) {
    e.preventDefault();
    const d = $(this).data();
    $('#dnModalTitle').text(d.name + ' - W' + d.week);
    $('#dnModalBody').html('<tr><td colspan="6">Loading...</td></tr>');
    $('#dnModal').modal('show');
    $.get("{{ route('dnMonitoring.detail') }}", {customer: d.customer, week: d.week}, function (rows) {
      $('#dnModalBody').html(rows.map(r => '<tr><td><a href="' + esc(r.url) + '" target="_blank">' + esc(r.dn_number) + '</a></td><td>'
        + esc(r.source) + '</td><td>' + esc(r.delivery_date) + '</td><td>' + esc(r.status) + '</td><td>'
        + esc(r.created_by) + '</td><td>' + esc(r.created_at) + '</td></tr>').join(''));
    });
  });
</script>
@endsection
