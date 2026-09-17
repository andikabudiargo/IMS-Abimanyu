@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

@php
  $isEditable = $canEdit;
@endphp

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="card-title">
      {{ $header->report_code }}
      @if($header->num_revision > 0) <span class="badge badge-pill badge-secondary">rev.{{ $header->num_revision }}</span> @endif
      <span class="badge badge-pill badge-light-primary">{{ $statusLabel }}</span>
    </h4>
    <div>
      @if($canCancel)
        <button type="button" class="btn btn-outline-warning btn-sm" id="btnCancel">Cancel</button>
      @endif
      @if($canRevise)
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRevise">Revise</button>
      @endif
      <a href="{{ route('conversionReport.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
  </div>
  <div class="card-body">
    @if($header->status == 5 && $header->cancel_reason)
      <div class="alert alert-secondary"><b>Canceled:</b> {{ $header->cancel_reason }} ({{ $header->canceled_by }}, {{ $header->canceled_at }})</div>
    @endif

    <div class="form-row">
      <div class="form-group col-md-2">
        <label>Nomor Conversion</label>
        <input type="text" class="form-control" value="{{ $header->report_code }}" disabled>
      </div>
      <div class="form-group col-md-2">
        <label for="periode">Periode (Bulan)</label>
        <select class="select2 form-control" id="periode" name="periode" {{ $isEditable ? '' : 'disabled' }}>
          @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $m)
            <option value="{{ $i + 1 }}" {{ $header->periode == $i + 1 ? 'selected' : '' }}>{{ $m }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group col-md-2">
        <label for="tahun">Tahun</label>
        <select class="select2 form-control" id="tahun" name="tahun" {{ $isEditable ? '' : 'disabled' }}>
          @for ($y = 2023; $y <= date('Y'); $y++)
            <option value="{{ $y }}" {{ $header->tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
          @endfor
        </select>
      </div>
      <div class="form-group col-md-4">
        <label for="reportName">Nama Conversion</label>
        <input type="text" class="form-control" id="reportName" name="reportName" value="{{ $header->report_name }}" {{ $isEditable ? '' : 'disabled' }}>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-7">
        <label for="note">Note</label>
        <textarea class="form-control" id="note" name="note" rows="2" {{ $isEditable ? '' : 'disabled' }}>{{ $header->note }}</textarea>
      </div>
    </div>

    @if($isEditable)
      <button type="button" class="btn btn-primary" id="btnSave">Update</button>
    @endif
    @if($approveValidate && count($approveValidate) && $approveValidate[0]->validate)
      <input type="hidden" id="approveLevel" value="{{ $approveValidate[0]->next_level }}">
      <button type="button" class="btn btn-success" id="btnApprove">Approve (Level {{ $approveValidate[0]->next_level }})</button>
    @endif
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h4 class="card-title">Approval</h4>
  </div>
  <div class="card-body">
    <div class="form-row card-statistics">
      @forelse($approvalHistory as $val)
        <div class="col-md-3 mb-2 d-flex align-items-center">
          @if($val->status == true)
            <div class="avatar bg-light-success mr-1"><i data-feather="check" class="avatar-icon"></i></div>
          @else
            <div class="avatar bg-light-danger mr-1"><i data-feather="x" class="avatar-icon"></i></div>
          @endif
          <div>
            <h6 class="mb-0">Approve-{{ $val->approval_order }}</h6>
            <small class="text-muted">{{ $val->status == true ? $val->name : $val->petugas }}</small>
          </div>
        </div>
      @empty
        <div class="col-12 text-muted">Belum ada konfigurasi approval untuk modul ini.</div>
      @endforelse
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h4 class="card-title">Article Detail</h4>
  </div>
  <div class="card-body">
    @php
      $isPainting = function ($u) { return in_array(strtoupper(trim($u)), ['PCS', 'SET']); };
      $sumPainting    = $details->filter(function ($d) use ($isPainting) { return $isPainting($d->uom); })->sum('conversion');
      $sumNonPainting = $details->filter(function ($d) use ($isPainting) { return !$isPainting($d->uom); })->sum('conversion');
      $cArticle     = number_format($details->count());
      $cQty         = number_format($details->sum('total_qty'), 2);
      $cConversion  = number_format($details->sum('conversion'), 2);
      $cPainting    = number_format($sumPainting, 2);
      $cNonPainting = number_format($sumNonPainting, 2);
    @endphp
    @include('conversion.conversionReport._summaryCards', ['cArticle' => $cArticle, 'cQty' => $cQty, 'cConversion' => $cConversion, 'cPainting' => $cPainting, 'cNonPainting' => $cNonPainting])

    <div class="table-responsive">
      <table class="table table-bordered table-sm">
        <thead class="thead-light">
          <tr>
            <th style="width:4%">No</th>
            <th>Article Code</th>
            <th>Article Desc</th>
            <th>Customer</th>
            <th class="text-right">Qty</th>
            <th class="text-right">Avg Selling Price</th>
            <th class="text-right">Avg Purchase Price</th>
            <th class="text-right">Konversi Painting</th>
            <th class="text-right">Konversi Non Painting</th>
            <th style="width:6%">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($details as $i => $d)
            <tr>
              <td class="text-center">{{ $i + 1 }}</td>
              <td>{{ $d->article_alternative_code ?? $d->article_code }}</td>
              <td>{{ $d->article_desc }}</td>
              <td>{{ $d->customer_names }}</td>
              <td class="text-right">{{ number_format($d->total_qty, 2) }} {{ $d->uom }}</td>
              <td class="text-right">{{ number_format($d->avg_selling_price, 2) }}</td>
              <td class="text-right">{{ number_format($d->avg_purchase_price, 2) }}</td>
              <td class="text-right">{{ $isPainting($d->uom) ? number_format($d->conversion, 4) : '-' }}</td>
              <td class="text-right">{{ $isPainting($d->uom) ? '-' : number_format($d->conversion, 4) }}</td>
              <td class="text-center">
                <button type="button" class="btn btn-icon btn-flat-primary btn-info-row"
                        data-det-id="{{ $d->id }}" data-label="{{ $d->article_code }}">
                  <i data-feather="info"></i>
                </button>
              </td>
            </tr>
          @empty
            <tr><td colspan="10" class="text-center text-muted">Tidak ada data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@if($revisions->count())
<div class="card">
  <div class="card-header">
    <h4 class="card-title">Revision History</h4>
  </div>
  <div class="card-body">
    @foreach($revisions as $rev)
      <div class="mb-2 pb-2" style="border-bottom:1px solid #eee;">
        <span class="badge badge-pill badge-secondary">rev.{{ $rev->num_revision }}</span>
        <b>{{ $rev->report_code }}</b> oleh {{ $rev->revised_by }} &mdash; {{ $rev->revised_at }}
        <div class="text-muted" style="font-size:.85rem;">"{{ $rev->reason }}"</div>
      </div>
    @endforeach
  </div>
</div>
@endif

<div class="modal fade" id="mdlDetail" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-truncate pr-1">Detail DN <span id="mdlArticleLabel"></span></h5>
        <button type="button" class="close m-0 p-0" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <table id="mdlDetailTable" class="table table-hover table-sm" style="width:100%">
          <thead class="thead-light"></thead>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  const encId = "{{ $id }}";

  // Stempel tanggal+jam untuk nama file export: YYYYMMDD_HHmmss
  function exportStamp() {
    const d = new Date();
    const p = (x) => String(x).padStart(2, '0');
    return `${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}_${p(d.getHours())}${p(d.getMinutes())}${p(d.getSeconds())}`;
  }

  $(document).on('click', '.btn-info-row', function () {
    const detId = $(this).data('det-id');
    const label = $(this).data('label');

    $('#mdlArticleLabel').text('| ' + label);
    $('#mdlDetail').modal('show');

    if ($('#mdlDetailTable tr').length > 0) {
      let table = $('#mdlDetailTable').DataTable();
      table.destroy();
      $('#mdlDetailTable tbody > tr').remove();
      $('#mdlDetailTable thead > tr').remove();
    }

    showDataTables({
      tableId: "mdlDetailTable",
      route: "{{ route('conversionReport.list.detail.dn') }}",
      kolom: @json([
        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => 'No', 'orderable' => false, 'searchable' => false],
        ['data' => 'dn_number_link', 'name' => 'dn_number_link', 'title' => 'DN Number', 'orderable' => false, 'searchable' => false],
        ['data' => 'customer_name', 'name' => 'customer_name', 'title' => 'Customer'],
        ['data' => 'qty', 'name' => 'qty', 'title' => 'Qty'],
        ['data' => 'price_unit', 'name' => 'price_unit', 'title' => 'Price Unit'],
        ['data' => 'price_total', 'name' => 'price_total', 'title' => 'Price Total'],
        ['data' => 'conversion_painting', 'name' => 'conversion_painting', 'title' => 'Konversi Painting', 'orderable' => false, 'searchable' => false],
        ['data' => 'conversion_non_painting', 'name' => 'conversion_non_painting', 'title' => 'Konversi Non Painting', 'orderable' => false, 'searchable' => false],
      ]),
      dataSearch: { reportDetId: detId },
      excelFileName: 'Detail_DN_' + label.replace(/[^A-Za-z0-9]+/g, '_') + '_' + exportStamp(),
      buttons: true,
    });
  });

  // DataTables yang di-init saat modal masih width:0 bikin header/body geser.
  // Recalculate kolom setelah modal benar-benar tampil.
  $('#mdlDetail').on('shown.bs.modal', function () {
    if ($.fn.dataTable.isDataTable('#mdlDetailTable')) {
      $('#mdlDetailTable').DataTable().columns.adjust();
    }
  });

  function submitUpdate(extra) {
    $.post("{{ route('conversionReport.update') }}", $.extend({
      id: encId,
      periode: $('#periode').val(),
      tahun: $('#tahun').val(),
      reportName: $('#reportName').val(),
      note: $('#note').val(),
    }, extra), function (res) {
      if (res.status == 1) {
        Swal.fire('Success', res.message, 'success').then(() => window.location.reload());
      } else {
        Swal.fire('Error', (res.message && res.message[0]) || 'Gagal.', 'error');
      }
    }).fail(function () {
      Swal.fire('Error', 'Gagal menghubungi server.', 'error');
    });
  }

  $('#btnSave').on('click', function () {
    submitUpdate({ statusSimpan: 'save' });
  });

  $('#btnApprove').on('click', function () {
    Swal.fire({
      title: 'Approve dokumen ini?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, approve',
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        submitUpdate({ statusSimpan: 'approve', approveLevel: $('#approveLevel').val() });
      }
    });
  });

  $('#btnRevise').on('click', function () {
    Swal.fire({
      title: 'Alasan Revisi',
      html: `<textarea id="swalReviseReason" class="form-control" rows="3" maxlength="500" placeholder="Kenapa dokumen ini direvisi?"></textarea>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Simpan Revisi',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#FF9F43',
      reverseButtons: true,
      focusConfirm: false,
      preConfirm: () => {
        const reason = document.getElementById('swalReviseReason').value.trim();
        if (!reason) { Swal.showValidationMessage('Alasan revisi wajib diisi'); return false; }
        return reason;
      }
    }).then((result) => {
      if (result.isConfirmed) {
        const $form = $('<form>', { action: "{{ route('conversionReport.revision') }}", method: 'POST' });
        $form.append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }));
        $form.append($('<input>', { type: 'hidden', name: 'id', value: encId }));
        $form.append($('<input>', { type: 'hidden', name: 'nR', value: '{{ $header->num_revision }}' }));
        $form.append($('<input>', { type: 'hidden', name: 'reason', value: result.value }));
        $form.appendTo('body').submit();
      }
    });
  });

  $('#btnCancel').on('click', function () {
    Swal.fire({
      title: 'Cancel dokumen ini?',
      html: `<textarea id="swalCancelReason" class="form-control" rows="3" placeholder="Alasan cancel"></textarea>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, cancel',
      confirmButtonColor: '#EA5455',
      reverseButtons: true,
      focusConfirm: false,
      preConfirm: () => {
        const reason = document.getElementById('swalCancelReason').value.trim();
        if (!reason) { Swal.showValidationMessage('Alasan cancel wajib diisi'); return false; }
        return reason;
      }
    }).then((result) => {
      if (result.isConfirmed) {
        const $form = $('<form>', { action: "{{ route('conversionReport.cancel') }}", method: 'POST' });
        $form.append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }));
        $form.append($('<input>', { type: 'hidden', name: 'id', value: encId }));
        $form.append($('<input>', { type: 'hidden', name: 'reason', value: result.value }));
        $form.appendTo('body').submit();
      }
    });
  });
</script>
@endsection
