@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

  {{-- FILTER CARD --}}
  <section id="article-index">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <form class="needs-validation" novalidate>
                  <div class="form-row">
                      <div class="form-group col-md-4"> 
                        <label for="seachCode">Kode</label>
                        <input type="text" class="form-control text-uppercase" id="seachCode" name="seachCode" placeholder=""  />
                      </div>
                      <div class="form-group col-md-4"> 
                        <label for="searchName">Name</label>
                        <input type="text" class="form-control text-uppercase" id="searchName" name="searchName" placeholder="" />
                      </div>
                      <div class="form-group col-md-4 d-none"> 
                        <label class="form-label" for="searchGroup">Group</label>
                        <select class="select2 form-control" id="searchGroup" name="searchGroup">
                            <option value="">All</option>
                            @foreach($groups as $val)
                                <option value="{{$val->code}}">{{$val->code}} - {{$val->name}}</option>
                            @endforeach
                        </select>
                      </div>
                      <div class="form-group col-md-4"> 
                        <label class="form-label" for="searchSupplier">Supplier/Customer</label>
                        <select class="select2 form-control" id="searchSupplier" name="searchSupplier">
                            <option value="">All</option>
                            @foreach($supps as $val)
                                <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                            @endforeach
                        </select>
                      </div>
                      <div class="form-group col-md-4"> 
                        <label class="form-label" for="searchType">Article Type</label>
                        <select class="select2 form-control" id="searchType" name="searchType">
                            <option value="">All</option>
                            @foreach($types as $val)
                              <option value="{{$val->code}}" >{{$val->code}} - {{$val->name}}</option>
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
      </div>
  </section>

  {{-- ADJUSTMENT SAFETY STOCK - DEFAULT COLLAPSE --}}
  <section id="section-safety-stock">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">Adjustment Safety Stock</h4>
        <div class="heading-elements">
            <ul class="list-inline mb-0">
                <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                <li><a data-action="reload"><i data-feather="rotate-cw"></i></a></li>
            </ul>
        </div>
      </div>
      {{-- Tambah class "collapse" supaya default-nya tertutup --}}
      <div class="card-content collapse">
        <div class="card-body">
          <form id="frmExcel" name="frmExcel" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-row">
                <div class="col-lg-3 col-md-12">
                    <div class="form-group">
                        <div>
                            <input type="file" class="custom-file-input" name="file" id="file" required/>
                            <label class="custom-file-label" for="file">Choose file</label>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-12">
                    <button type="button" class="btn btn-primary">
                        <i data-feather="upload" class="align-middle mr-sm-25 mr-0"></i>
                        <span class="align-middle d-sm-inline-block d-none" id="uploadExcel">Upload Excel</span>
                    </button>
                </div>
            </div>
            <div class="form-row">
                <div class="col-lg-3 col-md-12">
                    <a href="{{ route('articles.safetyStock.export.excel') }}" class="btn btn-light">
                        <i data-feather="download"></i> Download Template
                    </a>
                </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>

  {{-- 3 STAT CARDS --}}
  <section id="section-stats">
      <div class="row">

          {{-- Total --}}
          <div class="col-lg-4 col-md-4 col-sm-12">
              <div class="card stat-card cursor-pointer" id="cardTotal" data-filter="">
                  <div class="card-body d-flex align-items-center justify-content-between py-1">
                      <div>
                          <h6 class="text-muted mb-25">Total Article</h6>
                          <h2 class="font-weight-bolder mb-0" id="statTotal">-</h2>
                      </div>
                      <div class="avatar bg-light-primary p-50">
                          <span class="avatar-content">
                              <i data-feather="layers" class="font-medium-5"></i>
                          </span>
                      </div>
                  </div>
                  <div class="card-footer py-50 bg-light-primary" style="border-radius:0 0 .357rem .357rem">
                      <small class="text-primary">Klik untuk tampilkan semua</small>
                  </div>
              </div>
          </div>

          {{-- Active --}}
          <div class="col-lg-4 col-md-4 col-sm-12">
              <div class="card stat-card cursor-pointer" id="cardActive" data-filter="1">
                  <div class="card-body d-flex align-items-center justify-content-between py-1">
                      <div>
                          <h6 class="text-muted mb-25">Active</h6>
                          <h2 class="font-weight-bolder mb-0" id="statActive">-</h2>
                      </div>
                      <div class="avatar bg-light-success p-50">
                          <span class="avatar-content">
                              <i data-feather="check-circle" class="font-medium-5"></i>
                          </span>
                      </div>
                  </div>
                  <div class="card-footer py-50 bg-light-success" style="border-radius:0 0 .357rem .357rem">
                      <small class="text-success">Klik untuk filter Active</small>
                  </div>
              </div>
          </div>

          {{-- Freeze --}}
          <div class="col-lg-4 col-md-4 col-sm-12">
              <div class="card stat-card cursor-pointer" id="cardFreeze" data-filter="0">
                  <div class="card-body d-flex align-items-center justify-content-between py-1">
                      <div>
                          <h6 class="text-muted mb-25">Freeze</h6>
                          <h2 class="font-weight-bolder mb-0" id="statFreeze">-</h2>
                      </div>
                      <div class="avatar bg-light-danger p-50">
                          <span class="avatar-content">
                              <i data-feather="slash" class="font-medium-5"></i>
                          </span>
                      </div>
                  </div>
                  <div class="card-footer py-50 bg-light-danger" style="border-radius:0 0 .357rem .357rem">
                      <small class="text-danger">Klik untuk filter Freeze</small>
                  </div>
              </div>
          </div>

      </div>
  </section>

  {{-- TABLE --}}
  <section id="table-article">
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
                      <thead class="thead-light"></thead>
                    </table>
                  </div>
                </div>
            </div>  
          </div>
        </div>
      </div>
  </section>

  <!-- Modal movement-->
  <div class="modal fade text-left bisa-geser" id="mdlmovement" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Movement <span class="bold" id="mdlartikel"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
              <div class="table-responsive">
                <table class="table table-striped" id="mdlmovetable"></table>
              </div>
            </div>
        </div>
    </div>
  </div>

