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
                    <td class="isian disabled" style="width: 39%">
                        <input type="text" class="form-control-plaintext text-hitam" id = "articleId" name="articleId[]" data-code="" data-uom=""  data-price="" data-po-number="" data-so-code="" disabled>
                        <input type="hidden" class="form-control-plaintext" id = "dnNumber" name="dnNumber[]" >
                    </td>
                    {{-- <td class="isian disabled" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask-digit text-right" id = "qty_po" name="qty_po[]" disabled>
                    </td> --}}
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
                        {{-- <input type="text" class="form-control-plaintext numeral-mask text-right" id="totalLine" name="totalLine[]" > --}}
                        {{-- <span id="totalLine" name="totalLine[]"></span> --}}
                        <input type="text" class="form-control-plaintext numeral-mask-digit text-right" id = "totalLine" name="totalLine[]" disabled>
                        {{-- <span class="text-hitam text-hitam" id="totalLine" name="totalLine[]"></span> --}}
                    </td>
                    <td class="isian disabled text-right" style="width: 10%">
                        {{-- <input type="text" class="form-control-plaintext numeral-mask text-right" id="totalJasa" name="totalJasa[]" > --}}
                        {{-- <span id="totalJasa" name="totalJasa[]"></span> --}}
                        <input type="text" class="form-control-plaintext numeral-mask-digit text-right" id = "totalJasa" name="totalJasa[]" disabled>
                        {{-- <span class="text-hitam text-hitam" id="totalJasa" name="totalJasa[]""></span> --}}
                    </td>
                    <td class="isian disabled text-right" style="width: 10%">
                        <input type="text" class="form-control-plaintext numeral-mask-digit text-right" id = "subTotal" name="subTotal[]" disabled>
                        {{-- <span class="text-hitam text-hitam" id="subTotal" name="subTotal[]"></span> --}}
                    </td>
                    {{-- <td class="isian text-center" style="width: 5%">
                        <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal()">
                            <i data-feather="trash-2" class="remove_button feather-24">
                            </i>
                        </a>
                    </td> --}}
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

    let sNilaiPPN= "{{ $nilaiPPN }}";
    let sNilaiPPH= "{{ $nilaiPPH }}";
    let showDetail="";
    let edit="";

    let delayTimer;
    function inputDecimal(ele) {
        clearTimeout(delayTimer);
        delayTimer = setTimeout(function() {
            let nilai = ele.value.replace(/,/gi, '') || 0;;
            ele.value = humanizeNumber(parseFloat(nilai).toFixed(2)).toString();
        }, 1100); 
    }

    customer.change(function(){
        $('#accountPiutang').val("");
        $('#soNumber').empty();
        let coa = $(this).find(":selected").data("coa");
        if(coa){
            $('#accountPiutang').val(coa);
            searchSo('soNumber',$(this).val());
        }else{
            Swal.fire("Warning","Customer belum memiliki COA Piutang","warning"); 
        }
    });
    
    function searchSo(obj,value) {
        if(value){
            $.ajax({
                url:"{{ route('invoice.list.so') }}",
                method:"GET",
                data:{
                    value:value,
                },
                success:function(result){
                    $('#'+obj).html(result);
                    $('#'+obj).val('').trigger('change');
                },
                error: function (response) {
                    //Error here
                    Swal.fire("Warning","Get list PO failed","warning");
                }
            })
        }
    }

    function searchDn(soNumber) {
        let invNumber = $('#invNumber').val();
        $("#listOfDn > tbody").empty();
        if(soNumber){
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
                        $('#cmdSubmit').removeAttr('disabled');
                        $("#listOfDn tbody").append(result);
                        // if(invNumber){
                        //     if (edit == 'true'){
                        //         cmdSubmit();
                        //     }
                        // }
                    }else{
                        $('#cmdSubmit').attr('disabled','disabled');
                    }

                    // $('#'+obj).html(result);
                    // $('#'+obj).val('').trigger('change');
                },
                error: function (response) {
                    //Error here
                    Swal.fire("Warning","Get list DN failed","warning");
                }
            })
        }
    }

    soNumber.change(function(){
        let ppn = $(this).find(":selected").data("ppn");
        $('#ppn').val(ppn);
        sNilaiPPN=ppn;
        console.log(ppn);
        searchDn($(this).val());
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
            $("#nilaiPPN").text(ppn+"%");
            $("#nilaiPPH23").text(pph23+"%");
            $("#totalAmountJasa").val(humanizeNumber(totalAmountJasa.toFixed(2)));

            if ($('#vatCheck').is(':checked')){
                $("#totalPPN").val(humanizeNumber(((parseFloat(ppn)*totalAmountMaterial)/100).toFixed(2)));
            }else{
                $("#totalPPN").val(0);
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

    $("#vatCheck").change(function() {
        if(this.checked) {
            let totalAmount = parseFloat($('#totalAmount').val().replace(/,/gi, '')) || 0;
            $("#totalPPN").val((totalAmount * (sNilaiPPN/100)).toFixed(2)).trigger("input");
            $("#nilaiPPN").text(sNilaiPPN+'%');
            $("#totalPPN").removeAttr('disabled');
            $("#totalPPN").prop('required',true);
            $("#totalPPN").focus().select();
            mask_thousand();
            mask_thousand_digit(2);
            totalSummary();
        }else{
            $("#totalPPN").val(0);
            $("#nilaiPPN").text('');
            $("#totalPPN").prop('required',false);
            $("#totalPPN").attr('disabled','disabled');
            totalSummary();
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
            $('#soNumber').attr('disabled','dieabled');
            $('#customer').attr('disabled','dieabled');
        }else{
            $('#soNumber').removeAttr('disabled');
            $('#customer').removeAttr('disabled');
        }
    }

    cmdSubmit=()=> {
        let dnNumber="";
        let arrDn = $("input[name='dnNumber[]']").map(function(){return $(this).val();}).get();
        let jumlahCheck = 0;

        $('input:checkbox[name=customCheck]:checked').each(function(){
            // if(jQuery.inArray($(this).data('dn-number'), arrDn) == -1) {    
                dnNumber += $(this).data('dn-number')+",";
            // }
            jumlahCheck++;
        });

        dnNumber=dnNumber.slice(0,-1);
        let soNumber= $('#soNumber').val();
        $("#article_row").empty();
        $("#articleRow").empty();
        
        if(jumlahCheck > 0 && soNumber){
            $.ajax({
                url:"{{ route('invoice.dn.det') }}",
                method:"GET",
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

        tombolPanah('qtyInv');
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

</script>