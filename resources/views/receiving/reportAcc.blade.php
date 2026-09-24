@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
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
              <div class="form-group col-md-3">
                <label for="searchRec">Rec Number</label>
                <input type="text" class="form-control text-uppercase" id="searchRec" name="searchRec" />
              </div>
              <div class="form-group col-md-3">
                <label class="form-label" for="recType">Receive Type</label>
                <select class="select2 form-control" id="recType" name="recType">
                  <option value="">All</option>
                  <option value="NORMAL">Purchase Order</option>
                  <option value="NP">Non Purchase</option>
                  <option value="TRIAL">Trial &amp; Project</option>
                  <option value="JASA">Jasa</option>
                  <option value="TEMP">Receiving Sementara</option>
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
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label class="form-label" for="searchSupplier">Supplier</label>
                <select class="select2 form-control" id="searchSupplier" name="searchSupplier[]" multiple>
                    @foreach($suppliers as $val)
                        <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
                <small class="text-muted">Kosongkan = All</small>
              </div>
              <div class="form-group col-md-6">
                <label for="searchPo">PO Number</label>
                <select class="select2 form-control" id="searchPo" name="searchPo[]" multiple>
                  @foreach($poNumbers as $po)
                      <option value="{{ $po }}">{{ $po }}</option>
                  @endforeach
                </select>
                <small class="text-muted">Kosongkan = All</small>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="searchInv">Invoice Number</label>
                <select class="select2 form-control" id="searchInv" name="searchInv[]" multiple data-tags="true" data-token-separators='[",", " "]'></select>
                <small class="text-muted">Ketik nomor lalu Enter, bisa lebih dari satu. Kosongkan = All</small>
              </div>
              <div class="form-group col-md-6">
                <label for="searchVoucher">Voucher Number</label>
                <select class="select2 form-control" id="searchVoucher" name="searchVoucher[]" multiple data-tags="true" data-token-separators='[",", " "]'></select>
                <small class="text-muted">Ketik nomor lalu Enter, bisa lebih dari satu. Kosongkan = All</small>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-3">
                <label class="form-label" for="searchStatus">Rec Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                  <option value="">All</option>
                  @foreach($status as $index=>$val)
                      <option value="{{ $index }}">{{ $val }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group col-md-3">
                <label for="searchArticleCode">Article Code</label>
                <input type="text" class="form-control" id="searchArticleCode" name="searchArticleCode" />
              </div>
              <div class="form-group col-md-3">
                <label for="searchArticleDesc">Article Desc</label>
                <input type="text" class="form-control" id="searchArticleDesc" name="searchArticleDesc" />
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
        <button type="button" class="btn btn-primary" id="btnSummary" title="Tekan tombol untuk melihat data summary" style="display:none;">Summary</button>
        <button type="button" class="btn btn-primary" id="btnDetail" title="Tekan tombol untuk melihat data detail" style="display:none;">Detail</button>
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
@section('scripts')
<script type="text/javascript">

  let btnSummary = $('#btnSummary');
  let btnDetail = $('#btnDetail');
  let isSummary = false;

  const filters = () => ({
    searchRec: $("#searchRec").val(),
    recType: $("#recType").val(),
    recDate: $("#recDate").val(),
    doDate: $("#doDate").val(),
    searchSupplier: $("#searchSupplier").val(),
    searchPo: $("#searchPo").val(),
    searchInv: $("#searchInv").val(),
    searchVoucher: $("#searchVoucher").val(),
    searchStatus: $("#searchStatus").val(),
    searchArticleCode: $("#searchArticleCode").val(),
    searchArticleDesc: $("#searchArticleDesc").val()
  });

  const reload = () => (isSummary ? showSummary() : showList());

  $('a[data-action="reload"]').on('click', reload);

  let rangePickr = $('.flatpickr-range');
  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range'
    });
  }

  $("#btnSearch").click(function(e){
    isSummary = false;
    btnDetail.hide(); btnSummary.show();
    reload();
  });

  btnSummary.click(function(){
    isSummary = true;
    btnSummary.hide(); btnDetail.show();
    reload();
  });

  btnDetail.click(function(){
    isSummary = false;
    btnDetail.hide(); btnSummary.show();
    reload();
  });

  const resetTable = () => {
    if ($('#detailedTable tr').length >0){
        $('#detailedTable').DataTable().destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
  }

  const showSummary = () => {
    resetTable();
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('receiving.list.report.acc.summary') }}",
      kolom:{!! $kolomSummary !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15],
      columnDefs :[
        { targets: [ 4,5,6,7 ], render: dateRender },
      ],
      excelDates:true,
      dataSearch: filters(),
      type:'POST',
      orderColumn:[],
      excelFileName:'receiving_report_acc_summary'
    });
  }

  const showList = () => {
    resetTable();
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('receiving.list.report.acc') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31],
      columnDefs :[
        { width: '5%', targets: 0 },
        {
          targets: [ 13,14,15,16,17,18,19,24 ],
          render: $.fn.dataTable.render.number(',','.',2,''),
          className: "text-right"
        },
        { targets: [ 5,6,21,23 ], render: dateRender },
      ],
      excelDates:true,
      dataSearch: filters(),
      type:'POST',
      orderColumn:[], // pertahankan urutan dari server (DO Date terkecil dulu)
      excelFileName:'receiving_report_acc'
    });
  }

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

</script>
@endsection
