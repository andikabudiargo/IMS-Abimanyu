@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
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
              <div class="form-group col-md-3"> 
                <label for="searchNoAsset">No Asset</label>
                <input type="text" class="form-control text-uppercase" id="searchNoAsset" name="searchNoAsset" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label for="searchName">Nama Asset</label>
                <input type="text" class="form-control text-uppercase" id="searchName" name="searchName" placeholder=""  />
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    {{-- @can('asset-create') --}}
                    <a href="{{ route('asset.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
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
</style>
@endsection

@section('scripts')
<script type="text/javascript">
  let currentDate = todayDate('dd-mm-yyyy');  
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
    let searchNoAsset = $("#searchNoAsset").val();
    let searchName = $("#searchName").val();
    showList(searchNoAsset,searchName);
  });

  $("#btnSearch").click(function(e){
    let searchNoAsset = $("#searchNoAsset").val();
    let searchName = $("#searchName").val();
    showList(searchNoAsset,searchName);
  });

  const showList = (searchNoAsset,searchName) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('asset.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,3,4,5,6,7,8],
      columnDefs :[
        { width: '5%', targets: 0 },
        {
          targets: [ 3,4,7,8 ],
          render: $.fn.dataTable.render.number(',', '.', 0, ''),
          className: "text-right"
        },        
      ],
      type:"POST",
      dataSearch:  {
        searchNoAsset:searchNoAsset,
        searchName:searchName,
      },
      orderColumn:[[ 1, 'desc' ]],
      excelFileName:'assets'
    });
  }

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
