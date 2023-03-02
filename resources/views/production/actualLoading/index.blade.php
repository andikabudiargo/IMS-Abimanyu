@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="article-index">
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
                <label for="searchPrd">Production Number</label>
                <input type="text" class="form-control text-uppercase" id="searchPrd" name="searchPrd" placeholder=""  />
              </div>
              <div class="col-md-3 form-group">
                <label for="prdDate">Prd Date</label>
                <input type="text" id="prdDate" name="prdDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-3"> 
                <label for="searchWos">WOS Number</label>
                <input type="text" class="form-control text-uppercase" id="searchWos" name="searchWos" placeholder=""  />
              </div>
              <div class="col-md-3 form-group">
                <label for="wosDate">Wos Date</label>
                <input type="text" id="wosDate" name="wosDate" class="form-control flatpickr-range-1" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchStatus">Status</label>
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
                    @can('actualLoading-create')
                    <a href="{{ route('production.actualLoading.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                    @endcan
                </div>
            </div>
        </form>
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
  let searchWos = document.querySelector("#searchWos");
  let searchPrd = document.querySelector("#searchPrd");
  let searchStatus = document.querySelector("#searchStatus");
  let wosDate = document.querySelector("#wosDate");
  let prdDate = document.querySelector("#prdDate");
  let search = document.querySelector('#btnSearch');
  let refresh = document.querySelector('a[data-action="reload"]');
  let rangePickr = document.querySelector('.flatpickr-range');
  let rangePickr1 = document.querySelector('.flatpickr-range-1');
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

  initDatePicker(rangePickr1,{
    minDate: "01/01/2010",
    maxDate: "31/12/2030",
    dateFormat: "d-m-Y",
    mode: "range"
  });

  //refresh di cards
  refresh.addEventListener("click",function(){
    btnDetail.style.display = "block";
    btnSummary.style.display = "none";
    showList(searchPrd.value,prdDate.val,searchWos.value,wosDate.value,searchStatus.value);
  })

  search.addEventListener("click", function(){ 
    btnDetail.style.display = "block";
    btnSummary.style.display = "none";
    showList(searchPrd.value,prdDate.value,searchWos.value,wosDate.value,searchStatus.value);
  });

  btnSummary.addEventListener("click", function(){
    btnSummary.style.display = "none";
    btnDetail.style.display = "block";
    showList(searchPrd.value,prdDate.value,searchWos.value,wosDate.value,searchStatus.value);
  });
  
  btnDetail.addEventListener("click", function(){
    btnSummary.style.display = "block";
    btnDetail.style.display = "none";
    showList(searchPrd.value,prdDate.value,searchWos.value,wosDate.value,searchStatus.value);
  });

  const showList = (searchPrd,prdDate,searchWos,wosdate,searchStatus) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('production.actualLoading.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7,8,9,10,13],
      columnDefs :[
        { width: '5%', targets: 0 },
      ],
      dataSearch:  {
        searchPrd:searchPrd,
        prdDate:prdDate,
        searchWos:searchWos,
        wosDate:wosdate,
        searchStatus:searchStatus
      },
      orderColumn:[[ 1, 'asc' ]],
      excelFileName:'actualLoading_data'
    });
  }

  const showListDetail = (searchPrd,prddate,searchWos,wosdate,searchStatus) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('production.actualLoading.list.detail') }}",
      kolom:{!! $kolomDetail !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19],
      dataSearch:  {
        searchPrd:searchPrd,
        prdDate:prddate,
        searchWos:searchWos,
        wosDate:wosdate,
        searchStatus:searchStatus
      },
      columnDefs :[
        { width: '5%', targets: 0 },
      ],
      orderColumn:[[ 1, 'desc' ],[ 0, 'asc' ]],
      excelFileName:'wos_data'
    });
  }
        
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
