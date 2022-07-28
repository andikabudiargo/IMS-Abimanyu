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
            width:130%;
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

{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <div class="form-row d-flex align-items-center">
            <div class="col-md-1 col-12" style="max-width: 5%;">
                <div class="form-group margin-nol">
                    <label for="urutan" class="d-block d-md-none">Urutan</label>
                    <input type="text" class="form-control numeral-mask-satuan" id="urutan" name="urutan[]" >
                </div>
            </div>
            <div class="col-md-2 col-12" style="max-width: 14%;">
                <div class="form-group margin-nol">
                    <label for="salesOrder" class="d-block d-md-none">NO SPK / SO</label>
                    <select class="dynamicSelect form-control" id="salesOrder" name="salesOrder[]" data-dependent="salesOrder">
                    </select>
                </div>
            </div>
            <div class="col-md-4 col-12">
                <div class="form-group margin-nol">
                    <label for="articleId" class="d-block d-md-none">Article</label>
                    <select class="dynamicSelect form-control" id="articleId" name="articleId[]" data-dependent="articleId">
                    </select>
                    <input type="hidden" class="form-control" id="articleRm" name="articleRm[]" disabled />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qtyOrder" class="d-block d-md-none">QTY SO</label>
                    <input type="text" class="form-control numeral-mask-satuan text-right" id="qtyOrder" name="qtyOrder[]" disabled />
                    {{-- <div class="input-group input-group-merge">
                        <div class="input-group-append">
                            <span class="input-group-text" id ="uomQtyOrder" name="uomQtyOrder[]"></span>
                        </div>
                    </div> --}}
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qtyProd" class="d-block d-md-none">QTY Fresh</label>
                    <input type="text" class="form-control numeral-mask-satuan text-right" id="qtyProd" name="qtyProd[]" maxlength="9" />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qtyRepaint" class="d-block d-md-none">QTY Repaint</label>
                    <input type="text" class="form-control numeral-mask-satuan text-right" id = "qtyRepaint" name="qtyRepaint[]" maxlength="9" />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="waktu" class="d-block d-md-none">Waktu</label>
                    <input type="text" class="form-control" id="waktu" name="waktu[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12" style="max-width: 5%;">
                <div class="form-group margin-nol">
                    <label for="tag" class="d-block d-md-none">Tag</label>
                    <input type="text" class="form-control" id="tag" name="tag[]" disabled>
                    <input type="text" class="form-control" id="tagAsli" name="tagAsli[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12" style="max-width: 5%;">
                <div class="form-group margin-nol text-center">
                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();">
                        <i data-feather="trash-2" class="remove_button feather-24">
                        </i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- \.table row --}} 