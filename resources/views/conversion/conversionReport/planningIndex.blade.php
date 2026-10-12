@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="cvr-filter">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Filter</h4>
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
              <label for="searchCode">Report Number</label>
              <input type="text" class="form-control text-uppercase" id="searchCode" name="searchCode" />
            </div>
            <div class="form-group col-md-3">
              <label for="searchName">Name</label>
              <input type="text" class="form-control" id="searchName" name="searchName" />
            </div>
            <div class="form-group col-md-2">
              <label for="searchYear">Tahun</label>
              <input type="number" class="form-control" id="searchYear" name="searchYear" min="2000" max="2100" />
            </div>
          </div>
          <div class="form-row">
            <div class="col-12">
              <button type="button" class="btn btn-primary" id="btnSearch">Search</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<section id="cvr-chart">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Management Dashboard</h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0">
          <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="card-content collapse">
      <div class="card-body">
        <div class="form-row mb-1">
          <div class="form-group col-md-2">
            <label for="chartYear">Tahun</label>
            <select class="form-control" id="chartYear">
              @php $currentYear = (int) date('Y'); @endphp
              @for($y = $currentYear; $y >= 2024; $y--)
                <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}</option>
              @endfor
            </select>
          </div>
        </div>
        <div class="row">
          <div class="col-md-9">
            <div id="chartConversionCurve"></div>
          </div>
          <div class="col-md-3 d-flex flex-column">
            <div class="card bg-light-primary mb-1 flex-fill">
              <div class="card-body text-center">
                <h6 class="text-muted mb-1">Total Article</h6>
                <h3 class="mb-0" id="cardTotalArticle">0</h3>
              </div>
            </div>
            <div class="card bg-light-info mb-1 flex-fill">
              <div class="card-body text-center">
                <h6 class="text-muted mb-1">Total Delivery</h6>
                <h3 class="mb-0" id="cardTotalDelivery">0</h3>
              </div>
            </div>
            <div class="card bg-light-success flex-fill">
              <div class="card-body text-center">
                <h6 class="text-muted mb-1">Total Konversi</h6>
                <h3 class="mb-0" id="cardTotalConversion">0</h3>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="table-cvr">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">@yield('title') List</h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0">
          <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
          <li><a data-action="reload"><i data-feather="rotate-cw"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <div class="card-datatable table-responsive pt-0">
          <table id="cvrTable" class="table">
            <thead class="thead-light"></thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js"></script>
<script type="text/javascript">
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  let chartConversionCurve = null;
  const loadConversionChart = (tahun) => {
    $.get("{{ route('conversionPlanning.chart') }}", { tahun: tahun }, function (res) {
      $('#cardTotalArticle').text(res.sumArticle);
      $('#cardTotalDelivery').text(res.sumDelivery);
      $('#cardTotalConversion').text(new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(res.sumConversion));

      const options = {
        chart: { type: 'line', height: 320, toolbar: { show: false } },
        series: [
          { name: 'Actual Konversi', data: res.totalConversion },
          { name: 'Target Konversi', data: res.targetConversion }
        ],
        xaxis: { categories: res.labels },
        stroke: { curve: 'smooth', width: 3 },
        markers: { size: 4 },
        dataLabels: {
          enabled: true,
          style: { fontSize: '10px' },
          offsetY: -6,
          formatter: (val) => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(val)
        },
        colors: ['#7367F0', '#FF9F43'],
        tooltip: {
          y: { formatter: (val) => new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2 }).format(val) },
          custom: ({ series, seriesIndex, dataPointIndex, w }) => {
            const fmt = (v) => new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2 }).format(v);
            const qtyDelivery = res.totalQtyDelivery[dataPointIndex];
            const qtyTarget = res.totalQtyTarget[dataPointIndex];
            let rows = w.config.series.map((s, i) =>
              `<div class="apexcharts-tooltip-series-group apexcharts-active" style="display:flex">
                 <span class="apexcharts-tooltip-marker" style="background-color:${w.config.colors[i]}"></span>
                 <span>${s.name}: <b>${fmt(series[i][dataPointIndex])}</b></span>
               </div>`
            ).join('');
            return `<div class="apexcharts-tooltip-title">${w.globals.labels[dataPointIndex]}</div>
                    <div class="apexcharts-tooltip-body" style="padding:6px 10px">
                      ${rows}
                      <div>Total Qty Delivery: <b>${fmt(qtyDelivery)}</b></div>
                      <div>Total Qty Target: <b>${fmt(qtyTarget)}</b></div>
                    </div>`;
          }
        }
      };

      if (chartConversionCurve) {
        chartConversionCurve.updateOptions(options);
      } else {
        chartConversionCurve = new ApexCharts(document.querySelector('#chartConversionCurve'), options);
        chartConversionCurve.render();
      }
    });
  };

  $('#chartYear').on('change', function () {
    loadConversionChart($(this).val());
  });

  let chartInitialized = false;
  $('#cvr-chart a[data-action="collapse"]').closest('.card').find('.card-content').on('shown.bs.collapse', function () {
    if (!chartInitialized) {
      chartInitialized = true;
      loadConversionChart($('#chartYear').val());
    }
  });

  let searchCode = document.querySelector('#searchCode');
  let searchName = document.querySelector('#searchName');
  let searchYear = document.querySelector('#searchYear');
  let refresh    = document.querySelector('a[data-action="reload"]');

  const loadTable = () => {
    if ($('#cvrTable tr').length > 0) {
      let table = $('#cvrTable').DataTable();
      table.destroy();
      $('#cvrTable tbody > tr').remove();
      $('#cvrTable thead > tr').remove();
    }
    showDataTables({
      tableId: "cvrTable",
      route: "{{ route('conversionPlanning.list') }}",
      kolom: {!! $kolom !!},
      arrColPrint: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13],
      columnDefs: [{ width: '5%', targets: 0 }],
      dataSearch: {
        reportCode: searchCode.value,
        reportName: searchName.value,
        tahun: searchYear.value,
      }
    });
  }

  $("#btnSearch").click(function () { loadTable(); });
  refresh.addEventListener("click", function () { loadTable(); });

  $(function () { loadTable(); });
</script>
@endsection
