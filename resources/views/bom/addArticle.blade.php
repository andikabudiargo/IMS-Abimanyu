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
            <div class="col-md-2 col-12">
                <div class="form-group">
                    <label for="qty_stock" class="d-block d-md-none">QTY</label>
                    <input type="text" class="form-control numeral-mask-digit text-right tombol-panah" id ="qtyBom" name="qtyBom[]" maxlength="10" />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label for="uom" class="d-block d-md-none">Uom</label>
                    <span class="" id = "uom" name="uom[]"></span>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group">
                    <label for="uom" class="d-block d-md-none">Type</label>
                    <span class="" id = "type" name="type[]"></span>
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