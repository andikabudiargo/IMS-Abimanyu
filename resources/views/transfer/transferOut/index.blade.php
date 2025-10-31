@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="transfer-index">
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
              <div class="form-group col-md-2"> 
                <label for="searchTr">Transfer Number</label>
                <input type="text" class="form-control text-uppercase" id="searchTr" name="searchTr" placeholder=""  />
              </div>
              <div class="col-md-2 form-group">
                <label for="trDate">Date</label>
                <input type="text" id="trDate" name="trDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-2"> 
                <label class="form-label" for="searchStatus">Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                    <option value="">All</option>
                    @foreach($status as $index=>$val)
                        <option value="{{ $index }}">{{ $index }} - {{ $val }}</option>
                    @endforeach
                </select>
              </div>
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchLocFrom">Location From</label>
                <select class="select2 form-control" id="searchLocFrom" name="searchLocFrom">
                    <option value="">All</option>
                    @foreach($locations as $val)
                      <option value="{{$val->location_code}}" >{{$val->location_name}}</option>
                    @endforeach
                </select>
              </div>
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchLocTo">Location To</label>
                <select class="select2 form-control" id="searchLocTo" name="searchLocTo">
                    <option value="">All</option>
                    @foreach($locations as $val)
                      <option value="{{$val->location_code}}" >{{$val->location_name}}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    @can('transferIn-create')
                    <a href="{{ route('transferOut.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                    @endcan
                </div>
            </div>
        </form>
      </div>
    </div>
  </div>
</section>
<section id="table-transfer">
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
        <button type="button" class="btn btn-primary d-none" id ="btnDetail" name="btnDetail" data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data detail">Detail</button>
        <button type="button" class="btn btn-primary d-none" id ="btnSummary" name="btnSummary" data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data summary">Summary</button>
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
<script type="text/javascript">

  let searchTr = document.querySelector("#searchTr");
  let searchType = 'TROUT';
  let searchStatus = document.querySelector("#searchStatus");
  let trDate = document.querySelector("#trDate");
  let search = document.querySelector('#btnSearch');
  let refresh = document.querySelector('a[data-action="reload"]');
  let rangePickr = document.querySelector('.flatpickr-range');
  let searchLocFrom = document.querySelector('#searchLocFrom');
  let searchLocTo = document.querySelector('#searchLocTo');
  let btnSummary = $('#btnSummary');
  let btnDetail = $('#btnDetail');

  let href;
  $(document).on('click', '#cancelReasonButton', function(event) {
      event.preventDefault();
      href = $(this).data('href');
      $('#modalReasonCancel').attr("action", href);
  });

  initDatePicker(rangePickr,{
    minDate: "01/01/2010",
    maxDate: "31/12/2030",
    dateFormat: "d-m-Y",
    mode: "range"
  });

  function dataSearch($type){
    btnSummary.addClass('d-none');
    btnDetail.addClass('d-none');

    $(".loading-spinner-container").addClass("-show");

    if($type == 'detail'){
      showListDetail(searchTr.value,searchType.value,searchStatus.value,trDate.value,searchLocFrom.value,searchLocTo.value);
    }

    if($type == 'summary'){ 
      showList(searchTr.value,searchType.value,searchStatus.value,trDate.value,searchLocFrom.value,searchLocTo.value); 
    }
    
  }

  //refresh di cards
  refresh.addEventListener("click",function(){
    dataSearch('summary');
  })

  btnDetail.click(function(){
    dataSearch('detail');
  });

  btnSummary.click(function(){
    dataSearch('summary');
  });

  $("#btnSearch").click(function(e){
    btnSummary.addClass('d-none');
    btnDetail.addClass('d-none');
    dataSearch('summary');
  });

  const showList = (searchTr,searchType,searchStatus,trDate,searchLocFrom,searchLocTo) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('transferOut.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7],
      columnDefs :[
        { width: '5%', targets: 0 },
      ],
      dataSearch:  {
        searchTr:searchTr,
        searchType:searchType,
        searchStatus:searchStatus,
        trDate:trDate,
        transferFrom:searchLocFrom,
        transferTo:searchLocTo
      },
      initComplete: function() {
        let api = this.api();
        if (api.data().length > 0) {
          btnDetail.removeClass('d-none');
          btnSummary.addClass('d-none');
        }
        $(".loading-spinner-container").removeClass("-show");
      },
      orderColumn:[[ 1, 'desc' ]],
      excelFileName:'transfer_out'
    });
  }

  const showListDetail = (searchTr,searchType,searchStatus,trDate,searchLocFrom,searchLocTo) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('transferOut.list.detail') }}",
      kolom:{!! $kolomDetail !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14],
      columnDefs :[
        { width: '5%', targets: 0 },
        { className: 'text-right','targets': [4] },
      ],
      dataSearch:  {
        searchTr:searchTr,
        searchType:searchType,
        searchStatus:searchStatus,
        trDate:trDate,
        transferFrom:searchLocFrom,
        transferTo:searchLocTo
      },
      initComplete: function() {
        let api = this.api();
        if (api.data().length > 0) {
          btnSummary.removeClass('d-none');
          btnDetail.addClass('d-none');
        }
        $(".loading-spinner-container").removeClass("-show");
      },
      orderColumn:[[ 0, 'asc' ],[ 1, 'asc' ],[ 2, 'asc' ]],
      excelFileName:'transfer_out'
    });
  }
 
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
