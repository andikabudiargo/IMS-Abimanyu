@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="article-index">
  <div class="card">
    <div class="card-header">  
      <h4 class="card-title">Filter <small class="text-muted"> {{ $lockDate ? "Locked From : ".$lockDate : '' }}</small></h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0">
            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <form class="needs-validation" novalidate>
            <div class="form-row">
              <div class="form-group col-md-3"> 
                <label for="seachVc">Voucher Number</label>
                <input type="text" class="form-control text-uppercase" id="seachVc" name="seachVc" placeholder=""  />
              </div>
              <div class="col-md-3 form-group">
                <label class="form-label" for="vcDate">Date</label>
                <input type="text" id="vcDate" name="vcDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-1">
                <label class="form-label" for="period1">Period Awal</label>
                <select class="select2 form-control" id="period1" name="period1" >
                    <option value=""></option>
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
              </div>
              <div class="form-group col-md-1">
                <label class="form-label" for="period2">Period Akahir</label>
                <select class="select2 form-control" id="period2" name="period2" >
                    <option value=""></option>
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
              </div>
              <div class="form-group col-md-1">
                <label class="form-label" for="year">Year</label>
                <select class="select2 form-control" id="year" name="year" >
                  <option value=""></option>
                  @for ($i = 2023; $i <= 2050 ; $i++)
                      <option value="{{ $i }}">{{ $i }}</option>
                  @endfor
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchStatus">Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                    <option value="">All</option>
                    @foreach($status as $index=>$val)
                        <option value="{{ $index }}">{{ $val }}</option>
                    @endforeach
                </select>
              </div>
              <div class="form-group col-md-6">
                <label for="paidTo">Bayar Ke*</label>
                <select class="select2 form-control" id="paidTo" name="paidTo" required>
                    <option value=""></option>
                    @foreach ($suppliers as $val)
                        <option value="{{ $val->kode }}" data-coa="{{ $val->account }}">{{ $val->kode }} | {{ $val->nama }}</option>
                    @endforeach
                    <option value="other">Other</option>
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    {{-- @can('pettyCash-create') --}}
                    <a href="{{ route('bankKeluar.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                    {{-- @endcan --}}
                </div>
            </div>
        </form>
      </div>
    </div>
  </div>
</section>

<section id="bk-analytics">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Analytics / Overview</h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0">
            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="card-content collapse" id="bkAnalyticsBody">
      <div class="card-body">
        <div class="row match-height mb-2">
          <div class="col-md-3 col-sm-6">
            <div class="card border shadow-none h-100 mb-0">
              <div class="card-body">
                <h6 class="text-muted mb-1">Outstanding</h6>
                <h3 class="mb-0" id="anlOutstandingAmount">Rp 0</h3>
                <small class="text-muted" id="anlOutstandingCount">0 dari 0 invoice belum dibayar (Hutang)</small>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="card border shadow-none h-100 mb-0">
              <div class="card-body">
                <h6 class="text-muted mb-1">Total Payment</h6>
                <h3 class="mb-0" id="anlPaidAmount">Rp 0</h3>
                <small class="text-muted" id="anlPaidCount">0 invoice terbayar via BK ini</small>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card border shadow-none h-100 mb-0">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <h6 class="text-muted mb-0">Trend Bank Keluar</h6>
                  <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary active" id="btnViewMonthly">Bulanan</button>
                    <button type="button" class="btn btn-outline-secondary" id="btnViewYearly">Tahunan</button>
                  </div>
                </div>
                <div id="chartSpendTrend"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-5">
            <div class="card border shadow-none mb-0">
              <div class="card-body">
                <h6 class="text-muted mb-1">Distribusi Cost Center</h6>
                <div id="chartCostCenter"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="table-article">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title"> @yield('title') List</h4>
      <div class="heading-elements">
          <ul class="list-inline mb-0">
              <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
              <li><a data-action="reload"><i data-feather="rotate-cw"></i></a></li>
          </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <div class="row">
            <div class="col-sm-12">
              <div class="card-datatable table-responsive pt-0">
                <table id="detailedTable" class="table">
                  <thead class="thead-light">
                  </thead>
                </table>
              </div>
            </div>
        </div>  
      </div>
    </div>
  </div>
</section>

@include('partials.delete-modal')

