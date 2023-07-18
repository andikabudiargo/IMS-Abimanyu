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
        <table class="table-bordered"  style="width: 98%;table-layout: fixed;">
            <tbody>
                <tr id="tabelBaru">
                    <td class="isian" style="width: 30%">
                        <select class="form-control tombol-panah" id="articleId1" name="articleId1[]" required>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
{{-- \.table row --}} 
<script type="text/javascript">
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
        // let objTotalAmount= $('#totalAmount');
        let TotalDebit=0;
        let TotalCredit=0;

        var arr = objvcDebit.map(function (i) {
            let debit = parseInt(objvcDebit.eq(i).val().replace(/,/gi, '')) || 0;
            TotalDebit+= debit;
        }).get();

        var arr = objVcCredit.map(function (i) {
            let cashOut = parseInt(objVcCredit.eq(i).val().replace(/,/gi, '')) || 0;
            TotalCredit+= cashOut;
        }).get();

        objTotalVcDebit.val(humanizeNumber(TotalDebit));
        objTotalVcCredit.val(humanizeNumber(TotalCredit));

        // if (type =='penerimaan'){
        //     objTotalAmount.val(humanizeNumber(TotalCredit));
        // }else{
        //     objTotalAmount.val(TotalDebit);
        // }
    }
</script>