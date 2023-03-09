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
                <label for="searchPo">PO Number</label>
                <input type="text" class="form-control text-uppercase" id="searchPo" name="searchPo" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchSupplier">Supplier</label>
                <select class="select2 form-control" id="searchSupplier" name="searchSupplier">
                  <option value="">All</option>
                    @foreach($supps as $val)
                        <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
              </div>
              <div class="col-md-3 form-group">
                <label for="orderDate">Date</label>
                <input type="text" id="orderDate" name="orderDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
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
                    @can('purchaseOrder-create')
                    <a href="{{ route('purchaseOrder.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                    @endcan
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
@include('partials.delete-modal')
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script type="text/javascript">

  let searchPo = document.querySelector("#searchPo");
  let searchSupplier = document.querySelector("#searchSupplier"); 
  let searchStatus = document.querySelector("#searchStatus");
  let orderDate = document.querySelector("#orderDate");
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

  //refresh di cards
  refresh.addEventListener("click",function(){
    btnDetail.style.display = "block";
    btnSummary.style.display = "none";
    showList(searchPo.value,searchSupplier.value,searchStatus.value,orderDate.value);
  })

  search.addEventListener("click", function(){ 
    btnDetail.style.display = "block";
    btnSummary.style.display = "none";
    showList(searchPo.value,searchSupplier.value,searchStatus.value,orderDate.value);
  });

  btnSummary.addEventListener("click", function(){
    btnSummary.style.display = "none";
    btnDetail.style.display = "block";
    showList(searchPo.value,searchSupplier.value,searchStatus.value,orderDate.value);
  });
  
  btnDetail.addEventListener("click", function(){
    btnSummary.style.display = "block";
    btnDetail.style.display = "none";
    showListDetail(searchPo.value,searchSupplier.value,searchStatus.value,orderDate.value);
  });

  const showList = (searchPo,searchSupplier,searchStatus,orderDate) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('purchaseOrder.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21],
      columnDefs :[
        { width: '5%', targets: 0 },
        { className: 'text-right','targets': [ 12,13,14,15,16,17 ] },
      ],
      dataSearch:  {
        searchPo:searchPo,
        searchSupplier:searchSupplier,
        searchStatus:searchStatus,
        orderDate:orderDate
      },
      orderColumn:[[ 1, 'desc' ]],
      excelFileName:'purchase_order'
    });
  }

  const showListDetail = (searchPo,searchSupplier,searchStatus,orderDate) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('purchaseOrder.list.detail') }}",
      kolom:{!! $kolomDetail !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27],
      columnDefs :[
        { width: '5%', targets: 0 },
        { className: 'text-right','targets': [ 6,8,9,10,11,12,14,15,16 ] },
      ],
      dataSearch:  {
        searchPo:searchPo,
        searchSupplier:searchSupplier,
        searchStatus:searchStatus,
        orderDate:orderDate
      },
      orderColumn:[[ 2, 'asc' ]],
      excelFileName:'purchase_order'
    });
  }

  let href;
  $(document).on('click', '#revisionReasonButton', function(event) {
      event.preventDefault();
      href = $(this).data('href');
      $('#modalReasonRevision').attr("action", href);
  });
 
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