@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js"></script>
<script type="text/javascript">
  let currentDate = todayDate('dd-mm-yyyy');   
  $(document).ready(function(){    
    let href;
    $(document).on('click', '#deleteButton', function(event) {
        event.preventDefault();
        href = $(this).data('href');
        $('#modalConfirmation').attr("action", href);
    });
  });

  const rangePickr = $('.flatpickr-range');
  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range'
    });
  }

  function searchdData(){
    let seachVc = $("#seachVc").val();
    let vcDate = $("#vcDate").val();
    let period1 = $("#period1").val();
    let period2 = $("#period2").val();
    let year = $("#year").val();
    let searchStatus = $("#searchStatus").val();
    let searchPaidTo = $("#paidTo").val();
    showList(seachVc,vcDate,year,searchStatus,searchPaidTo,period1,period2);
  }

  //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
    searchdData();
  });

  $("#btnSearch").click(function(e){
    searchdData();
  });

  const showList = (seachVc,vcDate,year,searchStatus,searchPaidTo,period1,period2) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('bankKeluar.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,4,5,6,7,8,9,10,11,12],
      columnDefs :[
        { width: '5%', targets: 0 },
        {
          targets: [ 6 ],
          render: $.fn.dataTable.render.number(',', '.',2, ''),
          className: "text-right"
        },
      ],
      excelCustomize:function(xlsx) {
        let sheet = xlsx.xl.worksheets['sheet1.xml'];
        $('row:last c', sheet).attr('s','50');
      },
      excelMessageBottom:function () { return "Tanggal export : "+currentDate },
      dataSearch:  {
        seachVc:seachVc,
        vcDate:vcDate,
        period1:period1,
        period2:period2,
        year:year,
        searchStatus:searchStatus,
        searchPaidTo:searchPaidTo
      },
      orderColumn:[[ 12, 'desc' ]],
      excelFileName:'bank_pembayaran'
    });
  }

  // ==== ANALYTICS ====
let bkTrendChart = null;
let bkCostCenterChart = null;
let bkAnalyticsLoaded = false;
let bkCurrentView = 'monthly';

function bkCurrentFilters(){
  return {
    seachVc: $('#seachVc').val(),
    vcDate: $('#vcDate').val(),
    period1: $('#period1').val(),
    period2: $('#period2').val(),
    year: $('#year').val(),
    searchStatus: $('#searchStatus').val(),
    searchPaidTo: $('#paidTo').val(),
    view: bkCurrentView
  };
}

function formatRupiah(num){
  return 'Rp ' + Number(num || 0).toLocaleString('id-ID', {maximumFractionDigits:0});
}

function loadAnalyticsSummary(){
  $.get("{{ route('bankKeluar.analytics.summary') }}", bkCurrentFilters(), function(res){
    $('#anlOutstandingAmount').text(formatRupiah(res.outstanding_amount));
    $('#anlOutstandingCount').text(res.outstanding_count + ' dari ' + res.total_invoice + ' invoice belum dibayar');
    $('#anlPaidAmount').text(formatRupiah(res.paid_amount));
    $('#anlPaidCount').text(res.paid_count + ' invoice terbayar via BK ini');
  });
}

function loadAnalyticsChart(){
  $.get("{{ route('bankKeluar.analytics.chart') }}", bkCurrentFilters(), function(res){
    const options = {
      chart: { type: 'bar', height: 260, toolbar: { show: false } },
      series: [{ name: 'Total Bayar', data: res.data }],
      xaxis: { categories: res.labels },
      colors: ['#5e5873'],
      plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
      dataLabels: { enabled: false },
      grid: { strokeDashArray: 4 },
      yaxis: { labels: { formatter: (v) => formatRupiah(v) } },
      tooltip: { y: { formatter: (v) => formatRupiah(v) } }
    };

    if (bkTrendChart) {
      bkTrendChart.updateOptions(options);
    } else {
      bkTrendChart = new ApexCharts(document.querySelector("#chartSpendTrend"), options);
      bkTrendChart.render();
    }
  });
}

function loadAnalyticsCostCenter(){
  $.get("{{ route('bankKeluar.analytics.costCenter') }}", bkCurrentFilters(), function(res){
    const options = {
      chart: { type: 'donut', height: 280 },
      series: res.data,
      labels: res.labels,
      colors: ['#5e5873','#82868b','#a8aaae','#babfc7','#d8d6de','#6e6b7b'],
      legend: { position: 'bottom' },
      dataLabels: { enabled: false },
      tooltip: { y: { formatter: (v) => formatRupiah(v) } }
    };

    if (bkCostCenterChart) {
      bkCostCenterChart.updateOptions(options);
    } else {
      bkCostCenterChart = new ApexCharts(document.querySelector("#chartCostCenter"), options);
      bkCostCenterChart.render();
    }
  });
}

function loadAllAnalytics(){
  loadAnalyticsSummary();
  loadAnalyticsChart();
  loadAnalyticsCostCenter();
}

// load pertama kali saat accordion Analytics dibuka
$('#bk-analytics [data-action="collapse"]').on('click', function(){
  if (!bkAnalyticsLoaded) {
    bkAnalyticsLoaded = true;
    setTimeout(loadAllAnalytics, 300);
  }
});

$('#btnViewMonthly').on('click', function(){
  bkCurrentView = 'monthly';
  $(this).addClass('active');
  $('#btnViewYearly').removeClass('active');
  loadAnalyticsChart();
});

$('#btnViewYearly').on('click', function(){
  bkCurrentView = 'yearly';
  $(this).addClass('active');
  $('#btnViewMonthly').removeClass('active');
  loadAnalyticsChart();
});

// ikut refresh saat tombol Search filter utama dipencet
$("#btnSearch").click(function(){
  if (bkAnalyticsLoaded) loadAllAnalytics();
});

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
