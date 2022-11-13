@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="accounts-index">
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
                        <label for="basicInput">Account</label>
                        <input type="text" class="form-control text-uppercase" id="searchAccCode" name="searchAccCode" placeholder="" />
                        </div>
                    </div>
                    <div class="col-md-4"> 
                    <div class="form-group">
                        <label for="basicInput">Description</label>
                        <input type="text" class="form-control text-uppercase" id="searchAcc" name="searchAcc" placeholder="" />
                    </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12"> 
                        <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                        @can('account-create')
                        <a href="{{ route('account.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                        @endcan
                    </div>
                </div>
            </form>
          </div>
        </div>
      </div>
    </div>
</section>
<section id="table-accounts">
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
    $(document).ready(function(){  
      let href;
      $(document).on('click', '#deleteButton', function(event) {
          event.preventDefault();
          href = $(this).data('href');
          console.log(href);
          $('#modalConfirmation').attr("action", href);
      });
      showList();
    });

     //refresh di cards
    $('a[data-action="reload"]').on('click', function () {
        showList();
    });

    $("#btnSearch").click(function(e){
        showList($("#searchAcc").val(),$("#searchAccCode").val());
    });

    const showList = (nama,code) => {
      showDataTables({
        tableId:"detailedTable",
        route:"{{ route('account.list') }}",
        kolom:{!! $kolom !!},
        arrColPrint:[1,2,3,4],
        columnDefs :[
          { width: '5%', targets: 0 }
        ],
        dataSearch:  {
          name:nama,
          code:code
        },
        orderColumn:[[ 1, 'asc' ]],
        excelFileName:'accounts'
      });
    }
    
</script>
@endsection
