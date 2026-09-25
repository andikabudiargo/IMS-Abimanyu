@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="dn-monitoring">
  <div class="card">
    <div class="card-header"><h4 class="card-title">Filter</h4></div>
    <div class="card-body">
      <form method="GET" autocomplete="off">
        <div class="form-row align-items-end">
          <div class="form-group col-md-3">
            <label for="periode">Periode</label>
            <input type="month" id="periode" name="periode" value="{{ $periode }}" class="form-control">
          </div>
          <div class="form-group col-md-3">
            <label for="filter">Filter</label>
            <select id="filter" name="filter" class="form-control">
              <option value="invoice" @if($filter == 'invoice') selected @endif>Belum Dibuatkan Invoice</option>
              <option value="kembali" @if($filter == 'kembali') selected @endif>Belum Kembali</option>
            </select>
          </div>
          <div class="form-group col-md-6">
            <label for="customer">Customer</label>
            <select id="customer" name="customer[]" class="select2 form-control" multiple>
              @foreach($customers as $c)
                <option value="{{ $c->kode }}" @if(in_array($c->kode, $selected)) selected @endif>{{ $c->kode }} - {{ $c->nama }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group col-md-3">
            <button class="btn btn-primary"><i class="fa fa-search"></i> Tampilkan</button>
            <a href="{{ route('dnMonitoring.exportSummary', request()->query()) }}" class="btn btn-success"><i class="fa fa-download"></i> Export Excel</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h4 class="card-title">Outstanding Surat Jalan Kembali - {{ $monthLabel }}</h4></div>
    <div class="card-body table-responsive">
      <table class="table table-bordered table-hover table-striped">
        <colgroup><col style="width:4%"><col style="width:28%"><col style="width:28%"><col style="width:8%"><col style="width:8%"><col style="width:8%"><col style="width:8%"><col style="width:8%"></colgroup>
        <thead>
          <tr>
            <th class="align-middle text-center">No</th><th class="align-middle">Customer</th><th class="align-middle">Cutt Off Surat Jalan Kembali</th>
            <th class="text-center">W1 (1-7)</th><th class="text-center">W2 (8-14)</th>
            <th class="text-center">W3 (15-21)</th><th class="text-center">W4 (22-akhir)</th><th class="text-center">Total</th>
          </tr>
        </thead>
        <tbody>
          @forelse($summary as $kode => $row)
            <tr>
              <td class="text-center">{{ $loop->iteration }}</td><td>{{ $row['name'] }}</td>
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
            <tr><td colspan="8" class="text-center text-muted">Tidak ada DN {{ $filter == 'kembali' ? 'yang belum kembali' : 'yang belum di-invoice' }} bulan ini</td></tr>
          @endforelse
        </tbody>
        @if($summary)
        <tfoot>
          <tr class="font-weight-bold">
            <td colspan="3" class="text-right">Total</td>
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
          <thead><tr><th>No</th><th>Nomor DN</th><th>Tipe</th><th>Delivery Date</th><th>Status</th><th>Created By</th><th>Created At</th></tr></thead>
          <tbody id="dnModalBody"></tbody>
        </table>
      </div>
      <div class="modal-footer"><a href="#" id="dnExport" class="btn btn-success btn-sm"><i class="fa fa-download"></i> Export Excel</a></div>
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
    $('#dnExport').attr('href', "{{ route('dnMonitoring.export') }}?" + $.param({customer: d.customer, week: d.week, periode: '{{ $periode }}', filter: '{{ $filter }}'}));
    $('#dnModalBody').html('<tr><td colspan="7">Loading...</td></tr>');
    $('#dnModal').modal('show');
    $.get("{{ route('dnMonitoring.detail') }}", {customer: d.customer, week: d.week, periode: '{{ $periode }}', filter: '{{ $filter }}'}, function (rows) {
      $('#dnModalBody').html(rows.map((r, i) => '<tr><td>' + (i + 1) + '</td><td><a href="' + esc(r.url) + '" target="_blank">' + esc(r.dn_number) + '</a></td><td>'
        + esc(r.source) + '</td><td>' + esc(r.delivery_date) + '</td><td>' + esc(r.status) + '</td><td>'
        + esc(r.created_by) + '</td><td>' + esc(r.created_at) + '</td></tr>').join(''));
    });
  });
</script>
@endsection
