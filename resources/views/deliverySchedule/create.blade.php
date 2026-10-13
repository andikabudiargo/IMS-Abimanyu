@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
  <div class="form-row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">@yield('title')</h4>
        </div>
        <div class="card-content">
          <div class="card-body">
            <div class="form-row">
              <div class="form-group col-md-4">
                <label>Customer</label>
                <input type="text" class="form-control disabled-el" value="{{ $customer->kode }} - {{ $customer->nama }}" disabled />
              </div>
              <div class="form-group col-md-8">
                <label>Sales Order ({{ $soCodes->count() }})</label>
                <input type="text" class="form-control disabled-el" value="{{ $soCodes->implode(', ') }}" disabled />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="form-row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Schedule Period</h4>
        </div>
        <div class="card-content">
          <div class="card-body">
            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="schedulePeriod">Delivery Date Range*</label>
                <input type="text" id="schedulePeriod" name="schedulePeriod" class="form-control flatpickr-range" placeholder="DD-MM-YYYY to DD-MM-YYYY" required/>
              </div>
              <div class="form-group col-md-2" style="padding-top:22px;">
                <button type="button" class="btn btn-primary" id="cmdGenerate">Generate Table</button>
              </div>
              <div class="form-group col-md-6 d-flex justify-content-between" style="padding-top:22px;">
                <a href="javascript:;" id="lnkTemplate" class="btn btn-success disabled" title="Pilih Delivery Date Range lalu klik Generate Table dulu">
                  <i data-feather="download"></i> Download Template
                </a>
                <input type="file" id="uploadFile" accept=".xls,.xlsx" class="d-none" />
                <button type="button" class="btn btn-info" id="cmdUpload">
                  <i data-feather="upload"></i> Upload Schedule
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="form-row" id="gridWrap" style="display:none;">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Schedule Detail <small class="text-muted">isi qty pengiriman per tanggal, total per material harus sama dengan Order Qty</small></h4>
        </div>
        <div class="card-content">
          <div class="card-body">
            <div class="table-scrollable table-responsive">
              <table class="table table-bordered table-sm" id="tblSchedule">
              </table>
            </div>
            <button class="btn btn-success mt-2" type="button" id="cmdSave">Save Schedule</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
