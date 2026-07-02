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
              <div class="form-group col-md-4"> 
                <label for="searchPr">Request Number</label>
                <input type="text" class="form-control text-uppercase" id="searchPr" name="searchPr" placeholder=""  />
              </div>
              <div class="col-md-4 form-group">
                <label for="requestDate">Date</label>
                <input type="text" id="requestDate" name="requestDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-4">
                <label class="form-label" for="orderType">PO Type*</label>
                <select class="select2 form-control" id="orderType" name="orderType">
                      <option value="">All</option>
                      <option value="std">Standard</option>
                      {{-- <option value="sub">Subcontracting</option> --}}
                      <option value="tso">Target SO</option>
                      {{-- <option value="rm">Raw Material</option> --}}
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-4"> 
                <label class="form-label" for="searchStatus">Request Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                    <option value="">All</option>
                    @foreach($status as $index=>$val)
                        <option value="{{ $index }}">{{ $val }}</option>
                    @endforeach
                </select>
              </div>
              <div class="form-group col-md-4"> 
                <label class="form-label" for="dept">Departemen</label>
                <select class="select2 form-control" id="dept" name="dept">
                    <option value="">All</option>
                    @foreach($depts as $val)
                        <option value="{{ $val->code }}">{{ $val->name }}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    @can('purchaseRequest-create')
                    <a href="{{ route('purchaseRequest.createv2') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
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
@include('partials.modals')
@include('partials.delete-modal')
@endsection
@section('styles')
<style>

  .spinner {
    display: none;
    margin-left: 10px;
    width: 20px;
    height: 20px;
    /* border: 3px solid #f3f3f3; */
    border-top: 3px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }
  
  .spinner-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
  }
  
  .spinner-overlay .spinner {
    width: 50px;
    height: 50px;
    border-width: 1px;
    margin-left: 0;
  }
  
  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

