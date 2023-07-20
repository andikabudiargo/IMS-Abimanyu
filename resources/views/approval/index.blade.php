@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="master-index">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <form class="needs-validation" novalidate>
              <div class="form-row">
                  <div class="col-md-4"> 
                      <div class="form-group">
                      <label for="basicInput">Approval Name</label>
                      <input type="text" class="form-control text-uppercase" id="searchCode" name="searchCode" placeholder=""  />
                      </div>
                  </div>
                  <div class="col-md-4"> 
                  <div class="form-group">
                      <label for="basicInput">Userame</label>
                      <input type="text" class="form-control text-uppercase" id="searchName" name="searchName" placeholder="" />
                  </div>
                  </div>
              </div>
              <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
          </form>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Approval Level</h4>
          <div class="heading-elements">
              <ul class="list-inline mb-0">
                  <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                  <li><a data-action="reload"><i data-feather="rotate-cw"></i></a></li>
              </ul>
          </div>
        </div>
        <div class="card-body">
          <div class="form-row">
            <div class="col-md-12"> 
              @can('approval-create')
                <a href="javascript:void(0);" 
                  data-url="{{ route('approval.create.level') }}" 
                  class="btn btn-primary" 
                  data-ajax-popup="true" 
                  data-title="Approval Level"
                  data-size="sm">
                  <i class="fa fa-plus"></i> Create
                </a>
              @endcan
            </div>
          </div>
          <div class="row">
              <div class="col-sm-12">
                <div class="card-datatable table-responsive pt-0">
                  <table id="detailedTableLevel" class="table">
                    <thead class="thead-light">
                    </thead>
                  </table>
                </div>
              </div>
          </div>  
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Approval master</h4>
          <div class="heading-elements">
              <ul class="list-inline mb-0">
                <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                <li><a data-action="reload"><i data-feather="rotate-cw"></i></a></li>
              </ul>
          </div>
        </div>
        <div class="card-body">
          <div class="form-row">
              <div class="col-sm-12">
                <div class="card-datatable table-responsive pt-0">
                  <table id="detailedTableMaster" class="table">
                    <thead class="thead-light">
                    </thead>
                  </table>
                </div>
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
@section('scripts')
<script type="text/javascript">
  let code = document.querySelector('#searchCode');
  let name = document.querySelector('#searchName');
  let search = document.querySelector('#btnSearch');
  let refresh = document.querySelector('a[data-action="reload"]');

  $(document).ready(function() {
    
  });

  function initiateSelect2() {
  $('.select2').select2();
  }
  initiateSelect2();
  // when modal is open
  $('.modal').on('shown.bs.modal', function () {
    initiateSelect2();
  })

  document.addEventListener("DOMContentLoaded", function(event) {
    showListMaster();
    showListLevel();
  });

  //refresh card
  refresh.addEventListener("click",function(){
    showListMaster(code.value,name.value);
  })

  search.addEventListener("click", function(){ 
    showListLevel(code.value,name.value);
  }); 

  const showListLevel = (code,name) => {
    showDataTables({
      tableId:"detailedTableLevel",
      route:"{{ route('approval.list.level') }}",
      kolom:{!! $kolomLevel !!},
      arrColPrint:[1,2,3,4],
      columnDefs :[
        { width: '10%', targets: 0 },
        // { className: 'text-right','targets': [2] },
      ],
      dataSearch:  {
        name:name,
        code:code
      },
      orderColumn:[[1,'asc'],[2,'asc'],[4,'asc']],
      excelFileName:'Approval_level'
    });
  }

  const showListMaster = (code,name) => {
    showDataTables({
      tableId:"detailedTableMaster",
      route:"{{ route('approval.list.master') }}",
      kolom:{!! $kolomMaster !!},
      arrColPrint:[1,2,3],
      columnDefs :[
        { width: '10%', targets: 0 },
        // { className: 'text-right','targets': [2] },
      ],
      dataSearch:  {
        name:name,
        code:code
      },
      orderColumn:[[1,'asc'],[2,'asc'],[3,'asc']],
      excelFileName:'Approval_level'
    });
  }

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
</script>
@endsection

