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
                <label for="searchDn">Return Number</label>
                <input type="text" class="form-control text-uppercase" id="searchDn" name="searchDn" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchSupplier">Supplier</label>
                <select class="select2 form-control" id="searchSupplier" name="searchSupplier">
                    <option value="">All</option>
                    @foreach($suppliers as $val)
                      <option value="{{$val->kode}}" >{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
              </div>
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchLocation">Location</label>
                <select class="select2 form-control" id="searchLocation" name="searchLocation">
                    <option value="">All</option>
                    @foreach($locations as $val)
                      <option value="{{$val->location_number}}" >{{$val->location_number}} - {{$val->location_name}}</option>
                    @endforeach
                </select>
              </div>
              <div class="col-md-3 form-group">
                <label for="returnDate">Date</label>
                <input type="text" id="returnDate" name="returnDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchStatus">Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                    <option value="">All</option>
                    @foreach($status as $index=>$val)
                        <option value="{{ $index }}">{{ $val }}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    <a href="{{ route('supplierReturn.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
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
        <button type="button" class="btn btn-primary" id ="btnSummary" name="btnSummary" data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data summary">Summary</button>
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

@include('partials.modals')
@include('partials.delete-modal')
@endsection
@section('scripts')
<script type="text/javascript">
  let searchDn = document.querySelector("#searchDn");
  let searchStatus = document.querySelector("#searchStatus");
  let returnDate = document.querySelector("#returnDate");
  let searchSupplier = document.querySelector("#searchSupplier");
  let searchLocation = document.querySelector("#searchLocation");
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
    showList(searchDn.value,searchStatus.value,returnDate.value,searchSupplier.value,searchLocation.value);
  })

  search.addEventListener("click", function(){
    btnDetail.style.display = "block";
    btnSummary.style.display = "none";
    showList(searchDn.value,searchStatus.value,returnDate.value,searchSupplier.value,searchLocation.value);
  });

  btnSummary.addEventListener("click", function(){
    btnSummary.style.display = "none";
    btnDetail.style.display = "block";
    showList(searchDn.value,searchStatus.value,returnDate.value,searchSupplier.value,searchLocation.value);
  });
  
  btnDetail.addEventListener("click", function(){
    btnSummary.style.display = "block";
    btnDetail.style.display = "none";
    showListDetail(searchDn.value,searchStatus.value,returnDate.value,searchSupplier.value,searchLocation.value);
  });

  const showList = (searchDn,searchStatus,returnDate,searchSupplier,searchLocation) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route("supplierReturn.list") }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7,8,9,10,11,12],
      columnDefs :[
        { width: '5%', targets: 0 },
      ],
      dataSearch:  {
        searchDn:searchDn,
        searchStatus:searchStatus,
        returnDate:returnDate,
        searchSupplier:searchSupplier,
        searchLocation:searchLocation
      },
      orderColumn:[[ 1, 'desc' ]],
      excelFileName:'Supplier_return'
    });
  }

  const showListDetail = (searchDn,searchStatus,returnDate,searchSupplier,searchLocation) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('supplierReturn.list.detail') }}",
      kolom:{!! $kolomDetail !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12,13],
      columnDefs :[
        { width: '5%', targets: 0 },
        { className: 'text-right','targets': [8] },
      ],
      dataSearch:  {
        searchDn:searchDn,
        searchStatus:searchStatus,
        returnDate:returnDate,
        searchSupplier:searchSupplier,
        searchLocation:searchLocation
      },
      excelFileName:'Supplier_Return_detail'
    });
  }

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
</script>
@endsection