<style>

    .mb-03{
        margin-bottom: 0.3rem;
    }

    .jarak-antar-attr{
        padding-left: 0.3rem;
        margin-bottom: 0.3rem;
        padding-right: 0.3rem;
    }

    .jarak-antar-attr-qty-order{
        padding-left: 0.3rem;
        margin-bottom: 1.8rem;
        padding-right: 0.3rem;
    }

    td.isian{
        padding-right:10px;
        padding-left:10px;
    }

    td.isian-satu{
        padding-right:5px;
        padding-left:15px;
        width: 25%;border-top: 1px solid #ffffff !important;
        border-bottom: 1px solid #ffffff !important;
        border-left: 1px solid #ffffff !important;
    }

    td.disabled{
        background-color:#f8f8f8;
        color:black;
    }

    label.tanpa-padding{
        padding-top: 5px;
        padding-bottom: 0px;
    }

    .totalLine{
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
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
            width:100%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
    }

    @media only screen and (min-width: 600px)
    and (max-width: 1200px){
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
{{-- table row untuk di clone --}}
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris">
        <table class="table-bordered" id="listData" style="width: 98%;table-layout: fixed;">
            <tbody>
                <tr>
                    <td class="isian disabled" style="width: 25%">
                        <input type="text" class="form-control-plaintext text-hitam"
                               id="articleCode" name="articleCode[]"
                               data-code="" data-uom="" data-price="" disabled>
                    </td>
                    <td class="isian disabled" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask-digit text-right"
                               id="totQtyReturn" name="totQtyReturn[]" disabled>
                    </td>
                    <td class="isian disabled" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask-digit text-right"
                               id="qtyReturn" name="qtyReturn[]" disabled>
                    </td>

                    {{-- TAMBAHAN: kolom Qty Stock --}}
                    <td class="isian disabled" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask-digit text-right"
                               id="qtyStock" name="qtyStock[]" disabled>
                    </td>

                    <td class="isian" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask-digit text-right input-qty"
                               autocomplete="off" id="qtyReplace" name="qtyReplace[]"
                               maxlength="11" onkeyup="hitungTotal();">
                    </td>
                    <td class="isian disabled" style="width: 8%">
                        <input type="text" class="form-control-plaintext text-hitam"
                               id="uom" name="uom[]" disabled>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
{{-- \.table row --}}

<script type="text/javascript">
    let currentDate = "{{ $currentDateValue }}";
    let dariEdit = "";

    function searchDn(obj, value) {
        $("#dnNumber").val('');
        $.ajax({
            url: "{{ route('dnReplace.list.return') }}",
            method: "GET",
            data: { value: value },
            success: function (result) {
                $('#' + obj).html(result);
            },
            error: function () {
                Swal.fire("Warning", "Get list DN Return failed", "warning");
            }
        });
    }

    function searchDnDet(value, dariEdit) {
        if (dariEdit == 'false') {
            $.ajax({
                url: "{{ route('dnReplace.return.det') }}",
                method: "GET",
                data: { value: value },
                success: function (result) {
                    $("#articleRow").empty();
                    cloneCount = 0;

                    if (result.length > 0) {
                        for (let i = 0; i < result.length; i++) {
                            let article        = result[i].article_code;
                            let articleCode    = result[i].article_alternative_code;
                            let articleDesc    = result[i].article_desc;
                            let qtyReturn      = result[i].qty_return <= 0 ? 0 : result[i].qty_return;
                            let uom            = result[i].uom;
                            let returnNumber   = result[i].return_number;
                            let qty            = 0;
                            let totQtyReturn   = result[i].tot_qty_return <= 0 ? 0 : result[i].tot_qty_return;

                            // TAMBAHAN: ambil qty_stock dari response (pastikan API mengembalikan field ini)
                            let qtyStock = result[i].qty_stock != null ? result[i].qty_stock : 0;

                            addNewRow(article, articleCode, articleDesc, qtyReturn, uom, qty, returnNumber, totQtyReturn, qtyStock);
                        }
                    }
                },
                error: function () {
                    Swal.fire("Warning", "Get detail DN Return failed", "warning");
                }
            });
        } else {
            dariEdit = 'false';
        }
    }

    // Taruh di luar hitungTotal, scope global
let toastTimer = null;

hitungTotal = () => {
    let objQtyReplace = $('#articleRow input[name="qtyReplace[]"]');
    let objQtyReturn  = $('#articleRow input[name="qtyReturn[]"]');
    let objQtyStock   = $('#articleRow input[name="qtyStock[]"]');
    let pesanToast    = [];

    objQtyReplace.each(function(i) {
        let $input     = $(this);
        let qtyReplace = parseFloat($input.val().replace(/,/gi, '')) || 0;
        let qtyReturn  = parseFloat(objQtyReturn.eq(i).val().replace(/,/gi, '')) || 0;
        let qtyStock   = parseFloat(objQtyStock.eq(i).val().replace(/,/gi, '')) || 0;

        // Reset style dulu
        $input.css({ 'background-color': '', 'color': '' });

        if (qtyReplace > qtyReturn && qtyReplace != 0) {
            $input.css({ 'background-color': '#f8d7da', 'color': '#842029' });
            pesanToast.push(`Baris ${i+1}: Qty Replace melebihi Qty Return (${qtyReturn})`);

        } else if (qtyReplace > qtyStock && qtyReplace != 0) {
            $input.css({ 'background-color': '#f8d7da', 'color': '#842029' });
            pesanToast.push(`Baris ${i+1}: Qty Replace melebihi Qty Stock (${qtyStock})`);

        } else {
            $input.css({ 'background-color': '#ffffff', 'color': '' });
        }
    });

    // Debounce toast — tunggu 600ms setelah berhenti ketik baru tampil
    clearTimeout(toastTimer);
    if (pesanToast.length > 0) {
        toastTimer = setTimeout(() => {
            show_msg('Warning', pesanToast.join('<br>'), 'warning');
        }, 600);
    }

    let grandTotal = objQtyReplace.map(function() {
        return $(this).val().replace(/,/gi, '');
    }).get();
    let total = sumFromArray(grandTotal);
    $('#totalQTY').val(humanizeNumber(total));
    mask_thousand_digit(2);
}
    hitungBaris = () => {
        let objArticle = $('#articleRow input[name="articleCode[]"]');
        $("#totalRow").val(objArticle.length);
    }

    let cloneCount = 0;

    // MODIFIKASI: tambah parameter qtyStock
    function addNewRow(article, articleCode, articleDesc, qtyReturn, uom, qty, returnNumber, totQtyReturn, qtyStock) {
        returnNumber = returnNumber == null ? '' : returnNumber;
        qtyStock     = qtyStock != null ? qtyStock : 0;  // TAMBAHAN: default 0 jika null

        $("#articleRow").append($("#new_row").clone().html());
        cloneCount++;

        $("#articleRow").find('#baru').attr('id', 'new_row' + cloneCount);

        // Article Code
        $("#new_row" + cloneCount).find('#articleCode').attr('id', 'articleCode' + cloneCount);
        $('#articleCode' + cloneCount).attr('data-code', article);
        $('#articleCode' + cloneCount).attr('data-uom', uom);
        $('#articleCode' + cloneCount).attr('data-returnNumber', returnNumber);
        $('#articleCode' + cloneCount).val(articleCode + " - " + articleDesc);

        // Tot Qty Return
        $("#new_row" + cloneCount).find('#totQtyReturn').attr('id', 'totQtyReturn' + cloneCount);
        $('#totQtyReturn' + cloneCount).val(totQtyReturn * 1);

        // Qty Return
        $("#new_row" + cloneCount).find('#qtyReturn').attr('id', 'qtyReturn' + cloneCount);
        $('#qtyReturn' + cloneCount).val(qtyReturn * 1);

        // TAMBAHAN: Qty Stock
        $("#new_row" + cloneCount).find('#qtyStock').attr('id', 'qtyStock' + cloneCount);
        $('#qtyStock' + cloneCount).val(qtyStock * 1);

        // Qty Replace
        $("#new_row" + cloneCount).find('#qtyReplace').attr('id', 'qtyReplace' + cloneCount);
        qty ? $('#qtyReplace' + cloneCount).val(qty * 1) : '';

        // UOM
        $("#new_row" + cloneCount).find('#uom').attr('id', 'uom' + cloneCount);
        $('#uom' + cloneCount).val(uom);

        // Disable input jika qty return = 0
        qtyReturn === 0 ? $('#qtyReplace' + cloneCount).attr('disabled', 'disabled') : '';

        tombolPanah('qtyReplace');
        mask_thousand_digit(2);
        hitungBaris();
        qty ? hitungTotal() : '';
    }
</script>
