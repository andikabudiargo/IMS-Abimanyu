@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="delivery-monitoring">
  <div class="card">
    <div class="card-header"><h4 class="card-title">Filter</h4></div>
    <div class="card-body">
      <form method="GET" autocomplete="off">
        <div class="form-row align-items-end">
          <div class="form-group col-md-2">
            <label for="from">Dari Tanggal</label>
            <input type="date" id="from" name="from" value="{{ $from }}" class="form-control">
          </div>
          <div class="form-group col-md-2">
            <label for="to">Sampai Tanggal</label>
            <input type="date" id="to" name="to" value="{{ $to }}" class="form-control">
          </div>
          <div class="form-group col-md-6">
            <label for="customer">Customer</label>
            <select id="customer" name="customer[]" class="select2 form-control" multiple>
              @foreach($customers as $c)
                <option value="{{ $c->kode }}" @if(in_array($c->kode, $selected)) selected @endif>{{ $c->kode }} - {{ $c->nama }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group col-md-2">
            <button class="btn btn-primary"><i class="fa fa-search"></i> Tampilkan</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  @forelse($groups as $kode => $g)
    <div class="card">
      <div class="card-header"><h4 class="card-title">{{ $g['name'] }} ({{ $kode }})</h4></div>
      <div class="card-body table-responsive">
        <table class="table table-bordered table-hover table-sm">
          <thead>
            <tr>
              <th class="align-middle" style="min-width:220px">Article</th>
              @foreach($g['dates'] as $d)<th class="text-center" style="min-width:70px">{{ $d }}</th>@endforeach
              <th class="text-center font-weight-bold">Total</th>
            </tr>
          </thead>
          <tbody>
            @foreach($g['articles'] as $articleCode => $articleName)
              <tr>
                <td>{{ $articleCode }} - {{ $articleName }}</td>
                @php($rowTotal = 0)
                @foreach($g['dates'] as $d)
                  @php($qty = $g['matrix'][$articleCode][$d] ?? 0)
                  @php($rowTotal += $qty)
                  <td class="text-center">{{ $qty ? rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.') : '-' }}</td>
                @endforeach
                <td class="text-center font-weight-bold">{{ rtrim(rtrim(number_format($rowTotal, 2, '.', ''), '0'), '.') }}</td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr class="font-weight-bold">
              <td class="text-right">Total</td>
              @php($grandTotal = 0)
              @foreach($g['dates'] as $d)
                @php($colTotal = array_sum(array_column($g['matrix'], $d)))
                @php($grandTotal += $colTotal)
                <td class="text-center">{{ rtrim(rtrim(number_format($colTotal, 2, '.', ''), '0'), '.') }}</td>
              @endforeach
              <td class="text-center">{{ rtrim(rtrim(number_format($grandTotal, 2, '.', ''), '0'), '.') }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  @empty
    <div class="card"><div class="card-body text-center text-muted">Tidak ada delivery pada periode ini</div></div>
  @endforelse
</section>
@endsection
@section('scripts')
<script type="text/javascript">
  $('.select2').select2({placeholder: 'Semua Customer', allowClear: true});
</script>
@endsection
