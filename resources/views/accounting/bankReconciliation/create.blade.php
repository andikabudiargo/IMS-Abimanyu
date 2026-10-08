@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="add-bankReconciliation">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Reconciliation Kas & Bank — Upload Rekening Koran</h4>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <form id="frmAdd" name="frmAdd" autocomplete="off" enctype="multipart/form-data">
          @csrf
          <div class="form-row">
            <div class="form-group col-md-3">
              <label class="form-label" for="type">Type*</label>
              <select class="select2 form-control" id="type" name="type" required>
                <option value=""></option>
                <option value="KAS">Kas</option>
                <option value="BANK">Bank</option>
              </select>
            </div>
            <div class="form-group col-md-5">
              <label class="form-label" for="bankAccount">Akun COA (Kas/Bank)*</label>
              <select class="select2 form-control" id="bankAccount" name="bankAccount" required>
                <option value=""></option>
                @foreach ($accounts as $val)
                  <option value="{{ $val->account }}">{{ $val->account }} | {{ $val->description }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-3">
              <label class="form-label" for="periode">Periode*</label>
              <select class="select2 form-control" id="periode" name="periode" required>
                <option value=""></option>
                @foreach ($periodes as $val => $label)
                  <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-2">
              <label class="form-label" for="year">Tahun*</label>
              <input type="number" id="year" name="year" class="form-control" value="{{ date('Y') }}" required />
            </div>
            <div class="form-group col-md-5">
              <label class="form-label" for="description">Description</label>
              <input type="text" id="description" name="description" class="form-control" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label class="form-label" for="statement">Rekening Koran (PDF)*</label>
              <div class="custom-file">
                <input type="file" class="custom-file-input" name="statement" id="statement" accept=".pdf" required />
                <label class="custom-file-label" for="statement" id="statementLabel">Choose file</label>
              </div>
            </div>
          </div>
          <div class="form-row mt-1">
            <div class="col-12">
              <button class="btn btn-primary" type="button" id="cmdSave">
                <i data-feather="upload" class="align-middle mr-sm-25 mr-0"></i>
                <span class="align-middle d-sm-inline-block d-none">Upload &amp; Reconcile</span>
              </button>
              <a href="{{ route('bankReconciliation.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection

@section('scripts')
<script type="text/javascript">

  $(document).ready(function () {
    validateFormToast("frmAdd");

    $('#statement').on('change', function () {
      let name = $(this).val().split('\\').pop() || 'Choose file';
      $('#statementLabel').text(name);
    });
  });

  $('#cmdSave').on('click', function () {
    if (!$('#frmAdd')[0].checkValidity()) {
      $('#frmAdd')[0].reportValidity();
      return;
    }

    $(".loading-spinner-container").addClass("-show");
    $('#cmdSave').attr('disabled', 'disabled');

    $.ajax({
      url: "{{ route('bankReconciliation.store') }}",
      method: "POST",
      data: new FormData(document.getElementById('frmAdd')),
      dataType: "json",
      contentType: false,
      cache: false,
      processData: false,
      success: function (data) {
        $('#cmdSave').removeAttr('disabled');
        $(".loading-spinner-container").removeClass("-show");
        show_msg(data.title, data.message, data.alert);
        if (data.status == 1) {
          setTimeout(function () {
            window.location.href = "{{ route('bankReconciliation.index') }}";
          }, 1500);
        }
      },
      error: function (xhr) {
        $('#cmdSave').removeAttr('disabled');
        $(".loading-spinner-container").removeClass("-show");
        let err = JSON.parse(xhr.responseText);
        Swal.fire('Error..', err.message ? JSON.stringify(err.message) : 'Failed to save', 'error');
      }
    });
  });

  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

</script>
@endsection
