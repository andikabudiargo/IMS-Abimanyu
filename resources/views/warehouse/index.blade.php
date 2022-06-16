@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="purchase-index">
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
                <label for="searchTr">Transfer Number</label>
                <input type="text" class="form-control text-uppercase" id="searchTr" name="searchTr" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchType">Transfer Type</label>
                <select class="select2 form-control" id="searchType" name="searchType">
                  <option value="">All</option>
                    @foreach($type as $key => $val)
                        <option value="{{$key}}">{{$val}}</option>
                    @endforeach
                </select>
              </div>
              <div class="col-md-3 form-group">
                <label for="trDate">Date</label>
                <input type="text" id="trDate" name="trDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-2"> 
                <label class="form-label" for="searchStatus">Order Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                    <option value="">All</option>
                    @foreach($status as $index=>$val)
                        <option value="{{ $index }}">{{ $index }} - {{ $val }}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                </div>
            </div>
        </form>
      </div>
    </div>
  </div>
</section>
<section id="table-purchase">
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
        <button type="button" class="btn btn-primary" id ="btnDetail" name="btnDetail">Detail</button>
        <button type="button" class="btn btn-primary" id ="btnSummary" name="btnSummary">Summary</button>
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
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script type="text/javascript">

  let searchTr = document.querySelector("#searchTr");
  let searchType = document.querySelector("#searchType"); 
  let searchStatus = document.querySelector("#searchStatus");
  let trDate = document.querySelector("#trDate");
  let search = document.querySelector('#btnSearch');
  let refresh = document.querySelector('a[data-action="reload"]');
  let rangePickr = document.querySelector('.flatpickr-range');
  let btnSummary = document.querySelector('#btnSummary');
  let btnDetail = document.querySelector('#btnDetail');

  document.addEventListener("DOMContentLoaded", function(event) {
    btnSummary.style.display = "none";
    btnDetail.style.display = "none";
  });

  initDatePicker(rangePickr,{
    minDate: "01/01/2010",
    maxDate: "31/12/2030",
    dateFormat: "d-m-Y",
    mode: "range"
  });

  // if (rangePickr.length) {
  //   rangePickr.flatpickr({
  //     dateFormat: "d-m-Y",
  //     mode: 'range'
  //   });
  // }

  //refresh di cards
  refresh.addEventListener("click",function(){
    btnDetail.style.display = "block";
    btnSummary.style.display = "none";
    showList(searchTr.value,searchType.value,searchStatus.value,trDate.value);
  })

  search.addEventListener("click", function(){ 
    btnDetail.style.display = "block";
    btnSummary.style.display = "none";
    showList(searchTr.value,searchType.value,searchStatus.value,trDate.value);
  });

  btnSummary.addEventListener("click", function(){
    btnSummary.style.display = "none";
    btnDetail.style.display = "block";
    showList(searchTr.value,searchType.value,searchStatus.value,trDate.value);
  });
  
  btnDetail.addEventListener("click", function(){
    btnSummary.style.display = "block";
    btnDetail.style.display = "none";
    showListDetail(searchTr.value,searchType.value,searchStatus.value,trDate.value);
  });

  const showList = (searchTr,searchType,searchStatus,trDate) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('warehouse.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5],
      columnDefs :[
        { width: '5%', targets: 0 },
      ],
      dataSearch:  {
        searchTr:searchTr,
        searchType:searchType,
        searchStatus:searchStatus,
        trDate:trDate
      },
      orderColumn:[[ 1, 'desc' ]],
      excelFileName:'transfer_in_out'
    });
  }

  const showListDetail = (searchTr,searchType,searchStatus,trDate) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('warehouse.list.detail') }}",
      kolom:{!! $kolomDetail !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,10],
      columnDefs :[
        { width: '5%', targets: 0 },
        { className: 'text-right','targets': [ 3] },
      ],
      dataSearch:  {
        searchTr:searchTr,
        searchType:searchType,
        searchStatus:searchStatus,
        trDate:trDate
      },
      orderColumn:[[ 2, 'asc' ]],
      excelFileName:'transfer_in_out'
    });
  }
 
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
