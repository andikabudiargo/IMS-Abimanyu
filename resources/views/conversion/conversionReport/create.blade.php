@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">{{ $subtitle }}</h4>
  </div>
  <div class="card-body">
    <form id="frmCreate" method="POST" action="{{ route('conversionReport.store') }}">
      @csrf

      <div class="form-row">
        <div class="form-group col-md-3">
          <label>Nomor Conversion</label>
          <input type="text" class="form-control" value="Auto-generated" disabled>
        </div>
        <div class="form-group col-md-2">
          <label for="periode">Periode (Bulan) <span class="text-danger">*</span></label>
          <select class="select2 form-control" id="periode" name="periode" required>
            <option value="">-- Bulan --</option>
            @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $m)
              <option value="{{ $i + 1 }}">{{ $m }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-2">
          <label for="tahun">Tahun <span class="text-danger">*</span></label>
          <select class="select2 form-control" id="tahun" name="tahun" required>
            <option value="">-- Tahun --</option>
            @for ($y = 2023; $y <= date('Y'); $y++)
              <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-7">
          <label for="reportName">Nama Conversion <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="reportName" name="reportName" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-7">
          <label for="note">Note</label>
          <textarea class="form-control" id="note" name="note" rows="4"></textarea>
        </div>
      </div>

      <hr>
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Delivery Article List</h5>
        <button type="button" class="btn btn-outline-success btn-sm d-none" id="btnExport">
          <i data-feather="download" class="align-middle mr-50"></i>
          <span class="align-middle">Export Excel</span>
        </button>
      </div>
      <div id="previewEmpty" class="text-muted mb-2">Pilih Periode (Bulan) &amp; Tahun untuk menarik data delivery.</div>
      <div id="previewLoading" class="text-muted mb-2" style="display:none">
        <i data-feather="loader" class="mr-50"></i> Memuat data delivery...
      </div>
      <div id="previewDuplicate" class="alert alert-warning" style="display:none"></div>

      <div id="previewWrap" style="display:none">
        @include('conversion.conversionReport._summaryCards')

        <div class="table-responsive">
        <table class="table table-bordered table-sm">
          <thead class="thead-light">
            <tr>
              <th style="width:4%">No</th>
              <th>Article Code</th>
              <th>Article Desc</th>
              <th>Customer</th>
              <th class="text-right">Qty</th>
              <th class="text-right">Konversi Painting</th>
              <th class="text-right">Konversi Non Painting</th>
              <th style="width:6%">Action</th>
            </tr>
          </thead>
          <tbody id="previewRows"></tbody>
        </table>
        </div>
      </div>

      <hr>
      <div class="form-row mt-1">
        <div class="col-12">
          <a href="{{ route('conversionReport.index') }}" class="btn btn-light">Back</a>
          <button type="submit" class="btn btn-primary" id="btnSave" disabled>Save</button>
        </div>
      </div>
    </form>
  </div>
</div>

@include('conversion.conversionReport._detailDnModal')
@endsection

@section('scripts')
<script type="text/javascript">
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  const URL_PREVIEW_PERIOD = "{{ route('conversionReport.previewPeriod') }}";
  const URL_EXPORT_PREVIEW = "{{ route('conversionReport.exportPreview') }}";
  const EXCLUDE_ID = null;
</script>
@include('conversion.conversionReport._previewScript')
<script type="text/javascript">
  $('#frmCreate').on('submit', function () {
    if (previewRows.length === 0) {
      Swal.fire('Warning', 'Belum ada data delivery yang ditarik untuk periode ini.', 'warning');
      return false;
    }
  });
</script>
@endsection
