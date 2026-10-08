@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

<section id="bg-filter">
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
              <label for="searchNumber">Budgeting Number</label>
              <input type="text" class="form-control text-uppercase" id="searchNumber" name="searchNumber" />
            </div>
            <div class="form-group col-md-3">
              <label for="searchDept">Department</label>
              <select class="select2 form-control" id="searchDept" name="searchDept" data-placeholder="Semua department">
                <option value=""></option>
                @foreach($depts as $val)
                  <option value="{{ $val->code }}">{{ $val->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-3">
              <label for="searchStatus">Status</label>
              <select class="select2 form-control" id="searchStatus" name="searchStatus" data-placeholder="Semua status">
                <option value=""></option>
                <option value="Overbudget">Overbudget</option>
                <option value="Underbudget">Underbudget</option>
                <option value="Sesuai Budget">Sesuai Budget</option>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label for="searchFiscalYear">Fiscal Year</label>
              <select class="select2 form-control" id="searchFiscalYear" name="searchFiscalYear" data-placeholder="Semua fiscal year">
                <option value=""></option>
                @foreach($fiscalYears as $fy)
                  <option value="{{ $fy }}">{{ $fy }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="col-12">
              <button type="button" class="btn btn-primary" id="btnSearch">Search</button>
              <a href="{{ route('budgeting.create') }}" class="btn btn-info">
                <i data-feather="plus"></i> Add Budgeting
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<section id="bg-dashboard">
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
        <div class="row mb-1">
          <div class="col-sm-6 col-xl-3">
            <div class="card border shadow-none mb-1">
              <div class="card-body d-flex align-items-center p-1">
                <div class="avatar bg-light-primary p-50 mr-1" style="border-radius:8px;">
                  <i data-feather="archive" class="font-medium-3 text-primary"></i>
                </div>
                <div>
                  <h4 class="mb-0 font-weight-bolder" id="dashTotal">0</h4>
                  <small class="text-muted">Total Budgeting</small>
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-3">
            <div class="card border shadow-none mb-1">
              <div class="card-body d-flex align-items-center p-1">
                <div class="avatar bg-light-dark p-50 mr-1" style="border-radius:8px;">
                  <i data-feather="target" class="font-medium-3 text-dark"></i>
                </div>
                <div>
                  <h4 class="mb-0 font-weight-bolder" id="dashBudget">0</h4>
                  <small class="text-muted">Total Budget</small>
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-3">
            <div class="card border shadow-none mb-1">
              <div class="card-body d-flex align-items-center p-1">
                <div class="avatar bg-light-info p-50 mr-1" style="border-radius:8px;">
                  <i data-feather="credit-card" class="font-medium-3 text-info"></i>
                </div>
                <div>
                  <h4 class="mb-0 font-weight-bolder" id="dashActual">0</h4>
                  <small class="text-muted">Total Actual</small>
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-3">
            <div class="card border shadow-none mb-1" id="dashOverCard" style="border-color:#ea545533 !important;background:#ea54550d;">
              <div class="card-body d-flex align-items-center p-1">
                <div class="avatar bg-light-danger p-50 mr-1" style="border-radius:8px;">
                  <i data-feather="alert-triangle" class="font-medium-3 text-danger"></i>
                </div>
                <div>
                  <h4 class="mb-0 font-weight-bolder text-danger" id="dashOver">0</h4>
                  <small class="text-muted">Overbudget</small>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-8">
            <h6 class="mb-1">Budget vs Actual per Department</h6>
            <div id="chartBudgetDept"></div>
          </div>
          <div class="col-lg-4">
            <h6 class="mb-1">Sebaran Status</h6>
            <div id="chartStatus"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="table-bg">
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
          <table id="bgListTable" class="table">
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

  const nf = (v) => new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v || 0);

  const currentFilters = () => ({
    number: document.querySelector('#searchNumber').value,
    dept: document.querySelector('#searchDept').value,
    status: document.querySelector('#searchStatus').value,
    fiscalYear: document.querySelector('#searchFiscalYear').value,
  });

  let chartBudgetDept = null, chartStatus = null;
  const loadDashboard = () => {
    $.get("{{ route('budgeting.chart') }}", currentFilters(), function (res) {
      $('#dashTotal').text(res.summary.total);
      $('#dashBudget').text(nf(res.summary.totalBudget));
      $('#dashActual').text(nf(res.summary.totalActual));
      $('#dashOver').text(res.summary.overCount);

      const barOptions = {
        chart: { type: 'bar', height: 360, toolbar: { show: false }, stacked: false },
        plotOptions: { bar: { horizontal: true, barHeight: '55%', borderRadius: 4 } },
        series: [
          { name: 'Total Budget', data: res.budget },
          { name: 'Actual', data: res.actual }
        ],
        xaxis: {
          categories: res.depts,
          labels: { formatter: (val) => nf(val) }
        },
        colors: ['#7367F0', '#28C76F'],
        dataLabels: { enabled: false },
        legend: { position: 'top' },
        grid: { borderColor: '#ebe9f1' },
        tooltip: { y: { formatter: (val) => nf(val) } }
      };
      if (chartBudgetDept) { chartBudgetDept.updateOptions(barOptions); }
      else { chartBudgetDept = new ApexCharts(document.querySelector('#chartBudgetDept'), barOptions); chartBudgetDept.render(); }

      const donutOptions = {
        chart: { type: 'donut', height: 360 },
        series: res.statusCounts,
        labels: res.statusLabels,
        colors: ['#EA5455', '#28C76F', '#82868B'],
        legend: { position: 'bottom' },
        dataLabels: { enabled: true, formatter: (val, opts) => opts.w.config.series[opts.seriesIndex] },
        plotOptions: { pie: { donut: { labels: { show: true, total: { show: true, label: 'Total', formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0) } } } } }
      };
      if (chartStatus) { chartStatus.updateOptions(donutOptions); }
      else { chartStatus = new ApexCharts(document.querySelector('#chartStatus'), donutOptions); chartStatus.render(); }

      if (window.feather) feather.replace({ width: 14, height: 14 });
    });
  };

  // Chart hanya dirender begitu accordion-nya dibuka pertama kali -- container-nya
  // di-collapse default, dan ApexCharts butuh elemen yang sudah visible supaya tidak salah render 0px.
  let dashboardInitialized = false;
  $('#bg-dashboard a[data-action="collapse"]').closest('.card').find('.card-content').on('shown.bs.collapse', function () {
    if (!dashboardInitialized) { dashboardInitialized = true; loadDashboard(); }
  });

  let refresh = document.querySelector('a[data-action="reload"]');

  const loadTable = () => {
    if ($('#bgListTable tr').length > 0) {
      let table = $('#bgListTable').DataTable();
      table.destroy();
      $('#bgListTable tbody > tr').remove();
      $('#bgListTable thead > tr').remove();
    }
    showDataTables({
      tableId: "bgListTable",
      route: "{{ route('budgeting.list') }}",
      kolom: {!! $kolom !!},
      arrColPrint: [1, 2, 3, 4, 5, 6, 8, 9, 10, 11, 12, 13, 14],
      columnDefs: [{ width: '5%', targets: 0 }],
      dataSearch: currentFilters()
    });
  }

  $("#btnSearch").click(function () {
    loadTable();
    if (dashboardInitialized) loadDashboard();
  });
  refresh.addEventListener("click", function () {
    loadTable();
    if (dashboardInitialized) loadDashboard();
  });

  function deleteBudgeting(id, number) {
    Swal.fire({
      title: 'Are you sure?',
      html: `Delete <b>${number}</b>? This action can not be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#EA5455',
      reverseButtons: true,
    }).then((result) => {
      if (!result.isConfirmed) return;
      $.ajax({
        url: '{{ url("budgeting") }}/' + id,
        type: 'DELETE'
      }).done(function (res) {
        Swal.fire('Deleted!', res.message || 'Berhasil dihapus.', 'success').then(() => {
          loadTable();
          if (dashboardInitialized) loadDashboard();
        });
      }).fail(function (xhr) {
        Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus.', 'error');
      });
    });
  }

  $(function () { loadTable(); });
</script>
@endsection
