{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <div class="form-row d-flex align-items-center">
            <div class="col-md-2 col-12" style="max-width: 10.66667%;padding-right:2px;">
                <div class="form-group margin-nol">
                    <label for="prNumber" class="d-block d-md-none">PR Number</label>
                    <input type="text" class="form-control disabled-el" id = "prNumber" name="prNumber[]"  disabled style="font-size:0.8rem;padding-right: 0.4rem;padding-bottom: 0.438rem;padding-left: 0.4rem;" />
                </div>
            </div>
            <div class="col-md-5 col-12" style="max-width: 38.66667%;padding-right:2px;padding-left:2px;">
                <div class="form-group margin-nol">
                    <label for="articleDesc" class="d-block d-md-none">Article</label>
                    <input type="text" class="form-control disabled-el" id = "articleDesc" name="articleDesc[]" data-toggle="tooltip" data-placement="top" title="" style="font-size:0.9rem" />
                    <input type="hidden" class="form-control disabled-el" id = "articleId" name="articleId[]">
                    <input type="hidden" class="form-control disabled-el" id = "pRequest" name="pRequest[]">
                </div>
            </div>
            <div class="col-md-1 col-12" style="padding-right:2px;padding-left:2px;">
                <div class="form-group margin-nol">
                    <label for="qtyStock" class="d-block d-md-none">Stock</label>
                    <input type="text" class="form-control numeral-mask-satuan text-right" id = "qtyStock" name="qtyStock[]" disabled />
                </div>
            </div>
            <div class="col-md-2 col-12" style="max-width: 10.66667%;padding-right:2px;padding-left:2px;">
                <div class="form-group margin-nol">
                    <label for="qtyOrder" class="d-block d-md-none">QTY</label>
                    <div class="input-group input-group-merge">
                        <input type="text" class="form-control numeral-mask-satuan text-right" id = "qtyOrder" name="qtyOrder[]" maxlength="9" />
                        <div class="input-group-append">
                            <span class="input-group-text" id ="uom" name="uom[]"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-1 col-12 d-none">
                <div class="form-group margin-nol">
                    <label for="price" class="d-block d-md-none">Price</label>
                    <input type="text" class="form-control numeral-mask text-right" id= "price" name="price[]"  maxlength="11">
                </div>
            </div>
            <div class="col-md-2 col-12" style="max-width: 12.66667%;padding-right:2px;padding-left:2px;">
                <div class="form-group margin-nol">
                    <label for="price" class="d-block d-md-none">Price</label>
                    <div class="input-group input-group-merge">
                        <input type="text" class="form-control numeral-mask text-right" id = "newPrice" name="newPrice[]"  maxlength="11">
                        <div class="input-group-append">
                            <span class="input-group-text cursor-pointer">
                                <a onmouseover="this.style.cursor='pointer'" id="listPrice" name="listPrice[]" data-toggle="tooltip" data-placement="right" title="List Price">
                                    <i data-feather="info" class="feather-24">
                                    </i>
                                </a>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-12" style="max-width: 10.66667%;padding-right:2px;padding-left:2px;">
                <div class="form-group margin-nol">
                    <label for="totalLine" class="d-block d-md-none">Total</label>
                    <input type="text" class="form-control numeral-mask text-right" id="totalLine" name="totalLine[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12" style="max-width: 3%;">
                <div class="form-group margin-nol">
                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal();disabledEnabledSelect2();">
                        <i data-feather="trash-2" class="remove_button feather-24">
                        </i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- \.table row --}} 

