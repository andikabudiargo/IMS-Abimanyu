@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="location-index">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <form class="needs-validation" novalidate>
              <div class="form-row">
                  <div class="col-md-3">
                      <div class="form-group">
                      <label for="searchCode">Location Code</label>
                      <input type="text" class="form-control text-uppercase" id="searchCode" name="searchCode" placeholder="mis. 007" />
                      </div>
                  </div>
                  <div class="col-md-3">
                      <div class="form-group">
                      <label for="searchName">Location Name</label>
                      <input type="text" class="form-control text-uppercase" id="searchName" name="searchName" placeholder="mis. GUDANG" />
                      </div>
                  </div>
                  <div class="col-md-3">
                      <div class="form-group">
                      <label for="searchType">Location Type</label>
                      <select class="form-control" id="searchType" name="searchType">
                          <option value="">-- Semua --</option>
                          @foreach($locationTypes as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                          @endforeach
                      </select>
                      </div>
                  </div>
                  <div class="col-md-3">
                      <div class="form-group">
                      <label for="searchDept">Departemen</label>
                      <select class="form-control" id="searchDept" name="searchDept">
                          <option value="">-- Semua --</option>
                          @foreach($depts as $d)
                            <option value="{{ $d->code }}">{{ $d->code }} — {{ $d->name }}</option>
                          @endforeach
                      </select>
                      </div>
                  </div>
              </div>
              <div class="form-row">
                  <div class="col-12">
                      <button type="button" class="btn btn-primary" id="btnSearch" name="btnSearch">Search</button>
                      <button type="button" class="btn btn-outline-secondary" id="btnReset" name="btnReset">Reset</button>
                      <a href="{{ route('location.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                  </div>
              </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<section id="table-location">
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
@include('partials.delete-modal')
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script type="text/javascript">
  $(document).ready(function(){
    let href;
    $(document).on('click', '#deleteButton', function(event) {
        event.preventDefault();
        href = $(this).data('href');
        const stock = parseFloat($(this).data('stock')) || 0;
        const code  = $(this).data('code') || '';

        $('#modalConfirmation').attr('action', href);

        const $modal = $('#smallModal');
        $modal.find('.loc-stock-info').remove();

        const info = stock !== 0
          ? `<div class="loc-stock-info alert alert-warning text-left mt-1 mb-0">
                <strong>Perhatian:</strong> Lokasi <b>${code}</b> masih memiliki stock tercatat
                <b>${stock}</b>. Lokasi tidak bisa dihapus sebelum stock-nya nol.
             </div>`
          : `<div class="loc-stock-info alert alert-light-success text-left mt-1 mb-0">
                Stock di lokasi <b>${code}</b> saat ini <b>0</b> — aman untuk dihapus.
             </div>`;

        $modal.find('.modal-body').append(info);
        $modal.find('#modalConfirmation button[type="submit"]').prop('disabled', stock !== 0);
    });

    $('#smallModal').on('hidden.bs.modal', function () {
        $(this).find('.loc-stock-info').remove();
        $(this).find('#modalConfirmation button[type="submit"]').prop('disabled', false);
    });

    showList();
  });

  let showAlert = "{{ Session::get('alert') }}";
  if (showAlert) {
    $("#alert-message-alert").fadeTo(5000, 500).slideUp(500, function(){
      $("#alert-message-alert").slideUp(500);
    });
  }

  $('a[data-action="reload"]').on('click', function () { showList(); });

  $("#btnSearch").click(function(){
      showList();
  });

  $("#btnReset").click(function(){
      $('#searchCode, #searchName').val('');
      $('#searchType, #searchDept').val('');
      showList();
  });

  function typeBadge(t) {
    const map = { area: 'info', wip: 'warning', booth: 'secondary', transit: 'danger', dept: 'success' };
    const c = map[t] || 'primary';
    return t
      ? `<span class="badge badge-light-${c} text-capitalize">${t}</span>`
      : '<span class="text-muted">—</span>';
  }

  function statusBadge(s) {
    if (s === '1' || s === 1) return '<span class="badge badge-light-success">Aktif</span>';
    if (s === '0' || s === 0) return '<span class="badge badge-light-secondary">Non-Aktif</span>';
    return '<span class="badge badge-light-success">Aktif</span>';
  }

  function articleBadges(val) {
    if (!val) return '<span class="text-muted">—</span>';
    let arr = val;
    if (typeof arr === 'string') {
      try {
        arr = JSON.parse(arr);
      } catch (e) {
        arr = arr.replace(/^\{|\}$/g, '');
        arr = arr ? arr.split(',').map(s => s.trim()) : [];
      }
    }
    if (!Array.isArray(arr) || !arr.length) return '<span class="text-muted">—</span>';
    return arr.map(a => `<span class="badge badge-light-dark mr-25 mb-25">${a}</span>`).join('');
  }

  function showList(){
    let dtdom ='<"d-flex justify-content-between align-items-center header-actions mx-1 row mt-75"' +
        '<"col-lg-12 col-xl-6" l>' +
        '<"col-lg-12 col-xl-6 pl-xl-75 pl-0"<"dt-action-buttons text-xl-right text-lg-left text-md-right text-left d-flex align-items-center justify-content-lg-end align-items-center flex-sm-nowrap flex-wrap mr-1"<"mr-1"f>B>>' +
        '>t' +
        '<"d-flex justify-content-between mx-2 row mb-1"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>';
    let arr_col_print = [1,2,3,4,5,6,7,8,9,10,11,12,13,14];

    $(function(){
      let oTable = $("#detailedTable").DataTable({
        ajax: {
          url: '{{ route("location.list") }}',
          data: function (d) {
            d.code = $('#searchCode').val();
            d.name = $('#searchName').val();
            d.type = $('#searchType').val();
            d.dept = $('#searchDept').val();
          }
        },
        processing: true,
        serverSide: true,
        dom: dtdom,
        lengthMenu: [[20, 25, 50, -1], ['20', '25', '50', 'all']],
        buttons: [
          {
            extend: 'collection',
            className: 'btn btn-outline-secondary dropdown-toggle mr-2 mt-07',
            text: feather.icons['share'].toSvg({ class: 'font-small-4 mr-50' }) + 'Export',
            buttons: [
              { extend: 'print', text: feather.icons['printer'].toSvg({ class: 'font-small-4 mr-50' }) + 'Print', className: 'dropdown-item', exportOptions: { columns: arr_col_print } },
              { extend: 'csv',   text: feather.icons['file-text'].toSvg({ class: 'font-small-4 mr-50' }) + 'Csv', className: 'dropdown-item', exportOptions: { columns: arr_col_print } },
              { extend: 'excel', text: feather.icons['file'].toSvg({ class: 'font-small-4 mr-50' }) + 'Excel', className: 'dropdown-item', exportOptions: { columns: arr_col_print } },
              { extend: 'copy',  text: feather.icons['copy'].toSvg({ class: 'font-small-4 mr-50' }) + 'Copy', className: 'dropdown-item', exportOptions: { columns: arr_col_print } },
            ],
            init: function (api, node) {
              $(node).removeClass('btn-secondary');
              $(node).parent().removeClass('btn-group');
              setTimeout(function () {
                $(node).closest('.dt-buttons').removeClass('btn-group').addClass('d-inline-flex');
              }, 50);
            }
          }
        ],
        language: { paginate: { previous: '&nbsp;', next: '&nbsp;' } },
        order: [[1, 'asc']],
        bDestroy: true,
        columns: [
          { data: 'action', name: 'action', title: '', orderable: false, searchable: false, width: '5%' },
          { data: 'location_code', name: 'l.location_code', title: 'Kode', width: '8%' },
          { data: 'location_name', name: 'l.location_name', title: 'Nama Lokasi' },
          { data: 'location_type', name: 'l.location_type', title: 'Type', render: function (d) { return typeBadge(d); } },
          { data: 'article_type', name: 'l.article_type', title: 'Article Type', orderable: false, searchable: false, render: function (d) { return articleBadges(d); } },
          { data: 'dept_code', name: 'l.dept_code', title: 'Dept', render: function (d) { return d || '<span class="text-muted">—</span>'; } },
          {
            data: 'parent_location_name', name: 'p.location_name', title: 'Parent', orderable: false,
            render: function (d, t, row) {
              return row.parent_location
                ? `${row.parent_location} — ${d || ''}`
                : '<span class="text-muted">— (parent)</span>';
            }
          },
          { data: 'status', name: 'l.status', title: 'Status', render: function (d) { return statusBadge(d); } },
          { data: 'pic', name: 'l.pic', title: 'PIC', render: function (d) { return d || '<span class="text-muted">—</span>'; } },
          { data: 'stock_qty', name: 'stock_qty', title: 'Stock', orderable: false, searchable: false, render: function (d) { return parseFloat(d) || 0; } },
          { data: 'note', name: 'l.note', title: 'Keterangan', render: function (d) { return d || '<span class="text-muted">—</span>'; } },
          { data: 'created_by', name: 'l.created_by', title: 'Created By' },
          { data: 'created_at', name: 'l.created_at', title: 'Created At' },
          { data: 'updated_by', name: 'l.updated_by', title: 'Updated By' },
          { data: 'updated_at', name: 'l.updated_at', title: 'Updated At' },
        ],
        drawCallback: function () {
          feather.replace({ width: 14, height: 14 });
        }
      });
    });
  }

  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });
</script>
@endsection
