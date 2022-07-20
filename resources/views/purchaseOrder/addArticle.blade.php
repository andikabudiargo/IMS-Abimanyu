{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <div class="form-row d-flex align-items-center">
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label for="pRequest" class="d-block d-md-none">Purchase Request</label>
                    <select class="dynamicSelect form-control" id="pRequest" name="pRequest[]" data-dependent="pRequest">
                    </select>
                </div>
            </div>
            <div class="col-md-3 col-12">
                <div class="form-group margin-nol">
                    <label for="article_id" class="d-block d-md-none">Article</label>
                    <select class="dynamicSelect form-control" id="article_id" name="article_id[]" data-dependent="article_id">
                    </select>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qty_stock" class="d-block d-md-none">Stock</label>
                    <input type="text" class="form-control numeral-mask-satuan text-right" id = "qty_stock" name="qty_stock[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qty_order" class="d-block d-md-none">QTY Order</label>
                    <div class="input-group input-group-merge">
                        <input type="text" class="form-control numeral-mask-satuan text-right" id = "qty_order" name="qty_order[]" maxlength="9" />
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
            <div class="col-md-2 col-12">
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
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label for="totalLine" class="d-block d-md-none">Total</label>
                    <input type="text" class="form-control numeral-mask text-right" id="totalLine" name="totalLine[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal();">
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
                    <label for="pRequestShow" class="d-block d-md-none">Purchase Request</label>
                    <input type="text" class="form-control" id="pRequestShow" name="pRequestShow[]" disabled>
                </div>
            </div>
            <div class="col-md-3 col-12">
                <div class="form-group margin-nol">
                    <label for="article_id" class="d-block d-md-none">Article</label>
                    <input type="text" class="form-control" id="article_idShow" name="article_idShow[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qty_stockShow" class="d-block d-md-none">Stock</label>
                    <input type="text" class="form-control numeral-mask-satuan text-right" id = "qty_stockShow" name="qty_stockShow[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qty_orderShow" class="d-block d-md-none">QTY Order</label>
                    <div class="input-group input-group-merge">
                        <input type="text" class="form-control numeral-mask-satuan text-right" id = "qty_orderShow" name="qty_orderShow[]" maxlength="9" />
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
            width:150%;
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
    
    if (orderDate.length) {
        orderDate.flatpickr({
            dateFormat: "d-m-Y",
        });
    }

    if (deliveryDate.length) {
        deliveryDate.flatpickr({
            dateFormat: "d-m-Y",
            minDate: currentDate
        });
    }

    $('#pkp').change(function() {
        if ($(this).is(':checked')) {
            $('#ppn').val("{{ $vatValue }}");
            $("#nilaiPPN").text("{{ $vatValue }}%");
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

    $("#cmdCancel").click(function(){
        reloadPage();
    });

    $("#cmdNew").click(function(){
        reloadPage();
    });

    simpanData = () => {
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{ 
            $('.disabled-el').removeAttr('disabled');
            // ambil semua data article
            let objQty= $('input[name="qty_order[]"]');
            let objPrice= $('input[name="price[]"]');
            let objNewPrice= $('input[name="newPrice[]"]');
            let objUom= $('span[name="uom[]"]'); 
            let objpr= $('select[name="pRequest[]"]'); 
            let articles = []; 
            let flag=0; 
            let pesan="";

            $("#article_row select[name='article_id[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let article=$this.val().split("|");
                    let articleName=$this.select2('data')[0].text;
                    let plu=article[0];
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
                        return obj.article_code === plu;
                    })[0];
                    
                    if(obj) {
                        pesan +="Article "+articleName+" entered more than once !! <br>"; 
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
                let persenDiscount = $('#persenDiscount').val() || 0;
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
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#poNumber').attr('disabled','disabled');
                            $('#addNewRow').attr('disabled','disabled');
                            $('#cmdSave').attr('disabled','disabled');
                            $('#poNumber').val(data.poNumber);
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
            // ambil semua data article
            let objQty= $('input[name="qty_order[]"]');
            let objPrice= $('input[name="price[]"]');
            let objNewPrice= $('input[name="newPrice[]"]');
            let objUom= $('span[name="uom[]"]'); 
            let objpr= $('select[name="pRequest[]"]'); 
            let articles = []; 
            let flag=0; 
            let pesan="";
            
            $("#article_row select[name='article_id[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let article=$this.val().split("|");
                    let articleName=$this.select2('data')[0].text;
                    let plu=article[0];
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
                        return obj.article_code === plu;
                    })[0];
                    
                    if(obj) {
                        pesan +="Article "+plu+" entered more than once !! <br>"; 
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
                let persenDiscount = $('#persenDiscount').val() || 0;
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
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#poNumber').attr('disabled','disabled');
                            $('#cmdApprove').attr('disabled','disabled');
                            $('#cmdUpdate').attr('disabled','disabled');
                            $('#addNewRow').attr('disabled','disabled');
                            $('#poNumber').val(data.poNumber);
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

    function add_new_row() {
        let supplier = $('#supplier');
        let supp = supplier.val();
        let poType = $('#poType').val();
        if (supp){            
            $("#article_row").append($("#new_row").clone().html());
            cloneCount++;
            $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
            $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
            $("#new_row"+ cloneCount).find('#pRequest').attr('id', 'pRequest'+ cloneCount);
            poType =='std' ? changeselect('pRequest','pRequest'+ cloneCount,supp,'') : changeselect('pRequest_sub','pRequest'+ cloneCount,supp,'');
            // changeselect('pRequest','pRequest'+ cloneCount,supp,'');
            $("#article_id"+cloneCount).select2();
            $("#pRequest"+cloneCount).select2();
            $('#remove_button').tooltip();
            tombolPanah('qty_order');
            tombolPanah('newPrice');
            activate_angka();
            mask_thousand();
            // splitArticle();
            isiListArticle();
            hitungTotal();
            hitungGrandTotal();
            $('[data-toggle="tooltip"]').tooltip();
        }else{
            Swal.fire({
                title: 'Warning',
                text: "Choose supplier",
                icon: 'warning',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    supplier.select2('open');
                }
            })
        }
    };

    function isiListArticle(){
        // split article with delimiter |
        let objPrequest = $('#article_row select[name="pRequest[]"]');
        objPrequest.change(function(e){        
            let objIndex = objPrequest.index(this);
            let prNumber = objPrequest.eq(objIndex).val();
            let supp = $('#supplier').val();
            let poType = $('#poType').val();
            poType =='std' ? changeSelectArticle('searchFromPr',objIndex,supp,prNumber) : changeSelectArticle('searchFromPr_sub',objIndex,supp,prNumber);
            splitArticle();
		});
    }

    function changeSelectArticle(dependent,objIndex,value,prNumber) {
        let objArticle = $('#article_row select[name="article_id[]"]');
        $.ajax({
            url:"{{route('dynamic.dependent')}}",
            method:"POST",
            data:{
                value:value,
                prNumber:prNumber,
                dependent:dependent
            },
            success:function(result){
                objArticle.eq(objIndex).html(result);
                objArticle.eq(objIndex).select2();
                // objArticle.eq(objIndex).trigger('change');
            }
        })
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

    function listPrice(article,desc,indexNya){
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
                            <a href='javascript:;' type="button" class='btn btn-outline-primary btn-block btn-sm waves-effect text-right' onclick="definePrice('${indexNya}','${data[i].price}');">${humanizeNumber(data[i].price)}</a>
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

    function splitArticle(){
        // split article with delimiter |
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objStock= $('#article_row input[name="qty_stock[]"]');
        let objUom= $('#article_row span[name="uom[]"]'); 
        let objQty= $('#article_row input[name="qty_order[]"]');
        let objPrice= $('#article_row input[name="price[]"]');
        let objNewPrice= $('#article_row input[name="newPrice[]"]');
        let objListPrice= $('#article_row a[name="listPrice[]"]');
        let objTotal= $('#article_row input[name="totalLine[]"]');
        objArticle.change(function(e){   
            //     0            1           2         3       4        5             6
            // article_code.'|'group.'|'qty_stock.'|'qty.'|'uom1.'|'costprice.'|'last_price.'"
            let objIndex = objArticle.index(this);
            let detail = objArticle.eq(objIndex).val();
            let detailText = objArticle.eq(objIndex).select2('data')[0].text;
            let arrDetail = detail.split("|");
            let uomGroup = objArticle.eq(objIndex).find(":selected").data("uom-group");

            objListPrice.eq(objIndex).attr("onClick", `listPrice('${arrDetail[0]}','${detailText}','${objIndex}');`);
            objStock.eq(objIndex).val(humanizeNumber(arrDetail[2]||0));
            objUom.eq(objIndex).text(arrDetail[4]);
            objQty.eq(objIndex).val(humanizeNumber(arrDetail[3]||0));
            objPrice.eq(objIndex).val(humanizeNumber(arrDetail[5]||0));
            objNewPrice.eq(objIndex).val(humanizeNumber(arrDetail[6]||0));
            objArticle.eq(objIndex).select2('open');
            if (detail){
                setTimeout(() => {
                    objQty.eq(objIndex).focus().select();
                }, 5);
            }

            objTotal.eq(objIndex).val(humanizeNumber((arrDetail[3]||0)*(arrDetail[6]||0)));
            hitungGrandTotal();

            if ( uomGroup === 'PIECE' ){
                objQty.eq(objIndex).removeClass("numeral-mask-digit");
                objQty.eq(objIndex).addClass("numeral-mask-satuan");
                mask_thousand_satuan();
            }else{
                objQty.eq(objIndex).removeClass("numeral-mask-satuan");
                objQty.eq(objIndex).addClass("numeral-mask-digit");
                mask_thousand_digit(numberOfDecimalDigit);
            }
		});
    }

    definePrice = (indexNya,hargaNya) =>{
        $('#article_row input[name="newPrice[]"]').eq(indexNya).val(humanizeNumber(hargaNya||0));
        $("#modalListPrice").modal('hide');
    }
  
    function hitungTotal(){
        let objQty= $('#article_row input[name="qty_order[]"]');
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
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objQtyTiw= $('#article_row input[name="qty_order[]"]');
        let objQTY= $('#article_row input[name="qty_order[]"]');
        let objNewPrice= $('#article_row input[name="newPrice[]"]');
        let persenDiscount = $('#persenDiscount').val() || 0;
        let ppn= $('#ppn').val();
        let totalQty= 0;
        let totalAmount=0

        let qty = objQTY.map(function(){return $(this).val();}).get();
        let price = objNewPrice.map(function(){return $(this).val();}).get();
        
        totalQty = sumFromArray(qty);
        totalAmount = sumFromArray(qty,price);

        $("#totalRow").val(objArticle.length);
        $("#nilaiPPN").text(ppn+"%");
        $("#totalQTY").val(humanizeNumber(totalQty));
        $("#totalAmount").val(humanizeNumber(totalAmount));
        $("#totalDiscount").val(humanizeNumber((totalAmount*parseInt(persenDiscount))/100));
        $("#totalPPN").val(humanizeNumber((parseInt(ppn)*totalAmount)/100));
        $("#totalPPH").val(0);
        $("#totalNetto").val(humanizeNumber((totalAmount+((parseInt(ppn)*totalAmount)/100))-((totalAmount*parseInt(persenDiscount))/100)));

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