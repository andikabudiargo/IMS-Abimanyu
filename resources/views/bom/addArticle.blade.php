<style>    
    #article_row .form-group {
        margin-bottom: 0.5rem;
    }
</style>
{{-- table row untuk di clone--}}
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris barisDetail" >
        <div class="form-row d-flex align-items-center">
            <div class="col-md-6 col-12">
                <div class="form-group">
                    <label for="article_id" class="d-block d-md-none">Article Code</label>
                    <select class="dynamicSelect form-control" id="article_id" name="article_id[]" data-dependent="article_id">
                    </select>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label for="qty_stock" class="d-block d-md-none">QTY</label>
                    <input type="text" class="form-control numeral-mask-digit text-right tombol-panah" id ="qtyBom" name="qtyBom[]" maxlength="10" />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label for="uom" class="d-block d-md-none">Uom</label>
                    <select class="form-control" id="uom" name="uom[]">
                    </select>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label for="uomCon" class="d-block d-md-none">Uom Con.</label>
                    <select class="form-control" id="uomCon" name="uomCon[]">
                    </select>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group">
                    <label for="uom" class="d-block d-md-none">Type</label>
                    <span class="" id = "type" name="type[]"></span>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group text-center">
                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();">
                        <i data-feather="trash-2" class="remove_button feather-24">
                        </i>
                    </a>
                </div>
            </div>
        </div>
        <hr class="d-block d-md-none" />
    </div>
</div>
{{-- \.table row --}} 

