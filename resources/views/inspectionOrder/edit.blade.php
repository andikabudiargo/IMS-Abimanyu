@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="edit-index">
    <form id="frmEdit" name="frmEdit" action="{{ route('inspectionOrder.update') }}" method="post" autocomplete="off">
        @csrf
        <input type="hidden" name="id" value="{{ $hdr->id }}" />
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="inspection_date">Inspection Date</label>
                        <input type="date" id="inspection_date" name="inspection_date" class="form-control" value="{{ old('inspection_date', $hdr->inspection_date) }}" required />
                    </div>
                    <div class="form-group col-md-3">
                        <label for="inspection_post_id">Inspection Post</label>
                        <select class="select2 form-control" id="inspection_post_id" name="inspection_post_id" required>
                            <option value="">-- Select Post --</option>
                            @foreach ($posts as $post)
                            <option value="{{ $post->id }}" {{ old('inspection_post_id', $hdr->inspection_post_id) == $post->id ? 'selected' : '' }}>{{ $post->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="shift">Shift</label>
                        <input type="text" id="shift" name="shift" class="form-control" value="{{ old('shift', $hdr->shift) }}" maxlength="20" />
                    </div>
                    <div class="form-group col-md-3" id="checkMethodWrapper">
                        <label for="check_method">Check Method</label>
                        <input type="text" id="check_method" name="check_method" class="form-control" value="{{ old('check_method', $hdr->check_method) }}" maxlength="50" placeholder="100% / Sampling" />
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-4" id="supplierWrapper">
                        <label for="supplier_code">Supplier</label>
                        <select class="select2 form-control" id="supplier_code" name="supplier_code">
                            <option value="">-- Select Supplier --</option>
                            @foreach ($suppliers as $supp)
                            <option value="{{ $supp->kode }}" {{ old('supplier_code', $hdr->supplier_code) == $supp->kode ? 'selected' : '' }}>{{ $supp->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="article_code">Article</label>
                        <select class="select2 form-control" id="article_code" name="article_code" required>
                            <option value="">-- Select Article --</option>
                            @foreach ($articles as $art)
                            <option value="{{ $art->article_code }}" {{ old('article_code', $hdr->article_code) == $art->article_code ? 'selected' : '' }}>{{ $art->article_code }} - {{ $art->article_desc }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4" id="qtyReceivedWrapper">
                        <label for="qty_received">Qty Received</label>
                        <input type="number" step="0.01" min="0" id="qty_received" name="qty_received" class="form-control" value="{{ old('qty_received', $hdr->qty_received) }}" />
                    </div>
                </div>

                <div class="alert alert-info" id="ruleInfo" style="display:none;">
                    Target for this Article/Post &mdash; Pass Rate: <b id="ruleTargetPassRate">-</b>% |
                    Pass Through: <b id="ruleTargetPassThrough">-</b>%
                </div>
                <div class="alert alert-warning" id="ruleInfoNone" style="display:none;">
                    No Inspection Rule found for this Article/Post. Result will not be flagged PASS/FAIL.
                </div>

                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="total_check">Total Check</label>
                        <input type="number" step="0.01" min="0" id="total_check" name="total_check" class="form-control rate-input" value="{{ old('total_check', $hdr->total_check) }}" required />
                    </div>
                    <div class="form-group col-md-3">
                        <label for="total_ok">Total OK</label>
                        <input type="number" step="0.01" min="0" id="total_ok" name="total_ok" class="form-control rate-input" value="{{ old('total_ok', $hdr->total_ok) }}" required />
                    </div>
                    <div class="form-group col-md-3">
                        <label for="total_ok_repair">Total OK Repair</label>
                        <input type="number" step="0.01" min="0" id="total_ok_repair" name="total_ok_repair" class="form-control rate-input" value="{{ old('total_ok_repair', $hdr->total_ok_repair) }}" required />
                    </div>
                    <div class="form-group col-md-3">
                        <label for="total_ng">Total NG</label>
                        <input type="number" step="0.01" min="0" id="total_ng" name="total_ng" class="form-control rate-input" value="{{ old('total_ng', $hdr->total_ng) }}" required />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        Pass Rate: <b id="calcPassRate">0</b>% &nbsp;|&nbsp;
                        Pass Through Rate: <b id="calcPassThrough">0</b>%
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="note">Note</label>
                        <textarea id="note" name="note" class="form-control" rows="2">{{ old('note', $hdr->note) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title">Defect Lines</h4>
                <button type="button" class="btn btn-sm btn-primary" id="btnAddLine"><i class="fa fa-plus"></i> Add Line</button>
            </div>
            <div class="card-body">
                <table class="table table-bordered" id="defectTable">
                    <thead>
                        <tr>
                            <th style="min-width:220px;">Defect</th>
                            <th style="min-width:110px;">Category</th>
                            <th style="min-width:100px;">Qty</th>
                            <th style="min-width:100px;" class="okRepairCol">OK Repair</th>
                            <th>Note</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <a href="{{ route('inspectionOrder.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button class="btn btn-success" type="button" id="cmdSave">Save</button>
            </div>
        </div>
    </form>
</section>
@endsection
@section('scripts')
<script type="text/javascript">
    const existingLines = {!! $details->map(function($d){ return ['defect_id'=>$d->defect_id,'category'=>$d->category,'qty'=>$d->qty,'ok_repair'=>$d->ok_repair,'note'=>$d->note]; })->values() !!};
    const defectOptions = `<option value="">-- Select Defect --</option>@foreach ($defects as $d)<option value="{{ $d->id }}">{{ $d->code }} - {{ $d->name }}</option>@endforeach`;
    const postNames = { @foreach ($posts as $post) {{ $post->id }}: "{{ $post->name }}", @endforeach };

    function addLine(line) {
        line = line || {};
        const row = `<tr>
            <td><select class="select2 form-control defect-select" name="defect_id[]">${defectOptions}</select></td>
            <td>
                <select class="form-control" name="category[]">
                    <option value="NG" ${line.category === 'NG' ? 'selected' : ''}>NG</option>
                    <option value="NC" ${line.category === 'NC' ? 'selected' : ''}>NC</option>
                </select>
            </td>
            <td><input type="number" step="0.01" min="0" class="form-control" name="defect_qty[]" value="${line.qty || 0}" /></td>
            <td class="okRepairCol"><input type="number" step="0.01" min="0" class="form-control" name="defect_ok_repair[]" value="${line.ok_repair || 0}" /></td>
            <td><input type="text" class="form-control" name="defect_note[]" value="${line.note || ''}" /></td>
            <td><button type="button" class="btn btn-sm btn-danger btnRemoveLine"><i class="fa fa-trash"></i></button></td>
        </tr>`;
        $('#defectTable tbody').append(row);
        $('#defectTable tbody tr:last .defect-select').val(line.defect_id || '').trigger('change').select2();
        applyPostRules();
    }

    /** Sama seperti di create.blade.php -- lihat komentar di sana. */
    function applyPostRules() {
        const postName = (postNames[$('#inspection_post_id').val()] || '').trim();
        const isIncoming = postName === 'Incoming';

        $('#supplierWrapper, #qtyReceivedWrapper, #checkMethodWrapper').toggle(isIncoming);
        $('#qty_received, #check_method').prop('required', isIncoming);

        $('.okRepairCol').toggle(isIncoming);
        $('.okRepairCol input').prop('disabled', !isIncoming).prop('required', isIncoming);
        if (!isIncoming) { $('.okRepairCol input').val(0); }
    }

    function recalcRates() {
        const totalCheck = parseFloat($('#total_check').val()) || 0;
        const totalOk = parseFloat($('#total_ok').val()) || 0;
        const totalOkRepair = parseFloat($('#total_ok_repair').val()) || 0;
        const totalNg = parseFloat($('#total_ng').val()) || 0;
        let passRate = 0, passThrough = 0;
        if (totalCheck > 0) {
            passRate = (totalOk / totalCheck * 100).toFixed(2);
            passThrough = ((totalCheck - totalNg - totalOkRepair) / totalCheck * 100).toFixed(2);
        }
        $('#calcPassRate').text(passRate);
        $('#calcPassThrough').text(passThrough);
    }

    function loadRule() {
        const articleCode = $('#article_code').val();
        const postId = $('#inspection_post_id').val();
        if (!articleCode || !postId) {
            $('#ruleInfo, #ruleInfoNone').hide();
            return;
        }
        $.get('{{ route("inspectionOrder.getRule") }}', { articleCode: articleCode, postId: postId }, function (rule) {
            if (rule) {
                $('#ruleTargetPassRate').text(rule.target_pass_rate);
                $('#ruleTargetPassThrough').text(rule.target_pass_through_rate);
                $('#ruleInfo').show();
                $('#ruleInfoNone').hide();
            } else {
                $('#ruleInfo').hide();
                $('#ruleInfoNone').show();
            }
        });
    }

    $(document).ready(function(){
        $("#frmEdit").validate({}).settings.ignore = "";
        if (existingLines.length) {
            existingLines.forEach(addLine);
        } else {
            addLine();
        }
        recalcRates();
        loadRule();
        applyPostRules();

        $('#btnAddLine').click(function(){ addLine(); });
        $(document).on('click', '.btnRemoveLine', function(){ $(this).closest('tr').remove(); });
        $(document).on('input', '.rate-input', recalcRates);
        $('#article_code, #inspection_post_id').on('change', loadRule);
        $('#inspection_post_id').on('change', applyPostRules);
    });

    $("#cmdSave").click(function(){
        $("#frmEdit").submit();
    });
</script>
@endsection
