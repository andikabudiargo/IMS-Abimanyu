@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="article-index">
  <div class="card">
    <div class="card-header">  
      <h4 class="card-title">Filter  <small class="text-muted"> {{ $lockDate ? "Locked From : ".$lockDate : '' }}</small></h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0">
            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <form id="frmAdd" name="frmAdd">
            <div class="form-row">
              <div class="form-group col-md-3"> 
                <label for="searchOrder">Order Number</label>
                <input type="text" class="form-control text-uppercase" id="searchOrder" name="searchOrder" placeholder="" />
              </div>
              <div class="form-group col-md-2"> 
                <label for="seachPo">PO Number</label>
                <input type="text" class="form-control text-uppercase" id="seachPo" name="seachPo" placeholder="" />
              </div>
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchCustomer">Customer</label>
                <select class="select2 form-control" id="searchCustomer" name="searchCustomer">
                    <option value="">All</option>
                    @foreach($custs as $val)
                        <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
              </div>
              <div class="form-group col-md-2"> 
                <label class="form-label" for="searchSalesman">Salesman</label>
                <select class="select2 form-control" id="searchSalesman" name="searchSalesman">
                    <option value="">All</option>
                    @foreach($employees as $val)
                        <option value="{{$val->employee_id}}">{{$val->employee_id}} - {{$val->name}}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="col-md-3 form-group">
                <label for="orderDate">Range Date</label>
                <input type="text" id="orderDate" name="orderDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" required/>
              </div>
              <div class="form-group col-md-2"> 
                <label class="form-label" for="searchType">Order Type</label>
                <select class="select2 form-control" id="searchType" name="searchType">
                    <option value="">All</option>
                    @foreach($types as $val)
                        <option value="{{$val}}">{{$val}}</option>
                    @endforeach
                </select>
              </div>
              <div class="form-group col-md-2"> 
                <label class="form-label" for="searchStatus">Order Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                    <option value="">All</option>
                    @foreach($status as $key=>$val)
                        <option value="{{$key}}">{{$val}}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    @can('salesOrder-create')
                    <a href="{{ route('salesOrder.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
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
@include('partials.delete-modal')
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script type="text/javascript">
  let btnSummary = document.querySelector('#btnSummary');
  let btnDetail = document.querySelector('#btnDetail');

  $(document).ready(function(){    
    let href;
    validateFormToast("frmAdd");
    $(document).on('click', '#deleteButton', function(event) {
        event.preventDefault();
        href = $(this).data('href');
        console.log(href);
        $('#modalConfirmation').attr("action", href);
    });

    btnSummary.style.display = "none";
    btnDetail.style.display = "none";
  });

  let showAlert = "{{ Session::get('alert') }}";
  if ( showAlert ){
    showList();
    $("#alert-message-alert").fadeTo(5000, 500).slideUp(500, function(){
      $("#alert-message-alert").slideUp(500);
    });
  }

  //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
      showList();
  });

  rangePickr = $('.flatpickr-range');

  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range',
      allowInput:true
    });
  }

  $("#btnSearch").click(function(e){
    let searchOrder = $("#searchOrder").val();
    let seachPo = $("#seachPo").val();
    let searchCustomer = $("#searchCustomer").val();
    let searchSalesman = $("#searchSalesman").val();
    let searchType = $("#searchType").val();
    let searchStatus = $("#searchStatus").val();
    let orderDate = $("#orderDate").val();

    btnDetail.style.display = "block";
    btnSummary.style.display = "none";
    
    if(searchOrder || seachPo){
      showList(searchOrder,seachPo,searchCustomer,searchSalesman,searchType,searchStatus,orderDate);
    }else{
      if (!$("#frmAdd")[0].checkValidity()){
          $("#frmAdd").submit();
      }else{ 
          showList(searchOrder,seachPo,searchCustomer,searchSalesman,searchType,searchStatus,orderDate);
      }
    }


  });

  btnSummary.addEventListener("click", function(){
    btnSummary.style.display = "none";
    btnDetail.style.display = "block";
    let searchOrder = $("#searchOrder").val();
    let seachPo = $("#seachPo").val();
    let searchCustomer = $("#searchCustomer").val();
    let searchSalesman = $("#searchSalesman").val();
    let searchType = $("#searchType").val();
    let searchStatus = $("#searchStatus").val();
    let orderDate = $("#orderDate").val();

    if (!$("#frmAdd")[0].checkValidity()){
        $("#frmAdd").submit();
    }else{ 
        showList(searchOrder,seachPo,searchCustomer,searchSalesman,searchType,searchStatus,orderDate);
    }  

  });
  
  btnDetail.addEventListener("click", function(){
    btnSummary.style.display = "block";
    btnDetail.style.display = "none";
    let searchOrder = $("#searchOrder").val();
    let seachPo = $("#seachPo").val();
    let searchCustomer = $("#searchCustomer").val();
    let searchSalesman = $("#searchSalesman").val();
    let searchType = $("#searchType").val();
    let searchStatus = $("#searchStatus").val();
    let orderDate = $("#orderDate").val();
    
    if (!$("#frmAdd")[0].checkValidity()){
      $("#frmAdd").submit();
    }else{ 
      showListDetail(searchOrder,seachPo,searchCustomer,searchSalesman,searchType,searchStatus,orderDate);
    }

  });

  const showList = (searchOrder,seachPo,searchCustomer,searchSalesman,searchType,searchStatus,orderDate) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('salesOrder.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[2,3,4,5,6,7,8,9,10,11],
      columnDefs :[
        { width: '5%', targets: 0 },
      ],
      dataSearch:  {
        searchOrder:searchOrder,
        seachPo:seachPo,
        searchCustomer:searchCustomer,
        searchSalesman:searchSalesman,
        searchType:searchType,
        searchStatus:searchStatus,
        orderDate:orderDate
      },
      orderColumn:[[ 12, 'desc' ]],
      excelFileName:'sales_order'
    });
  }

  const showListDetail = (searchOrder,seachPo,searchCustomer,searchSalesman,searchType,searchStatus,orderDate) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('salesOrder.list.detail') }}",
      kolom:{!! $kolomDetail !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18],
      columnDefs :[
        { width: '5%', targets: 0 },
        {
            targets: [ 9,11,12,13 ],
            render: $.fn.dataTable.render.number(',', '.', 2, ''),
            className: "text-right"
        },
      ],
      dataSearch:  {
        searchOrder:searchOrder,
        seachPo:seachPo,
        searchCustomer:searchCustomer,
        searchSalesman:searchSalesman,
        searchType:searchType,
        searchStatus:searchStatus,
        orderDate:orderDate
      },
      orderColumn:[[ 19, 'asc' ]],
      excelFileName:'sales_order'
    });
  }

  let href;
  $(document).on('click', '#revisionReasonButton', function(event) {
      event.preventDefault();
      href = $(this).data('href');
      $('#modalReasonRevision').attr("action", href);
  });

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

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
