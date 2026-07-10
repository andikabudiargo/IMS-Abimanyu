@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText"></span></h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                            <input type="hidden" id="idRec" name="idRec" class="form-control" value="{{ $header->id }}" />
                        </ul>
                    </div>
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" autocomplete="off">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="recNumber">Receiving Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="recNumber" name="recNumber" class="form-control text-hitam disabled-el" value="{{ $header->rec_number }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="recDate">Receiving Date*</label>
                                    <input type="text" id="recDate" name="recDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header->rec_date }}" required/>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="recType">Receive Type</label>
                                    <select class="select2 form-control" id="recType" name="recType" required disabled>
                                        <option value="NORMAL" {{ $header->rec_type == 'NORMAL' ? 'selected' : '' }}>Purchase Order</option>
                                        <option value="NP" {{ $header->rec_type == 'NP' ? 'selected' : '' }}>Non Purchase</option>
                                        <option value="JASA" {{ $header->rec_type == 'JASA' ? 'selected' : '' }}>Jasa</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="supplier">Supplier*</label>
                                    <select class="select2 form-control" id="supplier" name="supplier" required disabled>
                                        <option value=""></option>
                                        @foreach($supps as $val)
                                            <option value="{{$val->kode}}" {{ $val->kode == $header->supplier_id ? 'selected' : '' }}>{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="poNumber">PO Number*</label>
                                    <select class="select2 form-control" id="poNumber" name="poNumber" required>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="doDate">DO Date*</label>
                                    <input type="text" id="doDate" name="doDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header->do_date }}" required />
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="doNumber">DO Number*</label>
                                    <input type="text" id="doNumber" name="doNumber" class="form-control disabled-el" value="{{ $header->do_number }}" required/>
                                </div>
                                <div class="form-group col-md-2 d-none">
                                    <label for="invDate">Invoice Date*</label>
                                    <input type="text" id="invDate" name="invDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header->inv_date }}" />
                                </div>
                                <div class="form-group col-md-3 d-none">
                                    <label for="invNumber">Invoice Number*</label>
                                    <input type="text" id="invNumber" name="invNumber" class="form-control disabled-el" value="{{ $header->inv_number }}" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1">{{ $header->note }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body">
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('receiving.headerColumnv2')
                            <input type="text" id="last_row_number" class="d-none" value="{{ count($detail) }}">
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-outline-primary btn-sm d-none" type="button" id="cmdAddRow">
                            <i data-feather="plus"></i> Add Row
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-04">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" disabled/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group row mb-03">
                                <label for="totalQTY" class="col-sm-4 col-form-label titik-dua">Total Qty</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQTY" disabled />
                                </div>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control text-right font-weight-bold text-hitam" id="convQTY" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalQtyFree" class="col-sm-4 col-form-label titik-dua">Total Qty Free</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQtyFree" disabled />
                                </div>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control text-right font-weight-bold text-hitam" id="convQtyFree" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="grandTotalQty" class="col-sm-4 col-form-label titik-dua">Grand Total Qty</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control text-right font-weight-bold" id="grandTotalQty" disabled />
                                </div>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control text-right font-weight-bold text-hitam" id="convGrandTotalQty" disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-12">
                            <a href="{{ route('receivings.index') }}" class="btn btn-light">Back</a>
                            @if( $approveValidate ? $approveValidate[0]->validate : '')
                                <input type="text" id="approveLevel" name="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                <input type="text" id="maxLevel" name="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                                @if( $statusRec == 'REVISI')
                                    <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate">Update</button>
                                @endif
                            @else
                                @if( !$approveValidate && $statusRec == 'REVISI')
                                    <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate">Update</button>
                                @endif
                            @endif
                            @if( $statusRec == 'POSTED')
                                <button class="btn btn-dark" type="button" id="cmdPrint" name="cmdPrint">Print</button>
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="form-row card-statistics">
                        @foreach($approvalHistory as $val)
                            @if($val->status == true)
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-{{ $val->statusapprove == 1 ? 'success':'warning' }} mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="{{ $val->statusapprove == 1 ? 'check':'x' }}" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">{{ $val->statusapprove == 1 ? 'Approve':'Decline' }}-{{ $val->approval_order }}</h4>
                                                <p class="card-text mb-0">{{ $val->name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-danger mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="x" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}</h4>
                                                <p class="card-text mb-0">{{ $val->petugas }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('receiving.addArticlev2')
@endsection
@section('styles')
@endsection
@section('scripts')
<script type="text/javascript">

    /* =====================================================================
       CATATAN PENYESUAIAN (mohon dicek ulang terhadap controller/DB Anda):
       1. $header->rec_type diasumsikan ada (NORMAL/NP/JASA), sama seperti
          create v2. Jika kolom ini belum ada di tabel receiving header,
          tambahkan dulu, atau ganti default value di bawah.
       2. $detail per baris diasumsikan punya field yang sama dengan yang
          disimpan oleh receiving.store (v2):
          article_code, article_alternative_code, article_desc, qty_po,
          uom, price, qty, qty_free, pr_number, unit_from, unit_to,
          unit_factor, uom_rec (opsional), uom_free (opsional).
       3. Route mengikuti versi v2 seperti di halaman create:
          receiving.list.pov2, receiving.po.det2, receiving.list.pr,
          receiving.pr.det, receiving.uom.conv, receiving.list.article,
          receiving.update, receiving.approve, receiving.print.
       4. Form (PO/PR, DO, Note, Article) hanya aktif diedit saat
          status = REVISI. Selain itu di-lock read only.
       5. Tombol Print hanya muncul saat status = POSTED.
       ===================================================================== */

    /* =====================================================================
       HELPERS
       ===================================================================== */
    let lockedAt   = "{{ $lockDate }}";
    let dariEdit   = 'true';   // true selama data awal (dari $detail) sedang dimuat
    let cloneCount = 0;

    function resetArticleArea(){
        $('#article_row').empty();
        cloneCount = 0;
        hitungGrandTotal();
    }

    /* =====================================================================
       KONVERSI UOM — rec & free factor TERPISAH
       ===================================================================== */
    function buildUomOptions(unitFrom, unitTo){
        let opts = '';
        if (unitFrom) opts += '<option value="'+unitFrom+'">'+unitFrom+'</option>';
        if (unitTo && unitTo !== unitFrom) opts += '<option value="'+unitTo+'">'+unitTo+'</option>';
        if (!opts) opts = '<option value=""></option>';
        return opts;
    }

    function factorForUnit(selected, unitFrom, unitTo, baseFactor){
        if (unitTo && selected === unitFrom) return { to: unitTo,              factor: baseFactor };
        if (unitTo && selected === unitTo)   return { to: unitTo,              factor: 1          };
        return                                      { to: selected || unitFrom, factor: 1          };
    }

    function applyRowConversion(suffix){
        let $qtyRec  = $('#qty_rec'+suffix);
        let unitFrom = $qtyRec.attr('data-unit-from');
        let unitTo   = $qtyRec.attr('data-unit-to');
        let base     = parseFloat($qtyRec.attr('data-unit-factor')) || 1;

        let selRec  = $('#uom'+suffix).val();
        let rec     = factorForUnit(selRec, unitFrom, unitTo, base);
        $qtyRec.attr('data-conv-from', selRec)
               .attr('data-conv-to',   rec.to)
               .attr('data-conv-factor', rec.factor);

        let selFree = $('#uomFree'+suffix).val() || selRec;
        let free    = factorForUnit(selFree, unitFrom, unitTo, base);
        $('#qty_free'+suffix)
               .attr('data-conv-from',   selFree)
               .attr('data-conv-to',     free.to)
               .attr('data-conv-factor', free.factor);

        hitungKonversi($qtyRec);
    }

    function hitungKonversi(elemen){
        let $row   = $(elemen).closest('[id^="new_row"]');
        let $qRec  = $row.find('input[name="qty_rec[]"]');
        let $qFree = $row.find('input[name="qty_free[]"]');

        let facRec  = parseFloat($qRec.attr('data-conv-factor'))  || 1;
        let facFree = parseFloat($qFree.attr('data-conv-factor')) || 1;
        let to      = $qRec.attr('data-conv-to') || $qRec.attr('data-conv-from');

        let qtyRec  = parseFloat(($qRec.val()  || '0').replace(/,/gi,'')) || 0;
        let qtyFree = parseFloat(($qFree.val() || '0').replace(/,/gi,'')) || 0;
        let hasil   = (qtyRec * facRec) + (qtyFree * facFree);

        $row.find('.conv-info').text(
            hasil.toLocaleString(undefined,{ maximumFractionDigits: numberOfDecimalDigit })
            + (to ? ' '+to : '')
        );
        hitungGrandTotal();
    }

    /* =====================================================================
       LABEL & TOMBOL ADD ROW MENGIKUTI recType
       ===================================================================== */
    function updateRecTypeLabels(){
        let isNp = $('#recType').val() === 'NP';
        $('label[for="poNumber"]').text(isNp ? 'PR Number' : 'PO Number*');
        $('#lblQtyRef').text(isNp ? 'QTY PR' : 'QTY PO');
        $('#lblQtyRefHeader').text(isNp ? 'QTY PR' : 'QTY PO');
        if (window.feather) feather.replace();
    }

    /* =====================================================================
       RECTYPE CHANGE (disabled di edit, disiapkan bila suatu saat dibuka)
       ===================================================================== */
    $('#recType').change(function(){
        updateRecTypeLabels();
        $('#supplier').val(null).trigger('change.select2');
        $('#poNumber').empty().append('<option value=""></option>').val('').trigger('change.select2');
        resetArticleArea();
    });

    /* =====================================================================
       SUPPLIER CHANGE (disabled di edit, disiapkan bila suatu saat dibuka)
       ===================================================================== */
    $('#supplier').change(function(){
        let value = $(this).val();
        let isNp  = $('#recType').val() === 'NP';

        resetArticleArea();

        if (isNp){
            $('#cmdAddRow').removeClass('d-none');
            searchPr('poNumber', value);
        } else {
            $('#cmdAddRow').addClass('d-none');
            searchPo('poNumber', value);
        }
    });

    /* =====================================================================
       PO / PR CHANGE — refresh artikel (di-guard oleh dariEdit saat load awal)
       ===================================================================== */
    $('#poNumber').change(function(){
        let value = $(this).val();
        if (dariEdit === 'false'){
            $('#doDate').val('');
            $('#doNumber').val('');
        }
        if ($('#recType').val() === 'NP'){
            searchPrDet(value);
        } else {
            searchPoDet(value, dariEdit);
        }
    });

    /* =====================================================================
       FETCH LIST PO (NORMAL / JASA)
       ===================================================================== */
    function searchPo(obj, value){
        $.ajax({
            url:"{{ route('receiving.list.pov2') }}",
            method:"GET",
            data:{ value: value, recType: $('#recType').val() },
            success:function(result){ $('#'+obj).html(result); },
            error: function(){ Swal.fire("Warning","Get list PO failed","warning"); }
        });
    }

    /* =====================================================================
       FETCH DETAIL PO → isi baris artikel (hanya jika bukan load awal edit)
       ===================================================================== */
    function searchPoDet(value, dariEditFlag){
        if (dariEditFlag !== 'false') return;

        resetArticleArea();
        if (!value || value === 'Choose PO') return;

        $.ajax({
            url:"{{ route('receiving.po.det2') }}",
            method:"GET",
            data:{ value: value },
            success:function(result){
                cloneCount = 0;
                if (result.length > 0){
                    result.forEach(function(r){
                        let qty = r.qty_order <= 0 ? 0 : '';
                        add_new_row(
                            r.article_code, r.article_alternative_code, r.article_desc,
                            r.qty_order, r.uom, r.price, qty,
                            r.pr_number, r.conv_to, r.conv_factor
                        );
                    });
                }
            },
            error: function(){ Swal.fire("Warning","Get detail PO failed","warning"); }
        });
    }

    /* =====================================================================
       FETCH LIST PR (NP)
       ===================================================================== */
    function searchPr(obj, value){
        if (!value){ $('#'+obj).empty().append('<option value=""></option>'); return; }
        $.ajax({
            type:'post',
            url:"{{ route('receiving.list.pr') }}",
            data:{ value: value },
            success:function(res){ $('#'+obj).html(res); },
            error: function(){ Swal.fire("Warning","Get list PR failed","warning"); }
        });
    }

    /* =====================================================================
       FETCH DETAIL PR → isi baris artikel (hanya jika bukan load awal edit)
       ===================================================================== */
    function searchPrDet(prNumber){
        if (dariEdit !== 'false') return;

        resetArticleArea();

        let adaPr = prNumber && prNumber !== '' && prNumber !== 'Choose PR';

        if ($('#recType').val() === 'NP'){
            adaPr ? $('#cmdAddRow').addClass('d-none') : $('#cmdAddRow').removeClass('d-none');
        }

        if (!adaPr) return;

        $.ajax({
            type:'post',
            url:"{{ route('receiving.pr.det') }}",
            data:{ value: prNumber, supp: $('#supplier').val() },
            dataType:'json',
            success:function(data){
                data.forEach(function(it){
                    add_new_row(
                        it.article_code, it.article_alternative_code, it.article_desc,
                        it.qty_order, it.uom, it.price, it.qty_order,
                        it.pr_number, it.conv_to, it.conv_factor
                    );
                });
                hitungGrandTotal();
            },
            error: function(){ Swal.fire("Warning","Get detail PR failed","warning"); }
        });
    }

    /* =====================================================================
       FETCH UOM CONV dari uom_con_v2 (baris manual NP)
       ===================================================================== */
    function loadUomConv(suffix, articleCode, suppCode, defaultUom){
        let $uom    = $('#uom'+suffix);
        let $uomFr  = $('#uomFree'+suffix);
        let $qtyRec = $('#qty_rec'+suffix);

        $uom.html(buildUomOptions(defaultUom, null));
        $uomFr.html(buildUomOptions(defaultUom, null));
        $qtyRec.attr('data-unit-from', defaultUom || '')
               .attr('data-unit-to',   defaultUom || '')
               .attr('data-unit-factor', 1);
        applyRowConversion(suffix);

        if (!articleCode || !suppCode) return;

        $.ajax({
            url:"{{ route('receiving.uom.conv') }}",
            method:"GET",
            data:{ article_code: articleCode, supplier_code: suppCode },
            dataType:"json",
            success:function(rows){
                if (!rows || rows.length === 0) return;
                let r     = rows[0];
                let uFrom = r.unit_from || defaultUom;
                let uTo   = r.unit_to;
                let fac   = parseFloat(r.unit_factor) || 1;

                $uom.html(buildUomOptions(uFrom, uTo));
                $uomFr.html(buildUomOptions(uFrom, uTo));
                $qtyRec.attr('data-unit-from', uFrom || '')
                       .attr('data-unit-to',   uTo || uFrom || '')
                       .attr('data-unit-factor', fac);
                applyRowConversion(suffix);
            },
            error: function(){ Swal.fire("Warning","Get UOM conversion failed","warning"); }
        });
    }

    /* =====================================================================
       APPROVE
       ===================================================================== */
    function approve(recNumber, objButton){
        $('#'+objButton).attr('disabled','disabled');
        $.ajax({
            type:"POST",
            url:"{{ route('receiving.approve') }}",
            data:{ recNumber: recNumber },
            dataType:"json",
            success:function(data){
                if (data.status == 0){
                    data.message.forEach(m => show_msg(data.title, m, data.alert));
                    $('#'+objButton).removeAttr('disabled');
                } else {
                    show_msg(data.title, data.message, data.alert);
                    reloadPage();
                }
            },
            error: function(e){ console.log(e); $('#'+objButton).removeAttr('disabled'); }
        });
    }

    /* =====================================================================
       KUNCI FORM (read-only) JIKA STATUS BUKAN REVISI
       ===================================================================== */
    function lockFormForReadOnly(){
        $('#poNumber, #doDate, #doNumber, #note').attr('disabled','disabled');
        $('#article_row input, #article_row select').attr('disabled','disabled');
        $('#cmdAddRow').addClass('d-none');
    }

    /* =====================================================================
       MUAT DROPDOWN PO/PR SESUAI recType, LALU ARAHKAN KE NILAI TERSIMPAN
       (dariEdit tetap 'true' selama proses ini agar tidak memicu reset
       artikel — baris artikel dimuat manual dari $detail)
       ===================================================================== */
    function initPoDropdown(){
        let isNp       = $('#recType').val() === 'NP';
        let supplierId = "{{ $header->supplier_id }}";
        let poNumber   = "{{ $header->po_number }}";

        if (isNp){
            $.ajax({
                type:'post',
                url:"{{ route('receiving.list.pr') }}",
                data:{ value: supplierId },
                success:function(res){
                    $('#poNumber').html(res);
                    $('#poNumber').val(poNumber).trigger('change');
                    dariEdit = 'false';
                },
                error: function(){ Swal.fire("Warning","Get list PR failed","warning"); }
            });
        } else {
            $.ajax({
                url:"{{ route('receiving.list.pov2') }}",
                method:"GET",
                data:{ value: supplierId, recType: $('#recType').val() },
                success:function(res){
                    $('#poNumber').html(res);
                    $('#poNumber').val(poNumber).trigger('change');
                    dariEdit = 'false';
                },
                error: function(){ Swal.fire("Warning","Get list PO failed","warning"); }
            });
        }
    }

    /* =====================================================================
       DOCUMENT READY
       ===================================================================== */
    $(document).ready(function(){
        validateFormToast("frmAdd");
        $('#statusText').text("{{ $statusRec }}");
        updateRecTypeLabels();
        initPoDropdown();

        let detailData = {!! $detail !!};
        detailData.forEach(function(row){
            add_new_row_edit(row);
        });
        hitungGrandTotal();

        if ("{{ $statusRec }}" !== 'REVISI'){
            lockFormForReadOnly();
        }
    });

    let invDateEl = $('#invDate');
    if (invDateEl.length) invDateEl.flatpickr({ dateFormat:"d-m-Y", maxDate:"today" });

    let doDateEl = $('#doDate');
    if (doDateEl.length) doDateEl.flatpickr({ dateFormat:"d-m-Y", maxDate:"today", minDate: lockedAt });

    let recDateEl = $('#recDate');
    if (recDateEl.length) recDateEl.flatpickr({ dateFormat:"d-m-Y", maxDate:"today", minDate: lockedAt });

    function reloadPage(){ window.location.reload(); }
    $("#cmdNew").click(function(){ reloadPage(); });

    $('#cmdApprove').click(function(){
        let recNumber = $('#recNumber').val();
        approve(recNumber, 'cmdApprove');
    });

    $('#cmdPrint').click(function(){
        let url = "{{ route('receiving.print', ['id' => $header->id]) }}";
        window.open(url, '_blank');
    });

    /* =====================================================================
       UPDATE
       ===================================================================== */
    $("#cmdUpdate").click(function(){
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
            return;
        }

        $("#cmdUpdate").attr('disabled','disabled');
        $('.disabled-el').removeAttr('disabled');

        let objQty     = $('input[name="qty_rec[]"]');
        let objUom     = $('select[name="uom[]"]');
        let objQtyFree = $('input[name="qty_free[]"]');
        let objUomFree = $('select[name="uomFree[]"]');
        let objQtyPo   = $('input[name="qty_po[]"]');

        let articles = [], flag = 0, pesan = "";

        $("#article_row [name='article_id[]']").map(function(i){
            let $this = $(this);
            if (!$this.val()) return;

            let articleCode  = $this.data("code");
            let articleUom   = $this.data("uom");
            let articlePrice = $this.data("price");
            articlePrice = (articlePrice === '' || articlePrice == null) ? 0 : articlePrice;
            let prNumber     = $this.data("prnumber");

            let qty        = objQty.eq(i).val().replace(/,/gi,'') || 0;
            let qtyUom     = objUom.eq(i).val() || articleUom;
            let qtyFree    = objQtyFree.eq(i).val().replace(/,/gi,'') || 0;
            let qtyFreeUom = objUomFree.eq(i).val() || qtyUom;
            let qtyPoRaw   = objQtyPo.eq(i).val().replace(/,/gi,'');
            let qtyPo      = qtyPoRaw || 0;

            let convTo         = objQty.eq(i).attr('data-conv-to') || qtyUom;
            let convFactor     = parseFloat(objQty.eq(i).attr('data-conv-factor'))     || 1;
            let convFactorFree = parseFloat(objQtyFree.eq(i).attr('data-conv-factor')) || 1;

            if (qtyPoRaw !== '' && parseFloat(qty) > parseFloat(qtyPo) && parseFloat(qty) != 0){
                pesan += `Article: ${articleCode} QTY Rec > QTY PO <br>`;
                flag = 1;
            }

            articles.push({
                article_code:     articleCode,
                qty:              qty,
                uom:              qtyUom,
                qty_free:         qtyFree,
                uom_free:         qtyFreeUom,
                price:            articlePrice,
                pr_number:        prNumber,
                conv_to:          convTo,
                conv_factor:      convFactor,
                conv_factor_free: convFactorFree,
            });
        });

        if (articles.length === 0){ pesan += "Articles must be filled in completely <br>"; flag = 1; }
        if ($("#grandTotalQty").val() == 0){ pesan += "Total Qty cannot be 0 <br>"; flag = 1; }

        if (flag !== 0){
            $('#cmdUpdate').removeAttr('disabled');
            Swal.fire('Warning..', pesan, 'warning');
            return;
        }

        $.ajax({
            type:"post",
            url:"{{ route('receiving.update') }}",
            data:{
                recNumber: $('#recNumber').val(),
                idRec:     $('#idRec').val(),
                invNumber: $('#invNumber').val() || 0,
                invDate:   $('#invDate').val(),
                doNumber:  $('#doNumber').val(),
                doDate:    $('#doDate').val(),
                poNumber:  $('#poNumber').val(),
                supp:      $('#supplier').val(),
                recDate:   $('#recDate').val(),
                recType:   $('#recType').val(),
                note:      $('#note').val(),
                articles:  JSON.stringify(articles),
            },
            dataType:"json",
            success:function(data){
                if (data.status == 0){
                    data.message.forEach(m => show_msg(data.title, m, data.alert));
                    $('#cmdUpdate').removeAttr('disabled');
                } else {
                    show_msg(data.title, data.message, data.alert);
                    $('#statusText').text(data.statusRec);
                    setTimeout(reloadPage, 1000);
                }
            },
            error: function(e){ console.log(e); $('#cmdUpdate').removeAttr('disabled'); }
        });
    });

    /* =====================================================================
       ISI BARIS ARTIKEL DARI DATA TERSIMPAN ($detail)
       ===================================================================== */
    function add_new_row_edit(row){
        let article     = row.article_code;
        let articleCode = row.article_alternative_code;
        let articleDesc = row.article_desc;
        let qtyPo       = row.qty_po;
        let uom         = row.uom;
        let price       = row.price;
        let qtyRec      = row.qty;
        let qtyFree     = row.qty_free;
        let prNumber    = row.pr_number == null ? '' : row.pr_number;
        let unitFrom    = row.unit_from || uom;
        let unitTo      = row.unit_to;
        let unitFactor  = parseFloat(row.unit_factor) || 1;
        let uomRec      = row.uom_rec  || uom  || unitFrom;
        let uomFree     = row.uom_free || uomRec;

        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        let n = cloneCount;

        $("#article_row").find('#baru').attr('id','new_row'+n);

        $("#new_row"+n).find('#article_id').attr('id','article_id'+n);
        $('#article_id'+n)
            .attr('data-code',     article)
            .attr('data-uom',      uom)
            .attr('data-price',    price)
            .attr('data-prnumber', prNumber)
            .val(articleCode + " - " + articleDesc);

        $("#new_row"+n).find('#qty_po').attr('id','qty_po'+n);
        $('#qty_po'+n).val(qtyPo * 1);

        $("#new_row"+n).find('#qty_rec').attr('id','qty_rec'+n);
        $('#qty_rec'+n).val(qtyRec);
        $('#qty_rec'+n).on('keyup input', function(){ hitungKonversi(this); });

        $("#new_row"+n).find('#qty_free').attr('id','qty_free'+n);
        $('#qty_free'+n).val(qtyFree);
        $('#qty_free'+n).on('keyup input', function(){ hitungKonversi(this); });

        $("#new_row"+n).find('#uom').attr('id','uom'+n);
        $("#new_row"+n).find('#uomFree').attr('id','uomFree'+n);
        let _opts = buildUomOptions(unitFrom, unitTo);
        $('#uom'+n).html(_opts).val(uomRec);
        $('#uomFree'+n).html(_opts).val(uomFree);

        $('#qty_rec'+n)
            .attr('data-unit-from',   unitFrom || '')
            .attr('data-unit-to',     unitTo   || '')
            .attr('data-unit-factor', unitFactor);

        applyRowConversion(n);

        tombolPanah('qty_rec');
        tombolPanah('qty_free');
        mask_thousand_digit(2);
        hitungTotal();
        hitungKonversi($('#qty_rec'+n));

        let _qr = parseFloat(($('#qty_rec'+n).val()  || '0').replace(/,/gi,'')) || 0;
        let _qf = parseFloat(($('#qty_free'+n).val() || '0').replace(/,/gi,'')) || 0;
        $('#new_row'+n).find('span[name="totalQty[]"]')
            .text((_qr+_qf).toLocaleString(undefined,{ maximumFractionDigits: numberOfDecimalDigit }));
    }

    /* =====================================================================
       TAMBAH BARIS DARI PO / PR (dipakai saat PO/PR diganti setelah unlock)
       ===================================================================== */
    function add_new_row(article, articleCode, articleDesc, qtyPo, uom, price, qtyRec, prNumber, convTo, convFactor){
        prNumber = prNumber == null ? '' : prNumber;
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        let n = cloneCount;

        $("#article_row").find('#baru').attr('id','new_row'+n);

        $("#new_row"+n).find('#article_id').attr('id','article_id'+n);
        $('#article_id'+n)
            .attr('data-code',     article)
            .attr('data-uom',      uom)
            .attr('data-price',    price)
            .attr('data-prnumber', prNumber)
            .val(articleCode + " - " + articleDesc);

        $("#new_row"+n).find('#qty_po').attr('id','qty_po'+n);
        $('#qty_po'+n).val(qtyPo * 1);

        $("#new_row"+n).find('#qty_rec').attr('id','qty_rec'+n);
        $('#qty_rec'+n).val(qtyRec);
        $('#qty_rec'+n).on('keyup input', function(){ hitungKonversi(this); });

        $("#new_row"+n).find('#qty_free').attr('id','qty_free'+n);
        $('#qty_free'+n).val('');
        $('#qty_free'+n).on('keyup input', function(){ hitungKonversi(this); });

        $("#new_row"+n).find('#uom').attr('id','uom'+n);
        $("#new_row"+n).find('#uomFree').attr('id','uomFree'+n);
        let _opts = buildUomOptions(uom, convTo);
        $('#uom'+n).html(_opts);
        $('#uomFree'+n).html(_opts);

        $('#qty_rec'+n)
            .attr('data-unit-from',   uom    || '')
            .attr('data-unit-to',     convTo || '')
            .attr('data-unit-factor', (convFactor != null && convFactor !== '') ? convFactor : 1);

        applyRowConversion(n);

        if (qtyRec === 0){
            $('#qty_rec'+n).attr('disabled','disabled');
            $('#qty_free'+n).attr('disabled','disabled');
        }

        tombolPanah('qty_rec');
        tombolPanah('qty_free');
        mask_thousand_digit(2);
        hitungTotal();
        hitungKonversi($('#qty_rec'+n));

        let _qr = parseFloat(($('#qty_rec'+n).val()  || '0').replace(/,/gi,'')) || 0;
        let _qf = parseFloat(($('#qty_free'+n).val() || '0').replace(/,/gi,'')) || 0;
        $('#new_row'+n).find('span[name="totalQty[]"]')
            .text((_qr+_qf).toLocaleString(undefined,{ maximumFractionDigits: numberOfDecimalDigit }));
    }

    /* =====================================================================
       TAMBAH BARIS MANUAL (Non Purchase — tanpa PR)
       ===================================================================== */
    $('#cmdAddRow').on('click', function(){
        if (!$('#supplier').val()){
            Swal.fire('Info','Pilih Supplier dulu agar konversi UOM sesuai.','info');
            return;
        }
        add_manual_row();
    });

    function add_manual_row(){
        $("#article_row").append($("#new_row_manual").clone().html());
        cloneCount++;
        let idx = cloneCount;

        $("#article_row").find('#baru_manual').attr('id','new_row'+idx);
        $("#new_row"+idx).find('#article_id').attr('id','article_id'+idx);
        $("#new_row"+idx).find('#qty_po').attr('id','qty_po'+idx).val('');
        $("#new_row"+idx).find('#qty_rec').attr('id','qty_rec'+idx);
        $("#new_row"+idx).find('#qty_free').attr('id','qty_free'+idx);
        $("#new_row"+idx).find('#uom').attr('id','uom'+idx);
        $("#new_row"+idx).find('#uomFree').attr('id','uomFree'+idx);

        let $art = $('#article_id'+idx);
        $art.select2({
            placeholder: 'Cari article...',
            width: '100%',
            ajax:{
                url: "{{ route('receiving.list.article') }}",
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term, supp: $('#supplier').val() }),
                processResults: data => ({ results: data })
            }
        });

        $art.on('select2:select', function(e){
            let d     = e.params.data;
            let price = (d.price === '' || d.price == null) ? 0 : d.price;
            $(this).data('code',d.code).attr('data-code',d.code)
                   .data('uom',d.uom).attr('data-uom',d.uom)
                   .data('price',price).attr('data-price',price)
                   .data('prnumber','').attr('data-prnumber','');
            $('#qty_rec'+idx).removeAttr('disabled');
            $('#qty_free'+idx).removeAttr('disabled');
            loadUomConv(idx, d.code, $('#supplier').val(), d.uom);
        });

        $('#qty_rec'+idx).on('keyup input', function(){ hitungKonversi(this); });
        $('#qty_free'+idx).on('keyup input', function(){ hitungKonversi(this); });

        $("#new_row"+idx).find('.btn-remove-row').on('click', function(){
            $("#new_row"+idx).remove();
            hitungGrandTotal();
        });

        tombolPanah('qty_rec');
        tombolPanah('qty_free');
        mask_thousand_digit(2);
        hitungTotal();
        hitungGrandTotal();
    }

    /* =====================================================================
       DELEGASI UOM CHANGE
       ===================================================================== */
    $('#article_row').on('change','select[name="uom[]"]', function(){
        applyRowConversion($(this).attr('id').replace('uom',''));
    });
    $('#article_row').on('change','select[name="uomFree[]"]', function(){
        applyRowConversion($(this).attr('id').replace('uomFree',''));
    });

    /* =====================================================================
       HITUNG TOTAL & GRAND TOTAL
       ===================================================================== */
    function hitungTotal(){
        let objQtyRec  = $('#article_row input[name="qty_rec[]"]');
        let objQtyFree = $('#article_row input[name="qty_free[]"]');
        let objTotal   = $('#article_row span[name="totalQty[]"]');
        let objQtyPo   = $('#article_row input[name="qty_po[]"]');

        objQtyRec.off('keyup.ht').on('keyup.ht', function(){
            let i       = objQtyRec.index(this);
            let qty     = parseFloat(objQtyRec.eq(i).val().replace(/,/gi,'') || 0);
            let qtyFree = parseFloat(objQtyFree.eq(i).val().replace(/,/gi,'') || 0);
            objTotal.eq(i).text((qty+qtyFree).toLocaleString(undefined,{maximumFractionDigits:numberOfDecimalDigit}));

            let poRaw = objQtyPo.eq(i).val();
            let hasPo = poRaw !== undefined && poRaw !== '';
            let qtyPo = parseFloat((poRaw||'0').replace(/,/gi,'')) || 0;
            objQtyRec.eq(i).css("background-color", (hasPo && qty > qtyPo) ? "rgba(255,0,0,0.5)" : "");

            hitungGrandTotal();
        });

        objQtyFree.off('keyup.ht').on('keyup.ht', function(){
            let i       = objQtyRec.index(this);
            let qty     = parseFloat(objQtyRec.eq(i).val().replace(/,/gi,'') || 0);
            let qtyFree = parseFloat(objQtyFree.eq(i).val().replace(/,/gi,'') || 0);
            objTotal.eq(i).text((qty+qtyFree).toLocaleString(undefined,{maximumFractionDigits:numberOfDecimalDigit}));
            hitungGrandTotal();
        });
    }

    function hitungGrandTotal(){
        let objArticle = $('#article_row input[name="article_id[]"]');
        let objQtyRec  = $('#article_row input[name="qty_rec[]"]');
        let objQtyFree = $('#article_row input[name="qty_free[]"]');

        let totalQty = 0, totalQtyFree = 0, convQty = 0, convQtyFree = 0, convUnit = '';

        objQtyRec.each(function(i){
            let qty     = parseFloat(objQtyRec.eq(i).val().replace(/,/gi,''))  || 0;
            let qtyFree = parseFloat(objQtyFree.eq(i).val().replace(/,/gi,'')) || 0;
            totalQty     += qty;
            totalQtyFree += qtyFree;

            let facRec  = parseFloat(objQtyRec.eq(i).attr('data-conv-factor'))  || 1;
            let facFree = parseFloat(objQtyFree.eq(i).attr('data-conv-factor')) || 1;
            let to      = objQtyRec.eq(i).attr('data-conv-to') || objQtyRec.eq(i).attr('data-conv-from');
            if (to) convUnit = to;

            convQty     += qty * facRec;
            convQtyFree += qtyFree * facFree;
        });

        let fmt = n => n.toLocaleString(undefined,{maximumFractionDigits:numberOfDecimalDigit});
        $("#totalRow").val(objArticle.length);
        $("#totalQTY").val(fmt(totalQty));
        $("#totalQtyFree").val(fmt(totalQtyFree));
        $("#grandTotalQty").val(fmt(totalQty + totalQtyFree));
        $("#convQTY").val(fmt(convQty));
        $("#convQtyFree").val(fmt(convQtyFree));
        $("#convGrandTotalQty").val(fmt(convQty + convQtyFree));
    }

    /* =====================================================================
       CSRF
       ===================================================================== */
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

</script>
@endsection