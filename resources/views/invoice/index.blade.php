@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="invoice-index">
  {{-- <div class="row match-height">
    <div class="col-lg-4 col-md-4 col-6">
        <div class="card">
          <div class="card-body">
            <div class="card-header flex-column align-items-start pb-0">
                <div class="avatar bg-light-success p-50 m-0">
                    <div class="avatar-content">
                        <i data-feather="package" class="font-medium-5"></i>
                    </div>
                </div>
                <h2 class="font-weight-bolder mt-1">{{ number_format($totalAll,2) }}</h2>
                <p class="card-text">Total Piutang</p>
            </div>
          </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-4 col-6">
        <div class="card card-tiny-line-stats">
          <div class="card-body">
            <div class="card-header flex-column align-items-start pb-0">
                <div class="avatar bg-light-primary p-50 m-0">
                    <div class="avatar-content">
                        <i data-feather="credit-card" class="font-medium-5"></i>
                    </div>
                </div>
                <h2 class="font-weight-bolder mt-1">{{ number_format($totalPaid,2) }}</h2>
                <p class="card-text">Total Bayar</p>
            </div>
          </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-4 col-6">
      <div class="card card-tiny-line-stats">
        <div class="card-body">
          <div class="card-header flex-column align-items-start pb-0">
              <div class="avatar bg-light-warning p-50 m-0">
                  <div class="avatar-content">
                      <i data-feather="minus-square" class="font-medium-5"></i>
                  </div>
              </div>
              <h2 class="font-weight-bolder mt-1">{{ number_format($totalBalance,2) }}</h2>
              <p class="card-text">Balance</p>
          </div>
        </div>
      </div>
    </div>
  </div> --}}
  <div class="card">
    <div class="card-header">  
      <h4 class="card-title">Filter <small class="text-muted"> {{ $lockDate ? "Locked From : ".$lockDate : '' }}</small></h4>
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
                <label for="searchInv">Invoice Number</label>
                <input type="text" class="form-control text-uppercase" id="searchInv" name="searchInv" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label for="searchSo">SO Number</label>
                <input type="text" class="form-control text-uppercase" id="searchSo" name="searchSo" placeholder=""  />
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
              <div class="col-md-3 form-group">
                <label for="recDate">Date</label>
                <input type="text" id="recDate" name="recDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchStatus">Invoice Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                    <option value="">All</option>
                    @foreach($status as $index=>$val)
                        <option value="{{ $index }}">{{ $val }}</option>
                    @endforeach
                </select>
              </div>
              <div class="col-md-1 form-group">
                <label class="form-label" for="arPeriod1">Period Awal</label>
                <select class="select2 form-control" id="arPeriod1" name="arPeriod1">
                  <option value=""></option>
                  @for ($i = 1; $i <= 12; $i++)
                      <option value="{{ $i }}">{{ $i }}</option>
                  @endfor
                </select>
              </div>
              <div class="col-md-1 form-group">
                <label class="form-label" for="arPeriod2">Period Akhir</label>
                <select class="select2 form-control" id="arPeriod2" name="arPeriod2">
                  <option value=""></option>
                  @for ($i = 1; $i <= 12; $i++)
                      <option value="{{ $i }}">{{ $i }}</option>
                  @endfor
                </select>
              </div>

              {{-- <div class="form-group col-md-2">
                <label class="form-label" for="period">Period</label>
                <select class="select2 form-control" id="period" name="period" >
                    <option value=""></option>
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
              </div> --}}
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    {{-- @can('receiving-create') --}}
                      <a href="{{ route('invoice.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                    {{-- @endcan --}}
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
  let currentDate = todayDate('dd-mm-yyyy');  
  let btnSummary = $('#btnSummary');
  let btnDetail = $('#btnDetail');

  $(document).ready(function(){    
    let href;
    $(document).on('click', '#deleteButton', function(event) {
        event.preventDefault();
        href = $(this).data('href');
        $('#modalConfirmation').attr("action", href);
    });
  });

  //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
      showList();
  });

  rangePickr = $('.flatpickr-range');
  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range'
    });
  }

  function searcData($type){
    let searchInv = $("#searchInv").val();
    let searchSo = $("#searchSo").val();
    let searchCustomer = $("#searchCustomer").val(); 
    let searchStatus = $("#searchStatus").val();
    let recDate = $("#recDate").val();
    let searchPeriod1 = $("#arPeriod1").val();
    let searchPeriod2 = $("#arPeriod2").val();
    btnSummary.addClass('d-none');
    btnDetail.removeClass('d-none');
    if($type == 'detail'){
      btnDetail.addClass('d-none');
      btnSummary.removeClass('d-none');
      showListDetail(searchInv,searchSo,searchCustomer,searchStatus,recDate,searchPeriod1,searchPeriod2);
    }
    if($type == 'summary'){
      btnSummary.addClass('d-none');
      btnDetail.removeClass('d-none');
      showList(searchInv,searchSo,searchCustomer,searchStatus,recDate,searchPeriod1,searchPeriod2);
    }
  }

  btnDetail.click(function(){
    searcData('detail');
  });

  btnSummary.click(function(){
    searcData('summary');
  });

  $("#btnSearch").click(function(e){
    btnSummary.addClass('d-none');
    btnDetail.addClass('d-none');
    searcData('summary');
  });

  const showList = (searchInv,searchSo,searchCustomer,searchStatus,recDate,searchPeriod1,searchPeriod2) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('invoice.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,4,5,6,7,8,9,10,11,12,13,14,16,18,20,21,22,23,24,26,27,28,29,30],
      columnDefs :[
        { width: '5%', targets: 0 },
        {
          targets: [ 10,11,12,13,14,21,22 ],
          render: $.fn.dataTable.render.number(',', '.', 2, ''),
          className: "text-right"
        },
      ],
      type:"POST",
      excelCustomize:function(xlsx) {
        let sheet = xlsx.xl.worksheets['sheet1.xml'];
        $('row:last c', sheet).attr('s','50');
      },
      excelMessageBottom:function () { return "Tanggal export : "+currentDate },
      dataSearch:  {
        searchInv:searchInv,
        searchSo:searchSo,
        searchCustomer:searchCustomer,
        searchStatus:searchStatus,
        recDate:recDate,
        searchPeriod1:searchPeriod1,
        searchPeriod2:searchPeriod2
        // searchPeriod:searchPeriod
      },
      initComplete: function() {
        let api = this.api();
        if (api.data().length === 0) {
          btnDetail.addClass('d-none');
        } else {
          btnDetail.removeClass('d-none');
        }
      },
      orderColumn:[[ 29, 'desc' ]],
      excelFileName:'invoice_customer'
    });
  }

  const showListDetail = (searchInv,searchSo,searchCustomer,searchStatus,recDate,searchPeriod1,searchPeriod2) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('invoice.list.detail') }}",
      kolom:{!! $kolomDetail !!},
      arrColPrint:[1,2,3,4,5,6,7,8,9,10,11],
      columnDefs :[
        { width: '5%', targets: 0 },
        {
          targets: [5,6,7,8,9,10,11],
          render: $.fn.dataTable.render.number(',', '.', 2, ''),
          className: "text-right"
        },
      ],
      type:"POST",
      excelCustomize:function(xlsx) {
        let sheet = xlsx.xl.worksheets['sheet1.xml'];
        $('row:last c', sheet).attr('s','50');
      },
      excelMessageBottom:function () { return "Tanggal export : "+currentDate },
      dataSearch:  {
        searchInv:searchInv,
        searchSo:searchSo,
        searchCustomer:searchCustomer,
        searchStatus:searchStatus,
        recDate:recDate,
        searchPeriod1:searchPeriod1,
        searchPeriod2:searchPeriod2
        // searchPeriod:searchPeriod
      },
      orderColumn:[[ 1, 'asc' ],[ 2, 'asc' ]],
      excelFileName:'invoice_customer_detail'
    });
  }

  $('body').on('shown.bs.modal', '#reasonModalCancel', function () {
    $('input:visible:enabled:first', this).focus();
  })

  let href;
  $(document).on('click', '#cancelReasonButton', function(event) {
      event.preventDefault();
      href = $(this).data('href');
      $('#modalReasonCancel').attr("action", href);
  });

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
