@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="customers-index">
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
                        <label for="searchCustomerCode">Kode</label>
                        <input type="text" class="form-control text-uppercase" id="searchCustomerCode" name="searchCustomerCode" placeholder="" />
                        </div>
                    </div>
                    <div class="col-md-4"> 
                    <div class="form-group">
                        <label for="searchCustomerCode">Nama</label>
                        <input type="text" class="form-control text-uppercase" id="searchCustomer" name="searchCustomer" placeholder="" />
                    </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12"> 
                        <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                        @can('customer-create')
                        <a href="{{ route('customer.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                        @endcan
                    </div>
                </div>
            </form>
          </div>
        </div>
      </div>
    </div>
</section>
<section id="table-customers">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">List @yield('title')</h4>
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

  let name = document.querySelector("#searchCustomer");
  let code = document.querySelector("#searchCustomerCode");
  let search = document.querySelector('#btnSearch');
  let refresh = document.querySelector('a[data-action="reload"]');

  document.addEventListener("DOMContentLoaded", function(event) {

  });

  refresh.addEventListener("click",function(){
    showList(name.value,code.value);
  })

  search.addEventListener("click", function(){ 
    showList(name.value,code.value);
  }); 

  const showList = (name,code) => {
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('customer.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15],
      columnDefs :[
        { width: '5%', targets: 0 },
      ],
      dataSearch:  {
        name:name,
        code:code
      },
      orderColumn:[[ 2, 'asc' ]],
      excelFileName:'customer'
    });
  }

</script>
@endsection
