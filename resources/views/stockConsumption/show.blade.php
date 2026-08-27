@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

@php
  $badge = ['1'=>'badge-primary','4'=>'badge-success','5'=>'badge-danger'];
@endphp

<section id="detail">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">
        {{ $header->sc_number }}
        <span class="badge {{ $badge[$header->status] ?? 'badge-secondary' }} ml-1">{{ $statusTr }}</span>
      </h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0"><li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li></ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">

        {{-- ── Info Header ─────────────────────────────────── --}}
        <div class="row">
          <div class="col-md-6">
            <table class="table table-sm table-borderless">
              <tr><th style="width:35%">Number</th><td>: {{ $header->sc_number }}</td></tr>
              <tr><th>Date</th><td>: {{ $header->sc_date }}</td></tr>
              <tr><th>Location</th><td>: {{ $header->location_name }}</td></tr>
            </table>
          </div>
          <div class="col-md-6">
            <table class="table table-sm table-borderless">
              <tr><th style="width:40%">Note</th><td>: {{ $header->note }}</td></tr>
              <tr><th>Created By</th><td>: {{ $header->created_name ?? $header->created_by }}</td></tr>
              @if($header->status == '4')
              <tr><th>Posted By</th><td>: {{ $header->authorized_name ?? $header->authorized_by }}</td></tr>
              <tr><th>Jurnal (KAS)</th><td>: {{ $header->kas_number ?? '-' }}</td></tr>
              <tr><th>Total Nilai</th><td>: {{ number_format($header->total_amount ?? 0, 2, '.', ',') }}</td></tr>
              @endif
            </table>
          </div>
        </div>

        <hr>

        {{-- ── Detail Artikel ─────────────────────────────── --}}
        <h5 class="mb-1">Article</h5>
        <div class="table-responsive">
          <table class="table table-bordered table-sm">
            <thead class="thead-light">
              <tr><th>#</th><th>Article Code</th><th>Description</th><th class="text-right">Qty</th><th>UOM</th><th>Note</th></tr>
            </thead>
            <tbody>
              @foreach($details as $i => $d)
              <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $d->article_alternative_code }}</td>
                <td>{{ $d->article_desc }}</td>
                <td class="text-right">{{ rtrim(rtrim(number_format($d->qty, 4, '.', ''), '0'), '.') }}</td>
                <td>{{ $d->uom }}</td>
                <td>{{ $d->note }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <hr>

        {{-- ── FORM POSTING — hanya muncul kalau DRAFT + berwenang ── --}}
        @if($header->status == '1' && $canPost)
        <div class="card border-warning mb-2">
          <div class="card-header bg-warning bg-lighten-5 py-75">
            <h6 class="mb-0"><i data-feather="send" class="mr-50"></i>Posting — Pilih COA Biaya</h6>
          </div>
          <div class="card-body py-1">
            <form id="frmPosting" method="POST" action="{{ route('stockConsumption.posting') }}">
              @csrf
              <input type="hidden" name="id" value="{{ Crypt::encryptString($header->sc_number) }}">
              <div class="form-row align-items-end">
                <div class="form-group col-md-5 mb-0">
                  <label for="postCoa" class="mb-25">
                    COA Biaya <span class="text-danger">*</span>
                    <small class="text-muted">(wajib diisi sebelum posting)</small>
                  </label>
                  <select class="select2 form-control" id="postCoa" name="coa" required>
                    <option value=""></option>
                    @foreach($coas as $c)
                      <option value="{{ $c->account }}"
                        {{ ($header->coa_code && $header->coa_code == $c->account) ? 'selected' : '' }}>
                        {{ $c->account }} - {{ $c->description }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-3 mb-0">
                  <button type="button" class="btn btn-success" id="btnPosting">
                    <i data-feather="check-circle" class="mr-50"></i>Posting
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
        @endif

        {{-- ── Tombol aksi lain ───────────────────────────── --}}
        <div class="form-row mt-1">
          <div class="col-md-12">
            <a href="{{ route('stockConsumption.index') }}" class="btn btn-light">Back</a>

            @if($header->status == '1')
              <a href="{{ route('stockConsumption.edit', ['id' => Crypt::encryptString($header->sc_number)]) }}"
                 class="btn btn-warning">
                <i data-feather="edit-2" class="mr-50"></i>Edit
              </a>
            @endif

            @if($header->status != '5')
              <a href="javascript:;" class="btn btn-danger" data-ajax-delete="true"
                 data-confirm="Batalkan konsumsi ini?|Stok & jurnal (jika sudah posting) akan dikembalikan."
                 data-url="{{ route('stockConsumption.cancel', ['id' => Crypt::encryptString($header->sc_number)]) }}">
                <i data-feather="x-circle" class="mr-50"></i>Cancel
              </a>
            @endif
          </div>
        </div>

      </div>{{-- card-body --}}
    </div>
  </div>
</section>

@include('partials.delete-modal')
@endsection

@section('scripts')
<script>
  // Init select2 untuk dropdown COA di form posting
  $('#postCoa').select2({ placeholder: '- Pilih COA Biaya -', allowClear: true, width: '100%' });

  // Tombol posting — confirm dulu
  $('#btnPosting').on('click', function () {
    const coa = $('#postCoa').val();
    if (!coa) {
      Swal.fire('Warning', 'COA Biaya harus dipilih sebelum posting.', 'warning');
      return;
    }

    Swal.fire({
      title  : 'Konfirmasi Posting',
      html   : `Posting konsumsi <b>{{ $header->sc_number }}</b>?<br>
                COA: <b>${coa}</b><br>
                Stok akan dikurangi dan jurnal dibuat. Proses tidak bisa dibatalkan dengan mudah.`,
      icon   : 'warning',
      showCancelButton  : true,
      confirmButtonText : 'Ya, Posting',
      cancelButtonText  : 'Batal',
      confirmButtonColor: '#28a745',
    }).then((result) => {
      if (result.isConfirmed) {
        document.getElementById('frmPosting').submit();
      }
    });
  });

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection