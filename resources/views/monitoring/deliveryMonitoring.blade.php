@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="delivery-monitoring">
  <div class="card">
    <div class="card-header"><h4 class="card-title">Filter</h4></div>
    <div class="card-body">
      <form method="GET" autocomplete="off" id="filterForm">
        <input type="hidden" name="view" id="viewInput" value="{{ $summary ? 'summary' : 'detail' }}">
        <div class="form-row align-items-end">
          <div class="form-group col-md-2">
            <label for="from">Dari Tanggal</label>
            <input type="date" id="from" name="from" value="{{ $from }}" class="form-control">
          </div>
          <div class="form-group col-md-2">
            <label for="to">Sampai Tanggal</label>
            <input type="date" id="to" name="to" value="{{ $to }}" class="form-control">
          </div>
          <div class="form-group col-md-3">
            <label for="dn_type">Tipe DN</label>
            <select id="dn_type" name="dn_type[]" class="select2 form-control" multiple>
              <option value="dn" @if(in_array('dn', $dnType)) selected @endif>Delivery Note</option>
              <option value="temp" @if(in_array('temp', $dnType)) selected @endif>SJ Sementara</option>
              <option value="replace" @if(in_array('replace', $dnType)) selected @endif>DN Replace</option>
            </select>
          </div>
          <div class="form-group col-md-5">
            <label for="customer">Customer</label>
            <select id="customer" name="customer[]" class="select2 form-control" multiple>
              @foreach($customers as $c)
                <option value="{{ $c->kode }}" @if(in_array($c->kode, $selected)) selected @endif>{{ $c->kode }} - {{ $c->nama }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="form-row align-items-end">
          <div class="form-group col-md-10">
            <label for="article">Article</label>
            <select id="article" name="article[]" class="select2 form-control" multiple>
              @foreach($articleOptions as $code => $name)
                <option value="{{ $code }}" @if(in_array($code, $selectedArticles)) selected @endif>{{ $name }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group col-md-2">
            <button class="btn btn-primary"><i class="fa fa-search"></i> Tampilkan</button>
          </div>
        </div>
        <div class="form-row align-items-center">
          <div class="form-group col-md-12">
            <div class="btn-group btn-group-toggle" data-toggle="buttons">
              <label class="btn btn-outline-primary @if(!$summary) active @endif">
                <input type="radio" name="viewToggle" value="detail" @if(!$summary) checked @endif> Detail (per Customer)
              </label>
              <label class="btn btn-outline-primary @if($summary) active @endif">
                <input type="radio" name="viewToggle" value="summary" @if($summary) checked @endif> Summary (Gabungan)
              </label>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h4 class="card-title">Heatmap Delivery vs Plan - {{ $from }} s/d {{ $to }}</h4></div>
    <div class="card-body">
      <div id="deliveryHeatmap"></div>
    </div>
  </div>

  @forelse($groups as $kode => $g)
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title">{{ $g['name'] }} @if(!$summary) ({{ $kode }}) @endif</h4>
        @if($summary)
        <div>
          <a href="{{ route('deliveryMonitoring.export.excel', request()->query()) }}" class="btn btn-sm btn-success"><i class="fa fa-file-excel"></i> Export Total (Excel)</a>
          <a href="{{ route('deliveryMonitoring.export.pdf', request()->query()) }}" target="_blank" class="btn btn-sm btn-danger"><i class="fa fa-file-pdf"></i> Export Total (PDF)</a>
        </div>
        @else
        <div>
          <a href="{{ route('deliveryMonitoring.export.excel', array_merge(request()->query(), ['customer' => [$kode]])) }}" class="btn btn-sm btn-success"><i class="fa fa-file-excel"></i> Excel</a>
          <a href="{{ route('deliveryMonitoring.export.pdf', array_merge(request()->query(), ['customer' => [$kode]])) }}" target="_blank" class="btn btn-sm btn-danger"><i class="fa fa-file-pdf"></i> PDF</a>
        </div>
        @endif
      </div>
      <div class="card-body">
        <div class="dm-table-scroll">
          <table class="table table-bordered table-sm dm-table">
            <thead>
              <tr>
                <th class="dm-sticky dm-col-no">No</th>
                @if($summary)<th class="dm-sticky dm-col-customer">Customer</th>@endif
                <th class="dm-sticky dm-col-article" @if($summary) style="left:265px" @endif>Article</th>
                <th>Remarks</th>
                @foreach($g['dates'] as $d)<th class="text-center">{{ $d }}</th>@endforeach
                <th class="text-center font-weight-bold">Total</th>
                <th class="text-center font-weight-bold">Total Konversi</th>
              </tr>
            </thead>
            <tbody>
              @foreach($g['articles'] as $rowKey => $articleName)
                @php([$rowCustomerId, $rowArticleCode] = $summary ? explode('|', $rowKey, 2) : [$kode, $rowKey])
                @foreach(['plan' => 'Plan', 'outstanding' => 'Outstanding', 'matrix' => 'Actual', 'balance' => 'Balance'] as $remarkKey => $remarkLabel)
                <tr @if($remarkKey == 'plan' && $loop->parent->index > 0) class="dm-article-divider" @endif>
                  @if($remarkKey == 'plan')
                  <td class="dm-sticky dm-col-no text-center" rowspan="4">{{ $loop->parent->iteration }}</td>
                  @if($summary)<td class="dm-sticky dm-col-customer" rowspan="4" title="{{ $g['rowCustomer'][$rowKey] ?? $rowCustomerId }}">{{ $g['rowCustomer'][$rowKey] ?? $rowCustomerId }}</td>@endif
                  <td class="dm-sticky dm-col-article" @if($summary) style="left:265px" @endif rowspan="4">{{ $articleName }}</td>
                  @endif
                  <td class="text-muted">{{ $remarkLabel }}</td>
                  @php($rowTotal = 0)
                  @foreach($g['dates'] as $d)
                    @php($plan = $g['plan'][$rowKey][$d] ?? 0)
                    @php($actual = $g['matrix'][$rowKey][$d] ?? 0)
                    @php($outstanding = $g['outstanding'][$rowKey][$d] ?? 0)
                    @php($balance = $actual - ($plan + $outstanding))
                    @if($remarkKey == 'plan') @php($val = $plan)
                    @elseif($remarkKey == 'matrix') @php($val = $actual)
                    @elseif($remarkKey == 'balance') @php($val = $balance)
                    @else @php($val = $outstanding) @endif
                    @php($rowTotal += $val)
                    {{-- Outstanding kurang kirim (>0) selalu tampil sampai lunas; outstanding
                    lebih kirim/lunas (<=0) cuma tampil kalau ada plan baru di tanggal itu. --}}
                    @php($hideOutstanding = $remarkKey == 'outstanding' && $outstanding <= 0 && !$plan)
                    <td class="text-center @if($remarkKey=='balance') @if($balance < 0) text-danger font-weight-bold @elseif($balance > 0) text-warning font-weight-bold @endif @elseif($remarkKey=='outstanding' && !$hideOutstanding) @if($outstanding > 0) text-danger font-weight-bold @elseif($outstanding < 0) text-warning font-weight-bold @endif @endif">
                      @if($hideOutstanding)
                        -
                      @elseif($remarkKey == 'balance' || $remarkKey == 'outstanding')
                        {{ $val == 0 ? '0' : ($val > 0 ? '+'.rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.') : rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.')) }}
                      @elseif($remarkKey == 'matrix' && $val)
                        <a href="#" class="btn-qty" data-customer="{{ $rowCustomerId }}" data-article="{{ $rowArticleCode }}" data-date="{{ $d }}" data-label="{{ $articleName }} - {{ $d }}">{{ rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.') }}</a>
                      @elseif($val)
                        {{ rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.') }}
                      @else - @endif
                    </td>
                  @endforeach
                  <td class="text-center font-weight-bold">{{ rtrim(rtrim(number_format($rowTotal, 2, '.', ''), '0'), '.') }}</td>
                  @if($remarkKey == 'plan')
                  <td class="text-center font-weight-bold" rowspan="4">{{ rtrim(rtrim(number_format($g['totalKonversi'][$rowKey] ?? 0, 8, '.', ''), '0'), '.') }}</td>
                  @endif
                </tr>
                @endforeach
              @endforeach
            </tbody>
            <tfoot>
              <tr class="font-weight-bold">
                <td class="dm-sticky dm-col-no"></td>
                @if($summary)<td class="dm-sticky dm-col-customer"></td>@endif
                <td class="dm-sticky dm-col-article text-right" @if($summary) style="left:265px" @endif>Total</td>
                <td></td>
                @php($grandTotal = 0)
                @foreach($g['dates'] as $d)
                  @php($colTotal = array_sum(array_column($g['matrix'], $d)))
                  @php($grandTotal += $colTotal)
                  <td class="text-center">{{ rtrim(rtrim(number_format($colTotal, 2, '.', ''), '0'), '.') }}</td>
                @endforeach
                <td class="text-center">{{ rtrim(rtrim(number_format($grandTotal, 2, '.', ''), '0'), '.') }}</td>
                <td class="text-center">{{ rtrim(rtrim(number_format(array_sum($g['totalKonversi'] ?? []), 8, '.', ''), '0'), '.') }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  @empty
    <div class="card"><div class="card-body text-center text-muted">Tidak ada delivery pada periode ini</div></div>
  @endforelse
</section>

<div class="modal fade" id="qtyModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="qtyModalTitle"></h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body table-responsive">
        <table class="table table-sm table-bordered">
          <thead><tr><th>No</th><th>Nomor</th><th>Tipe</th><th>Status</th><th>No SO</th><th class="text-center">Qty</th></tr></thead>
          <tbody id="qtyModalBody"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
@section('styles')
<style>
  .dm-table-scroll { max-height: 70vh; overflow: auto; position: relative; }
  .dm-table { margin-bottom: 0; }
  .dm-table thead th { position: sticky; top: 0; z-index: 3; background: #f3f3f3; }
  .dm-sticky { position: sticky; background: #fff; z-index: 1; }
  .dm-table thead th.dm-sticky { z-index: 4; }
  .dm-col-no { left: 0; min-width: 45px; }
  .dm-col-customer { left: 45px; min-width: 220px; max-width: 220px; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .dm-col-article { left: 45px; min-width: 240px; text-align: left; }
  .dm-table tbody tr:nth-child(odd) td { background-color: #fafafa; }
  .dm-table tbody tr:nth-child(even) td { background-color: #fff; }
  .dm-table tbody tr:hover td { background-color: #eaf3ff; }
  .dm-table tfoot td.dm-sticky { background: #f5f5f5; }
  .dm-table tbody tr.dm-article-divider td { border-top: 3px solid #999; }
</style>
@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js"></script>
<script type="text/javascript">
  (function () {
    new ApexCharts(document.querySelector('#deliveryHeatmap'), {
      chart: { type: 'heatmap', height: 320, toolbar: { show: false } },
      series: @json($heatmap),
      dataLabels: { enabled: false },
      plotOptions: {
        heatmap: {
          colorScale: {
            ranges: [
              { from: -99999999, to: -0.01, name: 'Kurang dari Plan', color: '#e05252' },
              { from: -0.009999, to: 0.009999, name: 'Sesuai Plan', color: '#2e8b45' },
              { from: 0.01, to: 99999999, name: 'Lebih dari Plan', color: '#eac54f' },
            ],
          },
        },
      },
      xaxis: { labels: { rotate: -45 } },
      tooltip: { y: { formatter: (v) => (v == null ? 'Tidak ada plan/delivery' : (v > 0 ? '+' : '') + v + ' qty vs plan') } },
    }).render();
  })();

  $('#customer').select2({placeholder: 'Semua Customer', allowClear: true});
  $('#dn_type').select2({placeholder: 'Semua Tipe DN', allowClear: true});
  $('#article').select2({placeholder: 'Semua Article', allowClear: true});
  $('input[name=viewToggle]').on('change', function () {
    $('#viewInput').val(this.value);
    $('#filterForm').submit();
  });

  const esc = s => $('<div>').text(s ?? '').html();
  $('.btn-qty').on('click', function (e) {
    e.preventDefault();
    const d = $(this).data();
    $('#qtyModalTitle').text(d.label);
    $('#qtyModalBody').html('<tr><td colspan="6">Loading...</td></tr>');
    $('#qtyModal').modal('show');
    $.get("{{ route('deliveryMonitoring.detail') }}", {
      customer: [d.customer],
      article: d.article, date: d.date, dn_type: {{ json_encode($dnType) }},
      from: '{{ $from }}', to: '{{ $to }}'
    }, function (rows) {
      $('#qtyModalBody').html(rows.length ? rows.map((r, i) => '<tr><td>' + (i + 1) + '</td><td><a href="' + esc(r.url) + '" target="_blank">' + esc(r.number) + '</a></td><td>'
        + esc(r.type) + '</td><td>' + esc(r.status) + '</td><td>' + esc(r.so_number) + '</td><td class="text-center">' + esc(r.qty) + '</td></tr>').join('')
        : '<tr><td colspan="6" class="text-center text-muted">Tidak ada data</td></tr>');
    });
  });
</script>
@endsection
