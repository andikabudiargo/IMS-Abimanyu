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
                    {{-- <button type="button" class="btn btn-info" id ="cmdExport" name="cmdExport"><i class="fa fa-download"></i> Downlod Excel</button> --}}
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
<!-- Modal detail DN-->
<div class="modal fade text-left bisa-geser" id="mdlDetail" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
      <div class="modal-content">
          <div class="modal-header">
              <h5>Delivery <span class="bold" id="mdLSoNumber"></span></h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
              </button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-striped" id="mdlDetailTable">
              </table>
            </div>
          </div>
      </div>
  </div>
</div>
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
    if(orderDate){
      $(".loading-spinner-container").addClass("-show");
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
        arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11],
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
        orderColumn:[[ 11, 'asc' ],[ 4, 'asc' ]],
        excelFileName:'report_sales_order',
        initComplete: function(settings, json) {
          $(".loading-spinner-container").removeClass("-show");
        }
      });
    }else{
      swal.fire("Warning","Isi tanggal terlebih dahulu","warning")
    }
  }

  searchCustomer.change(function(e){        
        let custCode = $(this).val();        
        changeselect('listSo','searchOrder',custCode); 
  });

  function changeselect(dependent,obj,value) {
    $('#'+obj).val('').trigger('change');
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

  const detailDelivery = (artCode,soNumber,artDesc) => {
    $('#mdlDetail').modal('show');
    $('#mdLSoNumber').text(' | '+soNumber+' - '+artDesc);

    if ($('#mdlDetailTable tr').length >0){
        let table= $('#mdlDetailTable').DataTable();
        table.destroy();
        $('#mdlDetailTable tbody > tr').remove();
        $("#mdlDetailTable thead > tr").remove();
    }
    showDataTables({
      tableId:"mdlDetailTable",
      route:"{{ route('salesOrder.list.report.detail.dn') }}",
      kolom:{!! $kolomDetailDn !!},
      arrColPrint:[0,1,2],
      columnDefs :[
        { width: '5%', targets: 0 },
        {
          targets: [ 2 ],
          render: $.fn.dataTable.render.number(',', '.',2, ''),
          className: "text-right"
        },
      ],
      dataSearch:  {
        artCode:artCode,
        soNumber:soNumber
      },
      orderColumn:[[0,'asc'],[1,'asc']],
      scrollY:350,
      excelFileName:'detailDn'+soNumber,
      lengthMenu: [
        [ -1, 10, 25, 50 ],
        [ 'all', '10', '25', '50']
      ],
    });
  }

  $("#cmdExport").click(function(){
    let so = searchOrder.val()
    let po = seachPo.val()
    let cust = searchCustomer.val()
    let uDate = orderDate.val()

    if(uDate){
      
      let url = "{{ route('salesOrderReport.export', ['searchOrder'=>':so','seachPo'=>':po','searchCustomer'=>':cust','orderDate'=>':uDate']) }}";
      url = url.replace('%3Aso', so);
      url = url.replace('%3Apo', po);
      url = url.replace('%3Acust', cust);
      url = url.replace('%3AuDate', uDate);
      url = url.replaceAll("&amp;", "&").replace("&amp;", "&");
      window.location.href = url;

    }else{
      swal.fire("Warning","Isi tanggal terlebih dahulu","warning")
    }
  });

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
