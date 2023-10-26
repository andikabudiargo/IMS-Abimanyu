@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
{{-- @include('partials.alert') --}}
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
        <form class="needs-validation" novalidate autocomplete="off"> 
            <div class="form-row">
              <div class="form-group col-md-3"> 
                <label for="searchDn">Delivery Number</label>
                <input type="text" class="form-control text-uppercase" id="searchDn" name="searchDn" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label for="searchSo">SO Number</label>
                <input type="text" class="form-control text-uppercase" id="searchSo" name="searchSo" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchCustomer">Customer</label>
                <select class="select2 form-control" id="searchCustomer" name="searchCustomer">
                    <option value="">All</option>
                    @foreach($customers as $val)
                        <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
              </div>
              <div class="col-md-3 form-group">
                <label for="dnDate">Delivery Date</label>
                <input type="text" id="dnDate" name="dnDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-2"> 
                <label class="form-label" for="searchStatus">Delivery Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                    <option value="">All</option>
                    @foreach($status as $index=>$val)
                        <option value="{{ $index }}" {{ $statusKu == $index ? 'selected' : '' }}>{{ $val }}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    @can('delivery-create')
                      <a href="{{ route('delivery.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
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
  .text-hijau {
    color:rgb(6, 248, 22);
  }
</style>
@endsection
@section('scripts')
<script type="text/javascript">
  let searchDn = $("#searchDn");
  let searchSo = $("#searchSo");
  let searchCustomer = $("#searchCustomer"); 
  let searchStatus = $("#searchStatus");
  let dnDate = $("#dnDate");

  $(document).ready(function(){    
    let href;
    $(document).on('click', '#deleteButton', function(event) {
        event.preventDefault();
        href = $(this).data('href');
        console.log(href);
        $('#modalConfirmationCancel').attr("action", href);
    });
    showList(searchDn.val(),searchSo.val(),searchCustomer.val(),searchStatus.val(),dnDate.val());
  });

  // let showAlert = "{{ Session::get('alert') }}";

  // if ( showAlert ){
  //   showList();
  //   $("#alert-message-alert").fadeTo(5000, 500).slideUp(500, function(){
  //     $("#alert-message-alert").slideUp(500);
  //   });
  // }

  //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
    showList(searchDn.val(),searchSo.val(),searchCustomer.val(),searchStatus.val(),dnDate.val());
  });

  let rangePickr = $('.flatpickr-range');
  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range'
    });
  }

  $("#btnSearch").click(function(e){
    showList(searchDn.val(),searchSo.val(),searchCustomer.val(),searchStatus.val(),dnDate.val());
  });

  const showList = (searchDn,searchSo,searchCustomer,searchStatus,dnDate) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('delivery.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[2,3,4,5,6,7,8,9,10,11],
      columnDefs :[
        { width: '5%', targets: 0 },
      ],
      dataSearch:  {
        searchDn:searchDn,
        searchSo:searchSo,
        searchCustomer:searchCustomer,
        searchStatus:searchStatus,
        dnDate:dnDate
      },
      orderColumn:[[ 11, 'asc' ],[ 1, 'asc' ]],
      excelFileName:'delivery_note'
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
