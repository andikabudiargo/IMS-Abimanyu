<style>
    td.isian{ padding-right:10px; padding-left:10px; }
    td.disabled{ background-color:#f8f8f8; color:black; }
    label.titik-dua::after{ content:":"; position:absolute; right:1px; }
    .mb-04{ margin-bottom:0.4rem; }
    .mb-03{ margin-bottom:0.3rem; }

    @media screen and (min-device-width:1200px) and (max-device-width:1600px) and (-webkit-min-device-pixel-ratio:1){
        .lebar-list-item{ width:100%; }
        .container-list-item{ max-width:100%; overflow-x:auto; scrollbar-width:thin; margin-top:7px; }
    }
    @media only screen and (min-width:600px) and (max-width:1200px){
        .lebar-list-item{ width:200%; }
        .container-list-item{ max-width:100%; overflow-x:auto; scrollbar-width:thin; margin-top:7px; }
    }
</style>

{{-- baris untuk di-clone --}}
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris">
        <table class="table-bordered" id="listData" style="width: 98%;table-layout: fixed;">
            <tbody>
                <tr>
                    <td class="isian disabled" style="width: 25%">
                        <input type="text" class="form-control-plaintext text-hitam" id="articleCode" name="articleCode[]" data-code="" data-uom="" data-return-number="" disabled>
                    </td>
                    <td class="isian disabled" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask-digit text-right" id="totQtyReturn" name="totQtyReturn[]" disabled>
                    </td>
                    <td class="isian disabled" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask-digit text-right" id="sisaQtyReturn" name="sisaQtyReturn[]" disabled>
                    </td>
                    <td class="isian" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask-digit text-right input-qty" autocomplete="off" id="qtyReplace" name="qtyReplace[]" maxlength="11" onkeyup="cekQtyReplace(this); hitungTotal();">
                    </td>
                    <td class="isian disabled" style="width: 8%">
                        <input type="text" class="form-control-plaintext text-hitam" id="uom" name="uom[]" disabled>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script type="text/javascript">
    let currentDate = "{{ $currentDate ?? date('d-m-Y') }}";
    let dariEdit = "";

    /**
     * Ambil daftar Return untuk supplier terpilih -> isi dropdown Return Number.
     * Reset grid dulu supaya baris supplier lama tidak nyangkut.
     */
    function searchReturn(obj, value) {
        $("#returnDate").val('');
        $("#articleRow").empty();
        $("#totalRow").val(0);
        $("#totalQTY").val(humanizeNumber(0));
        $('#'+obj).html('<option value="">Choose Return</option>').trigger('change.select2');

        $.ajax({
            url: "{{ route('supplierReplace.list.return') }}",
            method: "GET",
            data: { value: value },
            success: function(result){ $('#'+obj).html(result); },
            error: function(){ Swal.fire("Warning","Get list Return failed","warning"); }
        });
    }

    /**
     * Ambil detail artikel dari return + sisa qty return per artikel.
     */
    function searchReturnDet(value, dariEdit) {
        if (dariEdit == 'false') {
            $.ajax({
                url: "{{ route('supplierReplace.return.det') }}",
                method: "GET",
                data: { value: value },
                success: function(result){
                    $("#articleRow").empty();
                    cloneCount = 0;

                    if (result.length > 0) {
                        for (let i = 0; i < result.length; i++) {
                            let article        = result[i].article_code;
                            let articleCode    = result[i].article_alternative_code;
                            let articleDesc    = result[i].article_desc;
                            let totQtyReturn   = result[i].tot_qty_return  <= 0 ? 0 : result[i].tot_qty_return;
                            let sisaQtyReturn  = result[i].sisa_qty_return <= 0 ? 0 : result[i].sisa_qty_return;
                            let uom            = result[i].uom;
                            let returnNumber   = result[i].return_number;
                            let qty            = 0;
                            addNewRow(article, articleCode, articleDesc, totQtyReturn, sisaQtyReturn, uom, qty, returnNumber);
                        }
                    }
                    hitungBaris();
                    hitungTotal();
                },
                error: function(){ Swal.fire("Warning","Get detail Return failed","warning"); }
            });
        } else {
            dariEdit = 'false';
        }
    }

    // Batasi qty replace <= sisa qty return
    function cekQtyReplace(el) {
        let $row  = $(el).closest('.tanda-baris');
        let sisa  = parseFloat(($row.find('input[name="sisaQtyReturn[]"]').val() || '0').replace(/,/g,'')) || 0;
        let qty   = parseFloat(($(el).val() || '0').replace(/,/g,'')) || 0;
        if (qty > sisa) {
            $(el).val(sisa);
            Swal.fire('Warning', 'Qty replace tidak boleh melebihi sisa qty return ('+sisa+')', 'warning');
        }
    }

    hitungTotal = () => {
        let objQtyReplace = $('#articleRow input[name="qtyReplace[]"]');
        let grandTotal = objQtyReplace.map(function(){ return $(this).val().replace(/,/gi,''); }).get();
        let total = sumFromArray(grandTotal);
        $('#totalQTY').val(humanizeNumber(total));
        mask_thousand_digit(2);
    }

    hitungBaris = () => {
        let objArticle = $('#articleRow input[name="articleCode[]"]');
        $("#totalRow").val(objArticle.length);
    }

    let cloneCount = 0;
    function addNewRow(article, articleCode, articleDesc, totQtyReturn, sisaQtyReturn, uom, qty, returnNumber) {
        returnNumber = returnNumber == null ? '' : returnNumber;
        $("#articleRow").append($("#new_row").clone().html());
        cloneCount++;
        $("#articleRow").find('#baru').attr('id', 'new_row' + cloneCount);

        $("#new_row" + cloneCount).find('#articleCode').attr('id', 'articleCode' + cloneCount);
        $('#articleCode' + cloneCount).attr('data-code', article);
        $('#articleCode' + cloneCount).attr('data-uom', uom);
        $('#articleCode' + cloneCount).attr('data-return-number', returnNumber);
        $('#articleCode' + cloneCount).val(articleCode + " - " + articleDesc);

        $("#new_row" + cloneCount).find('#totQtyReturn').attr('id', 'totQtyReturn' + cloneCount);
        $('#totQtyReturn' + cloneCount).val(totQtyReturn * 1);

        $("#new_row" + cloneCount).find('#sisaQtyReturn').attr('id', 'sisaQtyReturn' + cloneCount);
        $('#sisaQtyReturn' + cloneCount).val(sisaQtyReturn * 1);

        $("#new_row" + cloneCount).find('#qtyReplace').attr('id', 'qtyReplace' + cloneCount);
        qty ? $('#qtyReplace' + cloneCount).val(qty * 1) : '';

        $("#new_row" + cloneCount).find('#uom').attr('id', 'uom' + cloneCount);
        $('#uom' + cloneCount).val(uom);

        // Kalau sisa 0 -> tidak bisa diisi
        sisaQtyReturn == 0 ? $('#qtyReplace' + cloneCount).attr('disabled','disabled') : '';

        tombolPanah('qtyReplace');
        mask_thousand_digit(2);
        hitungBaris();
        qty ? hitungTotal() : '';
    }
</script>