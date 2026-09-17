@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="card-title">
      {{ $header->report_code }}
      @if($header->num_revision > 0) <span class="badge badge-pill badge-secondary">rev.{{ $header->num_revision }}</span> @endif
      <span class="badge badge-pill badge-light-primary">{{ $statusLabel }}</span>
    </h4>
    <a href="{{ route('conversionReport.index') }}" class="btn btn-light btn-sm">Back</a>
  </div>
  <div class="card-body">
    <div class="form-row">
      <div class="form-group col-md-4">
        <label>Nama Conversion</label>
        <input type="text" class="form-control" value="{{ $header->report_name }}" disabled>
      </div>
      <div class="form-group col-md-3">
        <label>Periode</label>
        <input type="text" class="form-control" value="{{ $periodeLabel }}" disabled>
      </div>
      <div class="form-group col-md-3">
        <label>Conversion Value dipakai</label>
        <input type="text" class="form-control" value="{{ number_format($header->conversion_value_used, 4) }}" disabled>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-12">
        <label>Note</label>
        <textarea class="form-control" rows="2" disabled>{{ $header->note }}</textarea>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h4 class="card-title">Article Detail</h4>
  </div>
  <div class="card-body">
    @php
      $isPainting = fn($u) => in_array(strtoupper(trim($u)), ['PCS', 'SET']);
      $sumPainting    = $details->filter(fn($d) => $isPainting($d->uom))->sum('conversion');
      $sumNonPainting = $details->filter(fn($d) => !$isPainting($d->uom))->sum('conversion');
    @endphp
    @include('conversion.conversionReport._summaryCards', [
      'cArticle'     => number_format($details->count()),
      'cQty'         => number_format($details->sum('total_qty'), 2),
      'cConversion'  => number_format($details->sum('conversion'), 2),
      'cPainting'    => number_format($sumPainting, 2),
      'cNonPainting' => number_format($sumNonPainting, 2),
    ])

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
      excelFileName: 'Detail_DN_' + label.replace(/[^A-Za-z0-9]+/g, '_'),
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
</script>
@endsection
