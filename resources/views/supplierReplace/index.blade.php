@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="replace-index">
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
                <label for="searchReplace">Replace Number</label>
                <input type="text" class="form-control text-uppercase" id="searchReplace" name="searchReplace" />
              </div>
              <div class="form-group col-md-3">
                <label for="searchReturn">Return Number</label>
                <input type="text" class="form-control text-uppercase" id="searchReturn" name="searchReturn" />
              </div>
              <div class="form-group col-md-3">
                <label class="form-label" for="searchSupplier">Supplier</label>
                <select class="select2 form-control" id="searchSupplier" name="searchSupplier">
                    <option value="">All</option>
                    @foreach($suppliers as $val)
                        <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
              </div>
              <div class="col-md-3 form-group">
                <label for="replaceDate">Replace Date</label>
                <input type="text" id="replaceDate" name="replaceDate" class="form-control flatpickr-range" placeholder="DD-MM-YYYY to DD-MM-YYYY" />
              </div>
              <div class="form-group col-md-3">
                <label class="form-label" for="searchStatus">Status</label>
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
                    <button type="button" class="btn btn-primary" id="btnSearch" name="btnSearch">Search</button>
                    <a href="{{ route('supplierReplace.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                </div>
            </div>
        </form>
      </div>
    </div>
  </div>
</section>
<section id="table-replace">
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
        <button type="button" class="btn btn-primary" id="btnDetail" name="btnDetail">Detail</button>
        <button type="button" class="btn btn-primary" id="btnSummary" name="btnSummary">Summary</button>
        <div class="row">
            <div class="col-sm-12">
              <div class="table-responsive">
                <table id="detailedTable" class="table mb-0">
                  <thead class="thead-light"></thead>
                </table>
              </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- Modal reason: Cancel --}}
<div class="modal fade" id="reasonModalCancel" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="modalReasonCancel" method="POST" action="">
      @csrf
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Cancel Reason</h5></div>
        <div class="modal-body">
            <textarea name="reason" class="form-control" rows="2" placeholder="Alasan cancel..." required></textarea>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-danger">Cancel Document</button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- Modal reason: Revision --}}
<div class="modal fade" id="reasonModalRevision" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="modalReasonRevision" method="POST" action="">
      @csrf
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Revision Reason</h5></div>
        <div class="modal-body">
            <textarea name="reason" class="form-control" rows="2" placeholder="Alasan revisi..." required></textarea>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Create Revision</button>
        </div>
      </div>
    </form>
  </div>
</div>

@include('partials.delete-modal')
@endsection
@section('scripts')
<script type="text/javascript">
  let searchReplace  = $("#searchReplace");
  let searchReturn   = $("#searchReturn");
  let searchSupplier = $("#searchSupplier");
  let searchStatus   = $("#searchStatus");
  let replaceDate    = $("#replaceDate");
  let btnSummary     = $('#btnSummary');
  let btnDetail      = $('#btnDetail');

  $(document).ready(function(){
    let href;
    $(document).on('click', '#cancelReasonButton', function(e){
        e.preventDefault();
        href = $(this).data('href');
        $('#modalReasonCancel').attr("action", href);
    });
    $(document).on('click', '#revisionReasonButton', function(e){
        e.preventDefault();
        href = $(this).data('href');
        $('#modalReasonRevision').attr("action", href);
    });

    btnSummary.hide();
    btnDetail.hide();
  });

  $('a[data-action="reload"]').on('click', function(){
    btnSummary.hide(); btnDetail.show();
    showList(searchReplace.val(),searchReturn.val(),searchSupplier.val(),searchStatus.val(),replaceDate.val());
  });

  rangePickr = $('.flatpickr-range');
  if (rangePickr.length){ rangePickr.flatpickr({ dateFormat:"d-m-Y", mode:'range' }); }

  $("#btnSearch").click(function(){
    btnSummary.hide(); btnDetail.show();
    showList(searchReplace.val(),searchReturn.val(),searchSupplier.val(),searchStatus.val(),replaceDate.val());
  });

  btnSummary.click(function(){
    btnSummary.hide(); btnDetail.show();
    showList(searchReplace.val(),searchReturn.val(),searchSupplier.val(),searchStatus.val(),replaceDate.val());
  });

  btnDetail.click(function(){
    btnSummary.show(); btnDetail.hide();
    showListDetail(searchReplace.val(),searchReturn.val(),searchSupplier.val(),searchStatus.val(),replaceDate.val());
  });

  const showList = (searchReplace,searchReturn,searchSupplier,searchStatus,replaceDate) => {
    if ($('#detailedTable tr').length > 0){
        let table = $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('supplierReplace.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7,8,9,10,11],
      columnDefs:[ { width:'5%', targets:0 } ],
      dataSearch:{ searchReplace,searchReturn,searchSupplier,searchStatus,replaceDate },
      orderColumn:[[ 1, 'asc' ]],
      excelFileName:'supplier_replace'
    });
  }

  const showListDetail = (searchReplace,searchReturn,searchSupplier,searchStatus,replaceDate) => {
    if ($('#detailedTable tr').length > 0){
        let table = $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('supplierReplace.list.detail') }}",
      kolom:{!! $kolomDetail !!},
      type:'POST',
      arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15],
      columnDefs:[
        { targets:[8,9,10], render: $.fn.dataTable.render.number(',','.',2,''), className:"text-right" }
      ],
      dataSearch:{ searchReplace,searchReturn,searchSupplier,searchStatus,replaceDate },
      orderColumn:[[ 0, 'asc' ]],
      excelFileName:'supplier_replace_detail'
    });
  }

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection