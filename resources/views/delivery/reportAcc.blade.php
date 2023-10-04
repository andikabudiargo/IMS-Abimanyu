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
              <div class="col-md-3 form-group">
                <label for="dnDate">Delivery Date</label>
                <input type="text" id="dnDate" name="dnDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-6"> 
                <label class="form-label" for="searchCustomer">Customer</label>
                <select class="select2 form-control" id="searchCustomer" name="searchCustomer">
                    <option value="">All</option>
                    @foreach($customers as $val)
                        <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-3 d-none"> 
                <label for="searchDn">Delivery Number</label>
                <input type="text" class="form-control text-uppercase" id="searchDn" name="searchDn" placeholder=""  />
              </div>
              <div class="form-group col-md-9"> 
                <label for="searchSo">SO Number</label> <small class="text-muted">Daftar So Yang sudah di buat DN</small>
                <select class="select2 form-control" id="searchSo" name="searchSo">
                  <option value="">All</option>
                  @foreach($salesOrders as $val)
                      <option value="{{ $val->so_code }}">{{ $val->so_code }} | {{ $val->nama }}  | {{ $val->po_number }}</option>
                  @endforeach
              </select>
              </div>
              <div class="form-group col-md-2 d-none"> 
                <label class="form-label" for="searchStatus">Delivery Status</label>
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
@include('partials.delete-modal')
@endsection
@section('styles')
<style>
  .text-hijau {
    color:rgb(6, 248, 22);
  }
</style>
@endsection
@section('scripts')
<script type="text/javascript">
  let searchDn = $("#searchDn");
  let searchSo = $("#searchSo");
  let searchCustomer = $("#searchCustomer"); 
  let searchStatus = $("#searchStatus");
  let dnDate = $("#dnDate");

  $(document).ready(function(){    
  
  });

  //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
    showList(searchDn.val(),searchSo.val(),searchCustomer.val(),searchStatus.val(),dnDate.val());
  });

  let rangePickr = $('.flatpickr-range');
  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range'
    });
  }

  $("#btnSearch").click(function(e){
    showList(searchDn.val(),searchSo.val(),searchCustomer.val(),searchStatus.val(),dnDate.val());
  });

  const showList = (searchDn,searchSo,searchCustomer,searchStatus,dnDate) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('delivery.list.report.acc') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12],
      columnDefs :[
        { width: '5%', targets: 0 },
        // { className: 'text-right','targets': [ 6 ] },
        {
          targets: [ 8,9,10,11  ],
          render: $.fn.dataTable.render.number(',','.',2,''),
          className: "text-right"
        },
      ],
      dataSearch:  {
        searchDn:searchDn,
        searchSo:searchSo,
        searchCustomer:searchCustomer,
        searchStatus:searchStatus,
        dnDate:dnDate
      },
      type:'POST',
      orderColumn:[[ 1, 'asc' ]],
      excelFileName:'delivery_report_acc'
    });
  }

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
