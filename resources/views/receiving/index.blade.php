@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="receiving-index">
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
                <label for="searchRec">Rec Number</label>
                <input type="text" class="form-control text-uppercase" id="searchRec" name="searchRec" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label for="searchPo">PO Number</label>
                <input type="text" class="form-control text-uppercase" id="searchPo" name="searchPo" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label for="searchInv">Invoice Number</label>
                <input type="text" class="form-control text-uppercase" id="searchInv" name="searchInv" placeholder=""  />
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
                <label for="recDate">Receiving Date</label>
                <input type="text" id="recDate" name="recDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="col-md-3 form-group">
                <label for="doDate">DO Date</label>
                <input type="text" id="doDate" name="doDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-2"> 
                <label class="form-label" for="searchStatus">Rec Status</label>
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
                    @can('receiving-create')
                    <a href="{{ route('receiving.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                    @endcan
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
  let searchRec = $("#searchRec");
  let searchPo = $("#searchPo");
  let searchInv = $("#searchInv");
  let searchSupplier = $("#searchSupplier"); 
  let searchStatus = $("#searchStatus");
  let recDate = $("#recDate");
  let doDate = $("#doDate");
  let btnSummary = $('#btnSummary');
  let btnDetail = $('#btnDetail');

  $(document).ready(function(){    
    // let href;
    // $(document).on('click', '#deleteButton', function(event) {
    //     event.preventDefault();
    //     href = $(this).data('href');
    //     console.log(href);
    //     $('#modalConfirmationCancel').attr("action", href);
    // });
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
    showList(searchRec.val(),searchPo.val(),searchInv.val(),searchSupplier.val(),searchStatus.val(),recDate.val(),doDate.val());
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
    showList(searchRec.val(),searchPo.val(),searchInv.val(),searchSupplier.val(),searchStatus.val(),recDate.val(),doDate.val());
  });

  btnSummary.click(function(e){
    btnSummary.hide();
    btnDetail.show();
    showList(searchRec.val(),searchPo.val(),searchInv.val(),searchSupplier.val(),searchStatus.val(),recDate.val(),doDate.val());
  });

  btnDetail.click(function(e){
    btnSummary.show();
    btnDetail.hide();
    showListDetail(searchRec.val(),searchPo.val(),searchInv.val(),searchSupplier.val(),searchStatus.val(),recDate.val(),doDate.val());
  });

  const showList = (searchRec,searchPo,searchInv,searchSupplier,searchStatus,recDate,doDate) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }

    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('receiving.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7,8,9,10,11],
      columnDefs :[
        { width: '5%', targets: 0 }
      ],
      dataSearch:  {
        searchRec:searchRec,
        searchPo:searchPo,
        searchInv:searchInv,
        searchSupplier:searchSupplier,
        searchStatus:searchStatus,
        recDate:recDate,
        doDate:doDate
      },
      orderColumn:[[ 3, 'asc' ]],
      excelFileName:'receiving'
    });
  }

  const showListDetail = (searchRec,searchPo,searchInv,searchSupplier,searchStatus,recDate,doDate) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }

    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('receiving.list.detail') }}",
      kolom:{!! $kolomDetail !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20],
      columnDefs :[
        { width: '5%', targets: 0 },
        {
          targets: [ 11,12,13,14,15 ],
          render: $.fn.dataTable.render.number(',', '.',2, ''),
          className: "text-right"
        },
        
      ],
      dataSearch:  {
        searchRec:searchRec,
        searchPo:searchPo,
        searchInv:searchInv,
        searchSupplier:searchSupplier,
        searchStatus:searchStatus,
        recDate:recDate,
        doDate:doDate
      },
      orderColumn:[[ 21, 'asc' ]],
      excelFileName:'receiving_detail'
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
