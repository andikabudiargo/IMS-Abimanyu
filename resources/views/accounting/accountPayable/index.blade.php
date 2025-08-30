@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="article-index">
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
              <div class="form-group col-md-2"> 
                <label for="searchPo">PO Number</label>
                <input type="text" class="form-control text-uppercase" id="searchPo" name="searchPo" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label for="searchAp">Ap Number</label>
                <input type="text" class="form-control text-uppercase" id="searchAp" name="searchAp" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchSupplier">Supplier</label>
                <select class="select2 form-control" id="searchSupplier" name="searchSupplier">
                    <option value="">All</option>
                    @foreach($supps as $val)
                        <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
              </div>
              <div class="col-md-3 form-group">
                <label for="apDate">Ap Date Invoice</label>
                <input type="text" id="apDate" name="apDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-2"> 
                <label class="form-label" for="searchStatus">Invoice Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                    <option value="">All</option>
                    @foreach($status as $index=>$val)
                        <option value="{{ $index }}">{{ $val }}</option>
                    @endforeach
                </select>
              </div>
              <div class="col-md-1 form-group">
                <label class="form-label" for="apPeriod1">Period Awal</label>
                <select class="select2 form-control" id="apPeriod1" name="apPeriod1" >
                  <option value=""></option>
                  @for ($i = 1; $i <= 12; $i++)
                      <option value="{{ $i }}">{{ $i }}</option>
                  @endfor
                </select>
              </div>
              <div class="col-md-1 form-group">
                <label class="form-label" for="apPeriod2">Period Akhir</label>
                <select class="select2 form-control" id="apPeriod2" name="apPeriod2" >
                  <option value=""></option>
                  @for ($i = 1; $i <= 12; $i++)
                      <option value="{{ $i }}">{{ $i }}</option>
                  @endfor
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    @can('ap-create')
                    <a href="{{ route('accountPayable.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
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
      dataSearch('summary');
  });

  const rangePickr = $('.flatpickr-range');
  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range'
    });
  }

  function dataSearch($type){
    let searchPo = $("#searchPo").val();
    let searchAp = $("#searchAp").val();
    let searchSupplier = $("#searchSupplier").val(); 
    let searchStatus = $("#searchStatus").val();
    let apDate = $("#apDate").val();
    let apPeriod1 = $("#apPeriod1").val();
    let apPeriod2 = $("#apPeriod2").val();

    if($type == 'detail'){
      btnDetail.addClass('d-none');
      btnSummary.removeClass('d-none');
      showListDetail(searchPo,searchAp,searchSupplier,searchStatus,apDate,apPeriod1,apPeriod2);
    }

    if($type == 'summary'){
      btnDetail.addClass('d-none');
      btnSummary.removeClass('d-none');
      showList(searchPo,searchAp,searchSupplier,searchStatus,apDate,apPeriod1,apPeriod2);  
    }
    
  }

  btnDetail.click(function(){
    dataSearch('detail');
  });

  btnSummary.click(function(){
    dataSearch('summary');
  });

  $("#btnSearch").click(function(e){
    btnSummary.addClass('d-none');
    btnDetail.addClass('d-none');
    dataSearch('summary');
  });

  const showList = (searchPo,searchAp,searchSupplier,searchStatus,apDate,apPeriod1,apPeriod2) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('accountPayable.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,4,5,6,7,8,9,10,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34],
      columnDefs :[
        { width: '5%', targets: 0 },
        {
          targets: [ 18,19,20,21,22,23,24,25,28,29],
          render: $.fn.dataTable.render.number(',', '.', 2, ''),
          className: "text-right"
        },
        {
          targets: [ 12 ],
          render: function ( data, type, full, meta ) {
            return '\u200C'+data;
          }
        }
        
      ],
      type:"POST",
      excelCustomize:function(xlsx) {
        let sheet = xlsx.xl.worksheets['sheet1.xml'];
        $('row:last c', sheet).attr('s','50');
      },
      excelMessageBottom:function () { return "Tanggal export : "+currentDate },
      dataSearch:  {
        searchPo:searchPo,
        searchAp:searchAp,
        searchSupplier:searchSupplier,
        searchStatus:searchStatus,
        apDate:apDate,
        apPeriod1:apPeriod1,
        apPeriod2:apPeriod2
      },
      initComplete: function() {
        let api = this.api();
        if (api.data().length > 0) {
          btnDetail.removeClass('d-none');
          btnSummary.addClass('d-none');
        }
      },
      orderColumn:[[ 34, 'desc' ]],
      excelFileName:'invoice_supplier'
    });
  }

  const showListDetail = (searchPo,searchAp,searchSupplier,searchStatus,apDate,apPeriod1,apPeriod2) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('accountPayable.list.detail') }}",
      kolom:{!! $kolomDetail !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,10,11,12,13,14,15,16,17,18,19,20,21],
      columnDefs :[
        { width: '5%', targets: 0 },
        {
          targets: [19,20,21],
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
        searchPo:searchPo,
        searchAp:searchAp,
        searchSupplier:searchSupplier,
        searchStatus:searchStatus,
        apDate:apDate,
        apPeriod1:apPeriod1,
        apPeriod2:apPeriod2
      },
      orderColumn:[[ 0, 'asc' ],[ 1, 'asc' ]],
      excelFileName:'invoice_supplier_det'
    });
  }

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