<div id="new_row_show" name="new_row_show[]" class="d-none">
    <div id="baru_show">
        <div class="form-row d-flex align-items-center">
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label for="prNumber" class="d-block d-md-none">PR Number</label>
                    <input type="text" class="form-control" id="prNumber" name="prNumber[]" disabled>
                </div>
            </div>
            <div class="col-md-3 col-12">
                <div class="form-group margin-nol">
                    <label for="articleIdShow" class="d-block d-md-none">Article</label>
                    <input type="text" class="form-control" id="articleIdShow" name="articleIdShow[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qtyStockShow" class="d-block d-md-none">Stock</label>
                    <input type="text" class="form-control numeral-mask-satuan text-right" id = "qtyStockShow" name="qtyStockShow[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qtyOrderShow" class="d-block d-md-none">QTY Order</label>
                    <div class="input-group input-group-merge">
                        <input type="text" class="form-control numeral-mask-satuan text-right" id = "qtyOrderShow" name="qtyOrderShow[]" maxlength="9" />
                        <div class="input-group-append">
                            <span class="input-group-text" id ="uomShow" name="uomShow[]"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-1 col-12 d-none">
                <div class="form-group margin-nol">
                    <label for="priceShow" class="d-block d-md-none">Price</label>
                    <input type="text" class="form-control numeral-mask text-right" id= "priceShow" name="priceShow[]"  maxlength="11">
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label for="totalLineShow" class="d-block d-md-none">Total</label>
                    <input type="text" class="form-control numeral-mask text-right" id="totalLineShow" name="totalLineShow[]" disabled>
                </div>
            </div>
        </div>
    </div>
</div>