</style>
@endsection
@section('scripts')
@include('purchaseRequest.modalRevisi')
<script type="text/javascript">

  let searchPr = document.querySelector("#searchPr");
  let orderType = document.querySelector("#orderType"); 
  let searchStatus = document.querySelector("#searchStatus");
  let dept = document.querySelector("#dept");
  let requestDate = document.querySelector("#requestDate");
  let search = document.querySelector('#btnSearch');
  let refresh = document.querySelector('a[data-action="reload"]');
  let rangePickr = document.querySelector('.flatpickr-range');
  let btnSummary = document.querySelector('#btnSummary');
  let btnDetail = document.querySelector('#btnDetail');

  $(document).ready(function(){    
    validateFormToast("modalReasonRevisionTso");
    validateFormToast("modalReasonRevisionPr");
  });

  document.addEventListener("DOMContentLoaded", function(event) {
    btnSummary.style.display = "none";
    btnDetail.style.display = "none";
  });
  
  initDatePicker(rangePickr,{
    minDate: "01/01/2010",
    maxDate: "31/12/2030",
    dateFormat: "d-m-Y",
    mode: "range"
  });

  //refresh di cards
  refresh.addEventListener("click",function(){
    btnDetail.style.display = "block";
    btnSummary.style.display = "none";
    showList(searchPr.value,orderType.value,searchStatus.value,requestDate.value,dept.value);
  })

  search.addEventListener("click", function(){
    btnDetail.style.display = "block";
    btnSummary.style.display = "none";
    showList(searchPr.value,orderType.value,searchStatus.value,requestDate.value,dept.value);
  });

  btnSummary.addEventListener("click", function(){
    btnSummary.style.display = "none";
    btnDetail.style.display = "block";
    showList(searchPr.value,orderType.value,searchStatus.value,requestDate.value,dept.value);
  });
  
  btnDetail.addEventListener("click", function(){
    btnSummary.style.display = "block";
    btnDetail.style.display = "none";
    showListDetail(searchPr.value,orderType.value,searchStatus.value,requestDate.value,dept.value);
  });

  const showList = (searchPr,orderType,searchStatus,requestDate,dept) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route("purchaseRequest.list") }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7,8,9,10,11],
      columnDefs :[
        { width: '5%', targets: 0 },
      ],
      dataSearch:  {
        searchPr:searchPr,
        orderType:orderType,
        searchStatus:searchStatus,
        requestDate:requestDate,
        dept:dept
      },
      orderColumn:[[ 1, 'desc' ]],
      excelFileName:'purchase_request'
    });
  }

  const showListDetail = (searchPr,orderType,searchStatus,requestDate,dept) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('purchaseRequest.list.detail') }}",
      kolom:{!! $kolomDetail !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18],
      columnDefs :[
        { width: '5%', targets: 0 },
        { className: 'text-right','targets': [4,5] },
      ],
      dataSearch:  {
        searchPr:searchPr,
        orderType:orderType,
        searchStatus:searchStatus,
        requestDate:requestDate,
        dept:dept
      },
      // orderColumn:[[ 2, 'asc' ]],
      excelFileName:'purchase_request'
    });
  }

  let href;
  $(document).on('click', '#revisionReasonButtonPr', function(event) {
      event.preventDefault();
      href = $(this).data('href');
      $('#modalReasonRevisionPr').attr("action", href);
  });

  $('#revisionReasonButtonPr').on('show.bs.modal', function() {
    $(this).find('form')[0].reset();
  });

  $(document).on('click', '#revisionReasonButtonTso', function(event) {
      event.preventDefault();
      href = $(this).data('href');
      $('#modalReasonRevisionTso').attr("action", href);
  });

  $('#revisionReasonButtonTso').on('show.bs.modal', function() {
    $(this).find('form')[0].reset();
  });

  $("#btnRevisionTso").click(function (e) {
    e.preventDefault();

    if (!$("#modalReasonRevisionTso")[0].checkValidity()){
        $('.disabled-el').removeAttr('disabled');
        $("#modalReasonRevisionTso").submit();
    }else{
        $('.disabled-el').removeAttr('disabled');
        $('#spinner').css('display', 'inline-block');
        $('#modalReasonRevisionTso').find('button').prop('disabled', true);
        lockModal('#reasonModalRevisionTso');
        setTimeout(function() {
          $('#spinner').css('display', 'none');
          unlockModal('#reasonModalRevisionTso');
          $('#modalReasonRevisionTso').find('button').prop('disabled', false);
        }, 50000);
        $("#modalReasonRevisionTso").submit();
    }
  });

  $("#btnRevisionPr").click(function (e) {
    e.preventDefault();

    if (!$("#modalReasonRevisionPr")[0].checkValidity()){
        $('.disabled-el').removeAttr('disabled');
        $("#modalReasonRevisionPr").submit();
    }else{
        $('.disabled-el').removeAttr('disabled');
        $('#spinnerPr').css('display', 'inline-block');
        $('#modalReasonRevisionPr').find('button').prop('disabled', true);
        lockModal('#reasonModalRevisionPr');
        setTimeout(function() {
          $('#spinnerPr').css('display', 'none');
          $('#modalReasonRevisionPr').find('button').prop('disabled', false);
          unlockModal('#reasonModalRevisionPr');
        }, 50000);
        $("#modalReasonRevisionPr").submit();
    }
  });

  $(document).on('click', '#rejectReasonButton', function(event) {
      event.preventDefault();
      href = $(this).data('href');
      $('#modalReasonReject').attr("action", href);
  });

  function lockModal(modalId) {
    const modal = $(modalId);
    modal.data('bs.modal')._config.backdrop = 'static';
    modal.data('bs.modal')._config.keyboard = false;
    modal.find('.close, .btn-close').prop('disabled', true);
  }

  function unlockModal(modalId) {
    const modal = $(modalId);
    modal.data('bs.modal')._config.backdrop = true;
    modal.data('bs.modal')._config.keyboard = true;
    modal.find('.close, .btn-close').prop('disabled', false);
  }

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });


  
    
</script>
@endsection
