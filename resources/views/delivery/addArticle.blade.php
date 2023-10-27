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
                    <td class="isian disabled" style="width: 40%">
                        <input type="text" class="form-control-plaintext text-hitam" id = "articleId" name="articleId[]" data-code="" data-uom=""  data-price="" data-po-number="" disabled>
                    </td>
                    <td class="isian disabled" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask text-right" id = "qtySo" name="qtySo[]" disabled>
                    </td>
                    <td class="isian" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask text-right" id = "qtyInv" name="qtyInv[]" maxlength="9">
                    </td>
                    <td class="isian" style="width: 5%">
                        <input type="text" class="form-control-plaintext" id = "uom" name="uom[]" disabled>
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

    $('#customer').change(function(){
        let value= $(this).val();
        $("#poNumberHdr").val('');
        if(value){
            searchSo('soNumber',value);
        }
    });

    $('#soNumber').change(function(){
        let value= $(this).val();
        $("#poNumberHdr").val('');
        if(value){
            let poNumber = $(this).find(":selected").data("po-number");
            $("#poNumberHdr").val(poNumber);
            searchSoDet(value);
        }
    })

    function searchSo(obj,value,kodeSo) {
        if(value){
            $.ajax({
                url:"{{ route('delivery.list.so') }}",
                method:"GET",
                data:{
                    value:value,
                },
                success:function(result){
                    $('#'+obj).html(result);
                    if(kodeSo){
                        $('#'+obj).val(kodeSo).trigger('change');
                    }
                },
                error: function (response) {
                    //Error here
                    Swal.fire("Warning","Get list SO failed","warning");
                }
            })
        }
    }

    function searchSoDet(value) {
        if(value && (fromEdit == false)){
            $.ajax({
                url:"{{ route('delivery.so.det') }}",
                method:"GET",
                data:{
                    value:value,
                },
                success:function(result){
                    if (cloneCount > 1){
                        $("#article_row").empty();
                        cloneCount=1;
                    }

                    if (cloneCountEdit > 1){
                        $("#article_row").empty();
                        cloneCount=1;
                    }

                    if(result.length > 0 ){
                        for (let i = 0; i < result.length; i++) {
                            article=result[i].article_code;
                            articleCode=result[i].article_alternative_code;
                            articleDesc=result[i].article_desc;
                            qtySo=result[i].qty_so;
                            uomGroup=result[i].uom_group;
                            uom=result[i].uom;
                            price=result[i].price;
                            priceService=result[i].price_service;
                            soCode=result[i].so_code;
                            poNumber=result[i].po_number;
                            add_new_row(article,articleCode,articleDesc,qtySo,uomGroup,uom,price,priceService,soCode,poNumber);
                        }
                    }

                    fromEdit = false;
                    
                },
                error: function (response) {
                    Swal.fire("Warning","Get detail SO failed","warning");
                }
            })
        }
    }

    let cloneCount=1;
    function add_new_row(article,articleCode,articleDesc,qtySo,uomGroup,uom,price,priceJasa,soCode,poNumber) {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#qtySo').attr('id', 'qtySo'+ cloneCount);
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        $("#new_row"+ cloneCount).find('#articleId').attr('id', 'articleId'+ cloneCount);
        $('#articleId'+ cloneCount).attr('data-code', article);
        $('#articleId'+ cloneCount).attr('data-desc', articleDesc);
        $('#articleId'+ cloneCount).attr('data-uom', uom);
        $('#articleId'+ cloneCount).attr('data-price', price);
        $('#articleId'+ cloneCount).attr('data-price-service', priceJasa);
        $('#articleId'+ cloneCount).attr('data-so-code', soCode);
        $('#articleId'+ cloneCount).attr('data-po-number', poNumber);
        $('#articleId'+ cloneCount).attr('data-so-qty', qtySo);
        $('#articleId'+ cloneCount).val(articleCode+'-'+articleDesc);
        $('#uom'+ cloneCount).val(uom);
        $('#qtySo'+ cloneCount).val(qtySo*1);
        tombolPanah('qtyInv');
        mask_thousand();
        hitungTotal();
        cekQty();
    }

    let cloneCountEdit=0;
    function add_new_row_edit(article,articleCode,articleDesc,qtyDel,uomGroup,uom,soCode,poNumber,qtySo) {
        // console.log(article,articleCode,articleDesc,qtyDel,uomGroup,uom);
        $("#article_row").append($("#new_row").clone().html());
        cloneCountEdit++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#qtySo').attr('id', 'qtySo'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#articleId').attr('id', 'articleId'+ cloneCountEdit);
        $('#articleId'+ cloneCountEdit).attr('data-code', article);
        $('#articleId'+ cloneCountEdit).attr('data-desc', articleDesc);
        $('#articleId'+ cloneCountEdit).attr('data-uom', uom);
        // $('#articleId'+ cloneCountEdit).attr('data-price', price);
        // $('#articleId'+ cloneCountEdit).attr('data-price-service', priceJasa);
        $('#articleId'+ cloneCountEdit).attr('data-so-code', soCode);
        $('#articleId'+ cloneCountEdit).attr('data-po-number', poNumber);
        $('#articleId'+ cloneCountEdit).attr('data-so-qty', qtySo);
        $('#articleId'+ cloneCountEdit).val(articleCode+'-'+articleDesc);

        $("#new_row"+ cloneCountEdit).find('#qtyInv').attr('id', 'qtyInv'+ cloneCountEdit);
        $('#qtyInv'+ cloneCountEdit).val(qtyDel);
        $("#new_row"+ cloneCountEdit).find('#uom').attr('id', 'uom'+ cloneCountEdit);
        $('#qtySo'+ cloneCountEdit).val(qtySo*1);
        listUom('uom'+ cloneCountEdit,uomGroup,uom,uom);
        tombolPanah('qtyInv');
        mask_thousand();
        hitungTotal();
        hitungGrandTotalLoad();
    }

    function hitungTotal(){
        let objQtyInv= $('#article_row input[name="qtyInv[]"]');
        objQtyInv.keyup(function() {
            let indexnya= objQtyInv.index(this);
            let qty = objQtyInv.eq(indexnya).val().replace(/,/gi, '') || 0; 
            hitungGrandTotal();
        });
    }

    function hitungGrandTotal(){
        let objArticle = $('#article_row input[name="articleId[]"]');
        let objQtyTiw= $('#article_row input[name="qtyInv[]"]');
        let objQTY= $('#article_row input[name="qtyInv[]"]');
        let totalQty=0;
        var arr = objQtyTiw.map(function (i) {
            let qty = parseInt(objQTY.eq(i).val().replace(/,/gi, '')) || 0;
            totalQty+= qty;
        }).get();
        
        $("#totalRow").val(objArticle.length);
        $("#totalQTY").val(humanizeNumber(totalQty));
    }

    function hitungGrandTotalLoad(){
        let objArticle = $('#article_row input[name="articleId[]"]');
        let objQtyTiw= $('#article_row input[name="qtyInv[]"]');
        let objQTY= $('#article_row input[name="qtyInv[]"]');
        let totalQty=0;
        var arr = objQtyTiw.map(function (i) {
            let qty = parseInt(objQTY.eq(i).val().replace(/,/gi, '')) || 0;
            totalQty+= qty;
        }).get();
        
        $("#totalRow").val(objArticle.length);
        $("#totalQTY").val(humanizeNumber(totalQty));
    }

    function listUom(obj,value,uom,uomSelect) {
        $.ajax({
        url:"{{ route('receiving.list.uom') }}",
        method:"GET",
        data:{
            value:value,
        },
        success:function(result){
            $('#'+obj).html(result);
            $('#'+obj).val(uomSelect).trigger('change');            
        },
        error: function (response) {
            Swal.fire("Warning","Get list UOM failed","warning");
        }
        })
    }

    function cekQty(){
        let objQtySo= $('#article_row input[name="qtySo[]"]');
        let objQtyDel= $('#article_row input[name="qtyInv[]"]');

        objQtyDel.keyup(function() {
            let indexnya= objQtyDel.index(this);
            let qtyDel = parseFloat(objQtyDel.eq(indexnya).val().replace(/,/gi, '') || 0);
            let qtySo = parseFloat(objQtySo.eq(indexnya).val().replace(/,/gi, '') || 0); 
            if ( qtyDel > qtySo ){
                objQtyDel.eq(indexnya).delay(3000).css("background-color","rgba(255,0,0, 0.5)");
            }else{
                objQtyDel.eq(indexnya).delay(3000).css("background-color","");
            }
            hitungGrandTotal();
        });    
    }

</script>