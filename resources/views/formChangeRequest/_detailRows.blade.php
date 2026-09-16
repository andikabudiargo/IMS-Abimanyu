{{--
  Reusable add-row table for Form Change Request "Perubahan Data" detail lines.
  Expects: $detail (Collection|array of rows), optional $readonly (bool)
--}}
@php $readonly = $readonly ?? false; @endphp

<div class="table-responsive">
  <table class="table table-bordered table-sm" id="detailTable">
    <thead class="thead-light">
      <tr>
        <th style="width:4%">NO</th>
        <th>Nomor Transaksi/Referensi</th>
        <th>Field / Kolom</th>
        <th>Data Saat Ini (Existing)</th>
        <th>Data Seharusnya (Usulan)</th>
        <th>Keterangan</th>
        @if(!$readonly)
          <th style="width:5%"></th>
        @endif
      </tr>
    </thead>
    <tbody id="detailRowContainer">
      @foreach($detail as $row)
        @php
          $refNumber = $row->ref_number ?? '';
          $fieldName = $row->field_name ?? '';
          $currentData = $row->current_data ?? '';
          $proposedData = $row->proposed_data ?? '';
          $note = $row->note ?? '';
        @endphp
        <tr>
          <td class="text-center align-middle rowNo">{{ $loop->iteration }}</td>
          @if($readonly)
            <td>{{ $refNumber ?: '-' }}</td>
            <td>{{ $fieldName ?: '-' }}</td>
            <td>{{ $currentData ?: '-' }}</td>
            <td>{{ $proposedData ?: '-' }}</td>
            <td>{{ $note ?: '-' }}</td>
          @else
            <td><input type="text" class="form-control form-control-sm" name="detail[{{ $loop->index }}][ref_number]" value="{{ $refNumber }}" placeholder="No. Transaksi/Referensi"></td>
            <td><input type="text" class="form-control form-control-sm" name="detail[{{ $loop->index }}][field_name]" value="{{ $fieldName }}" placeholder="Field/Kolom"></td>
            <td><input type="text" class="form-control form-control-sm" name="detail[{{ $loop->index }}][current_data]" value="{{ $currentData }}" placeholder="Data Saat Ini"></td>
            <td><input type="text" class="form-control form-control-sm" name="detail[{{ $loop->index }}][proposed_data]" value="{{ $proposedData }}" placeholder="Data Seharusnya"></td>
            <td><input type="text" class="form-control form-control-sm" name="detail[{{ $loop->index }}][note]" value="{{ $note }}" placeholder="Keterangan"></td>
            <td class="text-center align-middle">
              <button type="button" class="btn btn-icon btn-flat-danger btn-remove-detail-row"><i data-feather="trash-2"></i></button>
            </td>
          @endif
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

@if(!$readonly)
  <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddDetailRow">
    <i data-feather="plus" class="mr-50"></i> Add Row
  </button>
@endif
