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
      <div class="form-group col-md-7">
        <label>Nama Conversion</label>
        <input type="text" class="form-control" value="{{ $header->report_name }}" disabled>
      </div>
</div>
<div class="form-row">
      <div class="form-group col-md-4">
        <label>Periode</label>
        <input type="text" class="form-control" value="{{ $periodeLabel }}" disabled>
      </div>
      <div class="form-group col-md-3">
        <label>Conversion Value</label>
        <input type="text" class="form-control" value="{{ number_format($header->conversion_value_used, 4) }}" disabled>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-7">
        <label>Note</label>
        <textarea class="form-control" rows="4" disabled>{{ $header->note }}</textarea>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
    <h4 class="card-title">Article Detail</h4>
    <div class="form-row align-items-end" style="gap:0;">
      <div class="form-group col-auto mb-0 mr-1">
        <label class="mb-0 small">Range Tanggal</label>
        <input type="text" id="filterDateRange" class="form-control form-control-sm flatpickr-range"
               placeholder="DD-MM-YYYY to DD-MM-YYYY" autocomplete="off">
      </div>
      <div class="form-group col-auto mb-0 mr-1">
        <button type="button" id="btnApplyFilter" class="btn btn-primary btn-sm">
          <i data-feather="filter"></i> Terapkan
        </button>
      </div>
      <div class="form-group col-auto mb-0 mr-1">
        <button type="button" id="btnResetFilter" class="btn btn-light btn-sm">
          <i data-feather="rotate-ccw"></i> Reset
        </button>
      </div>
      <div class="form-group col-auto mb-0">
        <button type="button" id="btnExportArticleDetail" class="btn btn-success btn-sm">
          <i data-feather="file-text"></i> Export Excel
        </button>
      </div>
    </div>
  </div>
  <div class="card-body">
    <small class="text-muted d-block mb-2">Range tanggal hanya boleh di dalam periode tersimpan: {{ \Carbon\Carbon::parse($periodeStart)->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($periodeEnd)->format('d-m-Y') }}.</small>
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
        <tbody id="articleDetailBody">
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
  const URL_DETAIL_DN     = "{{ route('conversionReport.list.detail.dn') }}";
  const URL_RANGE_FILTER  = "{{ route('conversionReport.showRangeFilter') }}";
  const URL_EXPORT_RANGE  = "{{ route('conversionReport.exportRange') }}";
  const REPORT_ID         = "{{ $id }}";
  const PERIODE_START     = "{{ $periodeStart }}";
  const PERIODE_END       = "{{ $periodeEnd }}";

  $(document).on('click', '.btn-info-row', function () {
    loadDetailDnModal($(this).data('det-id'), $(this).data('label'));
  });

  function humanizeShow(n) {
    n = parseFloat(n) || 0;
    return n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function renderArticleDetailRows(rows) {
    let html = '';
    rows.forEach((r, i) => {
      const isPainting = !!r.is_painting;
      html += `<tr>
        <td class="text-center">${i + 1}</td>
        <td>${r.article_alternative_code || r.article_code}</td>
        <td>${r.article_desc || ''}</td>
        <td>${r.customer_names || ''}</td>
        <td class="text-right">${humanizeShow(r.total_qty)} ${r.uom || ''}</td>
        <td class="text-right">${humanizeShow(r.avg_selling_price)}</td>
        <td class="text-right">${humanizeShow(r.avg_purchase_price)}</td>
        <td class="text-right">${isPainting ? humanizeShow(r.conversion) : '-'}</td>
        <td class="text-right">${isPainting ? '-' : humanizeShow(r.conversion)}</td>
        <td class="text-center">
          <button type="button" class="btn btn-icon btn-flat-primary btn-info-row"
                  data-det-id="${r.det_id}" data-label="${r.article_code}">
            <i data-feather="info"></i>
          </button>
        </td>
      </tr>`;
    });
    $('#articleDetailBody').html(html || '<tr><td colspan="10" class="text-center text-muted">Tidak ada data.</td></tr>');
    if (window.feather) feather.replace({ width: 14, height: 14 });
  }

  // dd-mm-yyyy -> yyyy-mm-dd
  function dmyToYmd(dmy) {
    const p = dmy.trim().split('-');
    return p.length === 3 ? `${p[2]}-${p[1]}-${p[0]}` : '';
  }

  // yyyy-mm-dd -> dd-mm-yyyy
  function ymdToDmy(ymd) {
    const p = ymd.trim().split('-');
    return p.length === 3 ? `${p[2]}-${p[1]}-${p[0]}` : '';
  }

  function currentRange() {
    const raw = ($('#filterDateRange').val() || '').trim();
    const parts = raw.split(' to ').map((s) => s.trim()).filter(Boolean);
    if (parts.length === 2) {
      return { start: dmyToYmd(parts[0]) || PERIODE_START, end: dmyToYmd(parts[1]) || PERIODE_END };
    }
    if (parts.length === 1) {
      const d = dmyToYmd(parts[0]) || PERIODE_START;
      return { start: d, end: d };
    }
    return { start: PERIODE_START, end: PERIODE_END };
  }

  // yyyy-mm-dd -> Date object (hindari flatpickr salah parse minDate/maxDate/defaultDate,
  // yang selalu di-parse pakai dateFormat instance ("d-m-Y"), bukan format ISO ini)
  function ymdToDateObj(ymd) {
    const p = ymd.trim().split('-').map(Number);
    return p.length === 3 ? new Date(p[0], p[1] - 1, p[2]) : null;
  }

  let dateRangePicker = null;
  const $rangeInput = $('#filterDateRange');
  if ($rangeInput.length) {
    const periodeStartDate = ymdToDateObj(PERIODE_START);
    const periodeEndDate   = ymdToDateObj(PERIODE_END);
    dateRangePicker = $rangeInput.flatpickr({
      dateFormat: 'd-m-Y',
      mode: 'range',
      minDate: periodeStartDate,
      maxDate: periodeEndDate,
      defaultDate: [periodeStartDate, periodeEndDate],
    });
  }

  function applyDateFilter() {
    const { start, end } = currentRange();

    $('#btnApplyFilter').prop('disabled', true);
    $.get(URL_RANGE_FILTER, { id: REPORT_ID, start: start, end: end }, function (res) {
      if (!res || res.status !== 1) {
        Swal.fire('Gagal', (res && res.message) || 'Gagal memuat data.', 'error');
        return;
      }
      // clamp balik ke input kalau server membatasi ke periode
      if (dateRangePicker) {
        dateRangePicker.setDate([ymdToDmy(res.start), ymdToDmy(res.end)], false, 'd-m-Y');
      }

      renderArticleDetailRows(res.rows);
      $('#sumTotalArticle').text(res.totals.article);
      $('#sumTotalQty').text(humanizeShow(res.totals.qty));
      $('#sumConvPainting').text(humanizeShow(res.totals.painting));
      $('#sumConvNonPainting').text(humanizeShow(res.totals.nonPainting));
      $('#sumTotalConversion').text(humanizeShow(res.totals.conversion));
    }).fail(function () {
      Swal.fire('Gagal', 'Gagal memuat data untuk range tanggal tersebut.', 'error');
    }).always(function () {
      $('#btnApplyFilter').prop('disabled', false);
    });
  }

  $('#btnApplyFilter').on('click', applyDateFilter);

  $('#btnResetFilter').on('click', function () {
    if (dateRangePicker) {
      dateRangePicker.setDate([PERIODE_START, PERIODE_END], false, 'Y-m-d');
    }
    applyDateFilter();
  });

  $('#btnExportArticleDetail').on('click', function () {
    const { start, end } = currentRange();
    const params = $.param({ id: REPORT_ID, start: start, end: end });
    window.location.href = URL_EXPORT_RANGE + '?' + params;
  });
</script>
@include('conversion.conversionReport._detailDnScript')
@endsection
