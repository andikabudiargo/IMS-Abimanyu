@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="dr-index">
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
                <label for="searchDn">Delivery Number</label>
                <input type="text" class="form-control text-uppercase" id="searchDn" name="searchDn" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label for="searchCustomer">Customer</label>
                <select class="select2 form-control" id="searchCustomer" name="searchCustomer">
                  <option value="">All</option>
                  @foreach($custs as $val)
                    <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                  @endforeach
              </select>
              </div>
              {{-- <div class="form-group col-md-3"> 
                <label for="searchSo">SO Number</label>
                <input type="text" class="form-control text-uppercase" id="searchSo" name="searchSo" placeholder=""  />
              </div> --}}
              
              <div class="col-md-3 form-group">
                <label for="drDate">Received Date</label>
                <input type="text" id="drDate" name="drDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>

              <div class="col-md-3 form-group">
                <label for="dnDate">Delivery Date</label>
                <input type="text" id="dnDate" name="dnDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>

              <div class="form-group col-md-2"> 
                <label class="form-label" for="searchStatus">Delivery Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                    <option value="">All</option>
                    @foreach($status as $index=>$val)
                        <option value="{{ $index }}" {{ $index == $statusKu ? 'selected' : ''  }}>{{ $val }}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    {{-- @can('dnReceipt-create')
                      <a href="{{ route('dnReceipt.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                    @endcan --}}
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
  let searchDn = $("#searchDn");
  let drDate = $("#drDate");
  let dnDate = $("#dnDate");
  let searchStatus = $("#searchStatus");
  let searchCustomer = $("#searchCustomer");

  $(document).ready(function(){
    showList(searchDn.val(),drDate.val(),searchStatus.val(),dnDate.val(),searchCustomer.val());
  });

  //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
    showList(searchDn.val(),drDate.val(),searchStatus.val(),dnDate.val(),searchCustomer.val());
  });

  let rangePickr = $('.flatpickr-range');
  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range'
    });
  }

  $("#btnSearch").click(function(e){
    showList(searchDn.val(),drDate.val(),searchStatus.val(),dnDate.val(),searchCustomer.val());
  });

  const showList = (searchDn,drDate,searchStatus,dnDate,customer) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('dnReceipt.list') }}",
      kolom:{!! $kolom !!},
      type:'POST',
      arrColPrint:[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15],
      columnDefs :[
        { width: '5%', targets: 0 },
      ],
      dataSearch:  {
        searchDn:searchDn,
        drDate:drDate,
        searchStatus:searchStatus,
        dnDate:dnDate,
        customer:customer
      },
      orderColumn:[[ 3, 'desc' ],[ 1, 'asc' ]],
      excelFileName:'dn_receive'
    });
  }

  submitDr=(idKu)=>{
    // console.log(idKu);
    let id = idKu;
    let status = $("#searchStatus").val();
    let url = "{{ route('dnReceipt.edit', ['id'=>':id','status'=>':status']) }}";
    url = url.replace('%3Aid', id);
    url = url.replace('%3Astatus', status);
    url = url.replace("&amp;", "&");
    window.open(url,"_self");
  }

  receiveDr=(idKu)=>{
    console.log(idKu);
    let id = idKu;
    let status = $("#searchStatus").val();
    let url = "{{ route('dnReceipt.create', ['id'=>':id','status'=>':status']) }}";
    url = url.replace('%3Aid', id);
    url = url.replace('%3Astatus', status);
    url = url.replace("&amp;", "&");
    window.open(url,"_self");
  }

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
