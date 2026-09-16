@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

@php
  $statusBadges = [
    1 => 'badge-light-warning', 2 => 'badge-light-info', 3 => 'badge-light-primary',
    4 => 'badge-light-success', 5 => 'badge-light-secondary', 6 => 'badge-light-danger', 7 => 'badge-light-dark',
  ];
  $isFormEditable = $canEdit || $canRevise;
@endphp

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <h4 class="card-title">{{ $header->cr_number }}
        @if($header->rev_no > 0) <span class="badge badge-pill badge-light-dark">rev.{{ $header->rev_no }}</span> @endif
      </h4>
      <span class="badge badge-pill {{ $statusBadges[$header->status] ?? 'badge-light-secondary' }}">{{ $statusLabel }}</span>
    </div>
    <div>
      @if($canCancel)
        <button type="button" class="btn btn-outline-warning btn-sm" id="btnCancel">Cancel</button>
      @endif
      @if($canDelete)
        <button type="button" class="btn btn-outline-danger btn-sm" id="btnDelete">Delete</button>
      @endif
      <a href="{{ route('formChangeRequest.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
  </div>

  <div class="card-body">
    @if($header->status == 6 && $header->reject_reason)
      <div class="alert alert-danger"><b>Rejected:</b> {{ $header->reject_reason }}</div>
    @endif
    @if($header->status == 5 && $header->cancel_reason)
      <div class="alert alert-secondary"><b>Canceled:</b> {{ $header->cancel_reason }}</div>
    @endif
    @if($header->status == 4 && $header->finish_note)
      <div class="alert alert-success"><b>Catatan Penyelesaian:</b> {{ $header->finish_note }}</div>
    @endif

    <form id="frmEdit" method="POST" action="{{ route('formChangeRequest.update') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="id" value="{{ $id }}">
      <input type="hidden" name="reason" id="reason">

      <div class="form-row">
        <div class="form-group col-md-3">
          <label>Ticket Number</label>
          <input type="text" class="form-control" value="{{ $header->cr_number }}" disabled>
        </div>
        <div class="form-group col-md-3">
          <label for="modul">Modul</label>
          <select class="select2 form-control" id="modul" name="modul" {{ $isFormEditable ? '' : 'disabled' }}>
            @foreach($modules as $val)
              <option value="{{ $val }}" {{ $header->modul == $val ? 'selected' : '' }}>{{ $val }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-3">
          <label for="type">Type</label>
          <select class="select2 form-control" id="type" name="type" {{ $isFormEditable ? '' : 'disabled' }}>
            @foreach($types as $val)
              <option value="{{ $val }}" {{ $header->type == $val ? 'selected' : '' }}>{{ $val }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-3">
          <label for="urgency">Urgensi</label>
          <select class="select2 form-control" id="urgency" name="urgency" {{ $isFormEditable ? '' : 'disabled' }}>
            @foreach($urgencies as $val)
              <option value="{{ $val }}" {{ $header->urgency == $val ? 'selected' : '' }}>{{ $val }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-12">
          <label for="description">Deskripsi / Alasan / Latar Belakang</label>
          <textarea class="form-control" id="description" name="description" rows="4" {{ $isFormEditable ? '' : 'disabled' }}>{{ $header->description }}</textarea>
        </div>
      </div>

      <div class="form-group">
        <label>Lampiran</label>
        <div>
          @forelse($attachments as $att)
            <a href="{{ Storage::url($att->file_path) }}" target="_blank" class="badge badge-light-primary mr-1 mb-1">
              <i data-feather="paperclip" style="width:12px;height:12px;"></i> {{ $att->original_name }}
            </a>
          @empty
            <span class="text-muted">-</span>
          @endforelse
        </div>
        @if($isFormEditable)
          <input type="file" class="form-control mt-1" name="attachment[]" multiple>
          <small class="text-muted">File baru akan ditambahkan ke lampiran yang sudah ada.</small>
        @endif
      </div>

      <div id="detailSection" style="{{ $header->type == 'Perubahan Data' ? '' : 'display:none' }}">
        <hr>
        <h5>Detail Perubahan Data</h5>
        @include('formChangeRequest._detailRows', ['detail' => $detail, 'readonly' => !$isFormEditable])
      </div>

      @if($isFormEditable)
        <hr>
        <div class="form-row mt-1">
          <div class="col-12">
            <button type="button" class="btn btn-primary" id="btnSave">
              {{ $canRevise ? 'Save Revision' : 'Save' }}
            </button>
          </div>
        </div>
      @endif
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="card-title">Approval</h4>
    <div>
      @if($canApprove && $nextLevel < 3)
        <button type="button" class="btn btn-success btn-sm" id="btnApprove">Approve</button>
        <button type="button" class="btn btn-danger btn-sm" id="btnReject">Reject</button>
      @elseif($canApprove && $nextLevel == 3 && $header->status == 3)
        <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalFinish">Finish</button>
        <button type="button" class="btn btn-danger btn-sm" id="btnReject">Reject</button>
      @endif
    </div>
  </div>
  <div class="card-body">
    <table class="table table-bordered table-sm">
      <thead class="thead-light">
        <tr><th>Level</th><th>Approver</th><th>Status</th><th>Date</th></tr>
      </thead>
      <tbody>
        @php $levelLabels = [1 => 'Level 1 - Atasan Requester', 2 => 'Level 2 - Accounting', 3 => 'Level 3 - Superuser']; @endphp
        @forelse($approvalHistory as $ah)
          <tr>
            <td>{{ $levelLabels[$ah->approval_order] ?? 'Level '.$ah->approval_order }}</td>
            <td>{{ $ah->petugas ?? '-' }}</td>
            <td>
              @if($ah->statusapprove === null || $ah->statusapprove === '')
                <span class="badge badge-pill badge-light-secondary">Waiting</span>
              @elseif($ah->statusapprove == 1)
                <span class="badge badge-pill badge-light-success">Approved</span>
              @else
                <span class="badge badge-pill badge-light-danger">Rejected</span>
              @endif
            </td>
            <td>-</td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-center text-muted">No approval configuration for this module yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h4 class="card-title">Revision History</h4>
  </div>
  <div class="card-body">
    @forelse($revisionLog as $rev)
      <div class="mb-2 pb-2" style="border-bottom:1px solid #eee;">
        <span class="badge badge-pill badge-light-dark">{{ $rev->action }}</span>
        <b>rev.{{ $rev->rev_no }}</b> by {{ $rev->created_by }} — {{ $rev->created_at }}
        <div class="text-muted" style="font-size:.85rem;">"{{ $rev->reason }}"</div>
      </div>
    @empty
      <span class="text-muted">No revision history.</span>
    @endforelse
  </div>
</div>

<div class="modal fade" id="modalFinish" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="{{ route('formChangeRequest.approveFinal') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="id" value="{{ $id }}">
        <div class="modal-header">
          <h5 class="modal-title">Finish {{ $header->cr_number }}</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="finish_note">Tindakan / Catatan Perbaikan <span class="text-danger">*</span></label>
            <textarea class="form-control" id="finish_note" name="finish_note" rows="4" required></textarea>
          </div>
          <div class="form-group">
            <label for="finishAttachment">Lampiran (Foto / Dokumen)</label>
            <input type="file" class="form-control" id="finishAttachment" name="attachment[]" multiple>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Finish Request</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  let detailRowSeq = {{ count($detail) }};

  function toggleDetailSection() {
    if ($('#type').val() === 'Perubahan Data') {
      $('#detailSection').show();
    } else {
      $('#detailSection').hide();
    }
  }
  $('#type').on('change', toggleDetailSection);

  $('#btnAddDetailRow').on('click', function () {
    detailRowSeq++;
    const idx = detailRowSeq;
    const html = `
      <tr id="detailRow${idx}">
        <td class="text-center align-middle rowNo">${$('#detailRowContainer tr').length + 1}</td>
        <td><input type="text" class="form-control form-control-sm" name="detail[${idx}][ref_number]" placeholder="No. Transaksi/Referensi"></td>
        <td><input type="text" class="form-control form-control-sm" name="detail[${idx}][field_name]" placeholder="Field/Kolom"></td>
        <td><input type="text" class="form-control form-control-sm" name="detail[${idx}][current_data]" placeholder="Data Saat Ini"></td>
        <td><input type="text" class="form-control form-control-sm" name="detail[${idx}][proposed_data]" placeholder="Data Seharusnya"></td>
        <td><input type="text" class="form-control form-control-sm" name="detail[${idx}][note]" placeholder="Keterangan"></td>
        <td class="text-center align-middle">
          <button type="button" class="btn btn-icon btn-flat-danger btn-remove-detail-row"><i data-feather="trash-2"></i></button>
        </td>
      </tr>`;
    $('#detailRowContainer').append(html);
    if (window.feather) feather.replace({ width: 14, height: 14 });
  });

  $(document).on('click', '.btn-remove-detail-row', function () {
    $(this).closest('tr').remove();
    $('#detailRowContainer tr').each(function (i) { $(this).find('.rowNo').text(i + 1); });
  });

  $('#btnSave').on('click', function () {
    @if($canRevise)
      Swal.fire({
        title: 'Alasan Revisi',
        html: `<textarea id="swalReviseReason" class="form-control" rows="3" maxlength="500"
                placeholder="Kenapa dokumen ini direvisi?" style="resize:none;"></textarea>`,
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
          $('#reason').val(result.value);
          $('#frmEdit').submit();
        }
      });
    @else
      $('#frmEdit').submit();
    @endif
  });

  function postAction(url, extra = {}) {
    const $form = $('<form>', { action: url, method: 'POST' });
    $form.append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }));
    $form.append($('<input>', { type: 'hidden', name: 'id', value: '{{ $id }}' }));
    for (const k in extra) {
      $form.append($('<input>', { type: 'hidden', name: k, value: extra[k] }));
    }
    $form.appendTo('body').submit();
  }

  $('#btnApprove').on('click', function () {
    Swal.fire({
      title: 'Approve this request?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, approve',
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        $.post("{{ route('formChangeRequest.approve') }}", { id: '{{ $id }}' }, function (res) {
          if (res.status == 1) {
            Swal.fire('Success', res.message, 'success').then(() => window.location.reload());
          } else {
            Swal.fire('Error', res.message, 'error');
          }
        });
      }
    });
  });

  $('#btnReject').on('click', function () {
    Swal.fire({
      title: 'Reject this request?',
      html: `<textarea id="swalRejectReason" class="form-control" rows="3" placeholder="Alasan reject"></textarea>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Reject',
      confirmButtonColor: '#EA5455',
      reverseButtons: true,
      focusConfirm: false,
      preConfirm: () => {
        const reason = document.getElementById('swalRejectReason').value.trim();
        if (!reason) { Swal.showValidationMessage('Alasan reject wajib diisi'); return false; }
        return reason;
      }
    }).then((result) => {
      if (result.isConfirmed) {
        postAction("{{ route('formChangeRequest.reject') }}", { reason: result.value });
      }
    });
  });

  $('#btnCancel').on('click', function () {
    Swal.fire({
      title: 'Cancel this request?',
      html: `<textarea id="swalCancelReason" class="form-control" rows="3" placeholder="Alasan cancel"></textarea>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Cancel Request',
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
        postAction("{{ route('formChangeRequest.cancel') }}", { reason: result.value });
      }
    });
  });

  $('#btnDelete').on('click', function () {
    Swal.fire({
      title: 'Delete this request?',
      text: 'This action can not be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it',
      confirmButtonColor: '#EA5455',
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) { postAction("{{ route('formChangeRequest.destroy') }}"); }
    });
  });

  $(function () { toggleDetailSection(); });
</script>
@endsection
