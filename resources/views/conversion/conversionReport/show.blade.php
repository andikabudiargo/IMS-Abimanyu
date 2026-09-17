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

@include('conversion.conversionReport._detailDnModal')
@endsection

@section('scripts')
<script type="text/javascript">
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
  const URL_DETAIL_DN = "{{ route('conversionReport.list.detail.dn') }}";

  $(document).on('click', '.btn-info-row', function () {
    loadDetailDnModal($(this).data('det-id'), $(this).data('label'));
  });
</script>
@include('conversion.conversionReport._detailDnScript')
@endsection
