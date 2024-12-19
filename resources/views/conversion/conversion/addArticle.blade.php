<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <div class="form-row d-flex align-items-center">
            <div class="col-md-2 col-12" style="max-width: 12.66667%;">
                <div class="form-group margin-nol">
                    <label for="aDnNumber" class="d-block d-md-none">DnNumber</label>
                    <input type="text" class="form-control disabled-el" id = "aDnNumber" name="aDnNumber[]" readonly/>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label for="aCustomerName" class="d-block d-md-none">Customer Code</label>
                    <input type="text" class="form-control disabled-el" id = "aCustomerName" name="aCustomerName[]" 
                    data-toggle="tooltip" 
                    data-placement="bottom" 
                    title=""
                    readonly
                    />
                </div>
                <input type="hidden" class="form-control disabled-el" id = "aCustomerCode" name="aCustomerCode[]"/>
            </div>
            <div class="col-md-4 col-12"> 
                <div class="form-group margin-nol">
                    <label for="aArticleDescription" class="d-block d-md-none">Article Code</label>
                    <input type="text" class="form-control disabled-el" id = "aArticleDescription" name="aArticleDescription[]" 
                    data-toggle="tooltip" 
                    data-placement="bottom" 
                    title=""
                    readonly
                    />
                    <input type="hidden" class="form-control disabled-el" id = "aArticleCode" name="aArticleCode[]"/>
                </div>
            </div>
            <div class="col-md-2 col-12" style="max-width: 10.66667%;padding-right:2px;padding-left:2px;">
                <div class="form-group margin-nol">
                    <label for="aPurchasePrice" class="d-block d-md-none">Purchase Price</label>
                    <input type="text" class="form-control numeral-mask-digit text-right" id = "aPurchasePrice" name="aPurchasePrice[]" maxlength="9"/>
                </div>
            </div>
            <div class="col-md-2 col-12" style="max-width: 10.66667%;padding-right:2px;padding-left:2px;">
                <div class="form-group margin-nol">
                    <label for="aSellingPrice" class="d-block d-md-none">Selling Price</label>
                    <input type="text" class="form-control numeral-mask-digit text-right" id = "aSellingPrice" name="aSellingPrice[]" maxlength="9"/>
                </div>
            </div>
            <div class="col-md-2 col-12" style="max-width: 10.66667%;padding-right:2px;padding-left:2px;">
                <div class="form-group margin-nol">
                    <label for="aConversion" class="d-block d-md-none">Conversion</label>
                    <input type="text" class="form-control text-right disabled-el enable-tooltip" id = "aConversion" name="aConversion[]" 
                    data-toggle="tooltip" 
                    data-placement="bottom" 
                    title="Selling Price – Purchase Price / Nilai Konversi"
                    readonly/>
                </div>
            </div>
            <div class="col-md-1 col-12" style="max-width: 3%;">
                <div class="form-group margin-nol">
                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();disabledEnabledSelect2();">
                        <i data-feather="trash-2" class="remove_button feather-24">
                        </i>
                    </a>
                </div>
            </div>
        </div>
        <hr class="d-block d-md-none" />
    </div>
