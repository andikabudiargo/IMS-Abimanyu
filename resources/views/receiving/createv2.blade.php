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
                            <input type="hidden" id="idRec" name="idRec" class="form-control" />
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
                                    <input type="text" id="recNumber" name="recNumber" class="form-control text-hitam disabled-el"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="recDate">Receiving Date*</label>
                                    <input type="text" id="recDate" name="recDate" class="form-control" placeholder="DD-MM-YYYY" required/>
                                </div>                               
                            <div class="form-group col-md-3">
                                    <label class="form-label" for="recType">Receive Type</label>
                                    <select class="select2 form-control" id="recType" name="recType" required>
                                        <option value="NORMAL">Purchase Order</option>
                                        <option value="NP">Non Purchase</option>
                                        <option value="JASA">Jasa</option>
                                    </select>
                                </div>
                                 </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="supplier">Supplier*</label>
                                    <select class="select2 form-control" id="supplier" name="supplier" required>
                                        <option value=""></option>
                                        @foreach($supps as $val)
                                            <option value="{{$val->kode}}" >{{$val->kode}} - {{$val->nama}}</option>
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
                                    <input type="text" id="doDate" name="doDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>                               
                                <div class="form-group col-md-4">
                                    <label for="doNumber">DO Number*</label>
                                    <input type="text" id="doNumber" name="doNumber" class="form-control disabled-el" required/>
                                </div>
                                <div class="form-group col-md-2 d-none">
                                    <label for="invDate">Invoice Date*</label>
                                    <input type="text" id="invDate" name="invDate" class="form-control" placeholder="DD-MM-YYYY" />
                                </div>                               
                                <div class="form-group col-md-3 d-none">
                                    <label for="invNumber">Invoice Number*</label>
                                    <input type="text" id="invNumber" name="invNumber" class="form-control disabled-el" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" ></textarea>
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
                <div class="card-body" >
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('receiving.headerColumnv2') 
                            <input type="text" id ="last_row_number" class="d-none" value="0">
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4 ">
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
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                            <button class="btn btn-dark" type="button" id="cmdPrint" name="cmdPrint">Print</button>
                        </div>
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
    // currentDate: format DD-MM-YYYY, dipakai untuk default value recDate & doDate
    let currentDate = (function(){
        let d = new Date();
        let dd = String(d.getDate()).padStart(2, '0');
        let mm = String(d.getMonth() + 1).padStart(2, '0');
        let yyyy = d.getFullYear();
        return dd + '-' + mm + '-' + yyyy;
    })();

    /* =====================================================================
       BAGIAN INI SEBELUMNYA ADA DI addArticlev2.blade.php
       Dipindah ke sini supaya dijamin dieksekusi SETELAH jQuery ter-load
       (addArticlev2 di-include di bagian section content, yang
       dirender SEBELUM tag script src jquery di layout, sehingga
       dollar-sign is not defined saat script lama itu langsung jalan di sana)
       ===================================================================== */

    $('#recType').change(function(){
        let isPr = $(this).val() === 'NP';

        // reset supplier (select2) — pakai change.select2 supaya tidak ikut memanggil searchPo/searchPr
        $('#supplier').val(null).trigger('change.select2');

        // reset dropdown PO/PR (select2): kosongkan opsi + sisakan 1 opsi kosong, lalu refresh
        $('#poNumber').empty().append('<option value=""></option>').val('').trigger('change.select2');

        // reset tabel artikel & total
        $('#article_row').empty(); cloneCount = 0; hitungGrandTotal();

        // label ikut berubah
        $('label[for="poNumber"]').text(isPr ? 'PR Number*' : 'PO Number*');
        $('#lblQtyRef').text(isPr ? 'QTY PR' : 'QTY PO');
    });

    function searchPo(obj,value) {
        $.ajax({
            url:"{{ route('receiving.list.pov2') }}",
            method:"GET",
            data:{
                value: value,
                recType: $('#recType').val()
            },
            success:function(result){
                $('#'+obj).html(result);
            },
            error: function (response) {
                Swal.fire("Warning","Get list PO failed","warning");
            }
        })
    }

    function searchPoDet(value,dariEdit) {
        if(dariEdit=='false'){
            $.ajax({
                url:"{{ route('receiving.po.det2') }}",
                method:"GET",
                data:{
                    value:value,
                },
                success:function(result){
                    $("#article_row").empty();
                    cloneCount=1;
                    console.log(result.map(r => ({code:r.article_code, conv_to:r.conv_to, conv_factor:r.conv_factor})));
                    if (result.length > 0) {
                        for (let i = 0; i < result.length; i++) {
                            let article     = result[i].article_code;
                            let articleCode = result[i].article_alternative_code;
                            let articleDesc = result[i].article_desc;
                            let qtyPo       = result[i].qty_order;
                            let qty         = qtyPo <= 0 ? 0 : '';
                            let uom         = result[i].uom;
                            let price       = result[i].price;
                            let poPrNumber  = result[i].pr_number;
                            let convTo      = result[i].conv_to;
                            let convFactor  = result[i].conv_factor;
                            add_new_row(article, articleCode, articleDesc, qtyPo, uom, price, qty, poPrNumber, convTo, convFactor);
                        }
                    }
                },
                error: function (response) {
                    Swal.fire("Warning","Get detail PO failed","warning");
                }
            })
        }else{
            dariEdit='false';
        }
    }

    function searchPr(obj, value){
        if (!value){ $('#'+obj).empty(); return; }
        $.ajax({
            type:'post',
            url:"{{ route('receiving.list.pr') }}",
            data:{ value:value },
            success:function(res){ $('#'+obj).html(res).trigger('change'); },
            error: function (response) {
                Swal.fire("Warning","Get list PR failed","warning");
            }
        });
    }

    function searchPrDet(prNumber){
        $('#article_row').empty(); cloneCount = 0;
        if (!prNumber || prNumber === 'Choose PR'){ hitungGrandTotal(); return; }
        $.ajax({
            type:'post',
            url:"{{ route('receiving.pr.det') }}",
            data:{ value: prNumber, supp: $('#supplier').val() },
            dataType:'json',
            success:function(data){
                data.forEach(function(it){
                    add_new_row(
                        it.article_code,
                        it.article_alternative_code,
                        it.article_desc,
                        it.qty_order,
                        it.uom,
                        it.price,
                        it.qty_order,
                        it.pr_number,
                        it.conv_to,
                        it.conv_factor
                    );
                });
                hitungGrandTotal();
            },
            error: function (response) {
                Swal.fire("Warning","Get detail PR failed","warning");
            }
        });
    }

    function listUom(obj,obj2,value,uom) {
        $.ajax({
            url:"{{ route('receiving.list.uom') }}",
            method:"GET",
            data:{
                value:value,
            },
            success:function(result){
                $('#'+obj).html(result);
                $('#'+obj2).html(result);
                $('#'+obj).select2();
                $('#'+obj2).select2();
                $('#'+obj).val(uom).trigger('change');
                $('#'+obj2).val(uom).trigger('change');
            },
            error: function (response) {
                Swal.fire("Warning","Get list UOM failed","warning");
            }
        })
    }

    function uomChange(){
        let objUom= $('#article_row select[name="uom[]"]');
        let objUomFree= $('#article_row select[name="uomFree[]"]');
        let objQty= $('#article_row input[name="qty_rec[]"]');
        let objQtyFree= $('#article_row input[name="qty_free[]"]');

        objUom.change(function(e){
            mask_thousand_digit(numberOfDecimalDigit);
        });

        objUomFree.change(function(e){
            mask_thousand_digit(numberOfDecimalDigit);
        });
    }

    approve = (recNumber,objButton) => {
        $('#'+objButton).attr('disabled','disabled');
        $.ajax({
            type: "POST",
            url: "{{ route('receiving.approve') }}",
            data: {
                recNumber:recNumber
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#recNumber').attr('disabled','disabled');
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#recNumber').attr('disabled','disabled');
                    $('#cmdApprove').attr('disabled','disabled');
                    window.location.reload();
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
    }

    /* ===================================================================== 
       BAGIAN ASLI createv2.blade.php (tidak diubah logicnya)
       ===================================================================== */

    dariEdit = 'false';
    let lockedAt = "{{ $lockDate }}";
    $(document).ready(function(){
        validateFormToast("frmAdd");
        $("#totalRow").val(0);
        $("#totalQTY").val(humanizeNumber(0));
        $("#totalQtyFree").val(humanizeNumber(0));
        $("#grandTotalQty").val(humanizeNumber(0));
        $('#statusText').text('New');
        $('#recDate').val(currentDate);
        $('#doDate').val(currentDate);
        $('#cmdSave').show();
        $('#cmdPrint').hide();
    });

    invDate = $('#invDate');
    if (invDate.length) {
        invDate.flatpickr({
            dateFormat: "d-m-Y",
            maxDate: "today"
        });
    }

    doDate = $('#doDate');
    if (doDate.length) {
        doDate.flatpickr({
            dateFormat: "d-m-Y",
            maxDate: "today",
            minDate:lockedAt
        });
    }

    recDate = $('#recDate');
    if (recDate.length) {
        recDate.flatpickr({
            dateFormat: "d-m-Y",
            maxDate: "today",
            minDate:lockedAt
        });
    }
    
    function reloadPage(){
        window.location.reload();
    }

    $("#cmdNew").click(function(){
        reloadPage();
    });

    $("#cmdSave").click(function(){
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{
            $("#cmdSave").attr('disabled','disabled');
            $('.disabled-el').removeAttr('disabled');
            let objQty= $('input[name="qty_rec[]"]');
            let objUom= $('select[name="uom[]"]');
            let objQtyFree= $('input[name="qty_free[]"]');
            let objUomFree= $('select[name="uomFree[]"]');
            let objQtyPo= $('input[name="qty_po[]"]');
            
            let articles = []; 
            let flag=0; 
            let pesan="";

            $("#article_row input[name='article_id[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let articleCode = $this.data("code");
                    let articleUom = $this.data("uom");
                    let articlePrice = $this.data("price");
                    let prNumber = $this.data("prnumber");
                    let article=$this.val().split("|");
                    let plu=article[0];
                    let articleName=article[1];
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let qtyUom=objUom.eq(i).val() || articleUom;
                    let qtyFree=objQtyFree.eq(i).val().replace(/,/gi, '') || 0;
                    let qtyFreeUom=objUom.eq(i).val() || articleUom;
                    let qtyPo=objQtyPo.eq(i).val().replace(/,/gi, '') || 0;
                    let convTo     = objQty.eq(i).attr('data-conv-to') || qtyUom;
                    let convFactor = parseFloat(objQty.eq(i).attr('data-conv-factor')) || 1;

                    if ( (parseFloat(qty) > parseFloat(qtyPo)) && (parseFloat(qty) != 0)  ){
                        pesan +=`Articles : ${article} QTY Rec > QTY PO <br>`; 
                        flag=1;
                    }

                   articles.push({
                    "article_code":articleCode,
                    "qty":qty,
                    "uom":qtyUom,
                    "qty_free":qtyFree,
                    "uom_free":qtyFreeUom,
                    "price":articlePrice,
                    "pr_number":prNumber,
                    "conv_to":convTo,
                    "conv_factor":convFactor,
                    });
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if ( $("#grandTotalQty").val() == 0 ){
                pesan +="Total Qty cannot be 0 <br>"; 
                flag=1;
            }

            if (flag==0){
                let invNumber = $('#invNumber').val()||0;
                let invDate = $('#invDate').val();
                let doNumber = $('#doNumber').val();
                let doDate = $('#doDate').val();
                let poNumber = $('#poNumber').val();
                let supp = $('#supplier').val();
                let recDate = $('#recDate').val();
                let note = $('#note').val();
            
                $.ajax({
                    type: "post",
                    url: "{{ route('receiving.store') }}",
                    data: {
                        invNumber:invNumber,
                        invDate:invDate,
                        doNumber:doNumber,
                        doDate:doDate,
                        poNumber:poNumber,
                        supp:supp,
                        recDate:recDate,
                        recType:$('#recType').val(),
                        note:note,
                        articles:JSON.stringify(articles)
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#recNumber').attr('disabled','disabled');
                            $('#cmdSave').removeAttr('disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#statusText').text(data.statusRec);
                            $('#recNumber').val(data.recNumber);
                            $('#cmdSave').hide();
                            $('#cmdCancel').hide();
                            $('#recNumber').attr('disabled','disabled');
                            $('#cmdSave').attr('disabled','disabled');
                            $('#supplier').attr('disabled','disabled');
                            $('#poNumber').attr('disabled','disabled');
                            $('#invDate').attr('disabled','disabled');
                            $('#recDate').attr('disabled','disabled');
                            $('#invNumber').attr('disabled','disabled');

                            $('#statusText').val('NEW');
                            $('#idRec').val(data.idKu);

                            $('#cmdSave').hide();
                            $('#cmdPrint').show();
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });
            }else{
                $('#cmdSave').removeAttr('disabled');
                $('#cmdPrint').hide();
                Swal.fire('Warning..',pesan,'warning');
            }
        }
    });
  
    $('#supplier').change(function(){
        let value = $(this).val();
        if ($('#recType').val() === 'NP'){
            searchPr('poNumber', value);
        } else {
            searchPo('poNumber', value);
        }
    });

    let cloneCount=0;
    function add_new_row(article, articleCode, articleDesc, qtyPo, uom, price, qtyRec, prNumber, convTo, convFactor) {
        prNumber = prNumber == null ? '' : prNumber;
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;

        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);

        $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
        $('#article_id'+ cloneCount).attr('data-code', article);
        $('#article_id'+ cloneCount).attr('data-uom', uom);
        $('#article_id'+ cloneCount).attr('data-price', price);
        $('#article_id'+ cloneCount).attr('data-prnumber', prNumber);
        $('#article_id'+ cloneCount).val(articleCode + " - " + articleDesc);

        $("#new_row"+ cloneCount).find('#qty_po').attr('id', 'qty_po'+ cloneCount);
        $('#qty_po'+ cloneCount).val(qtyPo*1);

        $("#new_row"+ cloneCount).find('#qty_rec').attr('id', 'qty_rec'+ cloneCount);
        $('#qty_rec'+ cloneCount).val(qtyRec)
            .attr('data-conv-from', uom)
            .attr('data-conv-to', convTo || '')
            .attr('data-conv-factor', (convFactor != null ? convFactor : ''));

        $('#qty_rec'+ cloneCount).on('keyup input', function(){
            hitungKonversi(this);
        });

        $("#new_row"+ cloneCount).find('#qty_free').attr('id', 'qty_free'+ cloneCount);
        $('#qty_free'+ cloneCount).val(''); 
        $('#qty_free'+ cloneCount).on('keyup input', function(){
            hitungKonversi(this);
        });

        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        $("#new_row"+ cloneCount).find('#uomFree').attr('id', 'uomFree'+ cloneCount);
        $('#uom'+ cloneCount).html('<option value="'+uom+'" selected>'+uom+'</option>');
        $('#uomFree'+ cloneCount).html('<option value="'+uom+'" selected>'+uom+'</option>');

        qtyRec === 0 ? $('#qty_rec'+ cloneCount).attr('disabled','disabled') : '';
        qtyRec === 0 ? $('#qty_free'+ cloneCount).attr('disabled','disabled') : '';

        tombolPanah('qty_rec');
        tombolPanah('qty_free');
        mask_thousand_digit(2);
        hitungTotal();
        hitungKonversi($('#qty_rec'+ cloneCount));

        let _qr = parseFloat(($('#qty_rec'+ cloneCount).val()  || '0').replace(/,/gi,'')) || 0;
        let _qf = parseFloat(($('#qty_free'+ cloneCount).val() || '0').replace(/,/gi,'')) || 0;
        $('#new_row'+ cloneCount)
            .find('span[name="totalQty[]"]')
            .text((_qr + _qf).toLocaleString(undefined, { maximumFractionDigits: numberOfDecimalDigit }));
    }

    $('#poNumber').change(function(){
        let value = $(this).val();
        if ($('#recType').val() === 'NP'){
            searchPrDet(value);
        } else {
            searchPoDet(value, 'false');
        }
    });
    
    function hitungTotal(){
        let objQtyRec= $('#article_row input[name="qty_rec[]"]');
        let objQtyFree= $('#article_row input[name="qty_free[]"]');
        let objTotalQty= $('#article_row span[name="totalQty[]"]');
        let objQtyPo= $('#article_row input[name="qty_po[]"]');
        
        objQtyRec.keyup(function() {
            let indexnya= objQtyRec.index(this);
            let qtyRec = parseFloat(objQtyRec.eq(indexnya).val().replace(/,/gi, '') || 0); 
            let qtyFree = parseFloat(objQtyFree.eq(indexnya).val().replace(/,/gi, '') || 0); 
            let totalQty = qtyRec+qtyFree;
            let qtyPo = parseFloat(objQtyPo.eq(indexnya).val().replace(/,/gi, '') || 0); 
            let uomGroup = objQtyRec.eq(indexnya).data('uom-group');
            if ( qtyRec > qtyPo ){
                objQtyRec.eq(indexnya).delay(3000).css("background-color","rgba(255,0,0, 0.5)");
            }else{
                objQtyRec.eq(indexnya).delay(3000).css("background-color","");
            }
            objTotalQty.eq(indexnya).text(totalQty.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit})); 
            hitungGrandTotal();
        });    

        objQtyFree.keyup(function() {
            let indexnya= objQtyRec.index(this);
            let qtyRec = parseFloat(objQtyRec.eq(indexnya).val().replace(/,/gi, '') || 0); 
            let qtyFree = parseFloat(objQtyFree.eq(indexnya).val().replace(/,/gi, '') || 0); 
            let totalQty = qtyRec+qtyFree;
            let uomGroup = objQtyFree.eq(indexnya).data('uom-group');
            objTotalQty.eq(indexnya).text(totalQty.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit}));
            hitungGrandTotal();
        }); 
    }

    function hitungKonversi(elemen){
        let $row  = $(elemen).closest('[id^="new_row"]');
        let $qRec = $row.find('input[name="qty_rec[]"]');

        let factor = parseFloat($qRec.data('conv-factor'));
        let from   = $qRec.data('conv-from');
        let to     = $qRec.data('conv-to') || from;

        if (!factor || isNaN(factor)) {
            factor = 1;
            to = from;
        }

        let qtyRec  = parseFloat(($row.find('input[name="qty_rec[]"]').val()  || '0').replace(/,/gi,'')) || 0;
        let qtyFree = parseFloat(($row.find('input[name="qty_free[]"]').val() || '0').replace(/,/gi,'')) || 0;
        let total   = qtyRec + qtyFree;
        let hasil   = total * factor;

        $row.find('.conv-info').text(
            hasil.toLocaleString(undefined, { maximumFractionDigits: numberOfDecimalDigit })
            + (to ? ' ' + to : '')
        );

        hitungGrandTotal();
    }

    function hitungGrandTotal(){
        let objArticle = $('#article_row input[name="article_id[]"]');
        let objQtyRec  = $('#article_row input[name="qty_rec[]"]');
        let objQtyFree = $('#article_row input[name="qty_free[]"]');

        let totalQty = 0, totalQtyFree = 0;
        let convQty  = 0, convQtyFree  = 0;
        let convUnit = '';

        objQtyRec.each(function(i){
            let qty     = parseFloat(objQtyRec.eq(i).val().replace(/,/gi,''))  || 0;
            let qtyFree = parseFloat(objQtyFree.eq(i).val().replace(/,/gi,'')) || 0;
            totalQty     += qty;
            totalQtyFree += qtyFree;

            let factor = parseFloat(objQtyRec.eq(i).data('conv-factor'));
            let to     = objQtyRec.eq(i).data('conv-to') || objQtyRec.eq(i).data('conv-from');
            if (!factor || isNaN(factor)) {
                factor = 1;
            } else if (to) {
                convUnit = to;
            }

            convQty     += qty * factor;
            convQtyFree += qtyFree * factor;
        });

        let grandTotalQty = totalQty + totalQtyFree;
        let convGrand     = convQty + convQtyFree;
        let suffix        = convUnit ? ' ' + convUnit : '';
        let fmt = (n) => n.toLocaleString(undefined, { maximumFractionDigits: numberOfDecimalDigit });

        $("#totalRow").val(objArticle.length);
        $("#totalQTY").val(fmt(totalQty));
        $("#totalQtyFree").val(fmt(totalQtyFree));
        $("#grandTotalQty").val(fmt(grandTotalQty));

        $("#convQTY").val(fmt(convQty));
        $("#convQtyFree").val(fmt(convQtyFree));
        $("#convGrandTotalQty").val(fmt(convGrand));
    }

    $("#cmdPrint").click(function(){
        $("#cmdPrint").prop('disabled',true);
        $('#cmdSave').hide();
        $('#cmdPrint').hide();
        let objQty= $('input[name="qty_rec[]"]');
        let objUom= $('select[name="uom[]"]');
        let objQtyFree= $('input[name="qty_free[]"]');
        let objUomFree= $('select[name="uomFree[]"]');
        let objQtyPo= $('input[name="qty_po[]"]');    
        let recNumber = $('#recNumber').val();   
        let idRec = $('#idRec').val();  
        let dariNew = 'true';     
        $.ajax({
            type: "post",
            url: "{{ route('receiving.posting2') }}",
            data: {
                recNumber:recNumber,
                id:idRec,
                dariNew:dariNew
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#recNumber').attr('disabled','disabled');
                    $('#cmdSave').show();
                    $('#cmdPrint').hide();
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#cmdSave').hide();
                    $('#statusText').text('POSTED');
                    $('#cmdPrint').hide();
                    $('#recNumber').attr('disabled','disabled');
                    $('#soNumber').attr('disabled','disabled');
                    $('#customer').attr('disabled','disabled');
                    $('#dnDate').attr('disabled','disabled');

                    $('#cmdSave').hide();
                    $('#cmdPrint').hide();

                    objQty.attr('disabled','disabled');
                    objUom.attr('disabled','disabled');
                    objQtyFree.attr('disabled','disabled');
                    objUomFree.attr('disabled','disabled');

                    let id = data.idKu;
                    let url = "{{ route('receiving.print', ['id'=>':id']) }}";
                    url = url.replace('%3Aid', id);
                    window.open(url, '_blank');
                    reloadPage();
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection