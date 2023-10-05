@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
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
              <div class="form-group col-md-5"> 
                <label class="form-label" for="searchCustomer">Customer</label>
                <select class="select2 form-control" id="searchCustomer" name="searchCustomer">
                    <option value="">All</option>
                    @foreach($custs as $val)
                        <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
              </div>
              <div class="form-group col-md-3"> 
                <label for="searchOrder">Order Number</label>
                <select class="select2 form-control" id="searchOrder" name="searchOrder" multiple>
                </select>
              </div>
              <div class="form-group col-md-2 d-none"> 
                <label for="seachPo">PO Number</label>
                <input type="text" class="form-control text-uppercase" id="seachPo" name="seachPo" placeholder=""  />
              </div>
              <div class="col-md-3 form-group">
                <label for="orderDate">Range Date</label>
                <input type="text" id="orderDate" name="orderDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
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
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script type="text/javascript">

  let searchOrder = $("#searchOrder");
  let seachPo = $("#seachPo");
  let searchCustomer = $("#searchCustomer");
  let searchStatus = $("#searchStatus");
  let orderDate = $("#orderDate");

  $(document).ready(function(){    

  });

   //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
    showList(searchOrder.val(),seachPo.val(),searchCustomer.val(),orderDate.val());
  });

  rangePickr = $('.flatpickr-range');
  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range'
    });
  }

  $("#btnSearch").click(function(e){
    // if(searchCustomer.val()){
      showList(searchOrder.val(),seachPo.val(),searchCustomer.val(),orderDate.val());
    // }else{
      // swal.fire('Warning','Customer harus dipilih dulu','warning');
    // }
  });

  const showList = (searchOrder,seachPo,searchCustomer,orderDate) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('salesOrder.list.report') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,9],
      columnDefs :[
        {
            targets: [ 7,8,9 ],
            render: $.fn.dataTable.render.number(',','.',2,''),
            className: "text-right"
        },
      ],
      dataSearch:  {
        searchOrder:searchOrder,
        seachPo:seachPo,
        searchCustomer:searchCustomer,
        orderDate:orderDate
      },
      orderColumn:[[ 10, 'asc' ],[ 4, 'asc' ]],
      excelFileName:'os_sales_order'
    });
  }

  searchCustomer.change(function(e){        
        let custCode = $(this).val();        
        changeselect('listSo','searchOrder',custCode); 
  });

  function changeselect(dependent,obj,value) {
    $.ajax({
      url:"{{route('dynamic.dependent')}}",
      method:"POST",
      data:{
          value:value,
          dependent:dependent
      },
      success:function(result){
          $('#'+obj).html(result);
          // $('#'+obj).val('').trigger('change');
      }
    })
  }

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
