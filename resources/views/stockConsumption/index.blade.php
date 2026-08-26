@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="filter">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Filter</h4>
      <div class="heading-elements"><ul class="list-inline mb-0"><li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li></ul></div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <form class="needs-validation" novalidate>
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="searchNo">Number</label>
              <input type="text" class="form-control text-uppercase" id="searchNo" name="searchNo" />
            </div>
            <div class="form-group col-md-4">
              <label for="scDate">Date</label>
              <input type="text" id="scDate" name="scDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
            </div>
            <div class="form-group col-md-4">
              <label for="searchStatus">Status</label>
              <select class="select2 form-control" id="searchStatus" name="searchStatus">
                <option value="">All</option>
                @foreach($status as $i=>$val)<option value="{{ $i }}">{{ $i }} - {{ $val }}</option>@endforeach
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="searchLoc">Location</label>
              <select class="select2 form-control" id="searchLoc" name="searchLoc">
                <option value="">All</option>
                @foreach($locations as $val)<option value="{{ $val->location_code }}">{{ $val->location_name }}</option>@endforeach
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="col-12">
              <button type="button" class="btn btn-primary" id="btnSearch">Search</button>
              <a href="{{ route('stockConsumption.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<section id="table">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">@yield('title') List</h4>
      <div class="heading-elements"><ul class="list-inline mb-0">
        <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
        <li><a data-action="reload"><i data-feather="rotate-cw"></i></a></li>
      </ul></div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <div class="row"><div class="col-sm-12">
          <div class="card-datatable table-responsive pt-0">
            <table id="mainTable" class="table"><thead class="thead-light"></thead></table>
          </div>
        </div></div>
      </div>
    </div>
  </div>
</section>
@include('partials.delete-modal')
@endsection

@section('scripts')
<script type="text/javascript">
  let searchNo     = document.querySelector("#searchNo");
  let searchStatus = document.querySelector("#searchStatus");
  let searchLoc    = document.querySelector("#searchLoc");
  let scDate       = document.querySelector("#scDate");

  initDatePicker(document.querySelector('.flatpickr-range'), {
    minDate:"01/01/2010", maxDate:"31/12/2030", dateFormat:"d-m-Y", mode:"range"
  });

  const showList = () => {
    if ($('#mainTable tr').length > 0){
      let t = $('#mainTable').DataTable(); t.destroy();
      $('#mainTable tbody > tr').remove(); $("#mainTable thead > tr").remove();
    }
    showDataTables({
      tableId:"mainTable",
      route:"{{ route('stockConsumption.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7,8],
      columnDefs:[{ width:'5%', targets:0 }],
      dataSearch:{
        searchNo:searchNo.value, searchStatus:searchStatus.value,
        searchLoc:searchLoc.value, scDate:scDate.value
      },
      orderColumn:[[1,'desc']],
      excelFileName:'stock_consumption'
    });
  }

  $("#btnSearch").click(showList);
  $('a[data-action="reload"]').on('click', showList);
  $(document).ready(showList);

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection