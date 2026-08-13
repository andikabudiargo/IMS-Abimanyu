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
                    <button type="submit" class="btn btn-primary">
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
{{-- STATS + PIE CHART --}}
<section id="section-stats">
    <div class="row match-height">

        {{-- KIRI: 3 card vertikal --}}
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="d-flex flex-column h-100" style="gap: 1rem;">

                <div class="card stat-card cursor-pointer mb-0 flex-fill" id="cardTotal" data-filter="">
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

                <div class="card stat-card cursor-pointer mb-0 flex-fill" id="cardActive" data-filter="1">
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

                <div class="card stat-card cursor-pointer mb-0 flex-fill" id="cardFreeze" data-filter="0">
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

        {{-- KANAN: Pie Chart --}}
<div class="col-lg-6 col-md-6 col-sm-12">
    <div class="card h-100 mb-0">
        <div class="card-header pb-0">
            <h6 class="text-muted mb-25" style="padding-left: 1rem;">QTY BASED ON ARTICLE TYPE</h6>
        </div>
        <div class="card-body" style="padding: 2rem;">
            <div style="position: relative; height: 270px; width: 100%;">
                <canvas id="pieArticleType"></canvas>
            </div>
        </div>
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

  #section-stats .stat-card {
    height: 100%;
}
.card-header .heading-elements a {
    color: #667085;
    transition: color 0.15s ease;
}
.card-header .heading-elements a:hover {
    color: #344054;
}
#section-stats .row.match-height {
    align-items: stretch;
}
/* Jarak konsisten antar section */
#section-safety-stock,
#section-stats,
#table-article {
    margin-bottom: 1.5rem;
}

/* Biar row di dalam section gak nempel juga */
#section-stats .row {
    margin-bottom: 0;
}

#section-stats .card,
#section-safety-stock .card,
#table-article .card {
    border: 1px solid #e4e7ec;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border-radius: 8px;
}

/* card-header lebih formal: border bawah tipis, bukan cuma padding */
#section-stats .card-header,
#table-article .card-header {
    padding: 1rem 1.25rem;
}

#section-stats .card-header .card-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1d2939;
    margin-bottom: 0;
}

.stat-card .avatar {
    border-radius: 8px;   /* kotak rounded, bukan bulat penuh - kesan lebih enterprise */
}

.stat-card h2 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #101828;
}

.stat-card h6 {
    font-size: 0.8125rem;
    font-weight: 500;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: #667085;
}

/* ── SweetAlert custom ── */
.swal-label-body { font-family: inherit; }
.swal-label-preview {
    display: flex; align-items: center; gap: 14px;
    background: #f8f9fa; border: 1px solid #e0e0e0;
    border-radius: 10px; padding: 14px 16px; margin: 14px 0 0; text-align: left;
}
.swal-label-preview img {
    width: 72px; height: 72px; object-fit: contain; flex-shrink: 0;
    border: 1px solid #ddd; border-radius: 6px; background: #fff; padding: 4px;
}
.swal-label-preview .prev-info { line-height: 1.4; overflow: hidden; }
.swal-label-preview .prev-code { font-weight: 700; font-size: 15px; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 3px; }
.swal-label-preview .prev-desc { font-size: 12px; color: #666; white-space: normal; line-height: 1.4; }

.swal2-qty-wrap {
    display: flex; align-items: center; justify-content: center;
    gap: 10px; margin: 14px 0 0;
}
.swal2-qty-wrap label { font-size: 13px; color: #555; font-weight: 600; }
.swal2-qty-input {
    width: 85px; text-align: center;
    border: 1.5px solid #d0d0d0; border-radius: 6px;
    padding: 5px 8px; font-size: 15px; font-weight: 700; outline: none;
}
.swal2-qty-input:focus { border-color: #7367f0; }
.swal2-qty-btn {
    width: 30px; height: 30px; border-radius: 50%;
    border: 1.5px solid #7367f0; background: #fff;
    color: #7367f0; font-size: 18px; line-height: 1;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.swal2-qty-btn:hover { background: #7367f0; color: #fff; }

/* Print method tabs */
.print-method-tabs {
    display: flex; gap: 6px; margin: 14px 0 0; justify-content: center;
}
.pm-tab {
    flex: 1; padding: 7px 6px; border-radius: 8px; border: 1.5px solid #d0d0d0;
    background: #fff; cursor: pointer; font-size: 11px; font-weight: 600;
    color: #666; transition: all .2s; text-align: center; line-height: 1.3;
}
.pm-tab.active { border-color: #7367f0; background: #f0eeff; color: #7367f0; }
.pm-tab:hover:not(.active) { border-color: #b0b0b0; background: #f5f5f5; }
.pm-tab-icon { font-size: 16px; display: block; margin-bottom: 2px; }

.pm-panel { display: none; margin-top: 10px; }
.pm-panel.active { display: block; }

.pm-info {
    font-size: 11px; color: #888; background: #f8f9fa;
    border-radius: 6px; padding: 8px 10px; line-height: 1.5; text-align: left;
}
.pm-info a { color: #7367f0; }
.pm-info .status-dot {
    display: inline-block; width: 8px; height: 8px; border-radius: 50%;
    margin-right: 4px; background: #ccc;
}
.pm-info .status-dot.ok  { background: #28c76f; }
.pm-info .status-dot.err { background: #ea5455; }

.pm-ip-row {
    display: flex; gap: 6px; margin-top: 8px; align-items: center;
}
.pm-ip-input {
    flex: 1; border: 1.5px solid #d0d0d0; border-radius: 6px;
    padding: 5px 8px; font-size: 12px; outline: none;
}
.pm-ip-input:focus { border-color: #7367f0; }
.pm-ip-btn {
    padding: 5px 10px; border-radius: 6px; border: none;
    background: #7367f0; color: #fff; font-size: 11px; cursor: pointer;
}

@media print {
    body * { visibility: hidden !important; }
    #labelPrintArea, #labelPrintArea * { visibility: visible !important; }
    #labelPrintArea { position: fixed !important; top:0; left:0; width:100%; background:#fff; }
}
.label-sheet { display: flex; flex-wrap: wrap; }
.label-card{
    width:30mm; height:20mm;
    border:0.3mm solid #000;      /* hitam solid, bukan #ccc */
    padding:1mm 1.5mm;
    display:flex; flex-direction:column; justify-content:space-between;
    overflow:visible;
    page-break-inside:avoid;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.label-top { display: flex; align-items: center; gap: 1.5mm; flex: 1; min-height: 0; overflow: hidden; }
.label-qr  { width: 10mm; height: 10mm; flex-shrink: 0; object-fit: contain; }
.label-text { overflow: hidden; }
.label-altcode { 
    font-size: 5.5pt; 
    font-weight: 900;
    color: #000;
    -webkit-font-smoothing: none;
    text-shadow: none !important;
    white-space: nowrap;
    overflow: visible;       /* ← dari hidden jadi visible */
    text-overflow: clip;     /* ← hapus ellipsis */
}
.label-desc { 
    font-size: 4.5pt; 
    color: #000;
    font-weight: 600;
    line-height: 1.2; 
    text-shadow: none !important;
}
.label-footer { 
    font-size: 3.5pt; 
    font-weight: 600;
    color: #555; 
    border-top: 0.2mm solid #999; 
    padding-top: 0.5mm; 
    text-shadow: none !important;
}
</style>
@endsection

{{-- Area print (hidden di layar) --}}
<div id="labelPrintArea" style="display:none;"></div>

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"></script>
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
      loadPieChartByType();

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
            $('#uploadExcel').removeAttr('disabled');

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
            } else {
                $(".loading-spinner-container").removeClass("-show");

                let errorList = Array.isArray(data.message)
                    ? data.message.map(m => Array.isArray(m) ? m[0] : m).join('<br>')
                    : data.message;

                Swal.fire({
                    title: data.title,
                    html: (data.pesan ? data.pesan + '<br><br>' : '') + errorList,
                    icon: 'error'
                });
            }
        },
        error: function(xhr) {
            let err = JSON.parse(xhr.responseText);
            Swal.fire('Error..', err.message, 'error');
            $(".loading-spinner-container").removeClass("-show");
            $('#uploadExcel').removeAttr('disabled');
        }
    });   // ⬅️ TUTUP $.ajax({...})
});       // ⬅️ TUTUP .on('submit', function(event){...})

updateDataSafetyStock = (file, type) => {
    $.ajax({
        url: "{{ route('articles.safetyStock.update') }}",
        method: "POST",
        data: { file: file, type: type },
        dataType: "json",
        success: function(data){
            show_msg(data.title, data.message, data.alert);
            loadStats();
        },
        error: function(){
            Swal.fire('Error..','Error','error');
        }
    });
};

let pieChartInstance = null;

const pieColors = [
    '#4C6FFF', '#00A76F', '#F04438', '#F79009',
    '#0BA5EC', '#667085', '#7A5AF8', '#36BFFA',
    '#FF6B6B', '#12B76A'
];

// Plugin custom untuk nulis teks di tengah doughnut
const centerTextPlugin = {
    id: 'centerText',
    afterDraw: (chart) => {
        const { ctx, chartArea: { width, height, left, top } } = chart;
        const active = chart.getActiveElements();

        ctx.save();
        const centerX = left + width / 2;
        const centerY = top + height / 2;

        if (active.length > 0) {
            const idx = active[0].index;
            const label = chart.data.labels[idx];
            const value = chart.data.datasets[0].data[idx];
            const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
            const pct = ((value / total) * 100).toFixed(1);

            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            ctx.font = '600 11px Inter, sans-serif';
            ctx.fillStyle = '#475467';
            ctx.fillText(label, centerX, centerY - 12);

            ctx.font = '700 18px Inter, sans-serif';
            ctx.fillStyle = '#101828';
            ctx.fillText(value.toLocaleString('id-ID'), centerX, centerY + 6);

            ctx.font = '500 10px Inter, sans-serif';
            ctx.fillStyle = '#667085';
            ctx.fillText(`${pct}%`, centerX, centerY + 22);
        } else {
            const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            ctx.font = '600 10px Inter, sans-serif';
            ctx.fillStyle = '#667085';
            ctx.fillText('TOTAL', centerX, centerY - 10);

            ctx.font = '700 20px Inter, sans-serif';
            ctx.fillStyle = '#101828';
            ctx.fillText(total.toLocaleString('id-ID'), centerX, centerY + 10);
        }
        ctx.restore();
    }
};

function loadPieChartByType() {
    $.get("{{ route('article.statsByType') }}", function(res) {
        const ctx = document.getElementById('pieArticleType').getContext('2d');

        if (pieChartInstance) {
            pieChartInstance.destroy();
        }

        const total = res.values.reduce((a, b) => a + b, 0);

        pieChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: res.labels,
                datasets: [{
                    data: res.values,
                    backgroundColor: pieColors,
                    borderColor: '#fff',
                    borderWidth: 2,
                    hoverBorderWidth: 3,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,   // ⬅️ KUNCI UTAMA — chart tidak dipaksa persegi
                cutout: '65%',
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 800,
                    easing: 'easeOutQuart'
                },
                onHover: (event, activeElements, chart) => {
                    chart.draw();
                },
                plugins: {
                    legend: {
                        position: 'left',
                        align: 'center',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,          // ⬅️ dot lebih kecil
                            boxHeight: 8,
                            padding: 10,          // ⬅️ jarak antar item lebih rapat
                            font: { size: 11, weight: '500', family: "'Inter', sans-serif" },
                            color: '#667085',

                            generateLabels: function(chart) {
    const dataset = chart.data.datasets[0];
    return chart.data.labels.map((code, i) => {
        const value = dataset.data[i];
        const name = res.names[i] || '';

        return {
            text: `${code} - ${name} (${value.toLocaleString('id-ID')})`,
            fillStyle: dataset.backgroundColor[i],
            strokeStyle: dataset.borderColor,
            lineWidth: 1,
            pointStyle: 'circle',
            index: i
        };
    });
}
                        }
                    },
                    tooltip: { enabled: false },
                    datalabels: { display: false }
                }
            },
            plugins: [ChartDataLabels, centerTextPlugin]
        });
    });
}

$(function () {

    // ── State BrowserPrint ──
    var bpStatus  = 'unknown';
    var bpDevice  = null;
    var activePrintMethod = 'browser';

    // ── Cek BrowserPrint ──
    function checkBrowserPrint(cb) {
        if (typeof BrowserPrint === 'undefined') {
            bpStatus = 'err'; cb && cb(false); return;
        }
        BrowserPrint.getDefaultDevice('printer', function(device) {
            if (device && device.uid) {
                bpDevice = device; bpStatus = 'ok'; cb && cb(true);
            } else {
                BrowserPrint.getLocalDevices(function(devs) {
                    if (devs && devs.printer && devs.printer.length > 0) {
                        bpDevice = devs.printer[0]; bpStatus = 'ok'; cb && cb(true);
                    } else {
                        bpStatus = 'err'; cb && cb(false);
                    }
                }, function() { bpStatus = 'err'; cb && cb(false); }, 'printer');
            }
        }, function() { bpStatus = 'err'; cb && cb(false); });
    }

    // ── Load SDK — pakai port tersimpan dulu, fallback ke kandidat lain ──
    var savedBpUrl = localStorage.getItem('zebra_bp_url') || '';
    var bpCandidates = [
        'https://localhost:9101/zebra.js',
        'https://localhost:9100/zebra.js',
        'http://localhost:9101/zebra.js',
        'http://localhost:9100/zebra.js',
    ];
    if (savedBpUrl) {
        // taruh yang tersimpan di urutan pertama
        bpCandidates = [savedBpUrl + '/zebra.js'].concat(
            bpCandidates.filter(function(u) { return u !== savedBpUrl + '/zebra.js'; })
        );
    }
    (function loadBPSDK(urls, idx) {
        if (idx >= urls.length) { bpStatus = 'err'; return; }
        var s = document.createElement('script');
        s.src = urls[idx];
        s.onload = function () { checkBrowserPrint(); };
        s.onerror = function () { loadBPSDK(urls, idx + 1); };
        document.head.appendChild(s);
    })(bpCandidates, 0);

    // ── Helper: render status dot USB ──
    function getUsbStatusHtml() {
        if (bpStatus === 'ok') {
            return '<span class="status-dot ok"></span> BrowserPrint terdeteksi ✓';
        }
        if (bpStatus === 'err') {
            return '<span class="status-dot err"></span> BrowserPrint tidak terdeteksi. ' +
                   'Klik <b>🔍 Deteksi Port</b> di bawah, atau buka ' +
                   '<a href="https://localhost:9101" target="_blank">https://localhost:9101</a> ' +
                   'lalu klik Advanced → Proceed, kemudian klik <b>🔄 Coba Lagi</b>.';
        }
        return '<span class="status-dot"></span> Memeriksa...';
    }

    // ── applyBPPort: simpan & reload SDK dari port yang ditemukan ──
    window.applyBPPort = function(proto, port) {
        var baseUrl = proto + '://localhost:' + port;
        var $info   = $('#pmPanelUsb .pm-info');
        $info.html('<span class="status-dot"></span> Menghubungkan ke ' + baseUrl + '...');

        var s = document.createElement('script');
        s.src = baseUrl + '/zebra.js?t=' + Date.now();
        s.onload = function () {
            checkBrowserPrint(function(ok) {
                if (ok) {
                    localStorage.setItem('zebra_bp_url', baseUrl);
                    $info.html('<span class="status-dot ok"></span> BrowserPrint terdeteksi ✓ (' + baseUrl + ')');
                    $('#diagResult').html('');
                } else {
                    $info.html('<span class="status-dot err"></span> Port ' + baseUrl + ' respond tapi printer tidak ditemukan. Cek kabel USB printer.');
                }
            });
        };
        s.onerror = function () {
            $info.html('<span class="status-dot err"></span> Gagal load SDK dari ' + baseUrl + '. Coba port lain.');
        };
        document.head.appendChild(s);
    };

    // ── Klik Print Label ──
    $(document).on('click', '.btn-print-label', function () {
        var encId   = $(this).data('id');
        var code    = $(this).data('code');
        var desc    = $(this).data('desc');
        var savedIp = localStorage.getItem('zebra_ip') || '';

        Swal.fire({
            title: '<span style="font-size:15px">🖨️ Print Label Artikel</span>',
            html: `
            <div class="swal-label-body">
                <div class="swal-label-preview">
                    <img src="" id="swalQrImg" onerror="this.style.opacity=0.2">
                    <div class="prev-info" style="min-width:0">
                        <div class="prev-code">${code}</div>
                        <div class="prev-desc">${desc}</div>
                    </div>
                </div>

                <div class="swal2-qty-wrap">
                    <label>Jumlah Label</label>
                    <button type="button" class="swal2-qty-btn" id="btnQtyMinus">−</button>
                    <input type="number" id="inputQty" class="swal2-qty-input" value="1" min="1" max="100">
                    <button type="button" class="swal2-qty-btn" id="btnQtyPlus">+</button>
                </div>

                <div class="print-method-tabs">
                    <div class="pm-tab active" data-method="browser">
                        <span class="pm-tab-icon">🌐</span>Browser Print
                    </div>
                    <div class="pm-tab" data-method="usb">
                        <span class="pm-tab-icon">🔌</span>USB Direct<br><small>(ZPL)</small>
                    </div>
                    <div class="pm-tab" data-method="network">
                        <span class="pm-tab-icon">🖧</span>Network IP<br><small>(ZPL)</small>
                    </div>
                </div>

                <div id="pmPanelBrowser" class="pm-panel active">
                    <div class="pm-info">
                        Cetak via dialog print browser. Pastikan Zebra sudah dipilih
                        sebagai printer default &amp; ukuran kertas diset <b>30×20mm</b>.
                    </div>
                </div>

                <div id="pmPanelUsb" class="pm-panel">
                    <div class="pm-info" id="usbStatusInfo">
                        ${getUsbStatusHtml()}
                    </div>
                    <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">
                        <button type="button" id="btnDiagnostic"
                            style="flex:1;min-width:120px;padding:6px;border-radius:6px;
                                   border:1.5px solid #7367f0;background:#f0eeff;color:#7367f0;
                                   font-size:11px;cursor:pointer;font-weight:600;">
                            🔍 Deteksi Port
                        </button>
                        <a href="https://localhost:9101" target="_blank"
                            style="flex:1;min-width:120px;padding:6px;border-radius:6px;
                                   border:1.5px solid #f79009;background:#fffbf0;color:#f79009;
                                   font-size:11px;text-align:center;text-decoration:none;font-weight:600;">
                            🔐 Trust Cert
                        </a>
                        <button type="button" id="btnRetryBP"
                            style="flex:1;min-width:120px;padding:6px;border-radius:6px;border:none;
                                   background:#7367f0;color:#fff;font-size:11px;
                                   cursor:pointer;font-weight:600;">
                            🔄 Coba Lagi
                        </button>
                    </div>
                    <div id="diagResult" style="margin-top:6px;"></div>
                </div>

                <div id="pmPanelNetwork" class="pm-panel">
                    <div class="pm-info">
                        Kirim ZPL ke printer Zebra via <b>IP Address</b> (LAN).<br>
                        <div class="pm-ip-row">
                            <input type="text" id="inputZebraIp" class="pm-ip-input"
                                   placeholder="192.168.1.xxx" value="${savedIp}">
                            <button type="button" class="pm-ip-btn" id="btnTestIp">Test</button>
                        </div>
                        <span id="ipTestResult" style="font-size:10px;"></span>
                    </div>
                </div>

                <div style="font-size:10px;color:#bbb;margin-top:8px;">
                    Ukuran label: 30 × 20 mm &nbsp;|&nbsp; Printer: Zebra ZD220/ZD230
                </div>
            </div>`,
            showCancelButton: true,
            confirmButtonText: '<i class="feather icon-printer"></i> Print',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#7367f0',
            cancelButtonColor: '#aaa',
            width: 520,
            didOpen: function () {

                // ── Tab switch ──
                $(document).on('click.swal', '.pm-tab', function () {
                    $('.pm-tab').removeClass('active');
                    $('.pm-panel').removeClass('active');
                    $(this).addClass('active');
                    var m = $(this).data('method');
                    activePrintMethod = m;
                    $('#pmPanel' + m.charAt(0).toUpperCase() + m.slice(1)).addClass('active');
                });

                // ── Qty buttons ──
                $('#btnQtyMinus').on('click', function () {
                    var v = parseInt($('#inputQty').val()) || 1;
                    if (v > 1) $('#inputQty').val(v - 1);
                });
                $('#btnQtyPlus').on('click', function () {
                    var v = parseInt($('#inputQty').val()) || 1;
                    if (v < 100) $('#inputQty').val(v + 1);
                });

                // ── Test IP ──
                $('#btnTestIp').on('click', function () {
                    var ip = $('#inputZebraIp').val().trim();
                    if (!ip) { $('#ipTestResult').html('<span style="color:#ea5455">Masukkan IP dulu.</span>'); return; }
                    var ipReg = /^(\d{1,3}\.){3}\d{1,3}$/;
                    if (ipReg.test(ip)) {
                        localStorage.setItem('zebra_ip', ip);
                        $('#ipTestResult').html('<span style="color:#28c76f">✓ Format IP valid. IP disimpan.</span>');
                    } else {
                        $('#ipTestResult').html('<span style="color:#ea5455">Format IP tidak valid.</span>');
                    }
                });

                // ── Deteksi Port otomatis ──
                $('#btnDiagnostic').on('click', function () {
                    var $btn    = $(this);
                    var $result = $('#diagResult');
                    $btn.text('Mendeteksi...').prop('disabled', true);
                    $result.html('<div style="font-size:11px;color:#888;padding:4px 0;">Memindai port localhost...</div>');

                    var candidates = [
                        { proto: 'https', port: 9101 },
                        { proto: 'https', port: 9100 },
                        { proto: 'http',  port: 9101 },
                        { proto: 'http',  port: 9100 },
                        { proto: 'https', port: 8080 },
                        { proto: 'http',  port: 8080 },
                    ];
                    var found   = [];
                    var checked = 0;
                    var total   = candidates.length;

                    candidates.forEach(function(c) {
                        var url = c.proto + '://localhost:' + c.port + '/';
                        fetch(url, {
                            method: 'GET',
                            signal: AbortSignal.timeout(2500)
                        })
                        .then(function(r) { return r.text(); })
                        .then(function(t) {
                            var isZebra = t.toLowerCase().includes('api_version') ||
                                          t.toLowerCase().includes('zebra') ||
                                          t.toLowerCase().includes('printer');
                            found.push({ proto: c.proto, port: c.port, isZebra: isZebra, preview: t.substring(0, 60) });
                        })
                        .catch(function() { /* port tidak respond */ })
                        .finally(function() {
                            checked++;
                            if (checked === total) { renderDiagResult(found, $result, $btn); }
                        });
                    });
                });

                // ── Coba Lagi ──
                $('#btnRetryBP').on('click', function () {
                    var $btn  = $(this);
                    var saved = localStorage.getItem('zebra_bp_url') || 'https://localhost:9101';
                    $btn.text('Memeriksa...').prop('disabled', true);
                    $('#usbStatusInfo').html('<span class="status-dot"></span> Menghubungkan...');

                    var s = document.createElement('script');
                    s.src = saved + '/zebra.js?t=' + Date.now();
                    s.onload = function () {
                        checkBrowserPrint(function(ok) {
                            $('#usbStatusInfo').html(getUsbStatusHtml());
                            $btn.text(ok ? '✓ Terdeteksi' : '🔄 Coba Lagi')
                                .css('background', ok ? '#28c76f' : '#7367f0')
                                .prop('disabled', false);
                        });
                    };
                    s.onerror = function () {
                        bpStatus = 'err';
                        $('#usbStatusInfo').html(getUsbStatusHtml());
                        $btn.text('🔄 Coba Lagi').prop('disabled', false);
                    };
                    document.head.appendChild(s);
                });
            },

            willClose: function () {
                $(document).off('click.swal');
            },

            preConfirm: function () {
                var qty = parseInt($('#inputQty').val()) || 0;
                if (qty < 1 || qty > 100) {
                    Swal.showValidationMessage('Jumlah label harus antara 1–100');
                    return false;
                }
                if (activePrintMethod === 'network') {
                    var ip = $('#inputZebraIp').val().trim();
                    if (!ip) {
                        Swal.showValidationMessage('Masukkan IP Address printer terlebih dahulu.');
                        return false;
                    }
                    localStorage.setItem('zebra_ip', ip);
                }
                return $.ajax({
                    url: '{{ route("article.printLabel") }}',
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}', id: encId, qty: qty }
                }).then(function (res) {
                    if (res.status !== 1) { Swal.showValidationMessage(res.message); return false; }
                    return res;
                }).catch(function () {
                    Swal.showValidationMessage('Terjadi kesalahan server.');
                    return false;
                });
            }
        }).then(function (result) {
            if (!result.isConfirmed || !result.value) return;
            var data = result.value;
            if (activePrintMethod === 'browser')  { doBrowserPrint(data); }
            else if (activePrintMethod === 'usb') { doUsbZpl(data); }
            else if (activePrintMethod === 'network') { doNetworkZpl(data); }
        });
    });

    // ── Render hasil deteksi port ──
    function renderDiagResult(found, $result, $btn) {
        $btn.text('🔍 Deteksi Port').prop('disabled', false);

        if (found.length === 0) {
            $result.html(
                '<div style="color:#ea5455;font-size:11px;margin-top:4px;padding:6px 8px;' +
                'background:#fff5f5;border-radius:6px;border:1px solid #ffd0d0;">' +
                '✗ Tidak ada port yang respond.<br>' +
                'Pastikan aplikasi <b>Zebra BrowserPrint</b> sudah berjalan (cek icon di system tray Windows).' +
                '</div>'
            );
            return;
        }

        var html = '<div style="margin-top:4px;">';
        found.forEach(function(f) {
            var color = f.isZebra ? '#28c76f' : '#f79009';
            var bg    = f.isZebra ? '#f0fff8' : '#fffbf0';
            var icon  = f.isZebra ? '✓ ZEBRA' : '? Aktif';
            html +=
                '<div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;padding:5px 8px;' +
                'border-radius:6px;background:' + bg + ';border:1px solid ' + color + '44;">' +
                '<span style="color:' + color + ';font-weight:700;font-size:10px;min-width:55px;">' + icon + '</span>' +
                '<span style="font-size:11px;font-weight:600;">' + f.proto + '://localhost:' + f.port + '</span>';
            if (f.isZebra) {
                html +=
                    '<button type="button" onclick="applyBPPort(\'' + f.proto + '\',' + f.port + ')"' +
                    ' style="margin-left:auto;padding:2px 10px;border-radius:4px;border:none;' +
                    'background:#7367f0;color:#fff;font-size:10px;cursor:pointer;font-weight:600;">' +
                    'Pakai ini</button>';
            }
            html += '</div>';
            if (f.preview) {
                html += '<div style="font-size:9px;color:#aaa;padding:0 8px 4px;' +
                        'word-break:break-all;font-family:monospace;">' +
                        f.preview.replace(/</g,'&lt;') + '</div>';
            }
        });
        html += '</div>';
        $result.html(html);
    }

    // ══════════════════════════════════════
    // MODE 1: Browser Print (popup window)
    // ══════════════════════════════════════
    function doBrowserPrint(data) {
        var article   = data.article;
        var qrUrl     = data.qr_url;
        var printedBy = data.printed_by;
        var printedAt = data.printed_at;
        var qty       = data.qty;

        var labels = '';
        for (var i = 0; i < qty; i++) {
            labels +=
                '<div class="label-card">' +
                '<div class="label-top">' +
                '<img class="label-qr" src="' + qrUrl + '">' +
                '<div class="label-text">' +
                '<div class="label-altcode">' + article.article_alternative_code + '</div>' +
                '<div class="label-desc">' + article.article_desc + '</div>' +
                '</div></div>' +
                '<div class="label-footer">Dicetak: ' + printedBy + ' &bull; ' + printedAt + ' &bull; ' + (i+1) + '/' + qty + '</div>' +
                '</div>';
        }

        var html = '<!DOCTYPE html><html><head><meta charset="utf-8">' +
            '<title>Label ' + article.article_alternative_code + '</title>' +
            '<style>' +
            '@page{margin:0;size:30mm 20mm;}' +
            '*{box-sizing:border-box;margin:0;padding:0;font-family:Arial,sans-serif;text-shadow:none;}' +
            'body{background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            '.label-sheet{display:flex;flex-wrap:wrap;}' +
            '.label-card{width:30mm;height:20mm;border:0.3mm solid #000;padding:1mm 1.5mm;' +
                'display:flex;flex-direction:column;justify-content:space-between;' +
                'overflow:hidden;page-break-inside:avoid;}' +
            '.label-top{display:flex;align-items:center;gap:1.5mm;flex:1;min-height:0;overflow:hidden;}' +
           '.label-qr{width:10mm;height:10mm;flex-shrink:0;object-fit:contain;}' +
'.label-text{overflow:visible;min-width:0;flex:1;}' +
'.label-altcode{font-size:5.5pt;font-weight:900;color:#000;white-space:nowrap;overflow:visible;text-overflow:clip;}' +
            '.label-desc{font-size:4.5pt;color:#000;font-weight:600;line-height:1.2;overflow:visible;word-wrap:break-word;}' +
            '.label-footer{font-size:3.5pt;color:#555;border-top:0.2mm solid #999;padding-top:0.5mm;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}' +
            '</style></head><body>' +
            '<div class="label-sheet">' + labels + '</div>' +
            '<script>window.onload=function(){' +
                'var imgs=document.querySelectorAll("img"),loaded=0;' +
                'if(!imgs.length){window.print();return;}' +
                'imgs.forEach(function(img){' +
                    'if(img.complete){loaded++;if(loaded===imgs.length)window.print();}' +
                    'else{img.onload=img.onerror=function(){loaded++;if(loaded===imgs.length)window.print();};}' +
                '});' +
            '};' +
            '<\/script></body></html>';

        var w = window.open('', '_blank', 'width=600,height=400');
        if (!w) { Swal.fire('Popup Diblokir', 'Izinkan popup di browser Anda.', 'warning'); return; }
        w.document.write(html);
        w.document.close();
    }

    // ══════════════════════════════════════
    // MODE 2: USB Direct via BrowserPrint
    // ══════════════════════════════════════
    function doUsbZpl(data) {
        if (bpStatus !== 'ok' || !bpDevice) {
            Swal.fire({
                icon: 'error',
                title: 'BrowserPrint Tidak Terdeteksi',
                html: 'Gunakan tombol <b>🔍 Deteksi Port</b> di tab USB Direct untuk menemukan port yang aktif.',
            });
            return;
        }
        bpDevice.send(data.zpl, function () {
            Swal.fire({
                icon: 'success', title: 'Berhasil!',
                text: data.qty + ' label dikirim ke printer Zebra.',
                timer: 2000, showConfirmButton: false
            });
        }, function (err) {
            Swal.fire('Gagal Kirim', 'Error: ' + (err || 'Unknown error'), 'error');
        });
    }

    // ══════════════════════════════════════
    // MODE 3: Network / LAN via raw TCP
    // ══════════════════════════════════════
    function doNetworkZpl(data) {
        var ip = localStorage.getItem('zebra_ip') || '';
        if (!ip) { Swal.fire('IP Kosong', 'Masukkan IP Address printer.', 'warning'); return; }

        Swal.fire({ title: 'Mengirim ke printer...', didOpen: function() { Swal.showLoading(); } });

        $.ajax({
            url: '{{ route("article.printLabelNetwork") }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', zpl: data.zpl, ip: ip, port: 9100 }
        }).done(function (res) {
            if (res.status === 1) {
                Swal.fire({ icon:'success', title:'Berhasil!',
                    text: data.qty + ' label dikirim ke ' + ip,
                    timer: 2000, showConfirmButton: false });
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        }).fail(function () {
            Swal.fire('Error', 'Tidak bisa terhubung ke server.', 'error');
        });
    }

});

  $.ajaxSetup({
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });
});
</script>
@endsection