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
            <div class="card-header">
                <h4 class="card-title"><i data-feather="clipboard" class="mr-50"></i> {{ $hdr->order_no }}</h4>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="inspection_date">Inspection Date <span class="text-danger">*</span></label>
                        <input type="date" id="inspection_date" name="inspection_date" class="form-control" value="{{ old('inspection_date', $hdr->inspection_date) }}" required />
                    </div>
                    <div class="form-group col-md-3">
                        <label for="inspection_post_id">Inspection Post <span class="text-danger">*</span></label>
                        <select class="select2 form-control" id="inspection_post_id" name="inspection_post_id" required>
                            <option value="">-- Select Post --</option>
                            @foreach ($posts as $post)
                            <option value="{{ $post->id }}" {{ old('inspection_post_id', $hdr->inspection_post_id) == $post->id ? 'selected' : '' }}>{{ $post->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="article_code">Article <span class="text-danger">*</span></label>
                        <select class="select2 form-control" id="article_code" name="article_code" required>
                            <option value="">-- Select Article --</option>
                            @foreach ($articles as $art)
                            <option value="{{ $art->article_code }}" {{ old('article_code', $hdr->article_code) == $art->article_code ? 'selected' : '' }}>{{ $art->article_alternative_code }} - {{ $art->article_desc }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="border rounded bg-light p-2 mb-1" id="incomingFieldsWrapper">
                    <small class="text-muted d-block mb-1 ml-1 text-uppercase font-weight-bold" style="letter-spacing:.03em;">Incoming Details</small>
                    <div class="form-row">
                        <div class="form-group col-md-4 mb-0">
                            <label for="supplier_code">Supplier</label>
                            <select class="select2 form-control" id="supplier_code" name="supplier_code">
                                <option value="">-- Select Supplier --</option>
                                @foreach ($suppliers as $supp)
                                <option value="{{ $supp->kode }}" {{ old('supplier_code', $hdr->supplier_code) == $supp->kode ? 'selected' : '' }}>{{ $supp->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4 mb-0" id="qtyReceivedWrapper">
                            <label for="qty_received">Qty Received</label>
                            <input type="number" step="0.01" min="0" id="qty_received" name="qty_received" class="form-control" value="{{ old('qty_received', $hdr->qty_received) }}" />
                        </div>
                        <div class="form-group col-md-4 mb-0" id="checkMethodWrapper">
                            <label for="check_method">Check Method</label>
                            <select class="select2 form-control" id="check_method" name="check_method">
                                <option value="">-- Select Method --</option>
                                <option value="100%" {{ old('check_method', $hdr->check_method) == '100%' ? 'selected' : '' }}>100% (A)</option>
                                <option value="Sampling" {{ old('check_method', $hdr->check_method) == 'Sampling' ? 'selected' : '' }}>Sampling (S)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="total_check">Total Check <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" id="total_check" name="total_check" class="form-control" value="{{ old('total_check', $hdr->total_check) }}" required />
                        <small class="text-muted">Auto-filled from Qty Received + Check Method when post is Incoming</small>
                    </div>
                    <div class="form-group col-md-9">
                        <label for="note">Note</label>
                        <textarea id="note" name="note" class="form-control" rows="1">{{ old('note', $hdr->note) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" id="total_ok" name="total_ok" value="{{ old('total_ok', $hdr->total_ok) }}" />
        <input type="hidden" id="total_ok_repair" name="total_ok_repair" value="{{ old('total_ok_repair', $hdr->total_ok_repair) }}" />
        <input type="hidden" id="total_ng" name="total_ng" value="{{ old('total_ng', $hdr->total_ng) }}" />

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h4 class="card-title mb-0"><i data-feather="bar-chart-2" class="mr-50"></i> Inspection Summary</h4>
                <small class="text-muted" id="targetSummaryText">Select an Inspection Post to see its target</small>
            </div>
            <div class="card-body py-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Total OK</span><b id="totalOkDisplay">0</b>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted" id="okRepairLabel">Total OK Repair</span><b id="totalOkRepairDisplay">0</b>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Total NG</span><b id="totalNgDisplay">0</b>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Pass Rate</span>
                        <span><b id="calcPassRate">0%</b> <small class="text-muted" id="targetPassRateInline"></small></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Pass Through Rate</span>
                        <span><b id="calcPassThrough">0%</b> <small class="text-muted" id="targetPassThroughInline"></small></span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title"><i data-feather="list" class="mr-50"></i> Defect Lines</h4>
                <button type="button" class="btn btn-sm btn-primary" id="btnAddLine"><i class="fa fa-plus"></i> Add Line</button>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-sm mb-0" id="defectTable">
                    <thead class="thead-light">
                        <tr>
                            <th style="min-width:220px;">Defect</th>
                            <th style="width:100px;">Category</th>
                            <th style="width:100px;" class="text-right">Qty</th>
                            <th style="width:110px;" class="text-right okRepairCol">OK Repair</th>
                            <th>Note</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-2">
            <a href="{{ route('inspectionOrder.index') }}" class="btn btn-outline-secondary mr-1">Cancel</a>
            <button class="btn btn-success" type="button" id="cmdSave"><i data-feather="save" class="mr-50"></i> Save</button>
        </div>
    </form>
</section>
@endsection
@section('scripts')
<script type="text/javascript">
    const existingLines = {!! $details->map(function($d){ return ['defect_id'=>$d->defect_id,'category'=>$d->category,'qty'=>$d->qty,'ok_repair'=>$d->ok_repair,'note'=>$d->note]; })->values() !!};
    const defectOptions = `<option value="">-- Select Defect --</option>@foreach ($defects as $d)<option value="{{ $d->id }}">{{ $d->code }} - {{ $d->name }}</option>@endforeach`;
    const postNames = { @foreach ($posts as $post) {{ $post->id }}: "{{ $post->name }}", @endforeach };
    let currentTarget = null;

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
            <td><input type="number" step="0.01" min="0" class="form-control text-right qty-input" name="defect_qty[]" value="${line.qty || 0}" /></td>
            <td class="okRepairCol"><input type="number" step="0.01" min="0" class="form-control text-right ok-repair-input" name="defect_ok_repair[]" value="${line.ok_repair || 0}" /></td>
            <td><input type="text" class="form-control" name="defect_note[]" value="${line.note || ''}" /></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-danger btnRemoveLine"><i class="fa fa-trash"></i></button></td>
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

    /** Sama seperti di create.blade.php -- lihat komentar di sana. */
    function applyPostRules() {
        const isIncoming = isIncomingPost();

        $('#incomingFieldsWrapper').toggle(isIncoming);
        $('#qty_received, #check_method').prop('required', isIncoming);

        $('.okRepairCol').toggle(isIncoming);
        $('.okRepairCol input').prop('disabled', !isIncoming).prop('required', isIncoming);
        if (!isIncoming) { $('.okRepairCol input').val(0); }

        $('#okRepairLabel').text(isIncoming ? 'Total OK Repair' : 'Total NC');
        recalcSummary();
    }

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
        $('#calcPassRate').text(passRate + '%').removeClass('text-success text-danger');
        $('#calcPassThrough').text(passThrough + '%').removeClass('text-success text-danger');

        if (currentTarget && totalCheck > 0) {
            $('#calcPassRate').addClass(parseFloat(passRate) >= currentTarget.target_pass_rate ? 'text-success' : 'text-danger');
            $('#calcPassThrough').addClass(parseFloat(passThrough) >= currentTarget.target_pass_through_rate ? 'text-success' : 'text-danger');
        }
    }

    function loadRule() {
        const postId = $('#inspection_post_id').val();
        if (!postId) {
            currentTarget = null;
            $('#targetSummaryText').text('Select an Inspection Post to see its target');
            $('#targetPassRateInline, #targetPassThroughInline').text('');
            recalcSummary();
            return;
        }
        $.get('{{ route("inspectionOrder.getRule") }}', { postId: postId }, function (rule) {
            currentTarget = rule || null;
            if (rule) {
                $('#targetSummaryText').html('Target &mdash; Pass Rate <b>' + rule.target_pass_rate + '%</b> · Pass Through <b>' + rule.target_pass_through_rate + '%</b>');
                $('#targetPassRateInline').text('/ target ' + rule.target_pass_rate + '%');
                $('#targetPassThroughInline').text('/ target ' + rule.target_pass_through_rate + '%');
            } else {
                $('#targetSummaryText').text('No target set on this Inspection Post');
                $('#targetPassRateInline, #targetPassThroughInline').text('');
            }
            recalcSummary();
        });
    }

    $(document).ready(function(){
        $("#frmEdit").validate({}).settings.ignore = "";
        if (existingLines.length) {
            existingLines.forEach(addLine);
        } else {
            addLine();
        }
        loadRule();
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
        $("#frmEdit").submit();
    });
</script>
@endsection
