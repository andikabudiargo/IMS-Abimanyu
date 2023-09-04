@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="article-index">
  <div class="row match-height">
    <!-- Bar Chart - Orders -->
    <div class="col-lg-3 col-md-3 col-6">
        <div class="card">
            <div class="card-body pb-50">
                <h6>Total BOM</h6>
                <h2 class="font-weight-bolder mb-1">{{ $bomTotal }}</h2>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-3 col-6">
        <div class="card card-tiny-line-stats">
            <div class="card-body pb-50">
                <h6>BOM Baru</h6>
                <h2 class="font-weight-bolder mb-1">{{ $bomBaru }}</h2>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-3 col-6">
      <div class="card card-tiny-line-stats">
          <div class="card-body pb-50">
              <h6>Status Validate</h6>
              <h2 class="font-weight-bolder mb-1">{{ $bomValidate }}</h2>
          </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-3 col-6">
      <div class="card card-tiny-line-stats">
          <div class="card-body pb-50">
              <h6>Status Approved</h6>
              <h2 class="font-weight-bolder mb-1">{{ $bomApprove }}</h2>
          </div>
      </div>
    </div>
  </div>
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
                <label for="searchBom">Bom Number</label>
                <input type="text" class="form-control text-uppercase" id="searchBom" name="searchBom" placeholder=""  />
              </div>
              <div class="form-group col-md-6">
                <label class="form-label" for="articleCode">Article FG</label>
                <select class="select2 form-control" id="articleCode" name="articleCode">
                    <option value="">All</option>
                    @foreach($articles as $val)
                        <option value="{{ $val->article_code }}" >{{ $val->article_alternative_code }} - {{ $val->article_desc }}</option>
                    @endforeach
                </select>
              </div>
              <div class="form-group col-md-3">
                <label class="form-label" for="status">Status</label>
                <select class="select2 form-control" id="status" name="status">
                    <option value="">All</option>
                    @foreach($status as $key=>$val)
                        <option value="{{ $key }}" >{{ $val }}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    @can('bom-create')
                    <a href="{{ route('bom.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
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
{{-- <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css/pages/dashboard-ecommerce.css') }}"> --}}
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script type="text/javascript">
  let searchBom = $("#searchBom");
  let articleCode = $("#articleCode");
  let status = $("#status");
  
  $(document).ready(function(){    

  });

  //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
    showList(searchBom.val(),articleCode.val(),status.val());
  });

  $("#btnSearch").click(function(e){
    showList(searchBom.val(),articleCode.val(),status.val());
  });

  const showList = (searchBom,articleCode,status) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('bom.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18],
      columnDefs :[
        { width: '5%', targets: 0 },
      ],
      dataSearch:  {
        searchBom:searchBom,
        articleCode:articleCode,
        status:status
      },
      orderColumn:[[ 1, 'desc' ]],
      excelFileName:'bom'
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
