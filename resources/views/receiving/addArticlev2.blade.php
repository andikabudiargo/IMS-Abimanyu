<style>
    #article_row .form-group {
        margin-bottom: 0.5rem;
    }
     /* tampil abu-abu seperti field disabled, tapi span (read-only) */
    #article_row .form-control.readonly-grey {
        background-color: #e9ecef;
        opacity: 1;
    }

    #article_row .btn-remove-row {
    position: absolute;
    top: 2px;
    right: 2px;
    z-index: 2;
    line-height: 1;
    padding: 0 .4rem;
    color: #ea5455;
    font-size: 1.1rem;
}
</style>
{{-- table row untuk di clone--}}
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris">
        <div class="form-row d-flex align-items-center">

            {{-- Article Code --}}
            <div class="col-md-4 col-12">
                <div class="form-group">
                    <label for="article_id" class="d-block d-md-none">Article Code</label>
                    <input type="text" class="form-control text-hitam disabled-el"
                           id="article_id" name="article_id[]"
                           data-code="" data-uom="" data-price="" disabled>
                </div>
            </div>

            {{-- QTY PO --}}
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label for="qty_po" id="lblQtyRef" class="d-block d-md-none">QTY PO</label>
                    <input type="text" class="form-control text-hitam text-right disabled-el"
                           id="qty_po" name="qty_po[]" disabled>
                </div>
            </div>

            {{-- QTY Rec --}}
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label for="qty_rec" class="d-block d-md-none">QTY Rec</label>
                    <input type="text" class="form-control numeral-mask-digit text-right text-hitam"
                           autocomplete="off" id="qty_rec" name="qty_rec[]" maxlength="11">
                </div>
            </div>

            {{-- UOM --}}
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label for="uom" class="d-block d-md-none">UOM</label>
                    <select class="form-control text-hitam" id="uom" name="uom[]"></select>
                </div>
            </div>

            {{-- QTY Free --}}
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label for="qty_free" class="d-block d-md-none">QTY Free</label>
                    <input type="text" class="form-control numeral-mask-digit text-right text-hitam"
                           autocomplete="off" id="qty_free" name="qty_free[]" maxlength="11">
                </div>
            </div>

            {{-- UOM Free --}}
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label for="uomFree" class="d-block d-md-none">UOM Free</label>
                    <select class="form-control text-hitam" id="uomFree" name="uomFree[]"></select>
                </div>
            </div>

            {{-- Total QTY --}}
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label for="totalQty" class="d-block d-md-none">Total</label>
                    <span class="form-control readonly-grey text-right numeral-mask-digit text-hitam"
                          id="totalQty" name="totalQty[]"></span>
                </div>
            </div>

            {{-- Conversion Info --}}
            <div class="col-md-2 col-12">
                <div class="form-group">
                    <label class="d-block d-md-none">Conv.</label>
                    <span class="form-control readonly-grey conv-info text-hitam text-right"
                          id="convInfo" name="convInfo[]"></span>
                </div>
            </div>

        </div>
        <hr class="d-block d-md-none" />
    </div>
</div>
{{-- \.table row --}}

{{-- MANUAL ROW (khusus Non Purchase) --}}
<div id="new_row_manual" class="d-none">
    <div id="baru_manual" class="tanda-baris position-relative">
        <button type="button" class="btn btn-sm btn-remove-row" title="Hapus baris">&times;</button>
        <div class="form-row d-flex align-items-center">

            <div class="col-md-4 col-12">
                <div class="form-group">
                    <label class="d-block d-md-none">Article</label>
                    <select class="form-control text-hitam" id="article_id" name="article_id[]"
                            data-code="" data-uom="" data-price="" data-prnumber="">
                        <option value=""></option>
                    </select>
                </div>
            </div>

            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label class="d-block d-md-none">QTY PO</label>
                    <input type="text" class="form-control text-hitam text-right disabled-el"
                           id="qty_po" name="qty_po[]" disabled>
                </div>
            </div>

            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label class="d-block d-md-none">QTY Rec</label>
                    <input type="text" class="form-control numeral-mask-digit text-right text-hitam"
                           autocomplete="off" id="qty_rec" name="qty_rec[]" maxlength="11">
                </div>
            </div>

            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label class="d-block d-md-none">UOM</label>
                    <select class="form-control text-hitam" id="uom" name="uom[]"></select>
                </div>
            </div>

            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label class="d-block d-md-none">QTY Free</label>
                    <input type="text" class="form-control numeral-mask-digit text-right text-hitam"
                           autocomplete="off" id="qty_free" name="qty_free[]" maxlength="11">
                </div>
            </div>

            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label class="d-block d-md-none">UOM Free</label>
                    <select class="form-control text-hitam" id="uomFree" name="uomFree[]"></select>
                </div>
            </div>

            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label class="d-block d-md-none">Total</label>
                    <span class="form-control readonly-grey text-right numeral-mask-digit text-hitam"
                          id="totalQty" name="totalQty[]"></span>
                </div>
            </div>

            <div class="col-md-2 col-12">
                <div class="form-group">
                    <label class="d-block d-md-none">Conv.</label>
                    <span class="form-control readonly-grey conv-info text-hitam text-right"
                          id="convInfo" name="convInfo[]"></span>
                </div>
            </div>

        </div>
        <hr class="d-block d-md-none" />
    </div>
</div>