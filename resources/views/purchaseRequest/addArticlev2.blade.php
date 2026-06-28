<style>
    #article_row .form-group {
        margin-bottom: 0.5rem;
    }
</style>
{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris">
        <div class="form-row d-flex align-items-center">
            <div class="col-md-5 col-12 art-col">
                <div class="form-group">
                    <label for="article_id" class="d-block d-md-none">Article Code</label>
                    <select class="form-control dynamicSelect article-count" id="article_id" name="article_id[]" data-dependent="article_id"></select>
                </div>
            </div>
            <div class="col-md-1 col-12 stock-col">
                <div class="form-group">
                    <label for="qtyStock" class="d-block d-md-none">QTY Stock</label>
                    <input type="text" class="form-control text-right disabled-el" id="qtyStock" name="qtyStock[]" disabled/>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group">
                    <label for="qty_order" class="d-block d-md-none">QTY</label>
                    <input type="text" class="form-control numeral-mask-digit text-right tombol-panah" id="qty_order" name="qty_order[]" maxlength="9"/>
                    <input type="hidden" class="form-control" id="qtyHitung" name="qtyHitung[]"/>
                </div>
            </div>
            <div class="col-md-1 col-12 uom-col">
                <div class="form-group div-span-ku">
                    <label for="uom" class="d-block d-md-none">Uom</label>
                    <select class="form-control uom-select" id="uom" name="uom[]"></select>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group">
                    <label for="note" class="d-block d-md-none">Note</label>
                    <input type="text" class="form-control" id="note" name="note[]" maxlength="100">
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove(); recordCount();">
                        <i data-feather="trash-2" class="remove_button feather-24"></i>
                    </a>
                </div>
            </div>
        </div>
        <hr class="d-block d-md-none"/>
    </div>
</div>
{{-- \.table row --}}

<script type="text/javascript">
    let orderDate   = $('#orderDate');
    let stockDate   = $('#stockDate');
    let mdlStockDate = $('#mdlStockDate');
    let oDept       = $('#dept');
    let objPoType   = $('#poType');
    let objTsoBox   = $('#tsoBox');
    let objTsoCode  = $('#tsoCode');
    let addNewRow   = $('#addNewRow');
    let suppBox     = $('#suppBox');
    let suppCode    = $('#suppCode');
    let dataArticle = "";
    let cloneCount  = 0;

    // ====== Data dari controller ======
    const uomConByArticle = @json($uomConByArticle ?? new \stdClass);
    const stockByArticle  = @json($stockByArticle ?? new \stdClass);

    // ====== Helpers ======
    function fmtStock(v){
        let n = parseFloat(v);
        if (isNaN(n)) n = 0;
        let s = parseFloat(n.toFixed(4)).toString();
        let parts = s.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }

    function fillStock($input, articleCode){
        let v = (stockByArticle && stockByArticle[articleCode] != null) ? stockByArticle[articleCode] : 0;
        $input.val(fmtStock(v));
    }

    // Isi <select> UOM. Lookup uom_con_v2 by "article|supplier", fallback ke uom article.
    function fillUomSelect($sel, articleCode, suppCode, selectedUom){
        if (!$sel || !$sel.length) return;

        let key   = articleCode + '|' + suppCode;
        let units = (uomConByArticle && uomConByArticle[key]) ? uomConByArticle[key] : [];
        let html  = '';

        if (units && units.length){
            units.forEach(function(u){ html += `<option value="${u}">${u}</option>`; });
            // pastikan selectedUom tetap tersedia
            if (selectedUom && units.indexOf(selectedUom) === -1){
                html += `<option value="${selectedUom}">${selectedUom}</option>`;
            }
        } else if (selectedUom){
            html = `<option value="${selectedUom}">${selectedUom}</option>`;
        }

        $sel.html(html);

        if (selectedUom && $sel.find(`option[value="${selectedUom}"]`).length){
            $sel.val(selectedUom);
        }

        // Refresh kalau select2 aktif
        if ($sel.hasClass('select2-hidden-accessible')){
            $sel.trigger('change.select2');
        }
    }

    function setMask($qty, uomGroup){
        if (uomGroup === 'PIECE'){
            $qty.removeClass("numeral-mask-digit").addClass("numeral-mask-satuan");
            mask_thousand_satuan();
        } else {
            $qty.removeClass("numeral-mask-satuan").addClass("numeral-mask-digit");
            mask_thousand_digit(numberOfDecimalDigit);
        }
    }

    function compareQty(){
        if (objPoType.val() == 'tso'){
            let objQtyOrder  = $('#article_row input[name="qty_order[]"]');
            let objQtyHitung = $('#article_row input[name="qtyHitung[]"]');
            objQtyOrder.off('keyup.cmp').on('keyup.cmp', function(){
                let i = objQtyOrder.index(this);
                let qtyOrder  = parseFloat(objQtyOrder.eq(i).val().replace(/,/gi, '') || 0);
                let qtyHitung = parseFloat(objQtyHitung.eq(i).val().replace(/,/gi, '') || 0);
                objQtyOrder.eq(i).css("background-color", qtyOrder > qtyHitung ? "rgba(255,0,0,0.5)" : "");
            });
        }
    }

    function recordCount(){
        let records = $('.article-count').length - 1;
        $('#records').text(records);
    }

    // ====== Data loaders ======
    function isiArticle(dependent){
        $.ajax({
            url:"{{route('dynamic.dependent')}}",
            method:"POST",
            data:{ dependent: dependent, poType: $('#poType').val() },
            success:function(result){ dataArticle = result; }
        });
    }

    function changeSelectFromSto(obj, article, articleCode){
        $('#'+obj).attr('disabled','disabled');
        $('#'+obj).html(article);
        $('#'+obj).select2();
        $('#'+obj).val(articleCode).trigger('change');
        $('#'+obj).removeAttr('disabled');
        $('#'+obj).select2('focus');
    }

    function changeselect(dependent, obj, value){
        changeSelect({
            dependent: dependent,
            obj: obj,
            url:"{{ route('dynamic.dependent') }}",
            extra:{ poType: $('#poType').val(), suppCode: value }
        });
    }

    // Dipakai setelah load TSO (article belum punya UOM tersimpan, ambil dari data-detail)
    isiUom = () => {
        let objUom   = $('#article_row select[name="uom[]"]');
        let objQty   = $('#article_row input[name="qty_order[]"]');
        let objStock = $('#article_row input[name="qtyStock[]"]');
        $("#article_row select[name='article_id[]']").map(function(i){
            let $this = $(this);
            if ($this.val()){
                let detail   = $this.find(":selected").data("detail").split('|'); // [code, uom, supp]
                let uomGroup = $this.find(":selected").data("uom-group");
                fillUomSelect(objUom.eq(i), detail[0], detail[2], detail[1]);
                fillStock(objStock.eq(i), detail[0]);
                setMask(objQty.eq(i), uomGroup);
            }
        });
    }

    function splitArticle(){
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objUom     = $('#article_row select[name="uom[]"]');
        let objQty     = $('#article_row input[name="qty_order[]"]');
        let objStock   = $('#article_row input[name="qtyStock[]"]');
        objArticle.off('change.split').on('change.split', function(){
            let $this = $(this);
            if ($this.val()){
                let idx      = objArticle.index(this);
                let detail   = $this.find(":selected").data("detail");
                let arr      = detail.split("|"); // [code, uom, supp]
                let uomGroup = $this.find(":selected").data("uom-group");
                fillUomSelect(objUom.eq(idx), arr[0], arr[2], arr[1]);
                fillStock(objStock.eq(idx), arr[0]);
                if (detail){
                    setTimeout(() => objQty.eq(idx).focus().select(), 5);
                }
                setMask(objQty.eq(idx), uomGroup);
            }
        });
    }

    // ====== Row builders ======
    add_new_row_sto = (articleCode, qty, uom, note, qtyStock, alternative, desc, uomGroup, supp) => {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        let idx = cloneCount;

        $("#article_row").find('#baru').attr('id', 'new_row'+idx);
        $("#new_row"+idx).find('#article_id').attr('id', 'article_id'+idx);
        $("#new_row"+idx).find('#qty_order').attr('id', 'qty_order'+idx);
        $("#new_row"+idx).find('#qtyHitung').attr('id', 'qtyHitung'+idx);
        $("#new_row"+idx).find('#qtyStock').attr('id', 'qtyStock'+idx);
        $("#new_row"+idx).find('#note').attr('id', 'note'+idx);
        $("#new_row"+idx).find('#uom').attr('id', 'uom'+idx);

        let articleList = `<option value="${articleCode}" data-detail="${articleCode}|${uom}|${supp}" data-uom-group="${uomGroup}">${alternative} - ${desc}</option>`;
        changeSelectFromSto('article_id'+idx, articleList, articleCode);

        $('#qty_order'+idx).val(qty);
        $('#qtyHitung'+idx).val(qty);
        $('#qtyStock'+idx).val(qtyStock);
        $('#note'+idx).val(note);
        $('#article_id'+idx).attr('disabled','disabled');

        $('#remove_button').tooltip();
        mask_thousand_digit(numberOfDecimalDigit);
        compareQty();
        recordCount();
    };

    add_new_row_edit = (articleCode, qty, uom, uomGroup, note, qtyStock, qtyHitung, alternative, desc, supp) => {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        let idx = cloneCount; // ✅ capture

        $("#article_row").find('#baru').attr('id', 'new_row'+idx);
        $("#new_row"+idx).find('#article_id').attr('id', 'article_id'+idx);
        $("#new_row"+idx).find('#qty_order').attr('id', 'qty_order'+idx);
        $("#new_row"+idx).find('#qtyHitung').attr('id', 'qtyHitung'+idx);
        $("#new_row"+idx).find('#qtyStock').attr('id', 'qtyStock'+idx);
        $("#new_row"+idx).find('#note').attr('id', 'note'+idx);
        $("#new_row"+idx).find('#uom').attr('id', 'uom'+idx);

        let articleList = `<option value="${articleCode}" data-detail="${articleCode}|${uom}|${supp}" data-uom-group="${uomGroup}">${alternative} - ${desc}</option>`;
        changeSelectFromSto('article_id'+idx, articleList, articleCode);

        $('#qty_order'+idx).val(qty);
        $('#qtyHitung'+idx).val(qtyHitung);
        $('#qtyStock'+idx).val(qtyStock);
        $('#note'+idx).val(note);

        $('#article_id'+idx).attr('disabled','disabled');
        $('#qtyStock'+idx).attr('disabled','disabled');
        $("#article_id"+idx).select2();
        $('#remove_button').tooltip();

        // ✅ Isi UOM langsung (tidak pakai setTimeout, pakai idx yang sudah di-capture)
        fillUomSelect($('#uom'+idx), articleCode, supp, uom);

        setMask($('#qty_order'+idx), uomGroup);
        recordCount();
        compareQty();
    };

    // ✅ Pengaman: isi ulang semua UOM berdasarkan data asli (dipanggil di akhir isiData)
    function refillAllUom(data){
        let objUom = $('#article_row select[name="uom[]"]');
        for (let i = 0; i < data.length; i++){
            fillUomSelect(objUom.eq(i), data[i].article_code, data[i].third_party, data[i].uom);
        }
    }

    add_new_row = () => {
        let poType = $('#poType').val();
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        let idx = cloneCount;

        $("#article_row").find('#baru').attr('id', 'new_row'+idx);
        $("#new_row"+idx).find('#article_id').attr('id', 'article_id'+idx);
        $("#new_row"+idx).find('#uom').attr('id', 'uom'+idx);

        let depentName;
        switch(poType){
            case 'std': depentName = 'article_pr'; break;
            case 'sub': depentName = 'article_pr_sub'; break;
            case 'tso': depentName = 'article_pr'; break;
            case 'rm':  depentName = 'article_pr_rm'; break;
            case 'np':  depentName = 'article_pr_np'; break;
            default:    depentName = 'article_pr';
        }

        let depValue = (poType === 'np') ? suppCode.val() : undefined;
        changeselect(depentName, 'article_id'+idx, depValue);
        $("#article_id"+idx).select2();
        $('#remove_button').tooltip();
        splitArticle();
        recordCount();
    };

</script>