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
                <label for="searchInv">Delivery Number</label>
                <input type="text" class="form-control text-uppercase" id="searchInv" name="searchInv" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label for="searchSo">SO Number</label>
                <input type="text" class="form-control text-uppercase" id="searchSo" name="searchSo" placeholder=""  />
              </div>
              
              <div class="col-md-3 form-group">
                <label for="recDate">Date</label>
                <input type="text" id="recDate" name="recDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
             
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    @can('dnReceipt-create')
                      <a href="{{ route('dnReceipt.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
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
  $(document).ready(function(){    
  });

  //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
      showList();
  });

  // rangePickr = $('.flatpickr-range');
  // if (rangePickr.length) {
  //   rangePickr.flatpickr({
  //     dateFormat: "d-m-Y",
  //     mode: 'range'
  //   });
  // }

  $("#btnSearch").click(function(e){
    // let searchInv = $("#searchInv").val();
    // let searchSo = $("#searchSo").val();
    // let searchCustomer = $("#searchCustomer").val(); 
    // let searchStatus = $("#searchStatus").val();
    // let recDate = $("#recDate").val();
    // showList(searchInv,searchSo,searchCustomer,searchStatus,recDate);

    showList();

  });

  const showList = () => {
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
      arrColPrint:[1,2,3,4,5,6,7,8],
      columnDefs :[
        { width: '5%', targets: 0 },
      ],
      dataSearch:  {
        // searchWos:searchWos,
        // wosDate:wosdate,
        // searchStatus:searchStatus
      },
      orderColumn:[[ 1, 'asc' ]],
      excelFileName:'dn_receive'
    });
  }

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