@section('styles')
<style>
    #tblSchedule input.qty-cell{ width:80px; text-align:right; }
    .row-total.match{ color:#28c76f; font-weight:bold; }
    .row-total.mismatch{ color:#ea5455; font-weight:bold; }
</style>
@endsection
@section('scripts')
<script src="{{ asset('assets/js/freeze-table.js') }}"></script>
<script type="text/javascript">
    const customerId = "{{ $customer->kode }}";
    const articles = @json($articles);
    const existing = @json($existing);

    rangePickr = $('.flatpickr-range');
    if (rangePickr.length) {
        rangePickr.flatpickr({ dateFormat: "d-m-Y", mode: 'range', allowInput:true });
    }

    function toIso(ddmmyyyy){
        let p = ddmmyyyy.split('-');
        return p[2]+'-'+p[1]+'-'+p[0];
    }

    function toDdMmYyyy(iso){
        let p = iso.split('-');
        return p[2]+'-'+p[1]+'-'+p[0];
    }

    function buildDateList(fromIso, toIso_){
        let dates = [];
        let cur = new Date(fromIso);
        let end = new Date(toIso_);
        while (cur <= end){
            dates.push(cur.toISOString().slice(0,10));
            cur.setDate(cur.getDate()+1);
        }
        return dates;
    }

    // rowKey = so_code|article_code (one row per SO/article combo)
    function rowKeyOf(a){ return a.so_code+'|'+a.article_code; }

    let currentValues = {};

    function collectValues(){
        $('#tblSchedule input.qty-cell').each(function(){
            let key = $(this).data('row-key')+'|'+$(this).data('date');
            let val = parseFloat($(this).val()) || 0;
            if (val > 0) currentValues[key] = val;
        });
    }

    function prefillFromExisting(){
        existing.forEach(function(row){
            currentValues[row.so_code+'|'+row.article_code+'|'+row.schedule_date] = parseFloat(row.qty);
        });
    }

    function recalcRow(rowKey){
        let total = 0;
        $('#tblSchedule input.qty-cell[data-row-key="'+rowKey+'"]').each(function(){
            total += parseFloat($(this).val()) || 0;
        });
        let orderQty = parseFloat($('#tblSchedule .order-qty[data-row-key="'+rowKey+'"]').data('qty'));
        let el = $('#tblSchedule .row-total[data-row-key="'+rowKey+'"]');
        let matched = Math.abs(total-orderQty) < 0.01;
        el.text(total.toFixed(2));
        el.toggleClass('match', matched);
        el.toggleClass('mismatch', !matched);

        // Once a row's total matches its Order Qty, lock the still-empty cells
        // in that row so it can't be overfilled; unlock them again if it no
        // longer matches (e.g. the user edited a filled cell back down).
        $('#tblSchedule input.qty-cell[data-row-key="'+rowKey+'"]').each(function(){
            let hasValue = (parseFloat($(this).val()) || 0) > 0;
            $(this).prop('disabled', matched && !hasValue);
        });
    }

    function updateTemplateLink(fromDdMmYyyy, toDdMmYyyy_){
        let url = "{{ route('deliverySchedule.export.template', ['customer_id' => $customer->kode]) }}"
            + "?periode=" + encodeURIComponent(fromDdMmYyyy + ' to ' + toDdMmYyyy_);
        $('#lnkTemplate').attr('href', url).removeClass('disabled').removeAttr('title');
    }

    function generateGrid(fromDdMmYyyy, toDdMmYyyy_){
        updateTemplateLink(fromDdMmYyyy, toDdMmYyyy_);
        collectValues();
        let dates = buildDateList(toIso(fromDdMmYyyy), toIso(toDdMmYyyy_));

        let html = '<thead class="thead-light"><tr><th>SO Code</th><th>Article Code</th><th>Description</th><th>UOM</th><th>Order Qty</th>';
        dates.forEach(function(d){ html += '<th>'+toDdMmYyyy(d)+'</th>'; });
        html += '<th>Total</th></tr></thead><tbody>';

        articles.forEach(function(a){
            let rowKey = rowKeyOf(a);
            html += '<tr>';
            html += '<td>'+a.so_code+'</td>';
            html += '<td>'+(a.article_alternative_code || a.article_code)+'</td>';
            html += '<td>'+(a.article_desc || '')+'</td>';
            html += '<td>'+a.uom+'</td>';
            html += '<td class="order-qty" data-row-key="'+rowKey+'" data-qty="'+a.qty+'">'+a.qty+'</td>';
            dates.forEach(function(d){
                let val = currentValues[rowKey+'|'+d] || '';
                html += '<td><input type="text" class="form-control qty-cell" data-row-key="'+rowKey+'" data-date="'+d+'" value="'+val+'" /></td>';
            });
            html += '<td class="row-total" data-row-key="'+rowKey+'">0.00</td>';
            html += '</tr>';
        });
        html += '</tbody>';

        $('#tblSchedule').html(html);
        $('#gridWrap').show();

        // Resync currentValues strictly to what's now rendered, so a shrunk date
        // range doesn't silently keep saving values for dates no longer shown.
        currentValues = {};
        collectValues();

        $('#tblSchedule input.qty-cell').on('input', function(){
            recalcRow($(this).data('row-key'));
        });

        articles.forEach(function(a){ recalcRow(rowKeyOf(a)); });

        if ($.fn.freezeTable){
            $(".table-scrollable").freezeTable({ scrollable:true, columnNum:5 });
        }
    }

    $(document).ready(function(){
        if (existing.length){
            prefillFromExisting();
            let dates = existing.map(r => r.schedule_date).sort();
            let from = toDdMmYyyy(dates[0]);
            let to = toDdMmYyyy(dates[dates.length-1]);
            $('#schedulePeriod').val(from+' to '+to);
            generateGrid(from, to);
        }
    });

    $('#cmdGenerate').click(function(){
        let periode = $('#schedulePeriod').val();
        if (!periode){
            Swal.fire('Warning..','Delivery Date Range is required','warning');
            return;
        }
        let parts = periode.split('to');
        let from = parts[0].trim();
        let to = parts.length>1 ? parts[1].trim() : from;
        generateGrid(from, to);
    });

    $('#cmdUpload').click(function(){
        $('#uploadFile').click();
    });

    $('#uploadFile').change(function(){
        let file = this.files[0];
        if (!file) return;

        let formData = new FormData();
        formData.append('file', file);
        formData.append('customerId', customerId);

        $.ajax({
            type:'post',
            url:"{{ route('deliverySchedule.import.excel') }}",
            data:formData,
            contentType:false,
            processData:false,
            dataType:'json',
            success:function(data){
                if (data.errors && data.errors.length){
                    Swal.fire('Warning..', data.errors.join('<br>'), 'warning');
                }
                if (data.rows && data.rows.length){
                    collectValues();
                    data.rows.forEach(function(r){
                        currentValues[r.so_code+'|'+r.article_code+'|'+r.schedule_date] = parseFloat(r.qty);
                    });
                    let allDates = Object.keys(currentValues).map(k => k.split('|')[2]).sort();
                    let from = toDdMmYyyy(allDates[0]);
                    let to = toDdMmYyyy(allDates[allDates.length-1]);
                    $('#schedulePeriod').val(from+' to '+to);
                    generateGrid(from, to);
                    show_msg('Upload Schedule', 'Schedule rows imported, please review and Save', 'success');
                }
            },
            error:function(error){
                console.log(error);
                Swal.fire('Error..','Failed to upload file','error');
            }
        });
        $(this).val('');
    });

    $('#cmdSave').click(function(){
        let mismatch = $('#tblSchedule .row-total.mismatch').length;
        if (mismatch > 0){
            Swal.fire('Warning..','Total qty per article must match the Order Qty before saving','warning');
            return;
        }

        collectValues();
        let rows = [];
        Object.keys(currentValues).forEach(function(key){
            let parts = key.split('|');
            rows.push({ so_code: parts[0], article_code: parts[1], schedule_date: toDdMmYyyy(parts[2]), qty: currentValues[key] });
        });

        $('#cmdSave').attr('disabled','disabled');
        $.ajax({
            type:'post',
            url:"{{ route('deliverySchedule.save') }}",
            data:{ customerId: customerId, rows: JSON.stringify(rows) },
            dataType:'json',
            success:function(data){
                $('#cmdSave').removeAttr('disabled');
                if (data.status == 0){
                    let msgs = Array.isArray(data.message) ? data.message : [data.message];
                    msgs.forEach(function(m){ show_msg(data.title, m, data.alert); });
                }else{
                    show_msg(data.title, data.message, data.alert);
                    setTimeout(function(){ window.location = "{{ route('deliverySchedules.index') }}"; }, 1500);
                }
            },
            error:function(error){
                $('#cmdSave').removeAttr('disabled');
                console.log(error);
            }
        });
    });

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });
</script>
@endsection
