@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">{{ $subtitle }}</h4>
  </div>
  <div class="card-body">
    <form id="frmCreate" method="POST" action="{{ route('formChangeRequest.store') }}" enctype="multipart/form-data">
      @csrf

      <div class="form-row">
        <div class="form-group col-md-4">
          <label>Ticket Number</label>
          <input type="text" class="form-control" value="Auto-generated" disabled>
        </div>
        <div class="form-group col-md-4">
          <label for="modul">Modul <span class="text-danger">*</span></label>
          <select class="select2 form-control" id="modul" name="modul" required>
            <option value="">-- Select Modul --</option>
            @foreach($modules as $val)
              <option value="{{ $val }}">{{ $val }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-4">
          <label for="type">Type <span class="text-danger">*</span></label>
          <select class="select2 form-control" id="type" name="type" required>
            <option value="">-- Select Type --</option>
            @foreach($types as $val)
              <option value="{{ $val }}">{{ $val }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-4">
          <label for="urgency">Urgensi <span class="text-danger">*</span></label>
          <select class="select2 form-control" id="urgency" name="urgency" required>
            <option value="">-- Select Urgensi --</option>
            @foreach($urgencies as $val)
              <option value="{{ $val }}">{{ $val }}</option>
            @endforeach
          </select>
        </div>
</div>

      <div class="form-row">
        <div class="form-group col-md-8">
          <label for="description">Deskripsi / Alasan / Latar Belakang <span class="text-danger">*</span></label>
          <textarea class="form-control" id="description" name="description" rows="10" required></textarea>
        </div>
      </div>

      {{--<div class="form-row">
        <div class="form-group col-md-6">
          <label for="attachment">Lampiran (Foto / Dokumen)</label>
          <input type="file" class="form-control" id="attachment" name="attachment[]" multiple>
          <small class="text-muted">Opsional. Bisa lebih dari 1 file.</small>
        </div>
      </div>--}}

      <div id="detailSection" style="display:none">
        <hr>
        <h5>Detail Perubahan Data</h5>
        @include('formChangeRequest._detailRows', ['detail' => []])
      </div>

      <hr>
      <div class="form-row mt-1">
        <div class="col-12">
          <a href="{{ route('formChangeRequest.index') }}" class="btn btn-light">Back</a>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
  let detailRowSeq = 0;

  function toggleDetailSection() {
    if ($('#type').val() === 'Perubahan Data') {
      $('#detailSection').show();
      if ($('#detailRowContainer tr').length === 0) addDetailRow();
    } else {
      $('#detailSection').hide();
    }
  }

  function addDetailRow(row = {}) {
    detailRowSeq++;
    const idx = detailRowSeq;
    const html = `
      <tr id="detailRow${idx}">
        <td class="text-center align-middle rowNo">${$('#detailRowContainer tr').length + 1}</td>
        <td><input type="text" class="form-control form-control-sm" name="detail[${idx}][ref_number]" value="${row.ref_number ?? ''}" placeholder="No. Transaksi/Referensi"></td>
        <td><input type="text" class="form-control form-control-sm" name="detail[${idx}][field_name]" value="${row.field_name ?? ''}" placeholder="Field/Kolom"></td>
        <td><input type="text" class="form-control form-control-sm" name="detail[${idx}][current_data]" value="${row.current_data ?? ''}" placeholder="Data Saat Ini"></td>
        <td><input type="text" class="form-control form-control-sm" name="detail[${idx}][proposed_data]" value="${row.proposed_data ?? ''}" placeholder="Data Seharusnya"></td>
        <td><input type="text" class="form-control form-control-sm" name="detail[${idx}][note]" value="${row.note ?? ''}" placeholder="Keterangan"></td>
        <td class="text-center align-middle">
          <button type="button" class="btn btn-icon btn-flat-danger btn-remove-detail-row"><i data-feather="trash-2"></i></button>
        </td>
      </tr>`;
    $('#detailRowContainer').append(html);
    if (window.feather) feather.replace({ width: 14, height: 14 });
  }

  $(document).on('click', '.btn-remove-detail-row', function () {
    $(this).closest('tr').remove();
    renumberDetailRows();
  });

  function renumberDetailRows() {
    $('#detailRowContainer tr').each(function (i) {
      $(this).find('.rowNo').text(i + 1);
    });
  }

  $('#btnAddDetailRow').on('click', function () { addDetailRow(); });
  $('#type').on('change', toggleDetailSection);

  $('#frmCreate').on('submit', function () {
    if ($('#type').val() === 'Perubahan Data' && $('#detailRowContainer tr').length === 0) {
      addDetailRow();
    }
  });

  $(function () { toggleDetailSection(); });
</script>
@endsection
