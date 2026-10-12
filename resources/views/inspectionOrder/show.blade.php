@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4>{{ $hdr->order_no }} <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span></h4>
            <div>
                @if ($hdr->status == '1')
                    <a href="{{ route('inspectionOrder.edit', ['id' => $hdr->id]) }}" class="btn btn-sm btn-primary">Edit</a>
                    <button type="button" class="btn btn-sm btn-success" id="btnPosting">Post</button>
                @endif
                @if ($hdr->status != '5')
                    <button type="button" class="btn btn-sm btn-danger" id="btnCancel" data-toggle="modal" data-target="#cancelModal">Cancel</button>
                @endif
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-3"><b>Inspection Date</b><br>{{ $hdr->inspection_date }}</div>
            <div class="col-md-3"><b>Inspection Post</b><br>{{ $hdr->post_name }}</div>
            <div class="col-md-3"><b>Supplier</b><br>{{ $hdr->supplier_code ?: '-' }}</div>
            <div class="col-md-3"><b>Article</b><br>{{ $hdr->article_code }}</div>
        </div>
        <div class="row mt-2">
            <div class="col-md-3"><b>Shift</b><br>{{ $hdr->shift ?: '-' }}</div>
            <div class="col-md-3"><b>Check Method</b><br>{{ $hdr->check_method ?: '-' }}</div>
            <div class="col-md-3"><b>Qty Received</b><br>{{ $hdr->qty_received }}</div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-2"><b>Total Check</b><br>{{ $hdr->total_check }}</div>
            <div class="col-md-2"><b>Total OK</b><br>{{ $hdr->total_ok }}</div>
            <div class="col-md-2"><b>OK Repair</b><br>{{ $hdr->total_ok_repair }}</div>
            <div class="col-md-2"><b>Total NG</b><br>{{ $hdr->total_ng }}</div>
            <div class="col-md-2"><b>Pass Rate</b><br>{{ $hdr->pass_rate }}% <small class="text-muted">(target {{ $hdr->target_pass_rate ?? '-' }}%)</small></div>
            <div class="col-md-2"><b>Pass Through</b><br>{{ $hdr->pass_through_rate }}% <small class="text-muted">(target {{ $hdr->target_pass_through_rate ?? '-' }}%)</small></div>
        </div>
        <div class="row mt-2">
            <div class="col-md-4">
                <b>Result:</b>
                @if ($hdr->result == 'PASS')
                    <span class="badge badge-success">PASS</span>
                @elseif ($hdr->result == 'FAIL')
                    <span class="badge badge-danger">FAIL</span>
                @else
                    <span class="text-muted">No target set</span>
                @endif
            </div>
        </div>
        @if ($hdr->note)
        <div class="row mt-2"><div class="col-12"><b>Note</b><br>{{ $hdr->note }}</div></div>
        @endif
        @if ($hdr->status == '5' && $hdr->cancel_reason)
        <div class="row mt-2"><div class="col-12"><b>Cancel Reason</b><br>{{ $hdr->cancel_reason }}</div></div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header"><h4 class="card-title">Defect Lines</h4></div>
    <div class="card-body table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr><th>Defect</th><th>Category</th><th>Qty</th><th>OK Repair</th><th>Note</th></tr>
            </thead>
            <tbody>
                @forelse ($details as $d)
                <tr>
                    <td>{{ $d->defect_code }} - {{ $d->defect_name }}</td>
                    <td>{{ $d->category }}</td>
                    <td>{{ $d->qty }}</td>
                    <td>{{ $d->ok_repair }}</td>
                    <td>{{ $d->note }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center">No defect lines</td></tr>
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
