@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
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
              <div class="col-md-3 form-group">
                <label for="tanggalPenyusutan">Tgl. Awal Penyusutan</label>
                <input type="text" id="tanggalPenyusutan" name="tanggalPenyusutan" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-3">
                <label class="form-label" for="jenisAset">Jenis Aset</label>
                <select class="select2 form-control" id="jenisAset" name="jenisAset" >
                  <option value=""></option>
                  @foreach($accounts as $val)
                    <option value="{{ $val->account }}">{{ $val->description }}</option>
                  @endforeach
                </select>
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

  const rangePickr = $('.flatpickr-range');
  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range'
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
    let searchDate = $("#tanggalPenyusutan").val();
    let searchJenisAset = $("#jenisAset").val();
    showList(searchNoAsset,searchName,searchDate,searchJenisAset);
  });

  const showList = (searchNoAsset,searchName,searchDate,searchJenisAset) => {
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
      arrColPrint:[1,2,3,4,5,6,7,8,9,10,11,12],
      columnDefs :[
        { width: '5%', targets: 0 },
        {
          targets: [ 7,8,9,10,11,12],
          render: $.fn.dataTable.render.number(',', '.', 0, ''),
          className: "text-right"
        },        
      ],
      type:"POST",
      dataSearch:  {
        searchNoAsset:searchNoAsset,
        searchName:searchName,
        searchDate:searchDate,
        searchJenisAset:searchJenisAset
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
