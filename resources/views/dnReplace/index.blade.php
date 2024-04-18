@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="receiving-index">
  <div class="card">
    <div class="card-header">  
      {{-- <h4 class="card-title">Filter <small class="text-muted"> {{ $lockDate ? "Locked From : ".$lockDate : '' }}</small></h4> --}}
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
                <label for="searchReplace">Replace Number</label>
                <input type="text" class="form-control text-uppercase" id="searchReplace" name="searchReplace" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label for="searchReturn">Return Number</label>
                <input type="text" class="form-control text-uppercase" id="searchReturn" name="searchReturn" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchCustomer">Customer</label>
                <select class="select2 form-control" id="searchCustomer" name="searchCustomer">
                    <option value="">All</option>
                    @foreach($supps as $val)
                        <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
              </div>
              <div class="col-md-3 form-group">
                <label for="replaceDate">Replace Date</label>
                <input type="text" id="replaceDate" name="replaceDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-2"> 
                <label class="form-label" for="replaceStatus">Replace Status</label>
                <select class="select2 form-control" id="replaceStatus" name="replaceStatus">
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
                    {{-- @can('receiving-create') --}}
                    <a href="{{ route('dnReplace.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                    {{-- @endcan --}}
                </div>
            </div>
        </form>
      </div>
    </div>
  </div>
</section>
<section id="table-receiving">
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
              <div class="table-responsive">
                <table id="detailedTable" class="table mb-0">
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
  let searchReplace = $("#searchReplace");
  let searchReturn = $("#searchReturn");
  let searchCustomer = $("#searchCustomer"); 
  let searchStatus = $("#searchStatus");
  let replaceDate = $("#replaceDate");
  let btnSummary = $('#btnSummary');
  let btnDetail = $('#btnDetail');

  $(document).ready(function(){    
    let href;
    $(document).on('click', '#cancelReasonButton', function(event) {
        event.preventDefault();
        href = $(this).data('href');
        $('#modalReasonCancel').attr("action", href);
    });

    btnSummary.hide();
    btnDetail.hide();

  });

  //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
    btnSummary.hide();
    btnDetail.show();
    showList(searchReplace.val(),searchReturn.val(),searchCustomer.val(),searchStatus.val(),replaceDate.val());
  });

  rangePickr = $('.flatpickr-range');
  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range'
    });
  }

  $("#btnSearch").click(function(e){
    btnSummary.hide();
    btnDetail.show();
    showList(searchReplace.val(),searchReturn.val(),searchCustomer.val(),searchStatus.val(),replaceDate.val());
  });

  btnSummary.click(function(e){
    btnSummary.hide();
    btnDetail.show();
    showList(searchReplace.val(),searchReturn.val(),searchCustomer.val(),searchStatus.val(),replaceDate.val());
  });

  btnDetail.click(function(e){
    btnSummary.show();
    btnDetail.hide();
    showListDetail(searchReplace.val(),searchReturn.val(),searchCustomer.val(),searchStatus.val(),replaceDate.val());
  });

  const showList = (searchReplace,searchReturn,searchCustomer,searchStatus,replaceDate) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('dnReplace.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7,8],
      columnDefs :[
        { width: '5%', targets: 0 }
      ],
      dataSearch:  {
        searchReplace:searchReplace,
        searchReturn:searchReturn,
        searchCustomer:searchCustomer,
        searchStatus:searchStatus,
        replaceDate:replaceDate
      },
      orderColumn:[[ 8, 'desc' ]],
      excelFileName:'dn_repalce'
    });
  }

  const showListDetail = (searchReplace,searchReturn,searchCustomer,searchStatus,replaceDate) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('dnReplace.list.detail') }}",
      kolom:{!! $kolomDetail !!},
      type:'POST',
      arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12],
      columnDefs :[
        { width: '5%', targets: 0 },
        {
          targets: [ 7 ],
          render: $.fn.dataTable.render.number(',', '.',2, ''),
          className: "text-right"
        },
        
      ],
      dataSearch:  {
        searchReplace:searchReplace,
        searchReturn:searchReturn,
        searchCustomer:searchCustomer,
        searchStatus:searchStatus,
        replaceDate:replaceDate
      },
      orderColumn:[[ 12, 'asc' ]],
      excelFileName:'dn_replace_detail'
    });
  }

  let href;
  $(document).on('click', '#revisionReasonButton', function(event) {
      event.preventDefault();
      href = $(this).data('href');
      $('#modalReasonRevision').attr("action", href);
  });

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
