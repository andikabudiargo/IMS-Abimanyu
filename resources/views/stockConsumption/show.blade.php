@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

@php
  $badge = ['1'=>'badge-primary','2'=>'badge-info','3'=>'badge-warning','4'=>'badge-success','5'=>'badge-danger'];
  $canPost = auth()->user()->hasAnyRole(['Superuser','accounting']) || auth()->user()->can('stockConsumption-posting');
@endphp

<section id="detail">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">
        {{ $header->sc_number }}
        <span class="badge {{ $badge[$header->status] ?? 'badge-secondary' }} ml-1">{{ $statusTr }}</span>
      </h4>
      <div class="heading-elements"><ul class="list-inline mb-0"><li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li></ul></div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <table class="table table-sm table-borderless">
              <tr><th style="width:35%">Number</th><td>: {{ $header->sc_number }}</td></tr>
              <tr><th>Date</th><td>: {{ $header->sc_date }}</td></tr>
              <tr><th>Location</th><td>: {{ $header->location_name }}</td></tr>
              <tr><th>COA</th><td>: {{ $header->coa_code }}</td></tr>
            </table>
          </div>
          <div class="col-md-6">
            <table class="table table-sm table-borderless">
              <tr><th style="width:35%">Note</th><td>: {{ $header->note }}</td></tr>
              <tr><th>Created By</th><td>: {{ $header->created_name ?? $header->created_by }}</td></tr>
              <tr><th>Jurnal (KAS)</th><td>: {{ $header->kas_number ?? '-' }}</td></tr>
              <tr><th>Total Nilai</th><td>: {{ number_format($header->total_amount ?? 0, 2) }}</td></tr>
            </table>
          </div>
        </div>

        <hr>
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
                  <td class="text-right">{{ rtrim(rtrim(number_format($d->qty,4,'.',''), '0'), '.') }}</td>
                  <td>{{ $d->uom }}</td>
                  <td>{{ $d->note }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <hr>
        <div class="form-row">
          <div class="col-md-12">
            <a href="{{ route('stockConsumption.index') }}" class="btn btn-light">Back</a>

            @if($header->status == '1')
              <a href="{{ route('stockConsumption.edit', ['id'=>Crypt::encryptString($header->id)]) }}" class="btn btn-warning">Edit</a>
            @endif

            {{-- APPROVE: tampil kalau giliran user ini --}}
            @if(isset($approveValidate) && $approveValidate && in_array($header->status, ['1','2']))
              <button class="btn btn-success" type="button" id="cmdApprove">Approve</button>
            @endif

            {{-- POSTING: hanya kalau sudah APPROVED (3) & berwenang --}}
            @if($header->status == '3' && $canPost)
              <form id="posting-form" action="{{ route('stockConsumption.posting') }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="id" value="{{ Crypt::encryptString($header->id) }}">
                <button type="button" class="btn btn-primary"
                  onclick="if(confirm('Posting konsumsi ini? Stok akan berkurang dan jurnal dibuat.')){document.getElementById('posting-form').submit();}">
                  Posting
                </button>
              </form>
            @endif

            @if($header->status != '5')
              <a href="javascript:;" class="btn btn-danger" data-ajax-delete="true"
                 data-confirm="Batalkan konsumsi ini?|Stok & jurnal (jika sudah posting) akan dikembalikan."
                 data-url="{{ route('stockConsumption.cancel', ['id'=>Crypt::encryptString($header->id)]) }}">Cancel</a>
            @endif
          </div>
        </div>

        {{-- Approval History --}}
        @if(isset($approvalHistory) && count($approvalHistory))
        <hr>
        <div class="form-row card-statistics">
          @foreach($approvalHistory as $val)
            <div class="statistics-body">
              <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                <div class="media">
                  <div class="avatar bg-light-{{ $val->status == true ? ($val->statusapprove == 1 ? 'success' : 'warning') : 'danger' }} mr-2">
                    <div class="avatar-content"><i data-feather="{{ $val->status == true ? ($val->statusapprove == 1 ? 'check' : 'x') : 'x' }}" class="avatar-icon"></i></div>
                  </div>
                  <div class="media-body my-auto">
                    <h4 class="font-weight-bolder mb-0">{{ $val->status == true ? ($val->statusapprove == 1 ? 'Approve' : 'Decline') : 'Approve' }}-{{ $val->approval_order }}</h4>
                    <p class="card-text mb-0">{{ $val->status == true ? $val->name : $val->petugas }}</p>
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
        @endif
      </div>
    </div>
  </div>
</section>
@include('partials.delete-modal')
@endsection

@section('scripts')
<script type="text/javascript">
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  const approveBtn = document.querySelector('#cmdApprove');
  if (approveBtn) {
    approveBtn.addEventListener('click', function () {
      approveBtn.setAttribute('disabled','disabled');
      $.ajax({
        type:"GET",
        url:"{{ route('stockConsumption.approve') }}",
        data:{ scNumber: "{{ $header->sc_number }}" },
        dataType:"json",
        success:function(data){
          show_msg(data.title, Array.isArray(data.message)?data.message.join(', '):data.message, data.alert);
          if (data.status == 1) setTimeout(()=>window.location.reload(), 800);
          else approveBtn.removeAttribute('disabled');
        },
        error:function(e){ console.log(e); approveBtn.removeAttribute('disabled'); }
      });
    });
  }
</script>
@endsection