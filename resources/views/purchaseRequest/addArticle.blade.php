<style>
    #article_row .form-group {
        margin-bottom: 0.5rem;
    }
</style>
{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <div class="form-row d-flex align-items-center">
            <div class="col-md-6 col-12">
                <div class="form-group">
                    <label for="article_id" class="d-block d-md-none">Article Code</label>
                    <select class="form-control dynamicSelect" id="article_id" name="article_id[]" data-dependent="article_id">
                    </select>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group">
                    <label for="qty_order" class="d-block d-md-none">QTY</label>
                    {{-- <div class="input-group input-group-merge"> --}}
                        <input type="text" class="form-control numeral-mask-satuan text-right" id = "qty_order" name="qty_order[]" maxlength="9"/>
                        {{-- <div class="input-group-append">
                            <span class="input-group-text" id ="uom" name="uom[]"></span>
                        </div> --}}
                    {{-- </div> --}}
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group">
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
{{-- \.table row --}} 

<script type="text/javascript">
    let dataArticle=""; 
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

    function changeselectSto(dependent,obj,obj2,article) {
        $('#'+obj).attr('disabled','disabled');
        $('#'+obj).html(dataArticle);
        $('#'+obj).select2();
        $('#'+obj).val(article).trigger('change');
        $('#'+obj).removeAttr('disabled');
        $('#'+obj).select2('focus');
    }

    isiUom = () => {
        let objUom= $('#article_row span[name="uom[]"]'); 
        $("#article_row select[name='article_id[]']").map(function(i) {  
            let $this=$(this);
            if ($this.val()){
                let article=$this.find(":selected").data("detail").split('|');
                objUom.eq(i).text(article[1]);
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
                // let uomGroup = objArticle.eq(objIndex).find(":selected").data("uom-group");
                objUom.eq(objIndex).text(arrDetail[1]);
                if (detail){
                    setTimeout(() => {
                        objQty.eq(objIndex).focus().select();
                    }, 5);
                }

                // if ( uomGroup === 'PIECE' ){
                //     objQty.eq(objIndex).removeClass("numeral-mask-digit");
                //     objQty.eq(objIndex).addClass("numeral-mask-satuan");
                //     mask_thousand_satuan();
                // }else{
                //     objQty.eq(objIndex).removeClass("numeral-mask-satuan");
                //     objQty.eq(objIndex).addClass("numeral-mask-digit");
                //     mask_thousand_digit(numberOfDecimalDigit);
                // }
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

</script>