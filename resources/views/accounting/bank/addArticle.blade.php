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
                        <select class="dynamicSelect form-control" id="account" name="account[]">
                        </select>
                    </td>
                    <td class="isian" style="width: 25%">
                        <input type="text" class="form-control-plaintext" 
                        {{-- data-type-el-kiri="select" 
                        data-nama-el-kiri='account'
                        data-type-el-kanan='select'
                        data-nama-el-kanan='vcCc' --}}
                        id="vcDesc" name="vcDesc[]" />
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
                        <input type="text" class="form-control-plaintext numeral-mask text-right tombol-panah" 
                        data-type-el-kiri="select" 
                        data-nama-el-kiri='vcCc'
                        data-type-el-kanan='input'
                        data-nama-el-kanan='vcCredit'
                        id = "vcDebit" name="vcDebit[]" maxlength="12" />
                    </td>
                    <td class="isian" style="width: 10%">
                        <input type="text" class="form-control-plaintext numeral-mask text-right tombol-panah" 
                        data-type-el-kiri="input" 
                        data-nama-el-kiri='vcDebit'
                        data-type-el-kanan='input'
                        data-nama-el-kanan='vcDesc'
                        id = "vcCredit" name="vcCredit[]" maxlength="12" />
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
            let debit = parseInt(objvcDebit.eq(i).val().replace(/,/gi, '')) || 0;
            TotalDebit+= debit;
        }).get();

        var arr = objVcCredit.map(function (i) {
            let cashOut = parseInt(objVcCredit.eq(i).val().replace(/,/gi, '')) || 0;
            TotalCredit+= cashOut;
        }).get();

        objTotalVcDebit.val(humanizeNumber(TotalDebit));
        objTotalVcCredit.val(humanizeNumber(TotalCredit));
        objSelisih.val(humanizeNumber(TotalCredit-TotalDebit));

    }

    function findInvoice(){
        let objAccount = $('#item_row select[name="account[]"]');
        let objVcRef= $('#item_row select[name="vcRef[]"]');
        let objVcDebit= $('#item_row input[name="vcDebit[]"]');
        let objVcCredit= $('#item_row input[name="vcCredit[]"]');
        
        objAccount.change(function(e){        
            let objIndex = objAccount.index(this);
            let accountNumber = objAccount.eq(objIndex).val();
            let objCust = "vcRef"+(objIndex+1);
            if(accountNumber){
                if (accountNumber.substring(0,7) =='1100.40'){
                    // if(recFrom){
                        invList('referenceAr',objCust,accountNumber,'');
                    // }else{
                    //     Swal.fire('Warning..','Kolom bayar ke /supplier code masih kosong','warning');
                    // }
                }else{
                    objVcDebit.eq(objIndex).val("");
                    objVcCredit.eq(objIndex).val("");
                    objVcRef.eq(objIndex).empty().trigger('change');
                    hitungGrandTotal();
                }
            }
        });
    }

    function invList(dependent,obj,value,ref) {
      $.ajax({
        url:"{{route('dynamic.dependent')}}",
        method:"POST",
        data:{
            dependent:dependent,
            value:value
        },
        success:function(result){
            console.log(result);
            $('#'+obj).html(result).select2();
            $('#'+obj).val(ref).trigger('change');
        }
      })
    }

    function getAmount(){
        let objRef = $('#item_row select[name="vcRef[]"]');
        objRef.change(function(e){ 
            let objIndex = objRef.index(this);
            let vRef = objRef.eq(objIndex).val();
            if(vRef){
                getAmountValue(vRef,objIndex); 
            }
        });
    }   

    function getAmountValue(vRef,objIndex) {
        let objVcDebit= $('#item_row input[name="vcDebit[]"]');
        let objVcCredit= $('#item_row input[name="vcCredit[]"]');
        $.ajax({
            type: "get",
            url: "{{ route('bankPenerimaan.get.invoice.amount') }}",
            data: {
                vRef:vRef
            },
            dataType: "json",
            success: function(data) {
                objVcCredit.eq(objIndex).val('');
                objVcDebit.eq(objIndex).val('');
                if(data.amount){
                    objVcDebit.eq(objIndex).val('');
                    objVcCredit.eq(objIndex).val(humanizeNumber(data.amount));
                    hitungGrandTotal();
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
    }
</script>