<script type="text/javascript">
    let cloneCount=1;
    add_new_row_edit = (article,qty,uom,uomCon,typeName,uomMember,uoms) => {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
        changeselect('article_bom','article_id'+ cloneCount,article);
        $("#new_row"+ cloneCount).find('#qtyBom').attr('id', 'qtyBom'+ cloneCount);
        $("#qtyBom"+ cloneCount).val(qty);
        $("#new_row"+ cloneCount).find('#type').attr('id', 'type'+ cloneCount);
        $("#type"+ cloneCount).text(typeName);
        $("#article_id"+cloneCount).select2();

        let uomOption="";
        if (uoms){
            let arrUom = uomMember.split(',');
            $.each(arrUom, function(index, val) {
                uomOption +=`<option>${val}</option>`;
            });
        }else{
            if(uom){
                uomOption +=`<option>${uom}</option>`;
            }
        }
        
        let uomOptionCon="";
        if (uomMember){
            let arrUomMember = uomMember.split(',');
            $.each(arrUomMember, function(index, val) {
                uomOptionCon +=`<option>${val}</option>`;
            });
        }else{
            if(uom){
                uomOptionCon +=`<option>${uomCon}</option>`;
            }
        }

        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        $("#uom"+ cloneCount).html(uomOption);
        $("#uom"+ cloneCount).val(uom).trigger('change');
        $("#new_row"+ cloneCount).find('#uomCon').attr('id', 'uomCon'+ cloneCount);
        $("#uomCon"+ cloneCount).html(uomOptionCon);
        $("#uomCon"+ cloneCount).val(uom).trigger('change');
        $('#remove_button').tooltip();
        tombolPanah('qtyBom');
        mask_thousand_digit(numberOfDecimalDigit);
    }
    add_new_row = () => {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
        $("#new_row"+ cloneCount).find('#qtyBom').attr('id', 'qtyBom'+ cloneCount);
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        changeselect('article_bom','article_id'+ cloneCount);
        $("#article_id"+cloneCount).select2();
        $("#uom"+cloneCount).select2();
        $('#remove_button').tooltip();
        tombolPanah('qtyBom');
        splitArticle('new');
        mask_thousand_digit(numberOfDecimalDigit);
    };
    splitArticle = () => {
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objType= $('#article_row span[name="type[]"]'); 
        let objQty = $('#article_row input[name="qtyBom[]"]');
        let objUom = $('#article_row select[name="uom[]"]');
        let objUomCon = $('#article_row select[name="uomCon[]"]');
        
        objArticle.change(function(e){        
            let objIndex = objArticle.index(this);
            let article = objArticle.eq(objIndex).val();
            let detail="";
            if (article){
                detail = objArticle.eq(objIndex).find(":selected").data("detail");
                // 1000576|PCS||CM2|CONSUMABLE
            }
            let arrDetail = detail.split("|");
            let idUom = objUom.eq(objIndex).attr('id');        
            listUom(idUom,'',arrDetail[1]);
            objType.eq(objIndex).text(arrDetail[4]);

            let uomGroup = objArticle.eq(objIndex).find(":selected").data("uom-group");
            let uomMember = objArticle.eq(objIndex).find(":selected").data("uom-member");
            let uomOption="";
            if (uomMember){
                let arrUomMember = uomMember.split(',');
                $.each(arrUomMember, function(index, val) {
                    uomOption +=`<option>${val}</option>`;
                });
            }else{
                if(arrDetail[1]){
                    uomOption +=`<option>${arrDetail[1]}</option>`;
                }
            }
            objUomCon.eq(objIndex).html(uomOption);
            objUomCon.eq(objIndex).val(arrDetail[1]).trigger('change');
        
            if (detail){
                setTimeout(() => {
                    objQty.eq(objIndex).focus().select();
                }, 5);
            }
		});
    }
    listUom = (obj,value,uom) => {
        $.ajax({
        url:"{{ route('receiving.list.uom') }}",
        method:"GET",
        data:{
            value:value,
        },
        success:function(result){
            $('#'+obj).html(result);
            $('#'+obj).select2();
            $('#'+obj).val(uom).trigger('change');            
        },
        error: function (response) {
            Swal.fire("Warning","Get list UOM failed","warning");
        }
        })
    }
    changeselect = (dependent,obj,article) => {
        $('#'+obj).attr('disabled','disabled');
        $.ajax({
            url:"{{route('dynamic.dependent')}}",
            method:"POST",
            data:{
                dependent:dependent
            },
            success:function(result){
                $('#'+obj).html(result);
                $('#'+obj).val(article).trigger('change');
                $('#'+obj).removeAttr('disabled');
            }
        })
    }
    saveData = (oEdit) => {
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{
            $('.disabled-el').removeAttr('disabled');
            let objArticle = $("#article_row select[name='article_id[]']");
            let objQty = $('#article_row input[name="qtyBom[]"]');
            let objUom = $('#article_row select[name="uom[]"]');
            let objUomCon = $('#article_row select[name="uomCon[]"]');
            
            if (oEdit){
                articleCode = $('#articleCode').data('article-code');
                articleCodeRm = $('#articleCodeRm').data('article-code');
                uomHdr = $('#uomHdr').val();
                customer = $('#customer').data('customer-code');
                group = $('#group').data('group');
            }else{
                articleCode = $('#articleCode').val();
                articleCodeRm = $('#articleCodeRm').val();
                articleCode1 = $('#articleCode').find(":selected").data("detail").split("|");
                uomHdr = articleCode1[1];
                customer  = articleCode1[4];
                group = articleCode1[5];
            }
            
            let tag = $('#tag').val().replace(/,/gi, '') || 0;
            let passRate = $('#passRate').val().replace(/,/gi, '') || 0;
            let passThru = $('#passThru').val().replace(/,/gi, '') || 0;
            let cycleTime = $('#cycleTime').val().replace(/,/gi, '') || 0;
            let note = $('#note').val();
            let arrArticles = []; 
            let articles;
            let flag=0; 
            let pesan="";

            objArticle.map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let articleName=$this.select2('data')[0].text;
                    let plu=$this.val();
                    let uom=objUom.eq(i).val();
                    let uomCon=objUomCon.eq(i).val();
                    let detail = $this.find(":selected").data("detail").split("|");
                    let type=detail[3];
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    // let obj = articles.find(obj => obj.plu == plu);
                    // if(obj) {
                    //     pesan +="Article "+articleName+" entered more than once !! <br>"; 
                    //     flag=1;
                    // } else {
                        if ((plu!=='') && (qty> 0)){
                            arrArticles.push({
                                "article_code":plu,
                                "qty":parseFloat(qty),
                                "uom":uom,
                                "uom_con":uomCon,
                                "customer_code":customer,
                                "type":type
                            });
                        }
                    // } 
                
                    if (qty == 0){
                        pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
                        flag=1;
                    }
                }
            });
            
            if (customer == ''){
                pesan +="Customer must be filled in <br>"; 
                flag=1;
            }

            if (arrArticles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }else{
                //summary sata by article_code
                let obj = {}
                arrArticles.forEach((item)=>{
                    if(obj[item.article_code]){
                        obj[item.article_code].qty = obj[item.article_code].qty + item.qty
                    }else{
                        obj[item.article_code] = item
                    }
                })
                articles = Object.values(obj)
            }

            if (flag==0){
                let bomNumber = "";
                let url ="";
                if (oEdit){
                    bomNumber = $('#bomNumber').val();
                    url ="{{ route('bom.update') }}";
                }else{
                    url ="{{ route('bom.store') }}";
                }
                
                $.ajax({
                    type: "POST",
                    url: url,
                    data: {
                        articles:JSON.stringify(articles),
                        articleCode:articleCode,
                        articleCodeRm:articleCodeRm,
                        customer:customer,
                        note:note,
                        group:group,
                        uom:uomHdr,
                        tag:tag,
                        passRate:passRate,
                        passThru:passThru,
                        cycleTime:cycleTime,
                        bomNumber:bomNumber
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            let message="";
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#bomNumber').attr('disabled','disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#bomNumber,#customer,#group,#uomHdr,#articleCode,#articleCodeRm').attr('disabled','disabled');
                            $('#bomNumber').val(data.bomNumber);
                            $('#oEdit').val(data.oEdit);
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
    approve = (bomNumber) => {
        $.ajax({
            type: "GET",
            url: "{{ route('bom.approve') }}",
            data: {
                bomNumber:bomNumber
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#bomNumber').attr('disabled','disabled');
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#bomNumber').attr('disabled','disabled');
                    $('#cmdApprove').attr('disabled','disabled');
                    $('#addNewRow').attr('disabled','disabled');      
                    window.location.reload();                 
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
    }
    $("#cmdCancel,#cmdNew").click(function() {
        $('#bomNumber').val('');
        window.location.reload();
    });
    $("#articleCode").change(function() {
        let $this = $(this);
        let detail = $this.find(":selected").data("detail").split("|");
        $('#uomHdr').val(detail[1]);
        $('#customer').val(detail[2]);
        $('#customer').attr('data-customer-code', detail[4]);
        $('#group').val(detail[5]);
        $('#group').attr('data-group', detail[3]);
    })

</script>