@include('partials.delete-modal')
@endsection

@section('styles')
<style>
  .stat-card {
      transition: transform .15s ease, box-shadow .15s ease;
      border: 2px solid transparent;
  }
  .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(0,0,0,.12) !important;
  }
  .stat-card.active-filter {
      border: 2px solid #7367f0;
  }
  .cursor-pointer { cursor: pointer; }
</style>
@endsection

@section('scripts')
<script type="text/javascript">

  // ── state filter status dari card ──────────────────────────
  let activeStatusFilter = '';   // '' = semua, '1' = active, '0' = freeze

  // ── load stat cards via AJAX ───────────────────────────────
  function loadStats() {
    $.get("{{ route('article.stats') }}", {
        name:  $("#searchName").val(),
        code:  $("#seachCode").val(),
        group: $("#searchGroup").val(),
        supp:  $("#searchSupplier").val(),
        type:  $("#searchType").val(),
    }, function(res) {
        $('#statTotal').text(res.total.toLocaleString('id-ID'));
        $('#statActive').text(res.active.toLocaleString('id-ID'));
        $('#statFreeze').text(res.freeze.toLocaleString('id-ID'));
        feather.replace();
    });
}

  // ── klik stat card ─────────────────────────────────────────
  $('.stat-card').on('click', function() {
      $('.stat-card').removeClass('active-filter');
      $(this).addClass('active-filter');

      activeStatusFilter = $(this).data('filter').toString();

      // trigger showList dengan filter yang sudah ada + status filter
      triggerSearch();
  });

  // ── search button ──────────────────────────────────────────
  $("#btnSearch").on('click', function() {
      activeStatusFilter = '';                    // reset card filter saat manual search
      $('.stat-card').removeClass('active-filter');
      triggerSearch();
  });

  // ── reload di card table ───────────────────────────────────
  $('a[data-action="reload"]').on('click', function () {
      triggerSearch();
  });

  // ── fungsi utama trigger search ────────────────────────────
  function triggerSearch() {
      let name  = $("#searchName").val();
      let code  = $("#seachCode").val();
      let group = $("#searchGroup").val();
      let supp  = $("#searchSupplier").val();
      let type  = $("#searchType").val();
      loadStats();   // ← refresh angka card sesuai filter
      showList(name, code, group, supp, type, activeStatusFilter);
  }

  // ── showList ───────────────────────────────────────────────
  const showList = (name, code, group, supp, type, statusFilter = '') => {

      $(".loading-spinner-container").addClass("-show");

      if ($('#detailedTable tr').length > 0) {
          let table = $('#detailedTable').DataTable();
          table.destroy();
          $('#detailedTable tbody > tr').remove();
          $("#detailedTable thead > tr").remove();
      }

      showDataTables({
          tableId: "detailedTable",
          route: "{{ route('article.list') }}",
          kolom: {!! $kolom !!},
          arrColPrint: [1,2,3,4,5,6,7,8,9,10,11,12],
          columnDefs: [
              { width: '5%', targets: 0 },
              { className: 'text-right', targets: [8,9] },
          ],
          dataSearch: {
              name:         name,
              code:         code,
              group:        group,
              supp:         supp,
              type:         type,
              statusFilter: statusFilter,   // kirim ke controller
          },
          initComplete: function () {
              $(".loading-spinner-container").removeClass("-show");
          },
          orderColumn: [[2, 'asc']],
          excelFileName: 'article'
      });
  };

  // ── upload excel ───────────────────────────────────────────
  let $body = $('body');

  $(document).ready(function(){

      loadStats();   // load angka cards saat halaman dibuka

      $(document).on('click', '#deleteButton', function(event) {
          event.preventDefault();
          let href = $(this).data('href');
          $('#modalConfirmation').attr("action", href);
      });

      $('#frmExcel').on('submit', function(event){
          event.preventDefault();
          $.ajax({
              url: "{{ route('articles.safetyStock.import.excel') }}",
              method: "POST",
              data: new FormData(this),
              dataType: "json",
              contentType: false,
              cache: false,
              processData: false,
              beforeSend: function(){
                  $('#uploadExcel').attr('disabled','disabled');
              },
              success: function(data){
                  $('#file').val(null);
                  if(data.status == 1){
                      Swal.fire({
                          title: "Proses validasi...",
                          icon: "warning",
                          showConfirmButton: false,
                          didOpen: () => { Swal.showLoading(); },
                      });

                      let timerId = setInterval(() => checkVariable(), 1000);
                      function checkVariable() {
                          if (data.dataDetail.length > 0) {
                              clearInterval(timerId);
                              $(".loading-spinner-container").removeClass("-show");
                              Swal.fire({
                                  title: `Yakin akan proses update sejumlah ${data.JumlahData} data?`,
                                  showDenyButton: true,
                                  confirmButtonText: 'Yes',
                                  denyButtonText: 'Cancel',
                                  customClass: {
                                      actions: 'my-actions',
                                      cancelButton: 'order-1 right-gap',
                                      confirmButton: 'order-2',
                                      denyButton: 'order-3',
                                  },
                              }).then((result) => {
                                  if (result.isConfirmed) {
                                      updateDataSafetyStock(data.namaFile, 'update');
                                  } else if (result.isDenied) {
                                      updateDataSafetyStock(data.namaFile, 'cancel');
                                  }
                              });
                          }
                      }
                  }
              },
              error: function(xhr) {
                  let err = JSON.parse(xhr.responseText);
                  Swal.fire('Error..', err.message, 'error');
                  $(".loading-spinner-container").removeClass("-show");
              }
          });
      });
  });

  updateDataSafetyStock = (file, type) => {
      $.ajax({
          url: "{{ route('articles.safetyStock.update') }}",
          method: "POST",
          data: { file: file, type: type },
          dataType: "json",
          success: function(data){
              show_msg(data.title, data.message, data.alert);
              loadStats();   // refresh angka cards setelah update
          },
          error: function(){
              Swal.fire('Error..','Error','error');
          }
      });
  };

  $.ajaxSetup({
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

</script>
@endsection