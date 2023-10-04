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
              <div class="form-group col-md-4"> 
                <label class="form-label" for="searchSupplier">Supplier</label>
                <select class="select2 form-control" id="searchSupplier" name="searchSupplier">
                  <option value="">All</option>
                  @foreach($supps as $val)
                      <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group col-md-3"> 
                <label for="searchPo">PO Number</label>
                <select class="select2 form-control" id="searchPo" name="searchPo" multiple>
                </select>
              </div>
              <div class="col-md-3 form-group">
                <label for="orderDate">Date</label>
                <input type="text" id="orderDate" name="orderDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-2 d-none"> 
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
      <h4 class="card-title mb-1"> @yield('title') List </h4>
      <div class="heading-elements">
          <ul class="list-inline mb-0">
              <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
              <li><a data-action="reload"><i data-feather="rotate-cw"></i></a></li>
          </ul>
      </div>
    </div>    
    <div class="card-content collapse show">
      <div class="card-body">
        {{-- <p class="font-small-2">QTY LPB adalah qty receiving sesudah maupun sebelum di posting</p> --}}
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

  let searchPo = $("#searchPo");
  let searchSupplier = document.querySelector("#searchSupplier"); 
  let searchStatus = document.querySelector("#searchStatus");
  let orderDate = document.querySelector("#orderDate");
  let search = document.querySelector('#btnSearch');
  let refresh = document.querySelector('a[data-action="reload"]');
  let rangePickr = document.querySelector('.flatpickr-range');
 
  document.addEventListener("DOMContentLoaded", function(event) {
    
  });

  initDatePicker(rangePickr,{
    minDate: "01/01/2010",
    maxDate: "31/12/2030",
    dateFormat: "d-m-Y",
    mode: "range"
  });

  //refresh di cards
  refresh.addEventListener("click",function(){ 
    showList(searchPo.val(),searchSupplier.value,searchStatus.value,orderDate.value);
  })

  search.addEventListener("click", function(){ 
       showList(searchPo.val(),searchSupplier.value,searchStatus.value,orderDate.value);
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
      route:"{{ route('purchaseOrders.listReport') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12,13],
      columnDefs :[
        { width: '5%', targets: 0 },
        // { className: 'text-right','targets': [ 8,9,10,12 ] },
        {
            targets: [ 8,9,10,12 ],
            render: $.fn.dataTable.render.number(',','.',2,''),
            className: "text-right"
        },
      ],
      dataSearch:  {
        searchPo:searchPo,
        searchSupplier:searchSupplier,
        searchStatus:searchStatus,
        orderDate:orderDate
      },
      orderColumn:[[ 0, 'asc' ],[ 1, 'asc' ]],
      excelFileName:'os_purchase_order'
    });
  }

  $("#searchSupplier").change(function(e){        
        let suppCode = $(this).val();        
        changeselect('listPo','searchPo',suppCode); 
  });

  function changeselect(dependent,obj,value,type) {
    $.ajax({
      url:"{{route('dynamic.dependent')}}",
      method:"POST",
      data:{
          value:value,
          type:type,
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
