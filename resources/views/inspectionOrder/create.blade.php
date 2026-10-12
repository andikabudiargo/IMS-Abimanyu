@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="add-index">
    <form id="frmAdd" name="frmAdd" action="{{ route('inspectionOrder.store') }}" method="post" autocomplete="off">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="inspection_date">Inspection Date</label>
                        <input type="date" id="inspection_date" name="inspection_date" class="form-control" value="{{ old('inspection_date', date('Y-m-d')) }}" required />
                    </div>
                    <div class="form-group col-md-3">
                        <label for="inspection_post_id">Inspection Post</label>
                        <select class="select2 form-control" id="inspection_post_id" name="inspection_post_id" required>
                            <option value="">-- Select Post --</option>
                            @foreach ($posts as $post)
                            <option value="{{ $post->id }}" {{ old('inspection_post_id') == $post->id ? 'selected' : '' }}>{{ $post->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="shift">Shift</label>
                        <input type="text" id="shift" name="shift" class="form-control" value="{{ old('shift') }}" maxlength="20" />
                    </div>
                    <div class="form-group col-md-3" id="checkMethodWrapper">
                        <label for="check_method">Check Method</label>
                        <select class="select2 form-control" id="check_method" name="check_method">
                            <option value="">-- Select Method --</option>
                            <option value="100%" {{ old('check_method') == '100%' ? 'selected' : '' }}>100% (A)</option>
                            <option value="Sampling" {{ old('check_method') == 'Sampling' ? 'selected' : '' }}>Sampling (S)</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-4" id="supplierWrapper">
                        <label for="supplier_code">Supplier</label>
                        <select class="select2 form-control" id="supplier_code" name="supplier_code">
                            <option value="">-- Select Supplier --</option>
                            @foreach ($suppliers as $supp)
                            <option value="{{ $supp->kode }}" {{ old('supplier_code') == $supp->kode ? 'selected' : '' }}>{{ $supp->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="article_code">Article</label>
                        <select class="select2 form-control" id="article_code" name="article_code" required>
                            <option value="">-- Select Article --</option>
                            @foreach ($articles as $art)
                            <option value="{{ $art->article_code }}" {{ old('article_code') == $art->article_code ? 'selected' : '' }}>{{ $art->article_code }} - {{ $art->article_desc }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4" id="qtyReceivedWrapper">
                        <label for="qty_received">Qty Received</label>
                        <input type="number" step="0.01" min="0" id="qty_received" name="qty_received" class="form-control" value="{{ old('qty_received', 0) }}" />
                    </div>
                </div>

                <div class="alert alert-info" id="ruleInfo" style="display:none;">
                    Target for this Inspection Post &mdash; Pass Rate: <b id="ruleTargetPassRate">-</b>% |
                    Pass Through: <b id="ruleTargetPassThrough">-</b>%
                </div>
                <div class="alert alert-warning" id="ruleInfoNone" style="display:none;">
                    No target set on this Inspection Post. Result will not be flagged PASS/FAIL.
                </div>

                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="total_check">Total Check</label>
                        <input type="number" step="0.01" min="0" id="total_check" name="total_check" class="form-control" value="{{ old('total_check', 0) }}" required />
                        <small class="text-muted">Auto-filled from Qty Received + Check Method when post is Incoming</small>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="note">Note</label>
                        <textarea id="note" name="note" class="form-control" rows="2">{{ old('note') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" id="total_ok" name="total_ok" value="{{ old('total_ok', 0) }}" />
        <input type="hidden" id="total_ok_repair" name="total_ok_repair" value="{{ old('total_ok_repair', 0) }}" />
        <input type="hidden" id="total_ng" name="total_ng" value="{{ old('total_ng', 0) }}" />

        <div class="card">
            <div class="card-header"><h4 class="card-title">Inspection Summary</h4></div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted">Total OK</span><b id="totalOkDisplay">0</b>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted" id="okRepairLabel">Total OK Repair</span><b id="totalOkRepairDisplay">0</b>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted">Total NG</span><b id="totalNgDisplay">0</b>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted">Pass Rate</span><b id="calcPassRate">0%</b>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted">Pass Through Rate</span><b id="calcPassThrough">0%</b>
                    </li>
                </ul>
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
                <button class="btn btn-outline-secondary" type="button" onclick="window.location='{{ route('inspectionOrder.index') }}'">Cancel</button>
                <button class="btn btn-success" type="button" id="cmdSave">Save</button>
            </div>
        </div>
    </form>
</section>
@endsection
@section('scripts')
<script type="text/javascript">
    let lineIndex = 0;
    const defectOptions = `<option value="">-- Select Defect --</option>@foreach ($defects as $d)<option value="{{ $d->id }}">{{ $d->code }} - {{ $d->name }}</option>@endforeach`;
    const postNames = { @foreach ($posts as $post) {{ $post->id }}: "{{ $post->name }}", @endforeach };

    function addLine(line) {
        line = line || {};
        const row = `<tr>
            <td><select class="select2 form-control defect-select" name="defect_id[]">${defectOptions}</select></td>
            <td>
                <select class="form-control category-select" name="category[]">
                    <option value="NG" ${line.category === 'NG' ? 'selected' : ''}>NG</option>
                    <option value="NC" ${line.category === 'NC' ? 'selected' : ''}>NC</option>
                </select>
            </td>
            <td><input type="number" step="0.01" min="0" class="form-control qty-input" name="defect_qty[]" value="${line.qty || 0}" /></td>
            <td class="okRepairCol"><input type="number" step="0.01" min="0" class="form-control ok-repair-input" name="defect_ok_repair[]" value="${line.ok_repair || 0}" /></td>
            <td><input type="text" class="form-control" name="defect_note[]" value="${line.note || ''}" /></td>
            <td><button type="button" class="btn btn-sm btn-danger btnRemoveLine"><i class="fa fa-trash"></i></button></td>
        </tr>`;
        $('#defectTable tbody').append(row);
        $('#defectTable tbody tr:last .defect-select').val(line.defect_id || '').trigger('change').select2();
        applyPostRules();
    }

    /** Sampling table -- sama persis dengan getSamplingCheck() di create-daily-inspection.blade.php (abimanyu). */
    function getSamplingCheck(qty) {
        if (qty >= 2  && qty <= 8)    return 2;
        if (qty <= 15)   return 3;
        if (qty <= 25)   return 5;
        if (qty <= 50)   return 8;
        if (qty <= 90)   return 13;
        if (qty <= 150)  return 20;
        if (qty <= 280)  return 32;
        if (qty <= 500)  return 50;
        if (qty <= 1200) return 80;
        if (qty <= 3200) return 125;
        if (qty <= 10000) return 200;
        if (qty <= 35000) return 315;
        return 0;
    }

    function isIncomingPost() {
        return (postNames[$('#inspection_post_id').val()] || '').trim() === 'Incoming';
    }

    /**
     * Supplier/Qty Received/Check Method dan kolom OK Repair cuma relevan
     * untuk post "Incoming" -- sama seperti aturan di modul QC abimanyu
     * (create-daily-inspection.blade.php: toggleOkRepair() + listener post).
     */
    function applyPostRules() {
        const isIncoming = isIncomingPost();

        $('#supplierWrapper, #qtyReceivedWrapper, #checkMethodWrapper').toggle(isIncoming);
        $('#qty_received, #check_method').prop('required', isIncoming);

        $('.okRepairCol').toggle(isIncoming);
        $('.okRepairCol input').prop('disabled', !isIncoming).prop('required', isIncoming);
        if (!isIncoming) { $('.okRepairCol input').val(0); }

        $('#okRepairLabel').text(isIncoming ? 'Total OK Repair' : 'Total NC');
        recalcSummary();
    }

    /** Total Check diisi otomatis dari Qty Received + Check Method, cuma untuk post Incoming. */
    function autoFillTotalCheck() {
        if (!isIncomingPost()) return;
        const method = $('#check_method').val();
        const qty = parseFloat($('#qty_received').val()) || 0;
        let val = null;
        if (method === '100%') val = qty;
        else if (method === 'Sampling') val = getSamplingCheck(qty) || 0;
        if (val !== null) { $('#total_check').val(val); }
        recalcSummary();
    }

    /**
     * Total OK/OK Repair(NC)/NG, Pass Rate & Pass Through dihitung dari baris
     * defect, bukan diketik manual -- sama seperti updateAllSummary() di
     * create-daily-inspection.blade.php (abimanyu).
     */
    function recalcSummary() {
        const totalCheck = parseFloat($('#total_check').val()) || 0;
        const isIncoming = isIncomingPost();
        let totalNg = 0, totalOkRepair = 0;

        $('#defectTable tbody tr').each(function () {
            const category = $(this).find('.category-select').val();
            const qty = parseFloat($(this).find('.qty-input').val()) || 0;
            const okRepair = parseFloat($(this).find('.ok-repair-input').val()) || 0;
            if (category === 'NG') { totalNg += qty; }
            if (isIncoming) { totalOkRepair += okRepair; }
            else if (category === 'NC') { totalOkRepair += qty; }
        });

        const totalOk = Math.max(totalCheck - totalNg, 0);
        const passRate = totalCheck > 0 ? (totalOk / totalCheck * 100).toFixed(2) : '0.00';
        const passThrough = totalCheck > 0 ? (Math.max(totalCheck - totalNg - totalOkRepair, 0) / totalCheck * 100).toFixed(2) : '0.00';

        $('#total_ok').val(totalOk);
        $('#total_ok_repair').val(totalOkRepair);
        $('#total_ng').val(totalNg);
        $('#totalOkDisplay').text(totalOk);
        $('#totalOkRepairDisplay').text(totalOkRepair);
        $('#totalNgDisplay').text(totalNg);
        $('#calcPassRate').text(passRate + '%');
        $('#calcPassThrough').text(passThrough + '%');
    }

    function loadRule() {
        const postId = $('#inspection_post_id').val();
        if (!postId) {
            $('#ruleInfo, #ruleInfoNone').hide();
            return;
        }
        $.get('{{ route("inspectionOrder.getRule") }}', { postId: postId }, function (rule) {
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
        $("#frmAdd").validate({}).settings.ignore = "";
        addLine();
        applyPostRules();

        $('#btnAddLine').click(function(){ addLine(); });
        $(document).on('click', '.btnRemoveLine', function(){ $(this).closest('tr').remove(); recalcSummary(); });
        $(document).on('input change', '.qty-input, .ok-repair-input, .category-select', recalcSummary);
        $(document).on('input', '#total_check', recalcSummary);
        $('#check_method').on('change', autoFillTotalCheck);
        $('#qty_received').on('input', autoFillTotalCheck);
        $('#inspection_post_id').on('change', loadRule);
        $('#inspection_post_id').on('change', applyPostRules);
    });

    $("#cmdSave").click(function(){
        $("#frmAdd").submit();
    });
</script>
@endsection
