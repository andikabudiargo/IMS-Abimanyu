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
            <div class="form-group col-md-2">
                <label class="form-label" for="year">Tahun*</label>
                <select class="select2 form-control" id="year" name="year" required>
                    <option value=""></option>
                    @for ($i = 2022; $i <= 2050; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="form-group col-md-2">
                <label class="form-label" for="bulanAwal">Bulan Awal*</label>
                <select class="select2 form-control" id="bulanAwal" name="bulanAwal" required>
                    <option value=""></option>
                    @foreach ($bulan as $key=>$val)
                        <option value="{{ $key }}">{{ $val }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-2">
                <label class="form-label" for="bulanAkhir">Bulan Akhir*</label>
                <select class="select2 form-control" id="bulanAkhir" name="bulanAkhir" required>
                    <option value=""></option>
                    @foreach ($bulan as $key=>$val)
                        <option value="{{ $key }}">{{ $val }}</option>
                    @endforeach
                </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
                <label for="forcastName">Forecasting Name</label>
                <input type="text" id="forcastName" name="forcastName" class="form-control"  required/>
            </div>
          </div>
          {{-- <div class="form-row">
              <div class="form-group col-md-6">
                  <label for="customerCode">Customer</label>
                  <select class="select2 form-control" id="customerCode" name="customerCode">
                      <option value="">Choose Customer</option>
                      @foreach($customers as $val)
                      <option value="{{ $val->kode }}">{{ $val->nama }}</option>
                      @endforeach
                  </select>
              </div>
          </div> --}}
          <div class="form-row">
              <div class="col-12"> 
                  <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                  {{-- @can('pettyCash-create') --}}
                  <a href="{{ route('forecastSales.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
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
                <table id="detailedTable" >
                  <thead class="thead-light">
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
            </div>
        </div>  
      </div>
    </div>
  </div>
</section>

@include('forecasting.sales.addArticle')

@endsection
@section('styles')
<style>

td.wrapok {
    white-space:normal
}
</style>
@endsection
@section('scripts')
<script type="text/javascript">
  $(document).ready(function(){    
    // showList();
  });

  $("#btnSearch").click(function(e){
      showList();
  });

  const showList = () => {
    let bulanAwal = $('#bulanAwal').val();
    let bulanAkhir = $('#bulanAkhir').val();
    let year = $('#year').val().slice(-2);
    let customer = $('#customerCode').val();
    let forcastName= $('#forcastName').val();

    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('forecastSales.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7,8],
      columnDefs :[
        // { width: '5%', targets: 0 },
        // {
        //   targets: [ 8,9,19,20,21,22,23,24,25 ],
        //   render: $.fn.dataTable.render.number(',', '.', 2, ''),
        //   className: "text-right"
        // },
        // {
        //   targets: [ 11 ],
        //   render: function ( data, type, full, meta ) {
        //     return '\u200C'+data;
        //   }
        // }
      ],
      type:"POST",
      // excelCustomize:function(xlsx) {
      //   let sheet = xlsx.xl.worksheets['sheet1.xml'];
      //   $('row:last c', sheet).attr('s','50');
      // },
      // excelMessageBottom:function () { return "Tanggal export : "+currentDate },
      dataSearch:  {
        bulanAwal:bulanAwal,
        bulanAkhir:bulanAkhir,
        year:year,
        customer:customer,
        forcastName:forcastName
      },
      orderColumn:[[ 1, 'desc' ]],
      excelFileName:'forecasting_sales',
    });
  }
  
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
