@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="article-index">
    <div class="row">
      <div class="col-12">
        <div class="card">
          {{-- <div class="card-header">  
            <div class="card-title">@yield('title')
            </div>
          </div> --}}
          <div class="card-body">
            <form class="needs-validation" novalidate>
                <div class="form-row">
                    <div class="form-group col-md-4"> 
                      <label for="searchName">Name</label>
                      <input type="text" class="form-control text-uppercase" id="searchName" name="searchName" placeholder="" />
                    </div>
                    <div class="form-group col-md-4"> 
                      <label class="form-label" for="searchGroup">Group</label>
                      <select class="select2 form-control" id="searchGroup" name="searchGroup">
                          <option value="">All</option>
                          @foreach($groups as $val)
                              <option value="{{$val->code}}">{{$val->code}} - {{$val->name}}</option>
                          @endforeach
                      </select>
                    </div>
                    <div class="form-group col-md-4"> 
                      <label class="form-label" for="searchSupplier">Supplier/Customer</label>
                      <select class="select2 form-control" id="searchSupplier" name="searchSupplier">
                          <option value="">All</option>
                          @foreach($supps as $val)
                              <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                          @endforeach
                      </select>
                    </div>
                    <div class="form-group col-md-4"> 
                      <label class="form-label" for="searchType">Article Type</label>
                      <select class="select2 form-control" id="searchType" name="searchType">
                          <option value="">All</option>
                          @foreach($types as $val)
                            <option value="{{$val->code}}" >{{$val->code}} - {{$val->name}}</option>
                          @endforeach
                      </select>
                    </div>
                    <div class="form-group col-md-4"> 
                      <label class="form-label" for="searchStatus">Status</label>
                      <select class="select2 form-control" id="searchStatus" name="searchStatus">
                          <option value="">All</option>
                          <option value="1" selected>Requested</option>
                          <option value="2" >Approved</option>
                          <option value="3" >Decline</option>
                      </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12"> 
                        <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                        @can('article-create')
                        <a href="{{ route('article.request.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                        @endcan
                    </div>
                </div>
            </form>
          </div>
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
  let $body = $('body');
  let name = $("#searchName");
  let status = $("#seachStatus");
  let group = $("#searchGroup");
  let supp = $("#searchSupplier");
  let type = $("#searchType");

  $(document).ready(function(){
    $(document).on('click', '#deleteButton', function(event) {
        event.preventDefault();
        let href = $(this).data('href');
        $('#modalConfirmation').attr("action", href);        
    });
    showList(name.val(),status.val(),group.val(),supp.val(),type.val());
  });

  //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
      showList(name.val(),status.val(),group.val(),supp.val(),type.val());
  });

  $("#btnSearch").click(function(e){
    showList(name.val(),status.val(),group.val(),supp.val(),type.val());
  });

  const showList = (name,status,group,supp,type) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('article.request.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16],
      columnDefs :[
        { width: '5%', targets: 0 },
        { className: 'text-right','targets': [ 5,7,8,9 ] },
      ],
      dataSearch:  {
        name:name,
        status:status,
        group:group,
        supp:supp,
        type:type
      },
      orderColumn:[[ 2, 'asc' ]],
      excelFileName:'article'
    });
  }


  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
