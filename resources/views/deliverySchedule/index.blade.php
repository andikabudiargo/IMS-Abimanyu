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
        <button type="button" class="btn btn-primary d-none" id ="btnDetail" name="btnDetail">Detail</button>
        <button type="button" class="btn btn-primary d-none" id ="btnSummary" name="btnSummary">Summary</button>
        <div class="row" id="summaryWrap">
            <div class="col-sm-12">
              <div class="card-datatable table-responsive pt-0">
                <table id="detailedTable" class="table">
                  <thead class="thead-light">
                  </thead>
                </table>
              </div>
            </div>
        </div>
        <div id="detailWrap" style="display:none;"></div>
      </div>
    </div>
  </div>
</section>
@endsection
@section('styles')
<style>
    .ds-table-scroll{ overflow-x:auto; }
    .ds-table th, .ds-table td{ white-space:nowrap; }
    .ds-sticky{ position:sticky; left:0; background:#fff; z-index:1; }
    .ds-col-article{ left:0; min-width:220px; }
</style>
@endsection
@section('scripts')
<script type="text/javascript">
  let btnSummary = $('#btnSummary');
  let btnDetail = $('#btnDetail');

  $(document).ready(function(){
    validateFormToast("frmAdd");
    let now = new Date();
    let firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    let lastDay = new Date(now.getFullYear(), now.getMonth()+1, 0);
    let fmt = (d) => (d.getDate()<10?'0':'')+d.getDate()+'-'+((d.getMonth()+1)<10?'0':'')+(d.getMonth()+1)+'-'+d.getFullYear();
    $('#periode').val(fmt(firstDay)+' to '+fmt(lastDay));
    showSummary();
  });

  rangePickr = $('.flatpickr-range');

  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range',
      allowInput:true
    });
  }

  function showSummary(){
    $('#summaryWrap').show();
    $('#detailWrap').hide();

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
      arrColPrint:[1,2,3,4,5],
      columnDefs :[
        { width: '10%', targets: 0 },
      ],
      dataSearch:{
        periode:periode,
        searchCustomer:searchCustomer
      },
      initComplete: function() {
        let api = this.api();
        if (api.data().length > 0) {
          btnDetail.removeClass('d-none');
          btnSummary.addClass('d-none');
        }
      },
      excelFileName:'delivery_schedule'
    });
  }

  function showDetail(){
    $('#summaryWrap').hide();
    $('#detailWrap').show().html('<div class="text-muted p-2">Loading...</div>');

    $.ajax({
      type:'get',
      url:"{{ route('deliverySchedule.list.matrix') }}",
      data:{
        periode:$('#periode').val(),
        searchCustomer:$('#searchCustomer').val()
      },
      dataType:'json',
      success:function(data){
        renderMatrix(data.groups);
      },
      error:function(error){
        console.log(error);
      }
    });
  }

  function renderMatrix(groups){
    if (!groups.length){
        $('#detailWrap').html('<div class="text-muted p-2">No schedule found for this periode.</div>');
        return;
    }

    let html = '';
    groups.forEach(function(g){
      html += '<div class="card"><div class="card-header"><h4 class="card-title">'+g.name+'</h4></div>';
      html += '<div class="card-body"><div class="ds-table-scroll"><table class="table table-bordered table-sm ds-table">';
      html += '<thead class="thead-light"><tr><th class="ds-sticky ds-col-article">Article</th>';
      g.dates.forEach(function(d){ html += '<th class="text-center">'+d+'</th>'; });
      html += '<th class="text-center">Total</th></tr></thead><tbody>';

      Object.keys(g.articles).forEach(function(rowKey){
        let rowTotal = 0;
        html += '<tr><td class="ds-sticky ds-col-article">'+g.articles[rowKey]+'</td>';
        g.dates.forEach(function(d){
          let qty = (g.matrix[rowKey] && g.matrix[rowKey][d]) ? g.matrix[rowKey][d] : 0;
          rowTotal += qty;
          html += '<td class="text-center">'+(qty ? qty : '-')+'</td>';
        });
        html += '<td class="text-center font-weight-bold">'+rowTotal+'</td></tr>';
      });

      html += '</tbody></table></div></div></div>';
    });

    $('#detailWrap').html(html);
  }

  $("#btnSearch").click(function(e){
    showSummary();
    btnDetail.removeClass('d-none');
    btnSummary.addClass('d-none');
  });

  $('a[data-action="reload"]').on('click', function () {
    showSummary();
    btnDetail.removeClass('d-none');
    btnSummary.addClass('d-none');
  });

  btnSummary.click(function(e){
    showSummary();
    btnDetail.removeClass('d-none');
    btnSummary.addClass('d-none');
  });

  btnDetail.click(function(e){
    showDetail();
    btnDetail.addClass('d-none');
    btnSummary.removeClass('d-none');
  });

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
</script>
@endsection