</div>
<style>
   
    textarea {
        resize: none;
    }

    .margin-nol{
        margin-bottom:0.5rem;
    }

    @media screen 
    and (min-device-width: 1200px) 
    and (max-device-width: 1600px) 
    and (-webkit-min-device-pixel-ratio: 1) { 
        .lebar-list-item{
            width:125%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
    }

    @media only screen and (min-width: 600px)
    and (max-width: 1200px)
    {
        .lebar-list-item{
            width:200%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
    }
</style>

<script type="text/javascript">

    let dataArticle="";
    let cloneCount=0;
    let conversionValue = "{{ $conversionVal }}";
    conversionValue = parseFloat(conversionValue);
    
    function isiArticle(dependent) {
        $.ajax({
            url:"{{route('dynamic.dependent')}}",
            method:"POST",
            data:{
                dependent:dependent
            },
            success:function(result){
                dataArticle = result;
            }
        })
    }

    function changeselect(dependent,obj,value){
        changeSelect({
            dependent:dependent,
            obj:obj,
            value:value,
            url:"{{ route('dynamic.dependent') }}"            
        });
    }

    add_new_row = (deliveryNumber,customerCode,customerName,articleCode,articleDescription, purchasePrice, sellingPrice,conversionTotal) => {
        cloneCount++;
        $("#item_row").append($("#new_row").clone().html());
        $("#item_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#aDnNumber').attr('id', 'aDnNumber'+ cloneCount);
        $("#new_row"+ cloneCount).find('#aCustomerName').attr('id', 'aCustomerName'+ cloneCount);
        $("#new_row"+ cloneCount).find('#aCustomerCode').attr('id', 'aCustomerCode'+ cloneCount);
        $("#new_row"+ cloneCount).find('#aArticleDescription').attr('id', 'aArticleDescription'+ cloneCount);
        $("#new_row"+ cloneCount).find('#aArticleCode').attr('id', 'aArticleCode'+ cloneCount);
        $("#new_row"+ cloneCount).find('#aPurchasePrice').attr('id', 'aPurchasePrice'+ cloneCount);
        $("#new_row"+ cloneCount).find('#aSellingPrice').attr('id', 'aSellingPrice'+ cloneCount);
        $("#new_row"+ cloneCount).find('#aConversion').attr('id', 'aConversion'+ cloneCount);
        $("#aDnNumber"+ cloneCount).val(deliveryNumber);
        $("#aCustomerCode"+ cloneCount).val(customerCode);
        $("#aCustomerName"+ cloneCount).val(customerName);
        $("#aCustomerName"+ cloneCount).attr('title',customerName);
        $("#aArticleCode"+ cloneCount).val(articleCode);
        $("#aArticleDescription"+ cloneCount).val(articleDescription);
        $("#aArticleDescription"+ cloneCount).attr('title',articleDescription);
        if(inEdit == 'true'){
            $("#aPurchasePrice"+ cloneCount).val(purchasePrice);
            $("#aSellingPrice"+ cloneCount).val(sellingPrice);
            $("#aConversion"+ cloneCount).val(conversionTotal);
            hitungGrandTotal();
        }
        if(inShow == 'true'){
            $("#aPurchasePrice"+ cloneCount).attr('readonly',true);
            $("#aSellingPrice"+ cloneCount).attr('readonly',true);
        }
        $("#aCustomerName"+ cloneCount).tooltip();
        $("#aArticleDescription"+ cloneCount).tooltip();
        $("#aConversion"+ cloneCount).tooltip();
        mask_thousand_digit(numberOfDecimalDigit);
        hitungTotal();
    };
    
    function hitungTotal(){
        let objPurchasePrice = $('#item_row input[name="aPurchasePrice[]"]');
        let objSellligPrice = $('#item_row input[name="aSellingPrice[]"]')
        let objConversion = $('#item_row input[name="aConversion[]"]')

        let sellingPrice = 0;
        let purchasePrice = 0;
        let conversion = 0;

        objPurchasePrice.keyup(function() {
            let theIndex = objPurchasePrice.index(this);
            
            sellingPrice = parseFloat(objSellligPrice.eq(theIndex).val().replace(/,/gi, '')) || 0;
            purchasePrice = parseFloat(objPurchasePrice.eq(theIndex).val().replace(/,/gi, '')) || 0;
            conversion = (sellingPrice - purchasePrice)/conversionValue
            objConversion.eq(theIndex).val(conversion)
            hitungGrandTotal();
        });    

        objSellligPrice.keyup(function() {
            let theIndex = objSellligPrice.index(this);
            sellingPrice = parseFloat(objSellligPrice.eq(theIndex).val().replace(/,/gi, '')) || 0;
            purchasePrice = parseFloat(objPurchasePrice.eq(theIndex).val().replace(/,/gi, '')) || 0;
            conversion = (sellingPrice - purchasePrice)/conversionValue
            objConversion.eq(theIndex).val(conversion)
            hitungGrandTotal();
        });    
    }

    function hitungGrandTotal(){
        let objConversion = $('#item_row input[name="aConversion[]"]')
        let totalConversion=0;
        let arr = objConversion.map(function (i) {
            totalConversion += parseFloat(objConversion.eq(i).val().replace(/,/gi, '')) || 0;
        }).get();
        
        $("#totalConversion").val(totalConversion);
    }

    recordCount = () =>{
        let records = $('.article-count').length-1;
        $('#records').text(records);
    }

</script>