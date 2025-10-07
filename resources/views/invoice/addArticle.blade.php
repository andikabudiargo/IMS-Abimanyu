<style>
    .jarak-antar-attr{
        padding-left: 0.3rem;
        margin-bottom: 0.3rem;
        padding-right: 0.3rem;
    }

    .jarak-antar-attr-qty-order{
        padding-left: 0.3rem;
        margin-bottom: 1.8rem;
        padding-right: 0.3rem;
    }

    td.isian{
        padding-right:10px;
        padding-left:10px;
    }

    td.isian-satu{
        padding-right:5px;
        padding-left:15px;
        width: 25%;border-top: 1px solid #ffffff !important;
        border-bottom: 1px solid #ffffff !important;
        border-left: 1px solid #ffffff !important;
    }

    td.disabled{
        background-color:#f8f8f8;
        color:black;
    }

    .scrollable-box {

        max-height: 50vh; /* 50% of viewport height */
        overflow-y: auto;
        overflow-x: auto;
        /* max-height: 500px;
        overflow-y: auto;
        border: 1px solid #ccc;
        padding: 10px; */
    }

    #listOfDn {
        width: 100%;
        border-collapse: collapse;
    }
    
    #listOfDn th {
        position: sticky;
        top: 0;
        background-color: #f8f8f8;
        z-index: 10;
        padding: 8px 12px;
    }
    
    #listOfDn td {
        padding: 8px 12px;
        border: 1px solid #ddd;
    }
    
