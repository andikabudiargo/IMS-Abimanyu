{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <div class="form-row d-flex align-items-center">
            <div class="col-md-5 col-12">
                <div class="form-group margin-nol">
                    <label for="articleId" class="d-block d-md-none">Article</label>
                    <select class="dynamicSelect form-control" id="articleId" name="articleId[]" data-dependent="articleId">
                    </select>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label for="pOrder" class="d-block d-md-none">Purchase Order</label>
                    <select class="dynamicSelect form-control" id="pOrder" name="pOrder[]" data-dependent="pOrder">
                    </select>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="remQTY" class="d-block d-md-none">QTY PO</label>
                    <input type="text" class="form-control numeral-mask-digit text-right" id = "remQTY" name="remQTY[]" maxlength="9" disabled/>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label for="qty" class="d-block d-md-none">QTY</label>
                    <div class="input-group input-group-merge">
                        <input type="text" class="form-control numeral-mask-digit text-right" id = "qty" name="qty[]" maxlength="9" />
                        <div class="input-group-append">
                            <span class="input-group-text" id ="uom" name="uom[]"></span>
                        </div>
                    </div>
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
            width:120%;
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
    const diDate = $('#diDate');
    const deliveryDate = $('#deliveryDate');
    let dataArticle="";
    
    if (diDate.length) {
        diDate.flatpickr({
            dateFormat: "d-m-Y",
        });
    }

    if (deliveryDate.length) {
        deliveryDate.flatpickr({
            dateFormat: "d-m-Y",
            minDate: currentDate
        });
    }

    let objsupplier = $('#supplier');
    objsupplier.change(function(e){
        let supplier=$(this).val();
        if (supplier){
            isiArticle(supplier);
        }
        
    });

    function isiArticle(supplier) {
        $.ajax({
            url:"{{route('deliveryInstruction.article.list')}}",
            method:"GET",
            data:{
                supplier:supplier
            },
            success:function(result){
                dataArticle = result;
            }
        })
    }
    

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
            let objQty= $('#article_row  input[name="qty[]"]');
            let objPorder= $('#article_row select[name="pOrder[]"]');
            let objUom= $('#article_row span[name="uom[]"]');
            let articles = []; 
            let flag=0; 
            let pesan="";

            $("#article_row select[name='articleId[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let plu=$this.val()
                    let articleName=$this.select2('data')[0].text;
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let uom=objUom.eq(i).text();
                    let po=objPorder.eq(i).val();
                    let key = plu+po;
                                
                    //es6
                    // let obj = ingredient.find(obj => obj.plu == plu);

                    //jquery
                    //cek apakah article ada yang double input ato ngk
                    let obj = $.grep(articles, function(obj){
                        return obj.kunci === key;
                    })[0];
                    
                    if(obj) {
                        pesan +=`Article ${articleName} on PO : ${po} entered more than once !! <br>`; 
                        flag=1;
                    } else {
                        if ((plu!=='') && (qty> 0)){
                            articles.push({
                                "kunci": key,
                                "article_code":plu,
                                "po_number":po,
                                "qty":qty,
                                "uom":uom
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
                let diNumber = $('#diNumber').val();
                let diDate = $('#diDate').val();
                let deliveryDate = $('#deliveryDate').val();
                let supplier = $('#supplier').val();
                let note = $('#note').val();
        
                $.ajax({
                    type: "post",
                    url: "{{ route('deliveryInstruction.store') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        diNumber:diNumber,
                        diDate:diDate,
                        deliveryDate:deliveryDate,
                        supplier:supplier,
                        note:note
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#diNumber').attr('disabled','disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#diNumber').attr('disabled','disabled');
                            $('#addNewRow').attr('disabled','disabled');
                            $('#diNumber').val(data.diNumber);
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
            let objpr= $('select[name="pOrder[]"]'); 
            let articles = []; 
            let flag=0; 
            let pesan="";
            
            $("#article_row select[name='articleId[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let article=$this.val().split("|");
                    let articleName=$this.select2('data')[0].text;
                    let plu=article[0];
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let newPrice=objNewPrice.eq(i).val().replace(/,/gi, '') || 0;
                    let price=objPrice.eq(i).val().replace(/,/gi, '') || 0;
                    let pOrder=objpr.eq(i).val();
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
                                "pOrder":pOrder
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
                let diDate = $('#diDate').val();
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
                let diNumber = $('#diNumber').val();
                let approveLevel = $('#approveLevel').val();
                let maxLevel = $('#maxLevel').val();
                let tax = $('#pkp').is(':checked') ? 'PKP' : '';

                $.ajax({
                    type: "post",
                    url: "{{ route('purchaseOrder.update') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        diNumber:diNumber,
                        diDate:diDate,
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
                            $('#diNumber').attr('disabled','disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#diNumber').attr('disabled','disabled');
                            $('#cmdApprove').attr('disabled','disabled');
                            $('#cmdUpdate').attr('disabled','disabled');
                            $('#addNewRow').attr('disabled','disabled');
                            $('#diNumber').val(data.diNumber);
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
        let diNumber = $('#diNumber').val();
        $.ajax({
            type: "get",
            url: "{{ route('purchaseOrder.decline') }}",
            data: {
                diNumber:diNumber,
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#diNumber').attr('disabled','disabled');
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#diNumber').attr('disabled','disabled');
                    $('#cmdApprove').attr('disabled','disabled');
                    $('#cmdUpdate').attr('disabled','disabled');
                    $('#cmdDecline').attr('disabled','disabled');
                    $('#addNewRow').attr('disabled','disabled');
                    $('#diNumber').val(data.diNumber);
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
        if (supp){
            $("#article_row").append($("#new_row").clone().html());
            cloneCount++;
            $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
            $("#new_row"+ cloneCount).find('#articleId').attr('id', 'articleId'+ cloneCount);
            // $("#new_row"+ cloneCount).find('#pOrder').attr('id', 'pOrder'+ cloneCount);
            changeselect('articleId'+ cloneCount,'');
            $("#articleId"+cloneCount).select2();
            // $("#pOrder"+cloneCount).select2();
            tombolPanah('qty');
            mask_thousand_digit(numberOfDecimalDigit);
            dataPo();
            splitArticle();
            // isiListArticle();
            // hitungTotal();
            // hitungGrandTotal();
            $('#remove_button').tooltip();
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
                    // supplier.select2('open');
                    supplier.select2('focus');
                }
            })
        }
    };

    // function isiListArticle(){
    //     // split article with delimiter |
    //     let objpOrder = $('#article_row select[name="pOrder[]"]');
    //     objpOrder.change(function(e){        
    //         let objIndex = objpOrder.index(this);
    //         let prNumber = objpOrder.eq(objIndex).val();
    //         let supp = $('#supplier').val();
    //         let poType = $('#poType').val();
    //         poType =='std' ? changeSelectArticle('searchFromPr',objIndex,supp,prNumber) : changeSelectArticle('searchFromPr_sub',objIndex,supp,prNumber);
    //         splitArticle();
	// 	});
    // }

    // function changeSelectArticle(dependent,objIndex,value,prNumber) {
    //     let objArticle = $('#article_row select[name="articleId[]"]');
    //     $.ajax({
    //         url:"{{route('dynamic.dependent')}}",
    //         method:"POST",
    //         data:{
    //             value:value,
    //             prNumber:prNumber,
    //             dependent:dependent
    //         },
    //         success:function(result){
    //             objArticle.eq(objIndex).html(result);
    //             objArticle.eq(objIndex).select2();
    //             // objArticle.eq(objIndex).trigger('change');
    //         }
    //     })
    // }

    function changeselect(obj,article) {
        $('#'+obj).attr('disabled','disabled');
        $('#'+obj).html(dataArticle);
        $('#'+obj).select2();
        $('#'+obj).val(article).trigger('change');
        $('#'+obj).removeAttr('disabled');
        $('#'+obj).select2('focus');
    }

    function splitArticle(){
        // split article with delimiter |
        let objArticle = $('#article_row select[name="articleId[]"]');
        let objPorder= $('#article_row select[name="pOrder[]"]');
        objArticle.change(function(e){   
            let objIndex = objArticle.index(this);
            let listPo = objArticle.eq(objIndex).find(":selected").data("list-po");
            let arrListPo = listPo.split("|");
            let option = `<option value="">Choose Po</option>`;
            for (let i=0;i<arrListPo.length;i++){
                option += `<option value="${arrListPo[i]}">${arrListPo[i]}</option>`;
            }
            objPorder.eq(objIndex).html(option);
            // objPorder.eq(objIndex).select2();
		});
    }

    function dataPo(){
        let objPo = $('#article_row select[name="pOrder[]"]');
        let objArt = $('#article_row select[name="articleId[]"]');
        objPo.change(function(e){   
            let objIndex = objPo.index(this);
            let valPO = objPo.eq(objIndex).val();
            let valArt = objArt.eq(objIndex).val();
            let objQty= $('#article_row input[name="qty[]"]');
            let objRemQTY= $('#article_row input[name="remQTY[]"]');
            
            let objUom= $('#article_row span[name="uom[]"]');
            $.ajax({
                url:"{{route('deliveryInstruction.qty.po')}}",
                method:"GET",
                data:{
                    valPO:valPO,
                    valArt:valArt
                },
                success:function(result){
                    objQty.eq(objIndex).val(result.data['qty']);
                    objRemQTY.eq(objIndex).val(result.data['remain_qty']);
                    objUom.eq(objIndex).text(result.data['uom']);
                },
                error: function(error) {
                    console.log(error);
                }
            })

        });
    }


    function changeSelectArticle(dependent,objIndex,value,prNumber) {
        let objArticle = $('#article_row select[name="articleId[]"]');
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
        let objArticle = $('#article_row select[name="articleId[]"]');
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