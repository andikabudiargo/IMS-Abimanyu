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
                    </td>
                    {{-- <td class="isian disabled" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask-digit text-right" id = "qty_po" name="qty_po[]" disabled>
                    </td> --}}
                    <td class="isian" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask text-right" id = "qtyInv" name="qtyInv[]" maxlength="9">
                    </td>
                    <td class="isian" style="width: 5%">
                        <input type="text" class="form-control-plaintext" id = "uom" name="uom[]" disabled>
                    </td>
                    <td class="isian" style="width: 8%">
                        <input type="text" class="form-control-plaintext numeral-mask text-right" id = "price" name="price[]"  maxlength="11">
                    </td>
                    <td class="isian" style="width: 8%">
                        <input type="text" class="form-control-plaintext numeral-mask text-right" id = "priceJasa" name="priceJasa[]"  maxlength="11">
                    </td>
                    <td class="isian disabled text-right" style="width: 10%">
                        {{-- <input type="text" class="form-control-plaintext numeral-mask text-right" id="totalLine" name="totalLine[]" > --}}
                        {{-- <span id="totalLine" name="totalLine[]"></span> --}}
                        <span class="text-hitam text-hitam" id="totalLine" name="totalLine[]"></span>
                    </td>
                    <td class="isian disabled text-right" style="width: 10%">
                        {{-- <input type="text" class="form-control-plaintext numeral-mask text-right" id="totalJasa" name="totalJasa[]" > --}}
                        {{-- <span id="totalJasa" name="totalJasa[]"></span> --}}
                        <span class="text-hitam text-hitam" id="totalJasa" name="totalJasa[]""></span>
                    </td>
                    <td class="isian disabled text-right" style="width: 10%">
                        <span class="text-hitam text-hitam" id="subTotal" name="subTotal[]"></span>
                    </td>
                    <td class="isian text-center" style="width: 5%">
                        <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal()">
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
    const dnNumber = $('#dnNumber');

    customer.change(function(){
        searchSo('soNumber',$(this).val());
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

    function searchDn(obj,value) {
        if(value){
            $.ajax({
                url:"{{ route('invoice.list.dn') }}",
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
                    Swal.fire("Warning","Get list DN failed","warning");
                }
            })
        }
    }

    function searchDnDet(dnNumber,soNumber) {
        if(dnNumber){
            $.ajax({
                url:"{{ route('invoice.dn.det') }}",
                method:"GET",
                data:{
                    soNumber:soNumber,
                    dnNumber:dnNumber
                },
                success:function(result){                
                    if(result.length > 0 ){
                        for (let i = 0; i < result.length; i++) {
                            article=result[i].article_code;
                            articleCode=result[i].article_alternative_code;
                            articleDesc=result[i].article_desc;
                            qtyDn=result[i].qty_dn;
                            uomGroup=result[i].uom_group;
                            uom=result[i].uom;
                            price=result[i].price;
                            priceService=result[i].price_service;
                            soCode=result[i].so_number;
                            dnNumber=result[i].delivery_number;
                            poNumber=result[i].po_number;
                            add_new_row(article,articleCode,articleDesc,qtyDn,uomGroup,uom,price,priceService,soCode,dnNumber,poNumber);
                        }
                    }
                },
                error: function (response) {
                    Swal.fire("Warning","Get detail DN failed","warning");
                }
            })
        }
    }

    soNumber.change(function(){
        searchDn('dnNumber',$(this).val());
    })

    dnNumber.change(function(){
        searchDnDet($(this).val(),soNumber.val());
    })

    function hitungTotal(){
        let objQtyInv= $('#article_row input[name="qtyInv[]"]');
        let objPrice= $('#article_row input[name="price[]"]');
        let objTotal= $('#article_row span[name="totalLine[]"]');
        let objPriceJasa= $('#article_row input[name="priceJasa[]"]');
        let objTotalJasa= $('#article_row span[name="totalJasa[]"]');
        let objSubTotal= $('#article_row span[name="subTotal[]"]');
                
        objQtyInv.keyup(function() {

            let indexnya= objQtyInv.index(this);
            let qty = objQtyInv.eq(indexnya).val().replace(/,/gi, '') || 0; 
            let price = objPrice.eq(indexnya).val().replace(/,/gi, '') ||0;
            let priceJasa = objPriceJasa.eq(indexnya).val().replace(/,/gi, '') ||0;
            let total = qty*price;
            let totalJasa = qty*priceJasa;
            objTotal.eq(indexnya).text(humanizeNumber(total));
            objTotalJasa.eq(indexnya).text(humanizeNumber(totalJasa));
            objSubTotal.eq(indexnya).text(humanizeNumber(total+totalJasa));
            hitungGrandTotal();

        });

        objPrice.keyup(function() {
            let indexnya= objPrice.index(this);
            let qty = objQtyInv.eq(indexnya).val().replace(/,/gi, '') || 0; 
            let price = objPrice.eq(indexnya).val().replace(/,/gi, '')||0;
            let total = qty*price;
            let priceJasa = objPriceJasa.eq(indexnya).val().replace(/,/gi, '')||0;
            let totalJasa = qty*priceJasa;
            objTotal.eq(indexnya).text(humanizeNumber(total));
            objTotalJasa.eq(indexnya).text(humanizeNumber(totalJasa));
            objSubTotal.eq(indexnya).text(humanizeNumber(total+totalJasa));
            hitungGrandTotal();
        });    

        objPriceJasa.keyup(function() {
            let indexnya= objPrice.index(this);
            let qty = objQtyInv.eq(indexnya).val().replace(/,/gi, '') || 0; 
            let price = objPrice.eq(indexnya).val().replace(/,/gi, '')||0;
            let total = qty*price;
            let priceJasa = objPriceJasa.eq(indexnya).val().replace(/,/gi, '')||0;
            let totalJasa = qty*priceJasa;
            objTotal.eq(indexnya).text(humanizeNumber(total));
            objTotalJasa.eq(indexnya).text(humanizeNumber(totalJasa));
            objSubTotal.eq(indexnya).text(humanizeNumber(total+totalJasa));
            hitungGrandTotal();
        });

    }

    function hitungGrandTotal(){
        let objArticle = $('#article_row select[name="articleId[]"]');
        let objQtyTiw= $('#article_row input[name="qtyInv[]"]');
        let objQTY= $('#article_row input[name="qtyInv[]"]');
        let objPrice= $('#article_row input[name="price[]"]');
        let objPriceJasa= $('#article_row input[name="priceJasa[]"]');
        let ppn= $('#ppn').val() || 10;
        let pph23= $('#pph23').val() || 2;
        let totalQty= 0;
        let totalAmount=0
        let totalAmountJasa=0
        let totalAmountMaterial=0

        var arr = objQtyTiw.map(function (i) {
            let qty = parseInt(objQTY.eq(i).val().replace(/,/gi, '')) || 0;
            let price = parseInt(objPrice.eq(i).val().replace(/,/gi, '')) || 0;
            let priceJasa = parseInt(objPriceJasa.eq(i).val().replace(/,/gi, '')) || 0;
            totalQty+= qty;
            totalAmount+= (qty*price)+(qty*priceJasa);
            totalAmountMaterial+= (qty*price)+(qty*priceJasa);
            totalAmountJasa+= (qty*priceJasa);
        }).get();
        
        $("#totalRow").val(objArticle.length);
        $("#nilaiPPN").text(ppn+"%");
        $("#nilaiPPH23").text(pph23+"%");
        $("#totalQTY").val(humanizeNumber(totalQty));
        $("#totalAmount").val(humanizeNumber(totalAmount));
        $("#totalPPN").val(humanizeNumber((parseInt(ppn)*totalAmountMaterial)/100));
        $("#totalPPH").val("-"+humanizeNumber((pph23*totalAmountJasa)/100));
        $("#totalNetto").val(humanizeNumber(totalAmount+((parseInt(ppn)*totalAmount)/100)-((pph23*totalAmountJasa)/100)));
    
    }

</script>