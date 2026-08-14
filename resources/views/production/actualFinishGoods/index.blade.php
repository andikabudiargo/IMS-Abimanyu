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
              <div class="form-group col-md-4"> 
                <label for="searchPrd">AFG Number</label>
                <input type="text" class="form-control text-uppercase" id="searchPrd" name="searchPrd" placeholder=""  />
              </div>
              <div class="col-md-4 form-group">
                <label for="prdDate">AFG Date</label>
                <input type="text" id="prdDate" name="prdDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-4"> 
                <label class="form-label" for="spraybooth">Location</label>
                <select class="select2 form-control" id="spraybooth" name="spraybooth">
                    <option value="">All</option>
                    @foreach($listLocation as $loc)
                        <option value="{{ $loc->location_code }}">{{ $loc->location_name }}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    @can('actualFinishGoods-create')
                    <a href="{{ route('production.actualFinishGoods.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
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
        <button type="button" class="btn btn-primary" id ="btnDetail" name="btnDetail" data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data detail">Detail</button>
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
  let searchLocation = document.querySelector("#spraybooth");
  let searchPrd = document.querySelector("#searchPrd");
  let searchStatus = document.querySelector("#searchStatus");
  let prdDate = document.querySelector("#prdDate");
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

  refresh.addEventListener("click",function(){
    btnDetail.style.display = "block";
    btnSummary.style.display = "none";
    showList(searchPrd.value, prdDate.value, searchLocation.value, searchStatus.value);
  });

  search.addEventListener("click", function(){ 
    btnDetail.style.display = "block";
    btnSummary.style.display = "none";
    showList(searchPrd.value, prdDate.value, searchLocation.value, searchStatus.value);
  });

  btnSummary.addEventListener("click", function(){
    btnSummary.style.display = "none";
    btnDetail.style.display = "block";
    showList(searchPrd.value, prdDate.value, searchLocation.value, searchStatus.value);
  });
  
  btnDetail.addEventListener("click", function(){
    btnSummary.style.display = "block";
    btnDetail.style.display = "none";
    showListDetail(searchPrd.value, prdDate.value, searchLocation.value, searchStatus.value);
  });

  const showList = (searchPrd, prdDate, spraybooth, searchStatus) => {
    if ($('#detailedTable tr').length > 0){
        let table = $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId: "detailedTable",
      route: "{{ route('production.actualFinishGoods.list') }}",
      kolom: {!! $kolom !!},
      arrColPrint: [1,2,3,4,5,6,7],
      columnDefs: [
        { width: '5%', targets: 0 },
      ],
      dataSearch: {
        searchPrd: searchPrd,
        prdDate: prdDate,
        spraybooth: spraybooth,
        searchStatus: searchStatus
      },
      orderColumn: [[ 1, 'asc' ]],
      excelFileName: 'actual_finish_goods_data'
    });
  }

  const showListDetail = (searchPrd, prdDate, spraybooth, searchStatus) => {
    if ($('#detailedTable tr').length > 0){
        let table = $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId: "detailedTable",
      route: "{{ route('production.actualFinishGoods.list.detail') }}",
      kolom: {!! $kolomDetail !!},
      arrColPrint: [0,1,2,3,4,5,6,7,8,9,10],
      dataSearch: {
        searchPrd: searchPrd,
        prdDate: prdDate,
        spraybooth: spraybooth,
        searchStatus: searchStatus
      },
      columnDefs: [
        { width: '5%', targets: 0 },
      ],
      orderColumn: [[ 1, 'desc' ],[ 0, 'asc' ]],
      excelFileName: 'actual_finish_goods_data'
    });
  }
        
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection