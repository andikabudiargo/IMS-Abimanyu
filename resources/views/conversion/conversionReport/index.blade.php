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
              <a href="{{ route('conversionReport.create') }}" class="btn btn-info">
                <i class="fa fa-plus"></i> Create
              </a>
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
      <h4 class="card-title">Kurva Konversi per Bulan</h4>
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

@include('partials.delete-modal')
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js"></script>
<script type="text/javascript">
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  let chartConversionCurve = null;
  const loadConversionChart = (tahun) => {
    $.get("{{ route('conversionReport.chart') }}", { tahun: tahun }, function (res) {
      $('#cardTotalArticle').text(res.sumArticle);
      $('#cardTotalDelivery').text(res.sumDelivery);
      $('#cardTotalConversion').text(new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(res.sumConversion));

      const options = {
        chart: { type: 'line', height: 320, toolbar: { show: false } },
        series: [{ name: 'Total Konversi', data: res.totalConversion }],
        xaxis: { categories: res.labels },
        stroke: { curve: 'smooth', width: 3 },
        markers: { size: 4 },
        dataLabels: { enabled: false },
        colors: ['#7367F0'],
        tooltip: {
          y: { formatter: (val) => new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2 }).format(val) }
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

  // Chart hanya dirender begitu accordion-nya dibuka pertama kali --
  // container-nya di-collapse default, dan ApexCharts butuh elemen yang
  // sudah punya lebar (visible) supaya tidak salah render 0px.
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
      route: "{{ route('conversionReport.list') }}",
      kolom: {!! $kolom !!},
      arrColPrint: [1, 2, 3, 4, 5, 6, 7, 8, 9],
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

  function deleteReport(id, code) {
    Swal.fire({
      title: 'Are you sure?',
      html: `Delete <b>${code}</b>? This action can not be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#EA5455',
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        $('<form>', { action: "{{ route('conversionReport.destroy') }}", method: 'POST' })
          .append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }))
          .append($('<input>', { type: 'hidden', name: 'id', value: id }))
          .appendTo('body')
          .submit();
      }
    });
  }

  function cancelReport(id, code) {
    Swal.fire({
      title: 'Cancel this report?',
      html: `Cancel <b>${code}</b>?`,
      input: 'textarea',
      inputPlaceholder: 'Alasan cancel...',
      inputAttributes: { required: true },
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, cancel it',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#EA5455',
      reverseButtons: true,
      focusConfirm: false,
      preConfirm: (reason) => {
        if (!reason || !reason.trim()) {
          Swal.showValidationMessage('Alasan cancel wajib diisi');
          return false;
        }
        return reason;
      }
    }).then((result) => {
      if (result.isConfirmed) {
        $('<form>', { action: "{{ route('conversionReport.cancel') }}", method: 'POST' })
          .append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }))
          .append($('<input>', { type: 'hidden', name: 'id', value: id }))
          .append($('<input>', { type: 'hidden', name: 'reason', value: result.value }))
          .appendTo('body')
          .submit();
      }
    });
  }

  $(function () { loadTable(); });
</script>
@endsection
