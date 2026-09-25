@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="cashbook-filter">
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
        <form onsubmit="return false" autocomplete="off">
          <div class="form-row">
            <div class="form-group col-md-3">
              <label for="seachVc">Voucher Number</label>
              <input type="text" class="form-control text-uppercase" id="seachVc">
            </div>
            <div class="form-group col-md-3">
              <label for="vcDate">Date</label>
              <input type="text" id="vcDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD">
            </div>
            <div class="form-group col-md-2">
              <label for="searchType">Tipe</label>
              <select class="select2 form-control" id="searchType">
                <option value="">All</option>
                @foreach($types as $code => $t)
                  <option value="{{ $code }}">{{ $t[0] }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-2">
              <label for="searchStatus">Status</label>
              <select class="select2 form-control" id="searchStatus">
                <option value="">All</option>
                @foreach($status as $index => $val)
                  <option value="{{ $index }}">{{ $val }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-2">
              <label for="period1">Period Awal</label>
              <select class="select2 form-control" id="period1">
                <option value=""></option>
                @for ($i = 1; $i <= 12; $i++)<option value="{{ $i }}">{{ $i }}</option>@endfor
              </select>
            </div>
            <div class="form-group col-md-2">
              <label for="period2">Period Akhir</label>
              <select class="select2 form-control" id="period2">
                <option value=""></option>
                @for ($i = 1; $i <= 12; $i++)<option value="{{ $i }}">{{ $i }}</option>@endfor
              </select>
            </div>
            <div class="form-group col-md-2">
              <label for="year">Year</label>
              <select class="select2 form-control" id="year">
                <option value=""></option>
                @for ($i = 2023; $i <= 2050; $i++)<option value="{{ $i }}">{{ $i }}</option>@endfor
              </select>
            </div>
          </div>
          <button type="button" class="btn btn-primary" id="btnSearch"><i class="fa fa-search"></i> Search</button>
          <button type="button" class="btn btn-info" data-toggle="modal" data-target="#createModal"><i class="fa fa-plus"></i> Create</button>
        </form>
      </div>
    </div>
  </div>
</section>

<section id="cashbook-table">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">{{ $title }} List</h4>
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
          <table id="detailedTable" class="table"><thead class="thead-light"></thead></table>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="createModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create {{ $title }}</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <p class="mb-2">Pilih jenis {{ $title }}:</p>
        <div class="row">
          @foreach($types as $code => $t)
            <div class="col-6">
              <a href="{{ route($t[1] . '.create') }}" class="btn btn-block btn-lg {{ substr($code, 1) == 'M' ? 'btn-outline-success' : 'btn-outline-danger' }}">
                {{ $title }} {{ $t[0] }}
              </a>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>

@include('partials.delete-modal')
@endsection
@section('scripts')
<script type="text/javascript">
  const currentDate = todayDate('dd-mm-yyyy');

  $(document).on('click', '#deleteButton', function (event) {
    event.preventDefault();
    $('#modalConfirmation').attr('action', $(this).data('href'));
  });

  $('.flatpickr-range').flatpickr({dateFormat: 'd-m-Y', mode: 'range'});

  function searchData() {
    if ($('#detailedTable tr').length > 0) {
      $('#detailedTable').DataTable().destroy();
      $('#detailedTable tbody > tr').remove();
      $('#detailedTable thead > tr').remove();
    }
    showDataTables({
      tableId: 'detailedTable',
      route: "{{ route('cashbook.list', ['group' => $group]) }}",
      kolom: {!! $kolom !!},
      arrColPrint: [1, 2, 3, 4, 6, 7, 8, 9, 10, 11, 12, 13],
      columnDefs: [
        {width: '5%', targets: 0},
        {targets: [8], render: $.fn.dataTable.render.number(',', '.', 2, ''), className: 'text-right'},
      ],
      excelCustomize: function (xlsx) {
        $('row:last c', xlsx.xl.worksheets['sheet1.xml']).attr('s', '50');
      },
      excelMessageBottom: function () { return 'Tanggal export : ' + currentDate },
      dataSearch: {
        seachVc: $('#seachVc').val(),
        vcDate: $('#vcDate').val(),
        searchType: $('#searchType').val(),
        searchStatus: $('#searchStatus').val(),
        period1: $('#period1').val(),
        period2: $('#period2').val(),
        year: $('#year').val()
      },
      orderColumn: [[13, 'desc']],
      excelFileName: '{{ $group }}'
    });
  }

  $('#btnSearch').click(searchData);
  searchData();
  $('a[data-action="reload"]').on('click', searchData);

  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}});
</script>
@endsection
