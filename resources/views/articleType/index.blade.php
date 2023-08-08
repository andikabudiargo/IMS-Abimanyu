@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="articleTypes-index">
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
                    <div class="col-md-4"> 
                        <div class="form-group">
                        <label for="articleTypeCode">Kode</label>
                        <input type="text" class="form-control text-uppercase" id="articleTypeCode" name="articleTypeCode" placeholder=""  />
                        </div>
                    </div>
                    <div class="col-md-4"> 
                    <div class="form-group">
                        <label for="articleTypeName">Name</label>
                        <input type="text" class="form-control text-uppercase" id="articleTypeName" name="articleTypeName" placeholder="" />
                    </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12"> 
                        <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                        @can('articleType-create')
                        <a href="{{ route('articleType.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                        @endcan
                    </div>
                </div>
            </form>
          </div>
        </div>
      </div>
    </div>
</section>
<section id="table-articleTypes">
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

  let code = document.querySelector('#articleTypeCode');
  let name = document.querySelector('#articleTypeName');
  let search = document.querySelector('#btnSearch');
  let modal = document.querySelector('#modalConfirmation');
  let reload = document.querySelector('a[data-action="reload"]');

  document.addEventListener("DOMContentLoaded", function() {
    $(document).on('click', '#deleteButton', function(event) {
        event.preventDefault();
        let href = $(this).data('href');
        modal.setAttribute("action", href);
    });
    showList();
  });
   
  //refresh di cards
  reload.addEventListener("click",function(){
    showList(name.value,code.value);
  })

  search.addEventListener("click", function(){ 
    showList(name.value,code.value);
  }); 

  const showList = (nama,code) => {
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('articleType.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3],
      dataSearch:  {
        name:nama,
        code:code
      },
      columnDefs :[
        { width: '5%', targets: 0 },
      ],
      orderColumn:[[2,'asc']],
    });
  }

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
