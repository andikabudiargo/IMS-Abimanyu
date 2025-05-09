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
    
</style>
{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <table class="table-bordered" id="listData" style="width: 98%;table-layout: fixed;">
            <tbody>
                <tr>
                    <td class="isian disabled" style="width: 34%">
                        <input type="text" class="form-control" list="articlesList" id="articleId" name="articleId[]" maxlength="100">
                    </td>
                    <td class="isian" style="width: 10%">
                        <input type="text" class="form-control-plaintext numeral-mask-digit text-right recalculate" id = "qtyInv" name="qtyInv[]" maxlength="9">
                    </td>
                    <td class="isian" style="width: 5%">
                        <input type="text" class="form-control-plaintext" id = "uom" name="uom[]" value="PCS" maxlength="9">
                    </td>
                    <td class="isian" style="width: 10%">
                        <input type="text" class="form-control-plaintext numeral-mask-digit text-right recalculate" id = "price" name="price[]"  oninput='inputDecimal(this)' maxlength="15">
                    </td>
                    <td class="isian" style="width: 10%">
                        <input type="text" class="form-control-plaintext numeral-mask-digit text-right recalculate" id = "priceJasa" name="priceJasa[]"  oninput='inputDecimal(this)' maxlength="15">
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
                    <td class="isian text-right" style="width: 5%">
                        {{-- <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();disabledEnabledSelect2();"> --}}
                        <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();">
                            <i data-feather="trash-2" class="remove_button feather-24">
                            </i>
                        </a>
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
    const poNumber = $('#poNumber');
    const dnNumber = $('#dnNumber');
    const objAccountPiutang = $('#accountPiutang');
    const objCustomer = $('#customer');

    const objTotalAmountJasa = $("#totalAmountJasa");
    const objTotalQTY = $("#totalQTY");
    const objTotalAmount = $("#totalAmount");
    const objTotalPPN = $("#totalPPN");
    const objTotalPPH = $("#totalPPH");
    const objTotalNetto = $("#totalNetto");

    const approveBtn = document.querySelector('#cmdApprove');

    let sNilaiPPN= "{{ $nilaiPPN }}";
    let sNilaiPPH= "{{ $nilaiPPH }}";
    let showDetail="";
    let edit="";
    let dataArticle="";
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

    customer.on('change', function() {
        if ($(this).val()){
            $('#accountPiutang').val("");
            $('#soNumber').empty();
            let coa = $(this).find(":selected").data("coa");
            let coaPenjualan = $(this).find(":selected").data("coa-penjualan");
            let customerCode = $(this).val();
            if(coa && coaPenjualan){
                $('#accountPiutang').val(coa);
                $('#accountPenjualan').val(coaPenjualan);
                listArticle(customerCode);
            }else{
                Swal.fire("Warning","Customer belum memiliki COA Piutang atau COA Penjualan","warning"); 
            }
        }
    });

    listArticle = (customerCode) => {
        $.ajax({
            url:"{{ route('debitNote.get.article') }}",
            method:"POST",
            data:{
                custCode:customerCode,
            },
            success:function(result){
                dataArticle=result;
                $('#articlesList').html(dataArticle);
            }
        })
    }
   
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

    function hitungGrandTotal(){
        let objQtyTiw= $('#article_row input[name="qtyInv[]"]');
        let objQTY= $('#article_row input[name="qtyInv[]"]');
        let objPrice= $('#article_row input[name="price[]"]');
        let objPriceJasa= $('#article_row input[name="priceJasa[]"]');
        let ppn= sNilaiPPN;
        let pph23= sNilaiPPN;
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

        $("#totalAmountJasa").val(humanizeNumber(totalAmountJasa.toFixed(2)));
        $("#totalQTY").val(humanizeNumber(totalQty));
        $("#totalAmount").val(humanizeNumber(totalAmount.toFixed(2)));
        
        if(edit == 'false'){
            // $("#nilaiPPN").text(ppn+"%");
            $("#nilaiPPH23").text(pph23+"%");
            $("#totalAmountJasa").val(humanizeNumber(totalAmountJasa.toFixed(2)));

            if ($('#vatCheck').is(':checked')){ 
                    sNilaiPPN = $("#ppn").val();
                    let zDppNilaiLain = totalAmountMaterial * (sNilaiPpnPembilang/sNilaiPpnPenyebut);
                    let zba = $("#nilaiLainCheck").is(':checked') ? zDppNilaiLain : totalAmountMaterial;
                    // let zba = zDppNilaiLain ? zDppNilaiLain : totalAmountMaterial;
                    let qTotalPpn = Math.round(zba * (sNilaiPPN/100));
                    $("#totalPPN").val(humanizeNumber(parseFloat(qTotalPpn).toFixed(2)));
                    
                    // clearTimeout(delayTimerTax);
                    // delayTimerTax = setTimeout(function() {
                        // let qTotalPpn = Math.round(zba * (sNilaiPPN/100));
                        // $("#totalPPN").val(humanizeNumber(parseFloat(qTotalPpn).toFixed(2)));
                    // }, 2100);
            }else{
                $("#totalPPN").val(0);
            }

            if ($("#nilaiLainCheck").is(':checked')) {
                let zDppNilaiLain = totalAmountMaterial * (sNilaiPpnPembilang/sNilaiPpnPenyebut);
                $("#totalDppNilaiLain").val(humanizeNumber(parseFloat(zDppNilaiLain).toFixed(2)));
                let qTotalPpn = Math.round(zDppNilaiLain * (sNilaiPPN/100));
                $("#totalPPN").val(humanizeNumber(parseFloat(qTotalPpn).toFixed(2)));
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
        mask_thousand_digit(2);
    }

    $('#totalPPH,#totalPPN').keyup(function() {
        // totalSummary();
        hitungGrandTotal();
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
            // totalSummary();
            hitungGrandTotal();
        }else{
            $("#totalPPH").val(0);
            $("#nilaiPPH").text('');
            $('#totalPPH').attr('disabled','disabled');
            hitungGrandTotal();
            // totalSummary();
        }
    });

    hitungPpn = () => {
        let aDebitNDate = debitNDate.val();
        if(aDebitNDate){
            getActivePpn(aDebitNDate).done(function (result) {
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
        // totalSummary();
    }

    $("#vatCheck").change(function() {
        let aDebitNDate = debitNDate.val();
        if(aDebitNDate){
            if(this.checked) {
                hitungPpn();
                hitungGrandTotal();
            }else{
                $("#totalPPN").val(0);
                $("#nilaiPPN").text('');
                $("#totalPPN").prop('required',false);
                $("#totalPPN").attr('disabled','disabled');
                $("#nilaiDppLain").text('');
                $("#totalDppNilaiLain").val('');
                $("#nilaiLainCheck").prop('checked', false);
                // totalSummary();
                hitungGrandTotal();
            }
        }else{
            swal.fire('Warning',"Invoice date belum diisi !!",'warning');
            $("#vatCheck").prop('checked', false);
            $("#nilaiLainCheck").prop('checked', false);
        }
    });

    hitungNilaiLain = () =>{
        let aDebitNDate = debitNDate.val();
        if(aDebitNDate){
            getActivePpn(aDebitNDate).done(function (result) {
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
    }

    $("#nilaiLainCheck").change(function() {
        let aDebitNDate = debitNDate.val();
        if (aDebitNDate){
            if(this.checked) {
                hitungNilaiLain();
                hitungGrandTotal();
            }else{
                $("#totalDppNilaiLain").val('');
                $("#nilaiDppLain").text('');
                hitungTotal();
                if($('#vatCheck').is(':checked')) {
                    hitungPpn();
                }
                hitungGrandTotal();
            }
        }else{
            swal.fire('Warning',"Invoice date belum diisi !!",'warning');
            $("#vatCheck").prop('checked', false);
            $("#nilaiLainCheck").prop('checked', false);
        }
    });

    let cloneCount=0;
    function add_new_row(article,articleCode,articleDesc,qty,uomGroup,uom,price,priceJasa,soCode,dnNumber,poNumber) {
        if(dataArticle){
            cloneCount++;
            $("#article_row").append($("#new_row").clone().html());
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
            // $('#articleId'+ cloneCount).html(dataArticle);
            // $("#articleId"+cloneCount).select2();
            $('#remove_button').tooltip();
    
            tombolPanah('qtyInv');
            hitungTotal();
            hitungGrandTotal();
            mask_thousand();
            mask_thousand_digit(2);
            // disabledEnabledSelect2()
        }
        
    }

    function add_new_row_edit(article,articleCode,articleDesc,qty,uomGroup,uom,price,priceJasa) {
        // if(dataArticle){
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
            changeselectEdit('articleId','articleId'+ cloneCount,article) 
            $('#price'+ cloneCount).val(parseFloat(price).toFixed(2));
            $('#priceJasa'+ cloneCount).val(parseFloat(priceJasa).toFixed(2));
            $('#qtyInv'+ cloneCount).val(qty);
            $('#uom'+ cloneCount).val(uom);
            $('#totalLine'+ cloneCount).val((qty*price).toFixed(2)).trigger('input');
            $('#totalJasa'+ cloneCount).val((qty*priceJasa).toFixed(2)).trigger('input');
            $('#subTotal'+ cloneCount).val(((qty*price)+(qty*priceJasa)).toFixed(2)).trigger('input');
            $('#remove_button').tooltip();
    
            tombolPanah('qtyInv');
            hitungTotal();
            hitungGrandTotal();
            mask_thousand();
            mask_thousand_digit(2);
            // disabledEnabledSelect2()
        // }
        
    }
   
    recordCount = () =>{
        let records = $('.article-select').length-1;
        $('#totalRow').text(records);
    }

    function changeselectEdit(dependent,obj,article) {
        $('#'+obj).attr('disabled','disabled');
        dataArticle =dataArticle+"<option>"+article+"</option>";
        $('#articlesList').html(dataArticle);
        $('#'+obj).val(article);
        $('#'+obj).removeAttr('disabled');
    }

    $(document).on('keyup', '.recalculate', function(e){
        hitungGrandTotal();
    });

    $('#debitNDate').change(function () {
        let aDebitDate = $(this).val();
        getActivePpn(aDebitDate).done(function (result) {
            if(result){
                sNilaiPPN = result.ppnValue;
                sNilaiPpnPembilang = result.pembilang;
                sNilaiPpnPenyebut = result.penyebut;

                $("#ppn").val(sNilaiPPN);
                $("#pembilangNumber").val(sNilaiPpnPembilang);
                $("#penyebutNumber").val(sNilaiPpnPenyebut);
                $("#nilaiPPN").text(`${sNilaiPPN}%`);
                $("#nilaiDppLain").text(`${sNilaiPpnPembilang}/${sNilaiPpnPenyebut}`);
                
                if($("#nilaiLainCheck").is(':checked')){
                    $("#nilaiLainCheck").change();
                }

                if($("#vatCheck").is(':checked')){
                    $("#vatCheck").change();
                }
                $("#nilaiLainCheck").prop('checked',true).change();
            }
        })
    });

    

</script>