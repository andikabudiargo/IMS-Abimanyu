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
<section id="ar-dashboard">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">AR Dashboard</h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0">
          <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="card-content collapse">
      <div class="card-body">
        <div class="form-row mb-2">
          <div class="form-group col-md-2">
            <label for="arCutoff">Per Tanggal (Cut-off)</label>
            <input type="text" class="form-control flatpickr-single" id="arCutoff" placeholder="DD-MM-YYYY">
          </div>
        </div>

        <div class="row ar-stat-row">
          <div class="col-md-3 col-sm-6 mb-1">
            <div class="ar-stat-card">
              <div>
                <h3 class="ar-stat-value text-dark" id="cardOpeningBalance">0</h3>
                <span class="ar-stat-label">Opening Balance</span>
              </div>
              <div class="ar-stat-icon ar-icon-neutral">
                <i data-feather="database"></i>
              </div>
            </div>
          </div>

          <div class="col-md-3 col-sm-6 mb-1">
            <div class="ar-stat-card">
              <div>
                <h3 class="ar-stat-value ar-text-blue" id="cardTotalAr">0</h3>
                <span class="ar-stat-label">Sales</span>
              </div>
              <div class="ar-stat-icon ar-icon-blue">
                <i data-feather="file-text"></i>
              </div>
            </div>
          </div>

          <div class="col-md-3 col-sm-6 mb-1">
            <div class="ar-stat-card">
              <div>
                <h3 class="ar-stat-value ar-text-green" id="cardTotalPaid">0</h3>
                <span class="ar-stat-label">Pembayaran</span>
              </div>
              <div class="ar-stat-icon ar-icon-green">
                <i data-feather="check-circle"></i>
              </div>
            </div>
          </div>

          <div class="col-md-3 col-sm-6 mb-1">
            <div class="ar-stat-card">
              <div>
                <h3 class="ar-stat-value ar-text-red" id="cardOutstanding">0</h3>
                <span class="ar-stat-label">Balance</span>
              </div>
              <div class="ar-stat-icon ar-icon-red">
                <i data-feather="alert-triangle"></i>
              </div>
            </div>
          </div>
        </div>
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
.ar-stat-card {
  background: #fff;
  border-radius: 10px;
  padding: 1.25rem 1.25rem;
  box-shadow: 0 2px 6px rgba(0,0,0,0.06);
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  height: 100%;
}
.ar-stat-value { font-weight: 700; margin-bottom: 0.25rem; }
.ar-stat-label { color: #6e6b7b; font-size: 0.9rem; }
.ar-stat-icon {
  width: 40px; height: 40px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.ar-stat-icon i { width: 18px; height: 18px; }

.ar-icon-neutral { background: #ececec; color: #5e5873; }
.ar-icon-blue    { background: rgba(115,103,240,0.12); color: #7367f0; }
.ar-icon-green   { background: rgba(40,199,111,0.12); color: #28c76f; }
.ar-icon-red     { background: rgba(234,84,85,0.12); color: #ea5455; }

.ar-text-blue  { color: #7367f0; }
.ar-text-green { color: #28c76f; }
.ar-text-red   { color: #ea5455; }
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

  const fmtRp = (v) => new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0 }).format(v);

const loadArDashboard = (cutoffDate) => {
  $.get("{{ route('invoice.analyticsAr') }}", { cutoffDate: cutoffDate }, function (res) {
    $('#cardOpeningBalance').text(fmtRp(res.openingBalance));
    $('#cardTotalAr').text(fmtRp(res.totalAr));
    $('#cardTotalPaid').text(fmtRp(res.totalPaid));
    $('#cardOutstanding').text(fmtRp(res.outstanding));
  });
};

initDatePicker(document.querySelector('#arCutoff'), {
  minDate: "01/01/2010",
  maxDate: "31/12/2030",
  dateFormat: "d-m-Y",
  defaultDate: new Date()
});

let arDashboardInitialized = false;
$('#ar-dashboard a[data-action="collapse"]').closest('.card').find('.card-content').on('shown.bs.collapse', function () {
  if (!arDashboardInitialized) {
    arDashboardInitialized = true;
    loadArDashboard($('#arCutoff').val());
  }
});

$('#arCutoff').on('change', function () {
  loadArDashboard($(this).val());
});

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
      arrColPrint:[1,2,4,5,6,7,8,9,10,11,12,13,14,16,18,20,21,22,23,24,26,27,28,29,30,31],
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