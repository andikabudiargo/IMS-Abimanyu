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
              <div class="form-group col-md-4">
                <label class="form-label" for="vcDate">Tanggal</label>
                <input type="text" id="vcDate" name="vcDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-2">
                <label class="form-label" for="period1">Period Awal</label>
                <select class="select2 form-control" id="period1" name="period1" >
                    {{-- <option value=""></option> --}}
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
              </div>
              <div class="form-group col-md-2">
                <label class="form-label" for="period2">Period Akhir</label>
                <select class="select2 form-control" id="period2" name="period2" >
                    {{-- <option value=""></option> --}}
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-4">
                <label class="form-label" for="perkiraan1">Perkiraan Awal</label>
                <select class="select2 form-control" id="perkiraan1" name="perkiraan1" >
                  <option value=""></option>
                  @foreach($accounts as $val)
                    <option value="{{ $val->account }}">{{ $val->account }} - {{ $val->description }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group col-md-4">
                <label class="form-label" for="perkiraan2">Perkiraan Akhir</label>
                <select class="select2 form-control" id="perkiraan2" name="perkiraan2" >
                  <option value=""></option>
                  @foreach($accounts as $val)
                      <option value="{{ $val->account }}">{{ $val->account }} - {{ $val->description }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-8"> 
                <label class="form-label" for="departement">Departemen</label>
                <select class="select2 form-control" id="departement" name="departement" multiple>
                    <option value="">All</option>
                    @foreach($depts as $val)
                        <option value="{{ $val->code }}">{{ $val->name }}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    {{-- @can('pettyCash-create') --}}
                    {{-- <a href="{{ route('jurnalUmum.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a> --}}
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
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script type="text/javascript">
  $(document).ready(function(){    
  });

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
    let vcDate = $("#vcDate").val();
    let period1 = $("#period1").val();
    let period2 = $("#period2").val();
    let dept = $("#departement").val();
    let perkiraan1 = $("#perkiraan1").val();
    let perkiraan2 = $("#perkiraan2").val();
    showList(vcDate,period1,period2,dept,perkiraan1,perkiraan2);
  });

  const showList = (vcDate,period1,period2,dept,perkiraan1,perkiraan2) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('bukuBesar.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8],
      columnDefs :[
        { width: '5%', targets: 0 },
        {
          targets: [ 7,8 ],
          render: $.fn.dataTable.render.number(',', '.', 2, ''),
          className: "text-right"
        },
      ],
      dataSearch:  {
        vcDate:vcDate,
        period1:period1,
        period2:period2,
        dept:dept,
        perkiraan1,
        perkiraan2
      },
      // orderColumn:[[ 9,'asc']],
      excelFileName:'buku_besar'
    });
  }

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
