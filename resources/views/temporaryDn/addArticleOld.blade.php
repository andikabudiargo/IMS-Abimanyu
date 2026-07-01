<style>
    #article_row .form-group {
        margin-bottom: 0.5rem;
    }
</style>
{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <div class="form-row d-flex align-items-center">
            <div class="col-md-8 col-12">
                <div class="form-group">
                    <label for="articleCode" class="d-block d-md-none">Article Code</label>
                    <select class="form-control article-count" id="articleCode" name="articleCode[]">
                    </select>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group">
                    <label for="qtyOrder" class="d-block d-md-none">QTY</label>
                    <input type="text" class="form-control numeral-mask text-right" id = "qtyOrder" name="qtyOrder[]" maxlength="9"/>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group div-span-ku">
                    <label for="uom" class="d-block d-md-none">Uom</label>
                    <span class="form-control" id ="uom" name="uom[]"></span>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group">
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
    /* .div-span-ku{
        display: inline-block;
        overflow: hidden;
        white-space: nowrap;
    } */
</style>
{{-- \.table row --}} 

<script type="text/javascript">
    let deliveryDate = $('#deliveryDate');
    let cloneCount=0;
    let dataArticle="";
    let objCustomer = $('#cust');

    if (deliveryDate.length) {
        deliveryDate.flatpickr({
            dateFormat: "d-m-Y",
        });
    }
   
    $(document).on('change', '.article-count', function(e){
        let objArticle = $('#article_row select[name="articleCode[]"]');
        let objUom= $('#article_row span[name="uom[]"]'); 
        let $this=$(this);
        if ($this.val()){
            let objIndex = objArticle.index(this);
            let uom = objArticle.eq(objIndex).find(":selected").data("uom");
            objUom.eq(objIndex).text(uom);
            disabledEnabledSelect2();
        }
    });

    add_new_row = () => {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#articleCode').attr('id', 'articleCode'+ cloneCount);
        $("#new_row"+ cloneCount).find('#qtyOrder').attr('id', 'qtyOrder'+ cloneCount);
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        $('#articleCode'+cloneCount).html(dataArticle);
        $("#articleCode"+cloneCount).select2();
        $('#remove_button').tooltip();
        tombolPanah('qtyOrder');
        mask_thousand();
        recordCount();
        disabledEnabledSelect2();
    };

    $('#cust').on('change', function() {
        let custCode = $(this).val()
        $.ajax({
            url:"{{ route('suratJalanSementara.get.article') }}",
            method:"POST",
            data:{
                custCode:custCode,
            },
            success:function(result){
                dataArticle =result
            }
        })
    });

    function disabledEnabledSelect2(){
        let records = $('.article-count').length-1;
        if (records > 0){
            objCustomer.attr('disabled','disabled');
        }else{
            objCustomer.removeAttr('disabled');
        }        
    }

    add_new_row_edit = (articleCode,qty,uom) => {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#articleCode').attr('id', 'articleCode'+ cloneCount);
        $("#new_row"+ cloneCount).find('#qtyOrder').attr('id', 'qtyOrder'+ cloneCount);
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);   
        changeselectEdit('articleCode','articleCode'+ cloneCount,articleCode) 
        $('#qtyOrder'+ cloneCount).val(qty);
        $('#uom'+ cloneCount).text(uom);
        $('#articleCode'+ cloneCount).attr('disabled','disabled');
        $("#articleCode"+cloneCount).select2();
        $('#remove_button').tooltip();        
        tombolPanah('qtyOrder');
        mask_thousand();
        recordCount();
        disabledEnabledSelect2();
    };

    function changeselectEdit(dependent,obj,article) {
        $('#'+obj).attr('disabled','disabled');
        $('#'+obj).html(dataArticle);
        $('#'+obj).select2();
        $('#'+obj).val(article).trigger('change');
        $('#'+obj).removeAttr('disabled');
    }
    
    recordCount = () =>{
        let records = $('.article-count').length-1;
        $('#records').text(records);
    }

</script>