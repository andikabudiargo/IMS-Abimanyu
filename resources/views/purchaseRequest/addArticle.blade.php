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
                <div class="form-group margin-nol">
                    <label for="qty_order" class="d-block d-md-none">QTY</label>
                    <div class="input-group input-group-merge">
                        <input type="text" class="form-control numeral-mask-satuan text-right" id = "qty_order" name="qty_order[]" maxlength="9" />
                        <div class="input-group-append">
                            <span class="input-group-text" id ="uom" name="uom[]"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-12">
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