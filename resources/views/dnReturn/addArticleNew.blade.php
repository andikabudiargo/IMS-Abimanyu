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
                    <label for="articleCode" class="d-block d-md-none">Article Code</label>
                    <select class="form-control article-count" id="articleCode" name="articleCode[]">
                    </select>
                </div>
            </div>
            <div class="col-md-2 col-12">
    <div class="form-group margin-nol">
        <label class="d-block d-md-none">Qty Delivery</label>
        <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit"
               name="qtyDelivery[]" readonly tabindex="-1" />
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
    let returnDate = $('#returnDate');
    let cloneCount=0;
    let dataArticle="";
    let objCustomer = $('#cust');

    if (returnDate.length) {
        returnDate.flatpickr({
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

        // ── isi Qty Delivery (akumulasi qty terkirim) ──
        let qtyDelivered = $this.find(":selected").data("qty-delivered") || 0;
        let row = $this.closest('.tanda-baris');
        row.find('input[name="qtyDelivery[]"]').val(humanizeNumber(qtyDelivered));
        // ───────────────────────────────────────────────

        disabledEnabledSelect2();
    }
});

    $(document).on('input', '#article_row input[name="qtyOrder[]"]', function(){
    let row = $(this).closest('.tanda-baris');           // sesuaikan class baris Anda
    let maxQty = parseFloat(row.find('select[name="articleCode[]"] :selected').data('qty-delivered')) || 0;
    let val = parseFloat(($(this).val()||'0').replace(/,/g,'')) || 0;
    if (val > maxQty){
        $(this).css('background-color','rgba(255,0,0,0.5)');
        show_msg('Warning','Qty retur melebihi qty terkirim ('+maxQty+')','warning');
    } else {
        $(this).css('background-color','');
    }
});

    add_new_row = () => {
    $("#article_row").append($("#new_row").clone().html());
    cloneCount++;
    $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
    $("#new_row"+ cloneCount).find('#articleCode').attr('id', 'articleCode'+ cloneCount);  // rename DULU
    $('#articleCode'+ cloneCount).html(articleOptions);                                     // isi options
    $("#articleCode"+ cloneCount).select2();                                                // baru select2
    $("#new_row"+ cloneCount).find('#qtyOrder').attr('id', 'qtyOrder'+ cloneCount);
    $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
    $('#remove_button').tooltip();
    tombolPanah('qtyOrder');
    mask_thousand();
    recordCount();
    disabledEnabledSelect2();
};

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

    let articleOptions = '<option value="">Choose article</option>';

// Customer berubah → load SO
$('#cust').on('change', function(){
    let cust = $(this).val();
    $('#soNumber').html('<option value=""></option>').trigger('change');
    $('#article_row').empty();
    if (cust){
        $.ajax({
            url: "{{ route('dnReturn.list.so') }}",
            method: "GET",
            data: { value: cust },
            success: function(res){ $('#soNumber').html(res).trigger('change'); },
            error: function(){ Swal.fire('Warning','Gagal ambil daftar SO','warning'); }
        });
    }
});

// SO berubah → load article (dengan max qty terkirim)
$('#soNumber').on('change', function(){
    let so = $(this).val();
    $('#article_row').empty();
    if (so){
        $.ajax({
            url: "{{ route('dnReturn.article.bySo') }}",
            method: "GET",
            data: { value: so },
            dataType: "json",
            success: function(res){ articleOptions = res.options; },
            error: function(){ Swal.fire('Warning','Gagal ambil article SO','warning'); }
        });
    } else {
        articleOptions = '<option value="">Choose article</option>';
    }
});

</script>