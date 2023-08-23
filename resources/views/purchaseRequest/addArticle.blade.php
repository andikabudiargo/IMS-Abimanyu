<style>
    #article_row .form-group {
        margin-bottom: 0.5rem;
    }
</style>
{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <div class="form-row d-flex align-items-center">
            <div class="col-md-5 col-12">
                <div class="form-group">
                    <label for="article_id" class="d-block d-md-none">Article Code</label>
                    <select class="form-control dynamicSelect article-count" id="article_id" name="article_id[]" data-dependent="article_id" >
                    </select>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label for="qtyStock" class="d-block d-md-none">QTY Stock</label>
                    <input type="text" class="form-control numeral-mask-digit text-right disabled-el" id = "qtyStock" name="qtyStock[]" disabled/>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group">
                    <label for="qty_order" class="d-block d-md-none">QTY</label>
                    <input type="text" class="form-control numeral-mask-digit text-right tombol-panah" id = "qty_order" name="qty_order[]" maxlength="9"/>
                    <input type="hidden" class="form-control" id = "qtyHitung" name="qtyHitung[]" />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group div-span-ku">
                    <label for="note" class="d-block d-md-none">Uom</label>
                    <span class="form-control" id ="uom" name="uom[]"></span>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group">
                    <label for="note" class="d-block d-md-none">Note</label>
                    <input type="text" class="form-control" id = "note" name="note[]"  maxlength="100">
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group">
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

<style>
    /* .div-span-ku{
        display: inline-block;
        overflow: hidden;
        white-space: nowrap;
    } */
</style>
{{-- \.table row --}} 

<script type="text/javascript">
    let orderDate = $('#orderDate');
    let stockDate = $('#stockDate');
    let oDept = $('#dept');
    let objPoType = $('#poType');
    let objTsoBox = $('#tsoBox');
    let objTsoCode = $('#tsoCode');
    let addNewRow = $('#addNewRow');
    let dataArticle="";
    let cloneCount=0;
    

    function compareQty(){
        if (objPoType.val() == 'tso'){
            let objQtyOrder= $('#article_row input[name="qty_order[]"]');
            let objQtyHitung= $('#article_row input[name="qtyHitung[]"]');
                    
            objQtyOrder.keyup(function() {
                let indexnya= objQtyOrder.index(this);
                let qtyOrder = parseFloat(objQtyOrder.eq(indexnya).val().replace(/,/gi, '') || 0); 
                let qtyHitung = parseFloat(objQtyHitung.eq(indexnya).val().replace(/,/gi, '') || 0); 
                if ( qtyOrder > qtyHitung ){
                    objQtyOrder.eq(indexnya).delay(3000).css("background-color","rgba(255,0,0, 0.5)");
                }else{
                    objQtyOrder.eq(indexnya).delay(3000).css("background-color","");
                }
            });
        }
    }

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

    function changeSelectSto(dependent,obj,obj2,article) {
        $('#'+obj).attr('disabled','disabled');
        $('#'+obj).html(dataArticle);
        $('#'+obj).select2();
        $('#'+obj).val(article).trigger('change');
        $('#'+obj).removeAttr('disabled');
        $('#'+obj).select2('focus');
    }

    function changeSelectFromSto(obj,article,articleCode) {
        $('#'+obj).attr('disabled','disabled');
        $('#'+obj).html(article);
        $('#'+obj).select2();
        $('#'+obj).val(articleCode).trigger('change');
        $('#'+obj).removeAttr('disabled');
        $('#'+obj).select2('focus');
    }

    isiUom = () => {
        let objUom= $('#article_row span[name="uom[]"]');
        let objQty= $('#article_row input[name="qty_order[]"]'); 
        $("#article_row select[name='article_id[]']").map(function(i) {  
            let $this=$(this);
            if ($this.val()){
                let article = $this.find(":selected").data("detail").split('|');
                let uomGroup = $this.find(":selected").data("uom-group");
                objUom.eq(i).text(article[1]);
                if ( uomGroup === 'PIECE' ){
                    objQty.eq(i).removeClass("numeral-mask-digit");
                    objQty.eq(i).addClass("numeral-mask-satuan");
                    mask_thousand_satuan();
                }else{
                    objQty.eq(i).removeClass("numeral-mask-satuan");
                    objQty.eq(i).addClass("numeral-mask-digit");
                    mask_thousand_digit(numberOfDecimalDigit);
                }
            }
        })
    }

    function splitArticle(){
        // split article with delimiter |
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objUom= $('#article_row span[name="uom[]"]'); 
        let objQty= $('#article_row input[name="qty_order[]"]'); 
        objArticle.change(function(e){    
            let $this=$(this);
            if ($this.val()){
                let objIndex = objArticle.index(this);
                let detail = objArticle.eq(objIndex).find(":selected").data("detail");
                let arrDetail = detail.split("|");
                let uomGroup = objArticle.eq(objIndex).find(":selected").data("uom-group");
                objUom.eq(objIndex).text(arrDetail[1]);
                if (detail){
                    setTimeout(() => {
                        objQty.eq(objIndex).focus().select();
                    }, 5);
                }

                if ( uomGroup === 'PIECE' ){
                    objQty.eq(objIndex).removeClass("numeral-mask-digit");
                    objQty.eq(objIndex).addClass("numeral-mask-satuan");
                    mask_thousand_satuan();
                }else{
                    objQty.eq(objIndex).removeClass("numeral-mask-satuan");
                    objQty.eq(objIndex).addClass("numeral-mask-digit");
                    mask_thousand_digit(numberOfDecimalDigit);
                }
            }
		});
    }

    function changeselect(dependent,obj,value){
        changeSelect({
            dependent:dependent,
            obj:obj,
            value:value,
            url:"{{ route('dynamic.dependent') }}"            
        });
    }

    add_new_row_sto = (articleCode,qty,uom,note,qtyStock,alternative,desc,uomGroup,supp) => {
        let poType = $('#poType').val();
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
        $("#new_row"+ cloneCount).find('#qty_order').attr('id', 'qty_order'+ cloneCount);
        $("#new_row"+ cloneCount).find('#qtyHitung').attr('id', 'qtyHitung'+ cloneCount);
        $("#new_row"+ cloneCount).find('#qtyStock').attr('id', 'qtyStock'+ cloneCount);
        $("#new_row"+ cloneCount).find('#note').attr('id', 'note'+ cloneCount);
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        // $output .='<option value="'.$row->article_code.'" data-detail="'.$row->article_code.'|'.$row->uom.'|'.$row->third_party.'|'.$row->dept.'" data-uom-group="'.$row->uom_group.'">'.$row->article_alternative_code.' - '. $row->article_desc.'</option>';
        let articleList =`<option value="${articleCode}" data-detail="${articleCode}|${uom}|${supp}" data-uom-group="${uomGroup}">${alternative} - ${desc}</option>`;
        changeSelectFromSto('article_id'+ cloneCount,articleList,articleCode);
        // changeSelectSto('article_pr','article_id'+ cloneCount,'uom'+ cloneCount,articleCode);
        $('#qty_order'+ cloneCount).val(qty);
        $('#qtyHitung'+ cloneCount).val(qty);
        $('#qtyStock'+ cloneCount).val(qtyStock);
        $('#note'+ cloneCount).val(note);
        $('#article_id'+ cloneCount).attr('disabled','disabled');
        $('#remove_button').tooltip();
        // tombolPanah('qty_order');
        mask_thousand_digit(numberOfDecimalDigit);
        compareQty();
        recordCount();
    };

    add_new_row_edit = (articleCode,qty,uom,uomGroup,note,qtyStock,qtyHitung,alternative,desc,supp) => {
        let poType = $('#poType').val();
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
        $("#new_row"+ cloneCount).find('#qty_order').attr('id', 'qty_order'+ cloneCount);
        $("#new_row"+ cloneCount).find('#qtyHitung').attr('id', 'qtyHitung'+ cloneCount);
        $("#new_row"+ cloneCount).find('#qtyStock').attr('id', 'qtyStock'+ cloneCount);
        $("#new_row"+ cloneCount).find('#note').attr('id', 'note'+ cloneCount);
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        // changeSelectSto('article_pr','article_id'+ cloneCount,'uom'+ cloneCount,articleCode);
        let articleList =`<option value="${articleCode}" data-detail="${articleCode}|${uom}|${supp}" data-uom-group="${uomGroup}">${alternative} - ${desc}</option>`;
        changeSelectFromSto('article_id'+ cloneCount,articleList,articleCode);
        $('#qty_order'+ cloneCount).val(qty);
        $('#qtyHitung'+ cloneCount).val(qtyHitung);
        $('#qtyStock'+ cloneCount).val(qtyStock);
        $('#note'+ cloneCount).val(note);
        $('#uom'+ cloneCount).text(uom);
        $('#article_id'+ cloneCount).attr('disabled','disabled');
        $('#qtyStock'+ cloneCount).attr('disabled','disabled');
        // objPoType.val() ==='tso' ? $('#qty_order'+ cloneCount).attr('disabled','disabled'):'';
        $("#article_id"+cloneCount).select2();
        $('#remove_button').tooltip();        
        
        if ( uomGroup === 'PIECE' ){
            $('#qty_order'+ cloneCount).removeClass("numeral-mask-digit");
            $('#qty_order'+ cloneCount).addClass("numeral-mask-satuan");
            mask_thousand_satuan();
        }else{
            $('#qty_order'+ cloneCount).removeClass("numeral-mask-satuan");
            $('#qty_order'+ cloneCount).addClass("numeral-mask-digit");
            mask_thousand_digit(numberOfDecimalDigit);
        }

        // tombolPanah('qty_order');
        recordCount();
        compareQty();
    };
    
    add_new_row = () => {
        let poType = $('#poType').val();
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
        let depentName;
        switch(poType) {
        case 'std':
            depentName = 'article_pr';
            break;
        case 'sub':
            depentName = 'article_pr_sub';
            break;
        case 'tso':
            depentName = 'article_pr';
            break;
        case 'rm':
            depentName = 'article_pr_rm';
            break;
        default:
            depentName = 'article_pr';
        } 
        changeselect(depentName,'article_id'+ cloneCount);
        $("#article_id"+cloneCount).select2();
        $('#remove_button').tooltip();
        // tombolPanah('qty_order');
        splitArticle();
        recordCount();
    };

    recordCount = () =>{
        let records = $('.article-count').length-1;
        $('#records').text(records);
    }

</script>