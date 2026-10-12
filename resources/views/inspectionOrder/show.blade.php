@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h4 class="card-title mb-0">
            <i data-feather="clipboard" class="mr-50"></i> {{ $hdr->order_no }}
            <span class="badge {{ $statusBadge }} ml-1">{{ $statusLabel }}</span>
            @if ($hdr->result == 'PASS')
                <span class="badge badge-success">PASS</span>
            @elseif ($hdr->result == 'FAIL')
                <span class="badge badge-danger">FAIL</span>
            @endif
        </h4>
        <div>
            @if ($hdr->status == '1')
                <a href="{{ route('inspectionOrder.edit', ['id' => $hdr->id]) }}" class="btn btn-sm btn-primary"><i data-feather="edit-2" class="mr-50"></i>Edit</a>
                <button type="button" class="btn btn-sm btn-success" id="btnPosting"><i data-feather="check" class="mr-50"></i>Post</button>
            @endif
            @if ($hdr->status != '5')
                <button type="button" class="btn btn-sm btn-danger" id="btnCancel" data-toggle="modal" data-target="#cancelModal"><i data-feather="x" class="mr-50"></i>Cancel</button>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="col-md-3"><small class="text-muted d-block">Inspection Date</small>{{ $hdr->inspection_date }}</div>
            <div class="col-md-3"><small class="text-muted d-block">Inspection Post</small>{{ $hdr->post_name }}</div>
            <div class="col-md-3"><small class="text-muted d-block">Supplier</small>{{ $hdr->supplier_code ?: '-' }}</div>
            <div class="col-md-3"><small class="text-muted d-block">Article</small>{{ $hdr->article_alternative_code ?: $hdr->article_code }} @if($hdr->article_desc)<br><span class="text-muted">{{ $hdr->article_desc }}</span>@endif</div>
        </div>
        @if ($hdr->check_method || $hdr->qty_received)
        <div class="form-row mt-1">
            <div class="col-md-3"><small class="text-muted d-block">Check Method</small>{{ $hdr->check_method ?: '-' }}</div>
            <div class="col-md-3"><small class="text-muted d-block">Qty Received</small>{{ $hdr->qty_received }}</div>
        </div>
        @endif
        @if ($hdr->note)
        <div class="form-row mt-1"><div class="col-12"><small class="text-muted d-block">Note</small>{{ $hdr->note }}</div></div>
        @endif
        @if ($hdr->status == '5' && $hdr->cancel_reason)
        <div class="form-row mt-1"><div class="col-12"><small class="text-muted d-block">Cancel Reason</small>{{ $hdr->cancel_reason }}</div></div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0"><i data-feather="bar-chart-2" class="mr-50"></i> Inspection Summary</h4>
    </div>
    <div class="card-body py-0">
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span class="text-muted">Total Check</span><b>{{ $hdr->total_check }}</b>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span class="text-muted">Total OK</span><b>{{ $hdr->total_ok }}</b>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span class="text-muted">Total OK Repair / NC</span><b>{{ $hdr->total_ok_repair }}</b>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span class="text-muted">Total NG</span><b>{{ $hdr->total_ng }}</b>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span class="text-muted">Pass Rate</span>
                <span class="{{ $hdr->result == 'FAIL' ? 'text-danger' : ($hdr->result == 'PASS' ? 'text-success' : '') }}">
                    <b>{{ number_format($hdr->pass_rate, 2) }}%</b>
                    @if (!is_null($hdr->target_pass_rate))<small class="text-muted">/ target {{ $hdr->target_pass_rate }}%</small>@endif
                </span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span class="text-muted">Pass Through Rate</span>
                <span class="{{ $hdr->result == 'FAIL' ? 'text-danger' : ($hdr->result == 'PASS' ? 'text-success' : '') }}">
                    <b>{{ number_format($hdr->pass_through_rate, 2) }}%</b>
                    @if (!is_null($hdr->target_pass_through_rate))<small class="text-muted">/ target {{ $hdr->target_pass_through_rate }}%</small>@endif
                </span>
            </li>
        </ul>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0"><i data-feather="list" class="mr-50"></i> Defect Lines</h4>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Defect</th>
                    <th style="width:100px;">Category</th>
                    <th style="width:100px;" class="text-right">Qty</th>
                    <th style="width:110px;" class="text-right">OK Repair</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($details as $d)
                <tr>
                    <td>{{ $d->defect_code }} - {{ $d->defect_name }}</td>
                    <td>{{ $d->category }}</td>
                    <td class="text-right">{{ $d->qty }}</td>
                    <td class="text-right">{{ $d->ok_repair }}</td>
                    <td>{{ $d->note }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">No defect lines</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="cancelModal">
    <div class="modal-dialog">
        <form action="{{ route('inspectionOrder.cancel') }}" method="post">
            @csrf
            <input type="hidden" name="id" value="{{ $hdr->id }}" />
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel {{ $hdr->order_no }}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <label for="reason">Reason</label>
                    <textarea name="reason" id="reason" class="form-control" rows="3" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Cancel Order</button>
                </div>
            </div>
        </form>
    </div>
</div>

<form id="frmPosting" action="{{ route('inspectionOrder.posting') }}" method="post">
    @csrf
    <input type="hidden" name="id" value="{{ $hdr->id }}" />
</form>
@endsection
@section('scripts')
<script type="text/javascript">
    $('#btnPosting').click(function(){
        if (confirm('Post this Inspection Order? It can no longer be edited after posting.')) {
            $('#frmPosting').submit();
        }
    });
</script>
@endsection
