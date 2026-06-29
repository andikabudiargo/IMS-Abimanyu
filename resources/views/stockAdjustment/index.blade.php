@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="adjustment-filter">
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
            <div class="form-group col-md-4">
              <label for="searchAdj">Adjustment Code</label>
              <input type="text" class="form-control text-uppercase" id="searchAdj" name="searchAdj" placeholder="" />
            </div>
            <div class="col-md-4 form-group">
              <label for="adjDate">Adjustment Date</label>
              <input type="text" id="adjDate" name="adjDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
            </div>
            <div class="form-group col-md-4">
              <label class="form-label" for="searchStatus">Status</label>
              <select class="select2 form-control" id="searchStatus" name="searchStatus">
                <option value="">All</option>
                @foreach($status as $index => $val)
                  <option value="{{ $index }}">{{ $index }} - {{ $val }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4">
              <label class="form-label" for="searchType">Adjustment Type</label>
              <select class="select2 form-control" id="searchType" name="searchType">
                <option value="">All</option>
                @foreach($types as $val)
                  <option value="{{ $val }}">{{ $val }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-4">
              <label class="form-label" for="searchLocation">Location</label>
              <select class="select2 form-control" id="searchLocation" name="searchLocation">
                <option value="">All</option>
                @foreach($locations as $val)
                  <option value="{{ $val->location_code }}">{{ $val->location_name }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-4">
              <label for="searchDesc">Description</label>
              <input type="text" class="form-control" id="searchDesc" name="searchDesc" placeholder="" />
            </div>
          </div>
          <div class="form-row">
            <div class="col-12">
              <button type="button" class="btn btn-primary" id="btnSearch" name="btnSearch">Search</button>
              {{-- @can('stockAdjustment-create') --}}
              <a href="{{ route('stockAdjustment.create') }}" class="btn btn-info">
                <i class="fa fa-plus"></i> Create
              </a>
              {{-- @endcan --}}
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<section id="table-adjustment">
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
        <button type="button" class="btn btn-primary d-none" id="btnDetail" name="btnDetail"
          data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data detail">
          Detail
        </button>
        <button type="button" class="btn btn-primary d-none" id="btnSummary" name="btnSummary"
          data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data summary">
          Summary
        </button>
        <div class="row">
          <div class="col-sm-12">
            <div class="card-datatable table-responsive pt-0">
              <table id="adjustmentTable" class="table">
                <thead class="thead-light"></thead>
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
<script type="text/javascript">

  let searchAdj      = document.querySelector('#searchAdj');
  let searchType     = document.querySelector('#searchType');
  let searchStatus   = document.querySelector('#searchStatus');
  let searchLocation = document.querySelector('#searchLocation');
  let searchDesc     = document.querySelector('#searchDesc');
  let adjDate        = document.querySelector('#adjDate');
  let search         = document.querySelector('#btnSearch');
  let refresh        = document.querySelector('a[data-action="reload"]');
  let rangePickr     = document.querySelector('.flatpickr-range');
  let btnSummary     = $('#btnSummary');
  let btnDetail      = $('#btnDetail');

  initDatePicker(rangePickr, {
    minDate: "01/01/2010",
    maxDate: "31/12/2030",
    dateFormat: "d-m-Y",
    mode: "range"
  });

  function dataSearch($type) {
    btnSummary.addClass('d-none');
    btnDetail.addClass('d-none');
    $(".loading-spinner-container").addClass("-show");

    if ($type == 'detail') {
      showListDetail(
        searchAdj.value, searchType.value, searchStatus.value,
        adjDate.value, searchLocation.value, searchDesc.value
      );
    }

    if ($type == 'summary') {
      showList(
        searchAdj.value, searchType.value, searchStatus.value,
        adjDate.value, searchLocation.value, searchDesc.value
      );
    }
  }

  refresh.addEventListener("click", function () {
    dataSearch('summary');
  });

  btnDetail.click(function () {
    dataSearch('detail');
  });

  btnSummary.click(function () {
    dataSearch('summary');
  });

  $("#btnSearch").click(function (e) {
    btnSummary.addClass('d-none');
    btnDetail.addClass('d-none');
    dataSearch('summary');
  });

  // ── SUMMARY TABLE ──────────────────────────────────────────────
  const showList = (searchAdj, searchType, searchStatus, adjDate, searchLocation, searchDesc) => {
    if ($('#adjustmentTable tr').length > 0) {
      let table = $('#adjustmentTable').DataTable();
      table.destroy();
      $('#adjustmentTable tbody > tr').remove();
      $('#adjustmentTable thead > tr').remove();
    }
    showDataTables({
      tableId: "adjustmentTable",
      route: "{{ route('stockAdjustment.list') }}",
      kolom: {!! $kolom !!},
      arrColPrint: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
      columnDefs: [
        { width: '5%', targets: 0 },
      ],
      dataSearch: {
        searchAdj:      searchAdj,
        searchType:     searchType,
        searchStatus:   searchStatus,
        adjDate:        adjDate,
        searchLocation: searchLocation,
        searchDesc:     searchDesc,
      },
      initComplete: function () {
        let api = this.api();
        if (api.data().length > 0) {
          btnDetail.removeClass('d-none');
          btnSummary.addClass('d-none');
        }
        $(".loading-spinner-container").removeClass("-show");
      },
      orderColumn: [[1, 'desc']],
      excelFileName: 'stock_adjustment'
    });
  };

  // ── DETAIL TABLE ───────────────────────────────────────────────
  const showListDetail = (searchAdj, searchType, searchStatus, adjDate, searchLocation, searchDesc) => {
    if ($('#adjustmentTable tr').length > 0) {
      let table = $('#adjustmentTable').DataTable();
      table.destroy();
      $('#adjustmentTable tbody > tr').remove();
      $('#adjustmentTable thead > tr').remove();
    }
    showDataTables({
      tableId: "adjustmentTable",
      route: "{{ route('stockAdjustment.list.detail') }}",
      kolom: {!! $kolomDetail !!},
      arrColPrint: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16],
      columnDefs: [
        { width: '5%', targets: 0 },
        { className: 'text-right', targets: [8, 9, 10, 11] },
      ],
      dataSearch: {
        searchAdj:      searchAdj,
        searchType:     searchType,
        searchStatus:   searchStatus,
        adjDate:        adjDate,
        searchLocation: searchLocation,
        searchDesc:     searchDesc,
      },
      initComplete: function () {
        let api = this.api();
        if (api.data().length > 0) {
          btnSummary.removeClass('d-none');
          btnDetail.addClass('d-none');
        }
        $(".loading-spinner-container").removeClass("-show");
      },
      orderColumn: [[0, 'asc'], [1, 'asc']],
      excelFileName: 'stock_adjustment_detail'
    });
  };

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // ── CANCEL MODAL HANDLER (pola sama seperti receiving) ─────────
  // Tombol Cancel di datatable hanya set action form, reason diisi user lalu submit form biasa.
  $(document).on('click', '#cancelReasonButton', function (event) {
    event.preventDefault();
    var href = $(this).data('href');
    $('#modalReasonCancel').attr('action', href);
    $('#cancelReasonText').val(''); // reset reason tiap buka
  });

</script>
@endsection