<style>

    textarea {
        resize: none;
    }

    .mb-03{
        margin-bottom: 0.3rem;
    }
    
    label.titik-dua::after{
        content : ":"; 
        position : absolute;
        right : 1px;
    }

    .margin-nol{
        margin-bottom:0.5rem;
    }

    .pointer-link {
        cursor: pointer;
        color: #33548a;
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
    const currentDate = "{{ $currentDateValue }}";
    const orderDate = $('#orderDate');
    const deliveryDate = $('#deliveryDate');
    const objPrRequest = $('#article_row input[name="pRequest[]"]');
    const prSelect = $('#prSelect');
    const objSupplier = $('#supplier');
    const defaultPph23 = "{{ $pph23Value }}";
    const defaultPpn = "{{ $vatValue }}";
    $("#nilaiPPN").text("{{ $vatValue }}%");
    $("#nilaiPPH").text("{{ $pph23Value }}%");
   
    if (orderDate.length) {
        orderDate.flatpickr({
            dateFormat: "d-m-Y",
        });
    }

    if (deliveryDate.length) {
        deliveryDate.flatpickr({
            dateFormat: "d-m-Y",
            // minDate: currentDate
        });
    }

    $('#pkp').change(function() {
        if ($(this).is(':checked')) {
            $('#ppn').val("{{ $vatValue }}");
            $('#ppn').removeAttr('disabled');
            hitungGrandTotal();
        }else{
            $('#ppn').val(0);
            $('#ppn').attr('disabled','disabled');
            hitungGrandTotal();
        }
    });
    
    $('#persenDiscount,#ppn').on('keyup', function() {
        hitungGrandTotal();
    })

    function reloadPage(){
        window.location.reload();
    }

    $("#cmdNew").click(function(){
        reloadPage();
    });

    simpanData = () => {
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{ 
            $('.disabled-el').removeAttr('disabled');
            // ambil semua data article
            let objQty= $('input[name="qtyOrder[]"]');
            let objArticleDesc = $('input[name="articleDesc[]"]');
            let objPrice= $('input[name="price[]"]');
            let objNewPrice= $('input[name="newPrice[]"]');
            let objUom= $('span[name="uom[]"]'); 
            let objpr= $('input[name="pRequest[]"]'); 
            let articles = []; 
            let flag=0; 
            let pesan="";

            $("#article_row input[name='articleId[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let plu=$this.val();
                    let articleName=objArticleDesc.eq(i).val();
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let newPrice=objNewPrice.eq(i).val().replace(/,/gi, '') || 0;
                    let price=objPrice.eq(i).val().replace(/,/gi, '') || 0;
                    let pRequest=objpr.eq(i).val();
                    let uom=objUom.eq(i).text();
                    let supp=$('#supplier').val();
                    let suppName = $('#supplier').select2('data')[0].text;
                    let supplier=supp;
                
                    //es6
                    // let obj = ingredient.find(obj => obj.plu == plu);

                    //jquery
                    //cek apakah article ada yang double input ato ngk
                    let obj = $.grep(articles, function(obj){
                        return obj.article_code+obj.pRequest === plu+pRequest;
                    })[0];
                    
                    if(obj) {
                        pesan +=`Article ${plu} - PR:${pRequest} entered more than once !! <br>`;
                        flag=1;
                    } else {
                        if ((plu!=='') && (qty> 0)){
                            articles.push({
                                "article_code":plu,
                                "qty":qty,
                                "uom":uom,
                                "price":price,
                                "newPrice":newPrice,
                                "pRequest":pRequest
                            });
                        }
                    } 
                
                    if (qty == 0){
                        pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
                        flag=1;
                    }
                
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){

                let orderDate = $('#orderDate').val();
                let poType = $('#poType').val();
                let deliveryDate = $('#deliveryDate').val();
                let currency = $('#currency').val();
                let supp = $('#supplier').val();
                let term = $('#term').val() || 0;
                let kurs = $('#kurs').val() || 1;
                let ppn = $('#ppn').val().replace(/,/gi, '') || 0;
                let totalPph = $('#totalPPH').val().replace(/,/gi, '') || 0;
                let totalPpn = $('#totalPPN').val().replace(/,/gi, '') || 0;
                let note = $('#note').val();
                let persenDiscount = $('#persenDiscount').val().replace(/,/gi, '') || 0;
                let tax = $('#pkp').is(':checked') ? 'PKP' : '';
                let poNumber = $('#poNumber').val();

                $.ajax({
                    type: "post",
                    url: "{{ route('purchaseOrder.store') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        poNumber:poNumber,
                        orderDate:orderDate,
                        poType:poType,
                        deliveryDate:deliveryDate,
                        currency:currency,                
                        supplier:supp,
                        tax:tax,
                        ppn:ppn,
                        term:term,
                        totalPph:totalPph,
                        totalPpn:totalPpn,
                        kurs:kurs,
                        note:note,
                        discount:persenDiscount
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#poNumber').attr('disabled','disabled');
                            $('.disabled-el').attr('disabled','disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#poNumber').attr('disabled','disabled');
                            $('#addNewRow').attr('disabled','disabled');
                            $('#cmdSave').attr('disabled','disabled');
                            $('#poNumber').val(data.poNumber);
                            $('.disabled-el').attr('disabled','disabled');
                            reloadPage();
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });

            }else{
                Swal.fire('Warning..',pesan,'warning');
            }
        }
    }

    updateData = (statusSimpan) =>{
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{  
            $('.disabled-el').removeAttr('disabled');
            let objQty= $('input[name="qtyOrder[]"]');
            let objArticleDesc = $('input[name="articleDesc[]"]');
            let objPrice= $('input[name="price[]"]');
            let objNewPrice= $('input[name="newPrice[]"]');
            let objUom= $('span[name="uom[]"]'); 
            let objpr= $('input[name="pRequest[]"]'); 
            let articles = []; 
            let flag=0; 
            let pesan="";
            
            $("#article_row input[name='articleId[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let plu=$this.val();
                    let articleName=objArticleDesc.eq(i).val();
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let newPrice=objNewPrice.eq(i).val().replace(/,/gi, '') || 0;
                    let price=objPrice.eq(i).val().replace(/,/gi, '') || 0;
                    let pRequest=objpr.eq(i).val();
                    let uom=objUom.eq(i).text();
                    let supp=$('#supplier').val();
                    let suppName = $('#supplier').select2('data')[0].text;
                    let supplier=supp;
                
                    //es6
                    // let obj = ingredient.find(obj => obj.plu == plu);

                    //jquery
                    //cek apakah article ada yang double input ato ngk
                    let obj = $.grep(articles, function(obj){
                        return obj.article_code+obj.pRequest === plu+pRequest;
                    })[0];
                    
                    if(obj) {
                        pesan +=`Article ${plu} - PR:${pRequest} entered more than once !! <br>`; 
                        flag=1;
                    } else {
                        if ((plu!=='') && (qty> 0)){
                            articles.push({
                                "article_code":plu,
                                "qty":qty,
                                "uom":uom,
                                "price":price,
                                "newPrice":newPrice,
                                "pRequest":pRequest
                            });
                        }
                    } 
                
                    if (qty == 0){
                        pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
                        flag=1;
                    }
                
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){
                let orderDate = $('#orderDate').val();
                let poType = $('#poType').val();
                let deliveryDate = $('#deliveryDate').val();
                let currency = $('#currency').val();
                let supp = $('#supplier').val();
                let term = $('#term').val()||0;
                let kurs = $('#kurs').val()||1;
                let ppn = $('#ppn').val().replace(/,/gi, '') || 0;
                let totalPph = $('#totalPPH').val().replace(/,/gi, '') || 0;
                let totalPpn = $('#totalPPN').val().replace(/,/gi, '') || 0;
                let note = $('#note').val();
                let persenDiscount = $('#persenDiscount').val().replace(/,/gi, '') || 0;
                let poNumber = $('#poNumber').val();
                let approveLevel = $('#approveLevel').val();
                let maxLevel = $('#maxLevel').val();
                let tax = $('#pkp').is(':checked') ? 'PKP' : '';

                $.ajax({
                    type: "post",
                    url: "{{ route('purchaseOrder.update') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        poNumber:poNumber,
                        orderDate:orderDate,
                        poType:poType,
                        deliveryDate:deliveryDate,
                        currency:currency,                
                        supplier:supp,
                        tax:tax,
                        ppn:ppn,
                        term:term,
                        totalPph:totalPph,
                        totalPpn:totalPpn,
                        kurs:kurs,
                        note:note,
                        discount:persenDiscount,
                        statusSimpan:statusSimpan,
                        approveLevel:approveLevel,
                        maxLevel:maxLevel
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#poNumber').attr('disabled','disabled');
                            $('.disabled-el').attr('disabled','disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#poNumber').attr('disabled','disabled');
                            $('#cmdApprove').attr('disabled','disabled');
                            $('#cmdUpdate').attr('disabled','disabled');
                            $('#addNewRow').attr('disabled','disabled');
                            $('#poNumber').val(data.poNumber);
                            $('.disabled-el').attr('disabled','disabled');
                            window.location.reload();
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });

            }else{
                Swal.fire('Warning..',pesan,'warning');
            }
        }
    }

    declineData = () =>{
        let poNumber = $('#poNumber').val();
        $.ajax({
            type: "get",
            url: "{{ route('purchaseOrder.decline') }}",
            data: {
                poNumber:poNumber,
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#poNumber').attr('disabled','disabled');
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#poNumber').attr('disabled','disabled');
                    $('#cmdApprove').attr('disabled','disabled');
                    $('#cmdUpdate').attr('disabled','disabled');
                    $('#cmdDecline').attr('disabled','disabled');
                    $('#addNewRow').attr('disabled','disabled');
                    $('#poNumber').val(data.poNumber);
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
    }
 
    function add_new_row_pr(articleCode,articleDesc,group,qtyStock,qty,uom,uomGroup,costPrice,lastPrice,prNumber) {       
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#articleId').attr('id', 'articleId'+ cloneCount);
        $("#new_row"+ cloneCount).find('#articleDesc').attr('id', 'articleDesc'+ cloneCount);
        $("#new_row"+ cloneCount).find('#qtyStock').attr('id', 'qtyStock'+ cloneCount);
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        $("#new_row"+ cloneCount).find('#qtyOrder').attr('id', 'qtyOrder'+ cloneCount);
        $("#new_row"+ cloneCount).find('#price').attr('id', 'price'+ cloneCount);
        $("#new_row"+ cloneCount).find('#newPrice').attr('id', 'newPrice'+ cloneCount);
        $("#new_row"+ cloneCount).find('#listPrice').attr('id', 'listPrice'+ cloneCount);
        $("#new_row"+ cloneCount).find('#totalLine').attr('id', 'totalLine'+ cloneCount);
        $("#new_row"+ cloneCount).find('#pRequest').attr('id', 'pRequest'+ cloneCount);
        $("#new_row"+ cloneCount).find('#prNumber').attr('id', 'prNumber'+ cloneCount);

        $("#articleId"+cloneCount).val(articleCode);
        $("#articleDesc"+cloneCount).val(articleDesc);
        $("#articleDesc"+cloneCount).attr('title',articleDesc);
        $("#qtyStock"+cloneCount).val(qtyStock*1);
        $("#uom"+cloneCount).text(uom);
        $("#qtyOrder"+cloneCount).val(qty);
        $("#price"+cloneCount).val(costPrice);
        $("#newPrice"+cloneCount).val(lastPrice);
        $("#totalLine"+cloneCount).val(qty*lastPrice);
        $("#pRequest"+cloneCount).val(prNumber);
        $("#prNumber"+cloneCount).val(prNumber);

        let idNya = 'newPrice'+ cloneCount;
        $("#listPrice"+cloneCount).attr("onClick", `listPrice('${articleCode}','${articleDesc}','${idNya}');`);

        if ( uomGroup === 'PIECE' ){
            $("#qtyOrder"+cloneCount).removeClass("numeral-mask-digit");
            $("#qtyOrder"+cloneCount).addClass("numeral-mask-satuan");
            mask_thousand_satuan();
        }else{
            $("#qtyOrder"+cloneCount).removeClass("numeral-mask-satuan");
            $("#qtyOrder"+cloneCount).addClass("numeral-mask-digit");
            mask_thousand_digit(2);
            // mask_thousand_digit(numberOfDecimalDigit);
        }

        tombolPanah('qtyOrder');
        tombolPanah('newPrice');
        hitungTotal();
        hitungGrandTotal();
        mask_thousand();
        $('#remove_button').tooltip();
        $('[data-toggle="tooltip"]').tooltip();
        $('.disabled-el').attr('disabled','disabled');
    };

    function changeSelectPr(suppCode,prNumber) {
        if (prNumber){
            $.ajax({
                url:"{{route('purchaseOrder.listArticle.pr')}}",
                method:"GET",
                data:{
                    suppCode:suppCode,
                    prNumber:prNumber
                },
                success:function(result){
                    for(let i=0;i<result.data.length;i++){
                        // tampilkan kalo qty nya lebih dari 0
                        if (result.data[i].qty*1 > 0){
                            add_new_row_pr(result.data[i].artikel_code,result.data[i].article_description,result.data[i].group,result.data[i].qty_stock,result.data[i].qty*1,result.data[i].uom1,result.data[i].uom_group,result.data[i].cost_price,result.data[i].last_price,prNumber);
                        }
                    }
                    disabledEnabledSelect2();
                    $('#prSelect').val("").trigger("change");
                }
            });
        }
    }

    function disabledEnabledSelect2(){
        let arrValueSelected = $("#article_row input[name='pRequest[]']").map(function(){return $(this).val();}).get();
        arrValueSelected = Array.from(new Set(arrValueSelected));
        prSelect.find("option").removeAttr('disabled',true).trigger("chosen:updated");
        arrValueSelected.forEach((key, index) => {
            prSelect.find("option[value='" + key + "']").attr('disabled',true).trigger("chosen:updated");
        });
        
        if (arrValueSelected.length > 0){
            objSupplier.attr('disabled','disabled');
        }else{
            objSupplier.removeAttr('disabled');
        }
    }

    function changeselect(dependent,obj,value,type) {
      $.ajax({
        url:"{{route('dynamic.dependent')}}",
        method:"POST",
        data:{
            value:value,
            type:type,
            dependent:dependent
        },
        success:function(result){
            $('#'+obj).html(result);
            // $('#'+obj).val('').trigger('change');
        }
      })
    }

    function listPrice(article,desc,idNya){
        $("#modalTableData tbody> tr").remove();
        $.ajax({
            dataType: 'json',
            type:'GET',
            url: "{{ route('purchaseOrder.price.list') }}",
            data: { article:article },
            success: function(data) {
                if(data.length > 0 ){
                    let html = '';
                    for(let i=0;i<data.length;i++){
                        html += `<tr>
                        <td>${i+1}</td>
                        <td>${data[i].po_number}</td>
                        <td>${data[i].po_date}</td>
                        <td class="text-right">
                            <a href='javascript:;' type="button" class='btn btn-outline-primary btn-block btn-sm waves-effect text-right' onclick="definePrice('${idNya}','${data[i].price}');">${humanizeNumber(data[i].price)}</a>
                        </td>
                        </tr>`;
                    }
                    $('#modalTableData tbody').append(html);
                }                
            },
            error: function(data) {
                swal.fire("Warning","Error data","warning");
            }
        });
        $('#modalArticle').text(desc);
        $('#modalListPrice').modal('show'); 
    }

    definePrice = (idNya,hargaNya) =>{
        $('#'+idNya).val(humanizeNumber(hargaNya||0));
        $("#modalListPrice").modal('hide');
    }
  
    function hitungTotal(){
        let objQty= $('#article_row input[name="qtyOrder[]"]');
        let objNewPrice= $('#article_row input[name="newPrice[]"]');
        let objTotal= $('#article_row input[name="totalLine[]"]');
        
        objQty.keyup(function() {
            let indexnya= objQty.index(this);
            let qty = objQty.eq(indexnya).val().replace(/,/gi, '') || 0; 
            let newPrice = objNewPrice.eq(indexnya).val().replace(/,/gi, '') ||0;
            let total = qty*newPrice;
            objTotal.eq(indexnya).val(humanizeNumber(total));
            hitungGrandTotal();
        });    

        objNewPrice.keyup(function() {
            let indexnya= objNewPrice.index(this);
            let qty = objQty.eq(indexnya).val().replace(/,/gi, '') || 0; 
            let newPrice = objNewPrice.eq(indexnya).val().replace(/,/gi, '')||0;
            let total = qty*newPrice;
            objTotal.eq(indexnya).val(humanizeNumber(total));
            hitungGrandTotal();
        });    
    }

    function hitungGrandTotal(){
        let objArticle = $('#article_row select[name="articleId[]"]');
        let objPrNumber = $('#article_row input[name="prNumber[]"]');
        let objQtyTiw= $('#article_row input[name="qtyOrder[]"]');
        let objQTY= $('#article_row input[name="qtyOrder[]"]');
        let objNewPrice= $('#article_row input[name="newPrice[]"]');
        let persenDiscount = $('#persenDiscount').val().replace(/,/gi, '') || 0;
        let ppn= $('#ppn').val();
        let pph23= $('#pph23').val();
        let totalQty= 0;
        let totalAmount=0

        let qty = objQTY.map(function(){return $(this).val();}).get();
        let price = objNewPrice.map(function(){return $(this).val();}).get();
        
        totalQty = sumFromArray(qty);
        totalAmount = sumFromArray(qty,price);

        // mask_thousand_digit(2);

        let nilaiPph23 = parseFloat((parseFloat(pph23)*(totalAmount-((totalAmount*parseFloat(persenDiscount))/100))))/100;
        let nilaiPpn = parseFloat((parseFloat(ppn)*(totalAmount-((totalAmount*parseFloat(persenDiscount))/100))))/100;
        let nilaiDisc = (totalAmount*parseFloat(persenDiscount))/100;

        $("#totalRow").val(objPrNumber.length);
        // $("#nilaiPPN").text(ppn+"%");
        $("#totalQTY").val(totalQty);
        $("#totalAmount").val(humanizeNumber(totalAmount.toFixed(2)));
        $("#totalDiscount").val(nilaiDisc ?humanizeNumber(nilaiDisc.toFixed(2)):0);
        $("#totalDpp").val(humanizeNumber((totalAmount-((totalAmount*parseFloat(persenDiscount))/100)).toFixed(2)));
        $("#totalPPN").val(nilaiPpn ?humanizeNumber(nilaiPpn.toFixed(2)):0 );
        $("#totalPPH").val(nilaiPph23 ?humanizeNumber(nilaiPph23.toFixed(2)):0 );
        $("#totalNetto").val(humanizeNumber(((totalAmount-((totalAmount*parseFloat(persenDiscount))/100))+(parseFloat((parseFloat(ppn)*(totalAmount-((totalAmount*parseFloat(persenDiscount))/100))))/100)-(parseFloat((parseFloat(pph23)*(totalAmount-((totalAmount*parseFloat(persenDiscount))/100))))/100)).toFixed(2)));
        
    }

    $("input[type='text']").click(function () {
        $(this).select();
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>