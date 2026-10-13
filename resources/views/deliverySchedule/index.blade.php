@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="filter-index">
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
        <form id="frmAdd" name="frmAdd">
            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="periode">Periode</label>
                <input type="text" id="periode" name="periode" class="form-control flatpickr-range" placeholder="DD-MM-YYYY to DD-MM-YYYY" required/>
              </div>
              <div class="form-group col-md-4">
                <label class="form-label" for="searchCustomer">Customer</label>
                <select class="select2 form-control" id="searchCustomer" name="searchCustomer">
                    <option value="">All</option>
                    @foreach($custs as $val)
                        <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12">
                    <button type="button" class="btn btn-primary" id="btnSearch" name="btnSearch">Search</button>
                </div>
            </div>
        </form>
      </div>
    </div>
  </div>
</section>
<section id="table-customer">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">@yield('title') List</h4>
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
<!-- Modal detail SO per customer -->
<div class="modal fade text-left bisa-geser" id="mdlDetail" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
      <div class="modal-content">
          <div class="modal-header">
              <h5>Sales Order <span class="bold" id="mdlCustName"></span></h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
              </button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-striped" id="mdlDetailTable">
              </table>
            </div>
          </div>
      </div>
  </div>
</div>
@endsection
@section('scripts')
<script type="text/javascript">
  $(document).ready(function(){
    validateFormToast("frmAdd");
    let now = new Date();
    let firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    let lastDay = new Date(now.getFullYear(), now.getMonth()+1, 0);
    let fmt = (d) => (d.getDate()<10?'0':'')+d.getDate()+'-'+((d.getMonth()+1)<10?'0':'')+(d.getMonth()+1)+'-'+d.getFullYear();
    $('#periode').val(fmt(firstDay)+' to '+fmt(lastDay));
    filterData();
  });

  rangePickr = $('.flatpickr-range');

  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range',
      allowInput:true
    });
  }

  function filterData(){
    let periode = $('#periode').val();
    let searchCustomer = $('#searchCustomer').val();

    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('deliverySchedule.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4],
      columnDefs :[
        { width: '10%', targets: 0 },
      ],
      dataSearch:{
        periode:periode,
        searchCustomer:searchCustomer
      },
      excelFileName:'delivery_schedule'
    });
  }

  $("#btnSearch").click(function(e){
    filterData();
  });

  $('a[data-action="reload"]').on('click', function () {
    filterData();
  });

  const detailCustomer = (customerId, custName) => {
    $('#mdlDetail').modal('show');
    $('#mdlCustName').text(' | '+custName);

    if ($('#mdlDetailTable tr').length >0){
        let table= $('#mdlDetailTable').DataTable();
        table.destroy();
        $('#mdlDetailTable tbody > tr').remove();
        $("#mdlDetailTable thead > tr").remove();
    }
    showDataTables({
      tableId:"mdlDetailTable",
      route:"{{ route('deliverySchedule.list.detail') }}",
      kolom:{!! $kolomDetail !!},
      arrColPrint:[1,2,3,4,5],
      columnDefs :[
        { width: '10%', targets: 0 },
      ],
      dataSearch:{
        customerId:customerId,
        periode:$('#periode').val()
      },
      scrollY:350,
      excelFileName:'delivery_schedule_detail_'+customerId
    });
  }

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
</script>
@endsection
