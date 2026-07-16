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
              <div class="form-group col-md-3"> 
                <label for="searchDn">Return Number</label>
                <input type="text" class="form-control text-uppercase" id="searchDn" name="searchDn" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchCustomer">Customer</label>
                <select class="select2 form-control" id="searchCustomer" name="searchCustomer">
                    <option value="">All</option>
                    @foreach($customers as $val)
                      <option value="{{$val->kode}}" >{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
              </div>
              <div class="col-md-3 form-group">
                <label for="returnDate">Date</label>
                <input type="text" id="returnDate" name="returnDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchStatus">Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                    <option value="">All</option>
                    @foreach($status as $index=>$val)
                        <option value="{{ $index }}">{{ $val }}</option>
                    @endforeach
                </select>
              </div>
              
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    {{-- @can('purchaseRequest-create') --}}
                    <a href="{{ route('dnReturn.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
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
        <button type="button" class="btn btn-primary" id ="btnDetail" name="btnDetail" data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data detail">Detail</button>
        <button type="button" class="btn btn-primary" id ="btnSummary" name="btnSummary" data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data summary">Summary</button>
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

<div class="modal fade" id="modalReconciliation" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content rec-modal">

      <div class="modal-header rec-header">
        <div class="d-flex align-items-center">
          <div class="rec-header-icon"><i data-feather="git-merge"></i></div>
          <div>
            <h4 class="modal-title mb-0">Reconciliation</h4>
            <small class="rec-subtitle">Komparasi Return & Replacement</small>
          </div>
        </div>
        <button class="close text-danger" data-dismiss="modal" style="opacity:1">
  <span aria-hidden="true">&times;</span>
</button>
      </div>

      <div class="modal-body rec-body">

        <div class="row mt-1">
          <div class="col-md-5">
            <div class="rec-panel h-100">
              <div class="rec-panel-title">Document Info</div>
              <table class="table table-borderless table-sm rec-info mb-0">
                <tr><th>Return Number</th><td id="recReturnNumber">-</td></tr>
                <tr><th>Customer DN</th><td id="recDnNumber">-</td></tr>
                <tr><th>Return Date</th><td id="recReturnDate">-</td></tr>
                <tr><th>Customer</th>
                    <td><span id="recCustomerCode"></span> - <span id="recCustomerName"></span></td></tr>
              </table>
            </div>
          </div>

          <div class="col-md-7">
            <div class="rec-panel h-100">
              <div class="rec-panel-title">DN Replace History</div>
              <div class="table-responsive rec-history-wrap">
                <table class="table table-sm rec-table mb-0">
                  <thead>
                    <tr>
                      <th width="35">#</th>
                      <th>Replace Number</th>
                      <th class="text-right">Qty</th>
                      <th class="text-center">Status</th>
                      <th>Created By</th>
                      <th>Created At</th>
                    </tr>
                  </thead>
                  <tbody id="replaceHistory"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="rec-panel mt-1">
          <div class="rec-panel-title">Article Detail</div>
          <div class="table-responsive">
            <table class="table table-sm rec-table rec-table-striped mb-0">
              <thead>
                <tr>
                  <th width="40">#</th>
                  <th width="35%">Article</th>
                  <th width="8%" class="text-center">UOM</th>
                  <th class="text-right">Qty Return</th>
                  <th class="text-right">Qty Replace</th>
                  <th class="text-right">Remaining</th>
                  <th width="12%" class="text-center">Status</th>
                </tr>
              </thead>
              <tbody id="reconciliationDetail"></tbody>
            </table>
          </div>
        </div>

      </div>

      <div class="modal-footer rec-footer">
        <button class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

@include('partials.modals')
@include('partials.delete-modal')
@endsection
@section('styles')
<style>

  .rec-modal { border: 0; border-radius: 10px; overflow: hidden; box-shadow: 0 18px 45px rgba(0,0,0,.22); }

.rec-header {
  background: linear-gradient(135deg, #1f3b73 0%, #2f5fa8 100%);
  color: #fff; padding: .9rem 1.25rem; border-bottom: 0;
}
.rec-header .modal-title { color: #fff; font-size: 1.05rem; font-weight: 600; letter-spacing: .2px; }
.rec-header .close { color: #fff; opacity: .85; text-shadow: none; font-weight: 400; }
.rec-header .close:hover { opacity: 1; }
.rec-subtitle { color: rgba(255,255,255,.7); font-size: .72rem; letter-spacing: .5px; text-transform: uppercase; }
.rec-header-icon {
  width: 36px; height: 36px; border-radius: 8px; margin-right: .75rem;
  background: rgba(255,255,255,.14);
  display: flex; align-items: center; justify-content: center;
}
.rec-header-icon svg { width: 18px; height: 18px; }

.rec-body { background: #f6f7fb; padding: 1rem 1.25rem; }
.rec-footer { background: #f6f7fb; border-top: 1px solid #e6e9f0; }

.rec-panel {
  background: #fff; border: 1px solid #e6e9f0; border-radius: 8px;
  padding: .85rem 1rem; box-shadow: 0 1px 2px rgba(16,24,40,.04);
}
.rec-panel-title {
  font-size: .7rem; font-weight: 700; letter-spacing: .8px; text-transform: uppercase;
  color: #6b7a99; margin-bottom: .6rem; padding-bottom: .45rem; border-bottom: 1px solid #eef0f5;
}

.rec-stats { margin-bottom: .35rem; }
.rec-stat {
  background: #fff; border: 1px solid #e6e9f0; border-radius: 8px;
  padding: .7rem .9rem; display: flex; flex-direction: column;
  box-shadow: 0 1px 2px rgba(16,24,40,.04);
}
.rec-stat-label { font-size: .68rem; letter-spacing: .7px; text-transform: uppercase; color: #8a94a6; }
.rec-stat-value { font-size: 1.4rem; font-weight: 700; line-height: 1.3; font-variant-numeric: tabular-nums; }

.rec-info th { width: 130px; color: #6b7a99; font-weight: 600; font-size: .8rem; padding: .28rem 0; }
.rec-info td { font-size: .84rem; font-weight: 600; color: #2b3445; padding: .28rem 0; }

.rec-table thead th {
  background: #eef1f7; color: #52607a; font-size: .7rem; font-weight: 700;
  letter-spacing: .6px; text-transform: uppercase; border-top: 0;
  border-bottom: 1px solid #dfe3ec !important; padding: .5rem .6rem; white-space: nowrap;
}
.rec-table tbody td {
  font-size: .82rem; padding: .5rem .6rem; vertical-align: middle;
  border-top: 1px solid #f0f2f7; font-variant-numeric: tabular-nums;
}
.rec-table tbody tr:hover { background: #eaf1ff !important; }
.rec-table .rec-idx { color: #97a1b3; font-weight: 600; text-align: center; }

/* zebra striping */
.rec-table-striped tbody tr:nth-child(odd)  { background: #ffffff; }
.rec-table-striped tbody tr:nth-child(even) { background: #f7f9fc; }

.rec-history-wrap { max-height: 190px; overflow-y: auto; }
.rec-article-code { font-weight: 600; color: #2b3445; }
.rec-article-desc { font-size: .72rem; color: #97a1b3; }
.rec-row-canceled td { opacity: .55; }
.rec-row-canceled .rec-replace-link { text-decoration: line-through; }
.rec-empty { text-align: center; color: #97a1b3; font-size: .8rem; padding: 1rem !important; }
.badge.badge-status { font-size: .65rem; letter-spacing: .5px; padding: .35em .6em; }
</style>
@endsection
@section('scripts')
<script type="text/javascript">
  let searchDn = document.querySelector("#searchDn");
  let searchStatus = document.querySelector("#searchStatus");
  let returnDate = document.querySelector("#returnDate");
  let searchCustomer = document.querySelector("#searchCustomer");
  let search = document.querySelector('#btnSearch');
  let refresh = document.querySelector('a[data-action="reload"]');
  let rangePickr = document.querySelector('.flatpickr-range');
  let btnSummary = document.querySelector('#btnSummary');
  let btnDetail = document.querySelector('#btnDetail');

  document.addEventListener("DOMContentLoaded", function(event) {
    btnSummary.style.display = "none";
    btnDetail.style.display = "none";
  });
  
  initDatePicker(rangePickr,{
    minDate: "01/01/2010",
    maxDate: "31/12/2030",
    dateFormat: "d-m-Y",
    mode: "range"
  });

  //refresh di cards
  refresh.addEventListener("click",function(){
    btnDetail.style.display = "block";
    btnSummary.style.display = "none";
    showList(searchDn.value,searchStatus.value,returnDate.value,searchCustomer.value);
  })

  search.addEventListener("click", function(){
    btnDetail.style.display = "block";
    btnSummary.style.display = "none";
    showList(searchDn.value,searchStatus.value,returnDate.value,searchCustomer.value);
  });

  btnSummary.addEventListener("click", function(){
    btnSummary.style.display = "none";
    btnDetail.style.display = "block";
    showList(searchDn.value,searchStatus.value,returnDate.value,searchCustomer.value);
  });
  
  btnDetail.addEventListener("click", function(){
    btnSummary.style.display = "block";
    btnDetail.style.display = "none";
    showListDetail(searchDn.value,searchStatus.value,returnDate.value,searchCustomer.value);
  });

  const showList = (searchDn,searchStatus,returnDate,searchCustomer) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route("dnReturn.list") }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7,8,9,10,11],
      columnDefs :[
        { width: '5%', targets: 0 },
      ],
      dataSearch:  {
        searchDn:searchDn,
        searchStatus:searchStatus,
        returnDate:returnDate,
        searchCustomer:searchCustomer
      },
      orderColumn:[[ 1, 'desc' ]],
      excelFileName:'DN_return'
    });
  }

  const showListDetail = (searchDn,searchStatus,returnDate,searchCustomer) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('dnReturn.list.detail') }}",
      kolom:{!! $kolomDetail !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14],
      columnDefs :[
        { width: '5%', targets: 0 },
        { className: 'text-right','targets': [5] },
      ],
      dataSearch:  {
        searchDn:searchDn,
        searchStatus:searchStatus,
        returnDate:returnDate,
        searchCustomer:searchCustomer
      },
      // orderColumn:[[ 2, 'asc' ]],
      excelFileName:'DN_Return_detail'
    });
  }

  // let href;
  // $(document).on('click', '#revisionReasonButton', function(event) {
  //     event.preventDefault();
  //     href = $(this).data('href');
  //     $('#modalReasonRevision').attr("action", href);
  // });

  $(document).on('click', '.btn-reconciliation', function () {

    let id = $(this).data('id');

    $.ajax({
        url: "{{ route('dnReturn.reconciliation') }}",
        type: "GET",
        data: {
            id: id
        },
       success: function (res) {

    $('#recReturnNumber').html(res.header.return_number);
    $('#recDnNumber').html(res.header.dn_number ?? '-');
    $('#recReturnDate').html(res.header.return_date ?? '-');
    $('#recCustomerCode').html(res.header.customer_code ?? '-');
    $('#recCustomerName').html(res.header.customer_name ?? '-');

    $('#recTotalReturn').html(Number(res.header.total_return).toLocaleString());
    $('#recTotalReplace').html(Number(res.header.total_replace).toLocaleString());

    let remaining = Number(res.header.remaining);
    $('#recRemaining')
        .html(remaining.toLocaleString())
        .removeClass('text-primary text-success text-warning text-danger')
        .addClass(remaining === 0 ? 'text-success' : (remaining > 0 ? 'text-warning' : 'text-danger'));

    /* ---- Replace History ---- */
    let history = '';

    if (!res.replace.length) {
        history = '<tr><td colspan="6" class="rec-empty">Belum ada DN Replace</td></tr>';
    } else {
        $.each(res.replace, function (i, row) {
            history += `
<tr class="${row.is_canceled ? 'rec-row-canceled' : ''}">
    <td class="rec-idx">${i + 1}</td>
    <td>
        <a class="rec-replace-link"
           href="{{ route('dnReplace.show') }}?id=${row.id_encrypt}" target="_blank">
            ${row.replace_number}
        </a>
    </td>
    <td class="text-right">${Number(row.qty_total).toLocaleString()}</td>
    <td class="text-center">
        <span class="badge badge-status badge-${row.status_badge}">${row.status_label}</span>
    </td>
    <td>${row.created_by ?? '-'}</td>
    <td>${row.created_at ?? '-'}</td>
</tr>`;
        });
    }
    $('#replaceHistory').html(history);

    /* ---- Article Detail ---- */
    let detail = '';

    if (!res.details.length) {
        detail = '<tr><td colspan="7" class="rec-empty">Tidak ada article</td></tr>';
    } else {
        $.each(res.details, function (i, row) {
            detail += `
<tr>
    <td class="rec-idx">${i + 1}</td>
    <td>
        <div class="rec-article-code">${row.article_alternative_code ?? '-'}</div>
        <div class="rec-article-desc">${row.article_desc ?? ''}</div>
    </td>
    <td class="text-center">${row.uom ?? '-'}</td>
    <td class="text-right">${Number(row.qty_return).toLocaleString()}</td>
    <td class="text-right">${Number(row.qty_replace).toLocaleString()}</td>
    <td class="text-right">${Number(row.qty_remaining).toLocaleString()}</td>
    <td class="text-center">
        <span class="badge badge-status badge-${row.badge}">${row.status}</span>
    </td>
</tr>`;
        });
    }
    $('#reconciliationDetail').html(detail);

    feather.replace();
    $('#modalReconciliation').modal('show');
},
        error: function (xhr) {

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: xhr.responseJSON?.message ?? 'Failed to load reconciliation.'
            });

        }
    });

});

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });


  
    
</script>
@endsection
