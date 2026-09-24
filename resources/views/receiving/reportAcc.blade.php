@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
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
              <div class="col-md-3 form-group">
                <label for="recDate">Rec Date</label>
                <input type="text" id="recDate" name="recDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-4">
                <label class="form-label" for="searchSupplier">Supplier</label>
                <select class="select2 form-control" id="searchSupplier" name="searchSupplier[]" multiple>
                    @foreach($suppliers as $val)
                        <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
                <small class="text-muted">Kosongkan = All</small>
              </div>
              <div class="form-group col-md-5">
                <label for="searchPo">PO Number</label>
                <select class="select2 form-control" id="searchPo" name="searchPo[]" multiple>
                  @foreach($poNumbers as $po)
                      <option value="{{ $po }}">{{ $po }}</option>
                  @endforeach
                </select>
                <small class="text-muted">Kosongkan = All</small>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12">
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
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
@section('scripts')
<script type="text/javascript">
  let searchSupplier = $("#searchSupplier");
  let searchPo = $("#searchPo");
  let recDate = $("#recDate");

  $('a[data-action="reload"]').on('click', function () {
    showList(searchSupplier.val(),searchPo.val(),recDate.val());
  });

  let rangePickr = $('.flatpickr-range');
  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range'
    });
  }

  $("#btnSearch").click(function(e){
    showList(searchSupplier.val(),searchPo.val(),recDate.val());
  });

  const showList = (searchSupplier,searchPo,recDate) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('receiving.list.report.acc') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30],
      columnDefs :[
        { width: '5%', targets: 0 },
        {
          targets: [ 12,13,14,15,16,17,18,23 ],
          render: $.fn.dataTable.render.number(',','.',2,''),
          className: "text-right"
        },
      ],
      dataSearch:  {
        searchSupplier:searchSupplier,
        searchPo:searchPo,
        recDate:recDate
      },
      type:'POST',
      orderColumn:[], // pertahankan urutan dari server (DO Date terkecil dulu)
      excelFileName:'receiving_report_acc'
    });
  }

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

</script>
@endsection
