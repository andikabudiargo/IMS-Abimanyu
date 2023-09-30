@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
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
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script type="text/javascript">
  let $body = $('body');
  $(document).ready(function(){
    showList();
  });

  const showList = () => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('monitoring.qtyNotBalance.list') }}",
      kolom:{!! $kolom !!},
      type:'POST',
      arrColPrint:[0,1,2,3,4,5],
      columnDefs :[
        { width: '5%', targets: 0 },
        { className: 'text-right','targets': [ 4,5 ] },
      ],
      dataSearch:  {
      },
      orderColumn:[[ 0, 'asc' ]],
      excelFileName:'qty_not_balance'
    });
  }

  

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
