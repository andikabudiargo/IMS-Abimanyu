<style>
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
    
</style>
{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <table class="table-bordered" style="width: 98%;table-layout: fixed;">
            <tbody>
                <tr>
                    <td class="isian" style="width: 20%">
                        <select class="dynamicSelect form-control cekoki" id="account" name="account[]">
                        </select>
                    </td>
                    <td class="isian" style="width: 25%">
                        <input type="text" class="form-control-plaintext" 
                        id="vcDesc" name="vcDesc[]" autocomplete="off"/>
                    </td>
                    <td class="isian" style="">
                        <select class="form-control tombol-panah" id="vcRef" name="vcRef[]">                            
                        </select>
                    </td>
                    <td class="isian" style="">
                        <select class="form-control tombol-panah" id="vcCc" name="vcCc[]" required>
                            <option value="">Choose Cost Center</option>
                            @foreach($depts as $val)
                            <option value="{{ $val->code }}">{{ $val->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="isian" style="width: 10%">
                        <input type="text" class="form-control-plaintext numeral-mask-digit text-right tombol-panah" 
                        data-type-el-kiri="select" 
                        data-nama-el-kiri='vcCc'
                        data-type-el-kanan='input'
                        data-nama-el-kanan='vcCredit'
                        id = "vcDebit" name="vcDebit[]" maxlength="20" oninput='inputDecimal(this)' />
                    </td>
                    <td class="isian" style="width: 10%">
                        <input type="text" class="form-control-plaintext numeral-mask-digit text-right tombol-panah" 
                        data-type-el-kiri="input" 
                        data-nama-el-kiri='vcDebit'
                        data-type-el-kanan='input'
                        data-nama-el-kanan='vcDesc'
                        id = "vcCredit" name="vcCredit[]" maxlength="20" oninput='inputDecimal(this)'/>
                    </td>
                    <td class="isian text-center" style="width: 5%">
                        <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal()" data-toggle="tooltip" data-placement="left" title="Delete row">
                            <i data-feather="trash-2" class="remove_button feather-24">
                            </i>
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
{{-- \.table row --}} 
<script type="text/javascript">
    let delayTimer;
    function inputDecimal(ele) {
        clearTimeout(delayTimer);
        delayTimer = setTimeout(function() {
            let nilai = ele.value.replace(/,/gi, '') || 0;
            if(nilai!= 0){
                ele.value = humanizeNumber(parseFloat(nilai).toFixed(2)).toString();
            }else{
                ele.value ='';
            }
        }, 2100); 
    }

    function hitungTotal(){
        let objvcDebit= $('#item_row input[name="vcDebit[]"]');
        let objVcCredit= $('#item_row input[name="vcCredit[]"]');

        objvcDebit.keyup(function() {
            let theIndex = objvcDebit.index(this);
            if (objVcCredit.eq(theIndex).val()){
                objvcDebit.eq(theIndex).val('');
            }
            hitungGrandTotal();
        });    

        objVcCredit.keyup(function() {
            let theIndex1 = objVcCredit.index(this);
            if (objvcDebit.eq(theIndex1).val()){
                objVcCredit.eq(theIndex1).val('');
            }
            hitungGrandTotal();
        });    
    }

    function hitungGrandTotal(){
        let objvcDebit= $('#item_row input[name="vcDebit[]"]');
        let objTotalVcDebit= $('#vcTotalDebit');
        let objVcCredit= $('#item_row input[name="vcCredit[]"]');
        let objTotalVcCredit= $('#vcTotalCredit');
        let objSelisih= $('#selisih');
        // let objTotalAmount= $('#totalAmount');
        let TotalDebit=0;
        let TotalCredit=0;

        var arr = objvcDebit.map(function (i) {
            let debit = parseFloat(objvcDebit.eq(i).val().replace(/,/gi, '')) || 0;
            TotalDebit+= debit;
        }).get();

        var arr = objVcCredit.map(function (i) {
            let cashOut = parseFloat(objVcCredit.eq(i).val().replace(/,/gi, '')) || 0;
            TotalCredit+= cashOut;
        }).get();

        objTotalVcDebit.val(humanizeNumber(TotalDebit.toFixed(2)));
        objTotalVcCredit.val(humanizeNumber(TotalCredit.toFixed(2)));
        objSelisih.val(humanizeNumber((TotalDebit-TotalCredit).toFixed(2)));

    }

</script>