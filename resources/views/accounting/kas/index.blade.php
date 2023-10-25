@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
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
                <label for="seachVc">Voucher Number</label>
                <input type="text" class="form-control text-uppercase" id="seachVc" name="seachVc" placeholder=""  />
              </div>
              <div class="col-md-3 form-group">
                <label for="vcDate">Date</label>
                <input type="text" id="vcDate" name="vcDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-3">
                <label class="form-label" for="period">Period</label>
                <select class="select2 form-control" id="period" name="period" >
                    <option value=""></option>
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
              </div>
              <div class="form-group col-md-3">
                <label class="form-label" for="year">Year</label>
                <select class="select2 form-control" id="year" name="year" >
                  <option value=""></option>
                  @for ($i = 2000; $i <= 2050 ; $i++)
                      <option value="{{ $i }}">{{ $i }}</option>
                  @endfor
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-2"> 
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
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    {{-- @can('pettyCash-create') --}}
                    <a href="{{ route('kasPenerimaan.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
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
  td.wrapok {
      white-space:normal
  }
</style>
@endsection
@section('scripts')
<script type="text/javascript">
  $(document).ready(function(){    
    let href;
    $(document).on('click', '#deleteButton', function(event) {
        event.preventDefault();
        href = $(this).data('href');
        $('#modalConfirmation').attr("action", href);
    });
  });

  let showAlert = "{{ Session::get('alert') }}";

  if ( showAlert ){
    // showList();
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
      mode: 'range'
    });
  }

  $("#btnSearch").click(function(e){
    let seachVc = $("#seachVc").val();
    let vcDate = $("#vcDate").val();
    let period = $("#period").val();
    let year = $("#year").val();
    let searchStatus = $("#searchStatus").val();
    showList(seachVc,vcDate,period,year,searchStatus);

  });

  const showList = (seachVc,vcDate,period,year,searchStatus) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('kasPenerimaan.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7,8,9,10],
      columnDefs :[
        { width: '5%', targets: 0 },
        {
          targets: [ 4 ],
          render: $.fn.dataTable.render.number(',', '.', 2, ''),
          className: "text-right"
        },
        // {targets:[3], class:"wrapok"}
      ],
      dataSearch:  {
        seachVc:seachVc,
        vcDate:vcDate,
        period:period,
        year:year,
        searchStatus:searchStatus
      },
      orderColumn:[[ 9, 'asc' ]],
      excelFileName:'kas_penerimaan'
    });
  }

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