</style>
{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <table class="table-bordered" id="listData" style="width: 98%;table-layout: fixed;">
            <tbody>
                <tr>
                    <td class="isian disabled" style="width: 39%">
                        <input type="text" class="form-control-plaintext text-hitam" id = "articleId" name="articleId[]" data-code="" data-uom=""  data-price="" data-po-number="" data-so-code="" disabled>
                        <input type="hidden" class="form-control-plaintext" id = "dnNumber" name="dnNumber[]" >
                    </td>
                    <td class="isian" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask-digit text-right" id = "qtyInv" name="qtyInv[]" maxlength="9">
                    </td>
                    <td class="isian" style="width: 5%">
                        <input type="text" class="form-control-plaintext" id = "uom" name="uom[]" disabled>
                    </td>
                    <td class="isian" style="width: 8%">
                        <input type="text" class="form-control-plaintext numeral-mask-digit text-right" id = "price" name="price[]"  oninput='inputDecimal(this)' maxlength="15">
                    </td>
                    <td class="isian" style="width: 8%">
                        <input type="text" class="form-control-plaintext numeral-mask-digit text-right" id = "priceJasa" name="priceJasa[]"  oninput='inputDecimal(this)' maxlength="15">
                    </td>
                    <td class="isian disabled text-right" style="width: 10%">
                        <input type="text" class="form-control-plaintext numeral-mask-digit text-right" id = "totalLine" name="totalLine[]" disabled>
                    </td>
                    <td class="isian disabled text-right" style="width: 10%">
                        <input type="text" class="form-control-plaintext numeral-mask-digit text-right" id = "totalJasa" name="totalJasa[]" disabled>
                    </td>
                    <td class="isian disabled text-right" style="width: 10%">
                        <input type="text" class="form-control-plaintext numeral-mask-digit text-right" id = "subTotal" name="subTotal[]" disabled>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
{{-- \.table row --}} 

<script type="text/javascript">
    const customer = $('#customer');
    const soNumber = $('#soNumber');
    const dnNumber = $('#dnNumber');
    const soDate = $('#soDate');

    let sNilaiPPN= "{{ $nilaiPPN }}";
    let sNilaiPPH= "{{ $nilaiPPH }}";
    let showDetail="";
    let edit="";
    $("#ppn").val("{{ $nilaiPPN }}");
    let sNilaiPpnPembilang= "{{ $ppnPembilang }}";
    let sNilaiPpnPenyebut= "{{ $ppnPenyebut }}";

    getActivePpn = (tanggal) => {
        return $.ajax({
            async: false,
            url:"{{route('setting.lastPpn')}}",
            method:"GET",
            data:{
                tanggal:tanggal,
            },
            success:function(result){
            }
        });
    }

    let delayTimer;
    function inputDecimal(ele) {
        clearTimeout(delayTimer);
        delayTimer = setTimeout(function() {
            let nilai = ele.value.replace(/,/gi, '') || 0;;
            ele.value = humanizeNumber(parseFloat(nilai).toFixed(2)).toString();
        }, 1100); 
    }

    function searchSo(obj,value, soDate) {
        if(value && soDate){
            $.ajax({
                url:"{{ route('invoice.list.so') }}",
                method:"GET",
                data:{
                    value:value,
                    soDate:soDate
                },
                success:function(result){
                    if(result){
                        $('#'+obj).html(result);
                        soNumber.removeAttr('disabled');
                    }
                    // $('#'+obj).val('').trigger('change');
                },
                error: function (response) {
                    //Error here
                    Swal.fire("Warning","Get list SO failed","warning");
                }
            })
        }
    }

    function deleteSoNotInList() {

        let allSoIds = $('#soNumber').val();
        let allDnIds = $('#listOfDn tr').map(function() {
            return this.id.split('_')[0] || null;
        }).get().filter(Boolean);
        let distinctDnIds = [...new Set(allDnIds)];

        let invalidItems = $.map(distinctDnIds, function(item) {
            return $.inArray(item, allSoIds) === -1 ? item : null;
        }).filter(Boolean); // Remove null values

        $.map(invalidItems, function(item) {
            let classKu = item.replace(/\//g, ""); 
            $("."+classKu).remove();
        })

    }

    removeAllDn = () => {
        $('#listOfDn tbody > tr').remove();
    }

    function getNewSoforList(soNumberKu) {

        let allSoIds = soNumberKu;
        let allDnIds = $('#listOfDn tr').map(function() {
            return this.id.split('_')[0] || null;
        }).get().filter(Boolean);

        let distinctDnIds = [...new Set(allDnIds)];

        let validItems = $.map(allSoIds, function(item) {
            return $.inArray(item, distinctDnIds) === -1 ? item : null;
        }).filter(Boolean); // Remove null values

        return validItems;
        
    }

    function searchDn(soNumberKu) {
        let invNumber = $('#invNumber').val();
        let soNumber = getNewSoforList(soNumberKu);
        let statusKu = "{{ $status }}";
        if(soNumber.length > 0){
            $.ajax({
                url:"{{ route('invoice.list.dn') }}",
                method:"GET",
                data:{
                    soNumber:soNumber,
                    invNumber:invNumber,
                    edit:edit
                },
                success:function(result){
                    if(result){
                        if ( statusKu != 'PAID') {
                            $('#cmdSubmit').removeAttr('disabled');
                        }

                        $("#listOfDn tbody").append(result);
                        deleteSoNotInList();
                    }else{
                        const currentValues = $('#soNumber').val();
                        const newValues = currentValues.filter(item => item !== soNumber[0 ]); // Removes all instances of 3
                        let simpleArray = newValues.join(",").split(",");
                        $('#soNumber').val(simpleArray).trigger('change.select2',{ silent: true });
                        Swal.fire("Warning","All deliveries have been processed at SO:"+soNumber[0],"warning");
                    }
                },
                error: function (response) {
                    //Error here
                    Swal.fire("Warning","Get list DN failed","warning");
                }
            })
        }else{
            deleteSoNotInList();
            cmdSubmit();
        }
    }

    let lastSelectedCustomerValue = customer.val();
    
    customer.focus(function() {
        lastSelectedCustomerValue = $(this).val();
    });

    customer.change(function(){
        $('#accountPiutang').val("");
        $('#soNumber').empty();
        soNumber.attr('disabled','disabled');
        soDate.val("");
        let coa = $(this).find(":selected").data("coa");
        if(coa){
            $('#accountPiutang').val(coa);
            if(lastSelectedCustomerValue != $(this).val()){
                removeAllDn();
                if(soDate.val()){
                    searchSo('soNumber',$(this).val(),soDate);
                }
            }
            lastSelectedCustomerValue = $(this).val();
        }else{
            if(lastSelectedCustomerValue != $(this).val()){
                Swal.fire("Warning","Customer belum memiliki COA Piutang","warning"); 
            }else{
                removeAllDn();
            }
        }
    });
    
    soNumber.change(function(){
        if($(this).val().length > 0){
            searchDn($(this).val());
        }else{
            customer.removeAttr('disabled')
            soDate.removeAttr('disabled')
            $("#listOfDn > tbody").empty();
        }
    })

    dnNumber.change(function(){
        searchDnDet($(this).val(),soNumber.val());
    })
    
    function hitungTotal(){
        let objQtyInv= $('#article_row input[name="qtyInv[]"]');
        let objPrice= $('#article_row input[name="price[]"]');
        let objTotal= $('#article_row input[name="totalLine[]"]');
        let objPriceJasa= $('#article_row input[name="priceJasa[]"]');
        let objTotalJasa= $('#article_row input[name="totalJasa[]"]');
        let objSubTotal= $('#article_row input[name="subTotal[]"]');
                
        objQtyInv.keyup(function() {
            let indexnya= objQtyInv.index(this);
            let qty = objQtyInv.eq(indexnya).val().replace(/,/gi, '') || 0; 
            let price = objPrice.eq(indexnya).val().replace(/,/gi, '') ||0;
            let priceJasa = objPriceJasa.eq(indexnya).val().replace(/,/gi, '') ||0;
            let total = qty*price;
            let totalJasa = qty*priceJasa;
            objTotal.eq(indexnya).val(total.toFixed(2)).trigger('input');
            objTotalJasa.eq(indexnya).val(totalJasa.toFixed(2)).trigger('input');
            objSubTotal.eq(indexnya).val((total+totalJasa).toFixed(2)).trigger('input');
            hitungGrandTotal();
            // mask_thousand();
            // mask_thousand_digit(2);
        });

        objPrice.keyup(function() {
            let indexnya= objPrice.index(this);
            let qty = objQtyInv.eq(indexnya).val().replace(/,/gi, '') || 0; 
            let price = objPrice.eq(indexnya).val().replace(/,/gi, '')||0;
            let total = qty*price;
            let priceJasa = objPriceJasa.eq(indexnya).val().replace(/,/gi, '')||0;
            let totalJasa = qty*priceJasa;
            objTotal.eq(indexnya).val(total.toFixed(2)).trigger('input');
            objTotalJasa.eq(indexnya).val(totalJasa.toFixed(2)).trigger('input');
            objSubTotal.eq(indexnya).val((total+totalJasa).toFixed(2)).trigger('input');
            hitungGrandTotal();
            // mask_thousand();
            // mask_thousand_digit(2);
        });    

        objPriceJasa.keyup(function() {
            let indexnya= objPriceJasa.index(this);
            let qty = objQtyInv.eq(indexnya).val().replace(/,/gi, '') || 0; 
            let price = objPrice.eq(indexnya).val().replace(/,/gi, '')||0;
            let total = qty*price;
            let priceJasa = objPriceJasa.eq(indexnya).val().replace(/,/gi, '')||0;
            let totalJasa = qty*priceJasa;
            objTotal.eq(indexnya).val(total.toFixed(2)).trigger('input');
            objTotalJasa.eq(indexnya).val(totalJasa.toFixed(2)).trigger('input');
            objSubTotal.eq(indexnya).val((total+totalJasa).toFixed(2)).trigger('input');
            hitungGrandTotal();
            // mask_thousand();
            // mask_thousand_digit(2);
        });
        
    }

    let delayTimerTax;
    let delayTimerLain;

    function hitungGrandTotal(){
        let objArticle = $('#article_row input[name="articleId[]"]');
        let objArticleSum = $('#articleRow input[name="articleId[]"]');
        let objQtyTiw= $('#article_row input[name="qtyInv[]"]');
        let objQTY= $('#article_row input[name="qtyInv[]"]');
        let objPrice= $('#article_row input[name="price[]"]');
        let objPriceJasa= $('#article_row input[name="priceJasa[]"]');
        let ppn= sNilaiPPN;
        let pph23= sNilaiPPH;
        let totalQty= 0;
        let totalAmount=0
        let totalAmountJasa=0
        let totalAmountMaterial=0

        var arr = objQtyTiw.map(function (i) {
            let qty = parseFloat(objQTY.eq(i).val().replace(/,/gi, '')) || 0;
            let price = parseFloat(objPrice.eq(i).val().replace(/,/gi, '')) || 0;
            let priceJasa = parseFloat(objPriceJasa.eq(i).val().replace(/,/gi, '')) || 0;
            totalQty+= qty;
            totalAmount+= (qty*price)+(qty*priceJasa);
            totalAmountMaterial+= (qty*price)+(qty*priceJasa);
            totalAmountJasa+= (qty*priceJasa);
        }).get();

        // $("#vatCheck").prop("checked",true);
        // if((totalAmountJasa + totalAmount) > 0 ){
        //     $("#pph23Check").prop("checked",true);
        // }else{
        //     $("#pph23Check").prop("checked",false);
        // }

        $("#totalAmountJasa").val(humanizeNumber(totalAmountJasa.toFixed(2)));
        $("#totalRow").val(objArticleSum.length);
        $("#totalQTY").val(humanizeNumber(totalQty));
        $("#totalAmount").val(humanizeNumber(totalAmount.toFixed(2)));
        
        if(edit == 'false'){
            // $("#nilaiPPN").text(ppn+"%");
            $("#nilaiPPH23").text(pph23+"%");
            $("#totalAmountJasa").val(humanizeNumber(totalAmountJasa.toFixed(2)));

            if ($('#vatCheck').is(':checked')){
                sNilaiPPN = $("#ppn").val();
                    let zDppNilaiLain = totalAmountMaterial * (sNilaiPpnPembilang/sNilaiPpnPenyebut);
                    let zba = zDppNilaiLain ? zDppNilaiLain : totalAmountMaterial;
                    let qTotalPpn = Math.round(zba * (sNilaiPPN/100));
                    $("#totalPPN").val(humanizeNumber(parseFloat(qTotalPpn).toFixed(2)));
                // $("#totalPPN").val(humanizeNumber(((parseFloat(ppn)*totalAmountMaterial)/100).toFixed(2)));
            }else{
                $("#totalPPN").val(0);
            }

            if ($("#nilaiLainCheck").is(':checked')) {
                let zDppNilaiLain = totalAmountMaterial * (sNilaiPpnPembilang/sNilaiPpnPenyebut);
                // clearTimeout(delayTimerLain);
                // delayTimerLain = setTimeout(function() {
                    $("#totalDppNilaiLain").val(humanizeNumber(parseFloat(zDppNilaiLain).toFixed(2)));
                    let qTotalPpn = Math.round(zDppNilaiLain * (sNilaiPPN/100));
                    $("#totalPPN").val(humanizeNumber(parseFloat(qTotalPpn).toFixed(2)));
                // }, 2100);
            }else{
                $("#totalDppNilaiLain").val('');
            }
    
            if ($('#pph23Check').is(':checked')){
                if(totalAmountJasa > 0){
                    $("#totalPPH").val(humanizeNumber((totalAmountJasa * (sNilaiPPH/100)).toFixed(2)));
                }else{
                    $("#totalPPH").val(humanizeNumber((totalAmount * (sNilaiPPH/100)).toFixed(2)));
                }
            }
        }

        let tDpp =  totalAmount;
        let tPpn = $("#totalPPN").val().replace(/,/gi, '') || 0;
        let tPph = $("#totalPPH").val().replace(/,/gi, '') || 0;

        let totalNetto1 = (tDpp+parseFloat(tPpn))-parseFloat(tPph);

        $("#totalNetto").val(humanizeNumber(totalNetto1.toFixed(2)));

        jumlahDetail();

        mask_thousand_digit(2);
    }

    $('#totalPPH,#totalPPN').keyup(function() {
        totalSummary();
    })

    $("#pph23Check").change(function() {
        if(this.checked) {
            let totalAmountJasa = parseFloat($('#totalAmountJasa').val().replace(/,/gi, '')) || 0;
            let totalAmount = parseFloat($('#totalAmount').val().replace(/,/gi, '')) || 0;
            if (totalAmountJasa){
                $("#totalPPH").val((totalAmountJasa * (sNilaiPPH/100)).toFixed(2));
            }else{
                $("#totalPPH").val((totalAmount * (sNilaiPPH/100)).toFixed(2));
            }
            $("#nilaiPPH").text(sNilaiPPH+'%');
            $('#totalPPH').removeAttr('disabled');
            $('#totalPPH').focus().select();
            mask_thousand();
            mask_thousand_digit(2);
            totalSummary();
        }else{
            $("#totalPPH").val(0);
            $("#nilaiPPH").text('');
            $('#totalPPH').attr('disabled','disabled');
            totalSummary();
        }
    });

    hitungPpn = () => {
        let aInvDate = invDate.val();
        if(aInvDate){
            getActivePpn(aInvDate).done(function (result) {
                if(result){
                    // sNilaiPPN = result;
                    sNilaiPPN = result.ppnValue;
                    sNilaiPpnPembilang = result.pembilang;
                    sNilaiPpnPenyebut = result.penyebut;
                    $("#ppn").val(sNilaiPPN);
                    $("#pembilangNumber").val(sNilaiPpnPembilang);
                    $("#penyebutNumber").val(sNilaiPpnPenyebut);
                    // console.log(`Nilai PPN sesuai Invoice Date : ${sNilaiPPN}`);
                }
            })
        }

        let totalAmount = parseFloat($('#totalAmount').val().replace(/,/gi, '')) || 0;

        if($("#totalDppNilaiLain").val()){
            totalAmount = $("#totalDppNilaiLain").val().replace(/,/gi, '');
        }

        let zTotalPPn = Math.round(totalAmount * (sNilaiPPN/100));
        // console.log(`BA Tanpa pembulatan dari ppn:${totalAmount * (sNilaiPPN/100)}`);
        $("#totalPPN").val(parseFloat(zTotalPPn).toFixed(2));
        $("#nilaiPPN").text(sNilaiPPN+'%');
        $("#totalPPN").removeAttr('disabled');
        $("#totalPPN").prop('required',true);
        $("#totalPPN").focus().select();
        mask_thousand();
        mask_thousand_digit(2);
        totalSummary();
    }

    $("#vatCheck").change(function() {
        let aInvDate = invDate.val();
        if (aInvDate){
            if(this.checked) {
                hitungPpn();
                // let aInvDate = invDate.val();
                // if(aInvDate){
                //     getActivePpn(aInvDate).done(function (result) {
                //         if(result){
                //             // sNilaiPPN = result;
                //             sNilaiPPN = result.ppnValue;
                //             $("#ppn").val(sNilaiPPN);
                //             console.log(`Nilai PPN sesuai Invoice : ${sNilaiPPN}`);
                //         }
                //     })
                // }
                // let totalAmount = parseFloat($('#totalAmount').val().replace(/,/gi, '')) || 0;
                // $("#totalPPN").val((totalAmount * (sNilaiPPN/100)).toFixed(2)).trigger("input");
                // $("#nilaiPPN").text(sNilaiPPN+'%');
                // $("#totalPPN").removeAttr('disabled');
                // $("#totalPPN").prop('required',true);
                // $("#totalPPN").focus().select();
                // mask_thousand();
                // mask_thousand_digit(2);
                // totalSummary();
            }else{
                $("#totalPPN").val(0);
                $("#nilaiPPN").text('');
                $("#totalPPN").prop('required',false);
                $("#totalPPN").attr('disabled','disabled');
                $("#nilaiDppLain").text('');
                $("#totalDppNilaiLain").val('');
                $("#nilaiLainCheck").prop('checked', false);
                totalSummary();
            }
        }else{
            swal.fire('Warning',"Invoice date belum diisi !!",'warning');
            $("#vatCheck").prop('checked', false);
            $("#nilaiLainCheck").prop('checked', false);
        }
    });

    hitungNilaiLain = () =>{
        let aInvDate = invDate.val();
        if(aInvDate){
            getActivePpn(aInvDate).done(function (result) {
                if(result){
                    sNilaiPPN = result.ppnValue;
                    sNilaiPpnPembilang = result.pembilang;
                    sNilaiPpnPenyebut = result.penyebut;
                    $("#ppn").val(sNilaiPPN);
                    $("#pembilangNumber").val(sNilaiPpnPembilang);
                    $("#penyebutNumber").val(sNilaiPpnPenyebut);
                }
            })
        }
        
        /*
            jika ada DPP nilai lain maka perhituangan DPP lain-lain
            rumus 11/12* 
            dan untuk PPN 12% nya dihitung dari DPP Nilai Lain * 12%
        */

        let totalAmount = parseFloat($('#totalAmount').val().replace(/,/gi, '')) || 0;
        let zDppNilaiLain = totalAmount * (sNilaiPpnPembilang/sNilaiPpnPenyebut);

        $("#totalDppNilaiLain").val(parseFloat(zDppNilaiLain).toFixed(2));
        $("#nilaiDppLain").text(`${sNilaiPpnPembilang}/${sNilaiPpnPenyebut}`);
        totalAmount = zDppNilaiLain;
        let zTotalPPn = Math.round(totalAmount * (sNilaiPPN/100));
        // console.log(`BA Tanpa pembulatan dari nilai lain:${totalAmount * (sNilaiPPN/100)}`);
        $("#vatCheck").prop('checked', true);
        $("#totalPPN").val(parseFloat(zTotalPPn).toFixed(2)).trigger("input");
        $("#nilaiPPN").text(sNilaiPPN+'%');
        $("#totalPPN").removeAttr('disabled');
        $("#totalPPN").prop('required',true);
        $("#totalPPN").focus().select();
        mask_thousand();
        mask_thousand_digit(2);
        totalSummary();
    }

    $("#nilaiLainCheck").change(function() {
        let aInvDate = invDate.val();
        if (aInvDate){
            if(this.checked) {
                hitungNilaiLain();
            }else{
                $("#totalDppNilaiLain").val('');
                $("#nilaiDppLain").text('');
                hitungTotal();
                if($('#vatCheck').is(':checked')) {
                    hitungPpn();
                }
            }
        }else{
            swal.fire('Warning',"Invoice date belum diisi !!",'warning');
            $("#vatCheck").prop('checked', false);
            $("#nilaiLainCheck").prop('checked', false);
        }
    });

    totalSummary = () =>{
        let totalAmount1 = $("#totalAmount").val().replace(/,/gi, '') || 0;
        let jumlahPpn = $("#totalPPN").val().replace(/,/gi, '') || 0;
        let jumlahJasa = $('#totalPPH').val().replace(/,/gi, '') || 0;
        let totalSummary1 = (parseFloat(totalAmount1)+parseFloat(jumlahPpn))-parseFloat(jumlahJasa);
        $("#totalNetto").val(humanizeNumber(totalSummary1.toFixed(2)));
    }

    jumlahDetail = () =>{
        let jumlahData = $('#article_row input[name="articleId[]"]').length;
        if (jumlahData > 0 ){
            // $('#soNumber').attr('disabled','dieabled');
            $('#customer').attr('disabled','dieabled');
        }else{
            // $('#soNumber').removeAttr('disabled');
            $('#customer').removeAttr('disabled');
        }
    }

    cmdSubmit=()=> {
        let dnNumber="";
        let soNumber="";
        let arrDn = $("input[name='dnNumber[]']").map(function(){return $(this).val();}).get();
        let jumlahCheck = 0;

        $('input:checkbox[name=customCheck]:checked').each(function(){
            // if(jQuery.inArray($(this).data('dn-number'), arrDn) == -1) {    
                dnNumber += $(this).data('dn-number')+",";
                soNumber += $(this).data('so-number')+",";
            // }
            jumlahCheck++;
        });

        dnNumber=dnNumber.slice(0,-1);
        soNumber=soNumber.slice(0,-1);

        // let soNumber= $('#soNumber').val();
        $("#article_row").empty();
        $("#articleRow").empty();
        
        // if(jumlahCheck > 0 && soNumber){
        if(edit == 'true'){
            edit = 'false'
        }
        
        console.log("Edit : "+edit);

        if(jumlahCheck > 0 ){
            $.ajax({
                url:"{{ route('invoice.dn.det') }}",
                method:"POST",
                data:{
                    soNumber:soNumber,
                    dnNumber:dnNumber
                },
                success:function(result){
                    if(result.detail.length > 0 ){
                        for (let i = 0; i < result.detail.length; i++) {
                            article=result.detail[i].article_code;
                            articleCode=result.detail[i].article_alternative_code;
                            articleDesc=result.detail[i].article_desc;
                            qtyDn=result.detail[i].qty_dn;
                            uomGroup=result.detail[i].uom_group;
                            uom=result.detail[i].uom;
                            price=result.detail[i].price;
                            priceService=result.detail[i].price_service;
                            soCode=result.detail[i].so_number;
                            dnNumber=result.detail[i].delivery_number;
                            poNumber=result.detail[i].po_number;
                            add_new_row(article,articleCode,articleDesc,qtyDn,uomGroup,uom,price,priceService,soCode,dnNumber,poNumber);
                        }
                    }

                    if(result.summary.length > 0 ){
                        for (let i = 0; i < result.summary.length; i++) {
                            article=result.summary[i].article_code;
                            articleCode=result.summary[i].article_alternative_code;
                            articleDesc=result.summary[i].article_desc;
                            qtyDn=result.summary[i].qty_dn;
                            uomGroup=result.summary[i].uom_group;
                            uom=result.summary[i].uom;
                            price=result.summary[i].price;
                            priceService=result.summary[i].price_service;
                            soCode=result.summary[i].so_number;
                            add_new_row_summary(article,articleCode,articleDesc,qtyDn,uomGroup,uom,price,priceService,soCode);
                        }
                    }
                    jumlahDetail();
                },
                error: function (response) {
                    //Error here
                    Swal.fire("Warning","Get list data failed","warning");
                }
            })
            jumlahDetail();
            hitungTotal();
            hitungGrandTotal();
        }else{
            // Swal.fire("Warning","Po atau No Receiving belum dipilih","warning");
            $("#article_row").empty();
            jumlahDetail();
            hitungTotal();
            hitungGrandTotal();
        }
    }

    $("#cmdSubmit").click(function (e) {
        cmdSubmit();        
    });

    let cloneCount=0;
    function add_new_row(article,articleCode,articleDesc,qty,uomGroup,uom,price,priceJasa,soCode,dnNumber,poNumber) {
        // $('#poNumberi').val(poNumber);
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        $("#new_row"+ cloneCount).find('#qtyInv').attr('id', 'qtyInv'+ cloneCount);
        $("#new_row"+ cloneCount).find('#totalLine').attr('id', 'totalLine'+ cloneCount);
        $("#new_row"+ cloneCount).find('#totalJasa').attr('id', 'totalJasa'+ cloneCount);
        $("#new_row"+ cloneCount).find('#subTotal').attr('id', 'subTotal'+ cloneCount);
        $("#new_row"+ cloneCount).find('#articleId').attr('id', 'articleId'+ cloneCount);
        $("#new_row"+ cloneCount).find('#price').attr('id', 'price'+ cloneCount);
        $("#new_row"+ cloneCount).find('#priceJasa').attr('id', 'priceJasa'+cloneCount);
        $("#new_row"+ cloneCount).find('#dnNumber').attr('id', 'dnNumber'+cloneCount);
        $('#articleId'+ cloneCount).attr('data-code', article);
        $('#articleId'+ cloneCount).attr('data-desc', articleDesc);
        $('#articleId'+ cloneCount).attr('data-uom', uom);
        $('#articleId'+ cloneCount).attr('data-price', price);
        $('#articleId'+ cloneCount).attr('data-price-service', priceJasa);
        $('#articleId'+ cloneCount).attr('data-so-code', soCode);
        $('#articleId'+ cloneCount).attr('data-dn-number', dnNumber);
        $('#articleId'+ cloneCount).attr('data-po-number', poNumber);
        // $('#articleId'+ cloneCount).val(articleCode +" - " + articleDesc);
        $('#articleId'+ cloneCount).val(articleDesc);
        $('#price'+ cloneCount).val(parseFloat(price).toFixed(2));
        $('#priceJasa'+ cloneCount).val(parseFloat(priceJasa).toFixed(2));
        $('#qtyInv'+ cloneCount).val(qty);
        $('#uom'+ cloneCount).val(uom);
        $('#dnNumber'+ cloneCount).val(dnNumber);
        $('#totalLine'+ cloneCount).val((qty*price).toFixed(2)).trigger('input');
        $('#totalJasa'+ cloneCount).val((qty*priceJasa).toFixed(2)).trigger('input');
        $('#subTotal'+ cloneCount).val(((qty*price)+(qty*priceJasa)).toFixed(2)).trigger('input');

        hitungTotal();
        hitungGrandTotal();
        mask_thousand();
        mask_thousand_digit(2);
        
    }

    let cloneCountSum=0;
    function add_new_row_summary(article,articleCode,articleDesc,qty,uomGroup,uom,price,priceJasa,soCode) {
        // $('#poNumberi').val(poNumber);
        $("#articleRow").append($("#new_row").clone().html());
        cloneCountSum++;
        $("#articleRow").find('#baru').attr('id', 'newRow'+ cloneCountSum);
        $("#newRow"+ cloneCountSum).find('#uom').attr('id', 'uomSum'+ cloneCountSum);
        $("#newRow"+ cloneCountSum).find('#qtyInv').attr('id', 'qtyInvSum'+ cloneCountSum);
        $("#newRow"+ cloneCountSum).find('#totalLine').attr('id', 'totalLineSum'+ cloneCountSum);
        $("#newRow"+ cloneCountSum).find('#totalJasa').attr('id', 'totalJasaSum'+ cloneCountSum);
        $("#newRow"+ cloneCountSum).find('#subTotal').attr('id', 'subTotalSum'+ cloneCountSum);
        $("#newRow"+ cloneCountSum).find('#articleId').attr('id', 'articleIdSum'+ cloneCountSum);
        $("#newRow"+ cloneCountSum).find('#price').attr('id', 'priceSum'+ cloneCountSum);
        $("#newRow"+ cloneCountSum).find('#priceJasa').attr('id', 'priceJasaSum'+cloneCountSum);
        $('#articleIdSum'+ cloneCountSum).val(articleDesc);
        $('#priceSum'+ cloneCountSum).val(parseFloat(price).toFixed(2));
        $('#priceJasaSum'+ cloneCountSum).val(parseFloat(priceJasa).toFixed(2));
        $('#qtyInvSum'+ cloneCountSum).val(qty);
        $('#uomSum'+ cloneCountSum).val(uom);
        $('#totalLineSum'+ cloneCountSum).val((qty*price).toFixed(2)).trigger('input');
        $('#totalJasaSum'+ cloneCountSum).val((qty*priceJasa).toFixed(2)).trigger('input');
        $('#subTotalSum'+ cloneCountSum).val(((qty*price)+(qty*priceJasa)).toFixed(2)).trigger('input');

        hitungTotal();
        hitungGrandTotal();
        mask_thousand();
        mask_thousand_digit(2);
        
    }

    $('#invDate').change(function () {
        let aInvoiceDate = $(this).val();
        getActivePpn(aInvoiceDate).done(function (result) {
            if(result){
                sNilaiPPN = result.ppnValue;
                sNilaiPpnPembilang = result.pembilang;
                sNilaiPpnPenyebut = result.penyebut;

                $("#ppn").val(sNilaiPPN);
                $("#pembilangNumber").val(sNilaiPpnPembilang);
                $("#penyebutNumber").val(sNilaiPpnPenyebut);
                $("#nilaiPPN").text(`${sNilaiPPN}%`);
                $("#nilaiDppLain").text(`${sNilaiPpnPembilang}/${sNilaiPpnPenyebut}`);
                
                $("#nilaiLainCheck").prop('checked',true).change();
                
                // if($("#nilaiLainCheck").is(':checked')){
                //     $("#nilaiLainCheck").change();
                // }

                if($("#vatCheck").is(':checked')){
                    $("#vatCheck").change();
                }
            }
        })
    });

    const rangePickr = $('.flatpickr-range');
    if (rangePickr.length) {
        rangePickr.flatpickr({
            dateFormat: "d-m-Y",
            mode: 'range',
            onClose: function(selectedDates, dateStr, instance) {
                $('#soNumber').empty();
                removeAllDn();
                if(dateStr && customer.val()){
                    searchSo('soNumber', customer.val(), dateStr);
                }
            }
        });
    }

    // function getBuktiPotong(noBuktiPotong,customer,noInvoice, callback) {
    //     $.ajax({
    //         url:"{{ route('invoice.get.bukti.potong') }}",
    //         method:"GET",
    //         data:{
    //             noBuktiPotong:noBuktiPotong,
    //             customer:customer,
    //             noInvoice:noInvoice
    //         },
    //         success:function(result){
    //             callback(null, result); // Success: null error, result data
    //         },
    //         error: function (response) {
    //             //Error here
    //             Swal.fire("Warning","Get Bukti Potong failed","warning");
    //             callback(response, null); // Error: response error, null data
    //         }
    //     })
    // }

</script>