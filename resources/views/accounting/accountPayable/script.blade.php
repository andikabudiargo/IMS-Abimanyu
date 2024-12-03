<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');
    let poAda;
    // let recAda;
    // let status ="{{ Session::get('status') ? Session::get('status'): '' }}";
    let sNilaiPPN= "{{ $nilaiPPN }}";
    let sNilaiPPH23= "{{ $nilaiPPH23 }}";
    let sNilaiPPH21= "{{ $nilaiPPH21 }}";
    let sNilaiPPH42= "{{ $nilaiPPH42 }}";
    let showDetail="";
    let listArticle="";
    let listCoa="";
    let edit="";
    let dariEdit="";
    let urutanRow = 0;
    let depts = '{!! $depts !!}';

    let delayTimer;
    let delayTimerTax;
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
    
    $("#pph23Check").change(function() {
        if(this.checked) {
            let basisAmount = parseFloat($('#basisAmount').val().replace(/,/gi, '')) || 0;
            $("#totalPPH23").val(parseFloat(basisAmount * (sNilaiPPH23/100)).toFixed(2));
            $("#nilaiPPH23").text(sNilaiPPH23+'%');
            $('#pph21Check').prop('checked',false);
            $('#pph42Check').prop('checked',false);
            $("#nilaiPPH21").text('');
            $("#nilaiPPH42").text('');
            $("#totalPPH21").val('');
            $("#totalPPH42").val('');
            $('#totalPPH23').removeAttr('disabled');
            $('#totalPPH21').attr('disabled','disabled');
            $('#totalPPH42').attr('disabled','disabled');
            $('#totalPPH23').focus().select();
            $("#totalPPH23").prop('required',true);
            mask_thousand_digit(2);
            mask_thousand();
            hitungTotal();
        }else{
            $("#totalPPH23").val('');
            $("#nilaiPPH23").text('');
            $('#totalPPH23').attr('disabled','disabled');
            $("#totalPPH23").prop('required',false);
            hitungTotal();  
        }
    });

    $("#pph21Check").change(function() {
        if(this.checked) {
            let basisAmount = parseFloat($('#basisAmount').val().replace(/,/gi, '')) || 0;
            $("#totalPPH21").val(parseFloat(basisAmount * (sNilaiPPH21/100)).toFixed(2));
            $("#nilaiPPH21").text(sNilaiPPH21+'%');
            $("#nilaiPPH23").text('');
            $("#nilaiPPH42").text('');
            $("#totalPPH23").val('');
            $("#totalPPH42").val('');
            $('#pph23Check').prop('checked',false);
            $('#pph42Check').prop('checked',false);
            $('#totalPPH21').removeAttr('disabled');
            $('#totalPPH23').attr('disabled','disabled');
            $('#totalPPH42').attr('disabled','disabled');
            $('#totalPPH21').focus().select();
            $("#totalPPH21").prop('required',true);
            mask_thousand_digit(2);
            mask_thousand();
            hitungTotal();
        }else{
            $("#totalPPH21").val('');
            $("#nilaiPPH21").text('');
            $('#totalPPH21').attr('disabled','disabled');
            $("#totalPPH21").prop('required',false);
            hitungTotal();  
        }
    });

    $("#pph42Check").change(function() {
        if(this.checked) {
            let basisAmount = parseFloat($('#basisAmount').val().replace(/,/gi, '')) || 0;
            $("#totalPPH42").val(parseFloat(basisAmount * (sNilaiPPH42/100)).toFixed(2));
            $("#nilaiPPH42").text(sNilaiPPH42+'%');
            $('#pph23Check').prop('checked',false);
            $('#pph21Check').prop('checked',false);
            $("#nilaiPPH21").text('');
            $("#nilaiPPH23").text('');
            $("#totalPPH21").val('');
            $("#totalPPH23").val('');
            $('#totalPPH42').removeAttr('disabled');
            $('#totalPPH21').attr('disabled','disabled');
            $('#totalPPH23').attr('disabled','disabled');
            $('#totalPPH42').focus().select();
            $("#totalPPH42").prop('required',true);
            mask_thousand_digit(2);
            mask_thousand();
            hitungTotal();
        }else{
            $("#totalPPH42").val('');
            $("#nilaiPPH42").text('');
            $('#totalPPH42').attr('disabled','disabled');
            $("#totalPPH42").prop('required',false);
            hitungTotal();  
        }
    });

    $("#vatCheck").change(function() {
        if(this.checked) {
            let basisAmount = parseFloat($('#basisAmount').val().replace(/,/gi, '')) || 0;
            $("#totalPPN").val(parseFloat(basisAmount * (sNilaiPPN/100)).toFixed(2));
            $("#nilaiPPN").text(sNilaiPPN+'%');
            $("#totalPPN").removeAttr('disabled');
            $("#taxInvoiceNumber").removeAttr('disabled');
            $("#taxInvoiceNumber").prop('required',true);
            $("#totalPPN").prop('required',true);
            $("#totalPPN").focus().select();
            mask_thousand_digit(2);
            mask_thousand();
            hitungTotal();
        }else{
            $("#totalPPN").val('');
            $("#nilaiPPN").text('');
            $("#taxInvoiceNumber").val('');
            $("#taxInvoiceNumber").attr('disabled','disabled');
            $("#taxInvoiceNumber").prop('required',false);
            $("#totalPPN").prop('required',false);
            $("#totalPPN").attr('disabled','disabled');
            hitungTotal();
        }
    });

    hitungTotal = () => {
        // console.log(edit);
        let ba = parseFloat($('#basisAmount').val().replace(/[^0-9.]/g, '')) || 0;
        let baA = parseFloat($('#basisAmountA').val().replace(/[^0-9.]/g, '')) || 0;

        let vat = parseFloat($('#totalPPN').val().replace(/[^0-9.]/g, '')) || 0;
        let pph23 = parseFloat($('#totalPPH23').val().replace(/[^0-9.]/g, '')) || 0;
        let pph21 = parseFloat($('#totalPPH21').val().replace(/[^0-9.]/g, '')) || 0;
        let pph42 = parseFloat($('#totalPPH42').val().replace(/[^0-9.]/g, '')) || 0;

        let od = parseFloat($('#totalDiscount').val().replace(/[^0-9.]/g, '')) || 0;
        let total;

        let objDebit = $('input[name="addAccountDebit[]"]');
        let totalDebit= 0;
        let debit = objDebit.map(function(){return $(this).val().replace(/,/gi, '')}).get();
        
        totalDebit = sumFromArray(debit);
        ba = baA + totalDebit;

        if(edit == 'false'){
            if($('#vatCheck').is(':checked') ) {
                if(!$("#totalPPN").val()){
                    clearTimeout(delayTimerTax);
                    delayTimerTax = setTimeout(function() {
                        $("#totalPPN").val(parseFloat(ba * (sNilaiPPN/100)).toFixed(2));
                    }, 2100); 
                }
                vat = parseFloat($('#totalPPN').val().replace(/[^0-9.]/g, '')) || 0;
            }

            if($("#pph23Check").is(':checked') ) {
                if(!$("#totalPPH23").val()){
                    clearTimeout(delayTimerTax);
                    delayTimerTax = setTimeout(function() {
                        $("#totalPPH23").val(parseFloat(ba * (sNilaiPPH23/100)).toFixed(2));
                    }, 2100); 
                }                
                pph23 = parseFloat($('#totalPPH23').val().replace(/[^0-9.]/g, '')) || 0;
            }

            if($("#pph21Check").is(':checked') ) {
                if(!$("#totalPPH21").val()){
                    clearTimeout(delayTimerTax);
                    delayTimerTax = setTimeout(function() {
                        $("#totalPPH21").val(parseFloat(ba * (sNilaiPPH21/100)).toFixed(2));
                    }, 2100); 
                }
                pph21 = parseFloat($('#totalPPH21').val().replace(/[^0-9.]/g, '')) || 0;
            }

            if($("#pph42Check").is(':checked') ) {
                if(!$("#totalPPH42").val()){
                    clearTimeout(delayTimerTax);
                    delayTimerTax = setTimeout(function() {
                        $("#totalPPH42").val(parseFloat(ba * (sNilaiPPH42/100)).toFixed(2));
                    }, 2100); 
                }
                pph42 = parseFloat($('#totalPPH42').val().replace(/[^0-9.]/g, '')) || 0;
            }
        }
        
        if(vat){
            total = ba ? (ba-od)+vat-(pph23+pph21+pph42) : '';
        }else{
            total = ba ? (ba-od)-(pph23+pph21+pph42) : '';
        }

        $('#basisAmount').val(humanizeNumber(parseFloat(ba).toFixed(2)))
        $('#grandTotal').val(humanizeNumber(parseFloat(total).toFixed(2)));

        mask_thousand_digit(2);
        mask_thousand();
    }

    // $("#basisAmount,#totalPPN,#totalPPH23,#totalPPH21,#totalPPH42,#totalDiscount,nilaiDebit").keyup(function(){
    $("#basisAmount,#totalPPN,#totalPPH23,#totalPPH21,#totalPPH42,#totalDiscount,nilaiDebit").keyup(function(){
        hitungTotal();
    })

    $('body').on('keyup', 'input[name="addAccountDebit[]"]', function (){
        hitungTotal();
    });
   
    invoiceDate = $('#invoiceDate');
    if (invoiceDate.length) {
        invoiceDate.flatpickr({
            dateFormat: "d-m-Y"
        });
    }

    apDate = $('#apDate');
    if (apDate.length) {
        apDate.flatpickr({
            dateFormat: "d-m-Y"
        });
    }

    function reloadPage(){
        window.location.reload();
    }

    $("#cmdCancel").click(function(){
        reloadPage();
    });

    $("#cmdNew").click(function(){
        reloadPage();
    });

    $('#supplier').change(function(){
        let value= $(this).val();
        let obj = 'poNumber';
        let term = $(this).find(":selected").data("term");
        let coa = $(this).find(":selected").data("coa");
        if(coa){
            $('#term').val(term);
            $('#accountHutang').val(coa);
            kosongkanData();
            $.ajax({
                url:"{{ route('ap.list.po') }}",
                method:"GET",
                data:{
                    value:value,
                    edit:edit
                },
                success:function(result){
                        $('#'+obj).html(result);
                        poAda ? $('#'+obj).val(poAda).trigger('change'):'';
                },
                error: function (response) {
                    //Error here
                    Swal.fire("Warning","Get list PO failed","warning");
                }
            })
        }else{
            Swal.fire("Warning","Supplier belum memiliki COA Hutang","warning"); 
        }
    });

    $('#poNumber').change(function(){
        kosongkanData();
        let value = $(this).val();
        let poDate = $(this).find(":selected").data("po-date");
        let poCurrency = $(this).find(":selected").data("po-currency");
        $('#currency').val(poCurrency).trigger("change");
        let poKurs = $(this).find(":selected").data("po-kurs");
        $('#rate').val(poKurs);
        let obj = 'recNumber';
        $('#poDate').val(poDate);
        apNumber = $('#apNumber').val();
        if(value){
            $.ajax({
                url:"{{ route('accountPayable.list.rec') }}",
                method:"GET",
                data:{
                    value:value,
                    apNumber:apNumber,
                    showDetail:showDetail,
                    edit:edit
                },
                success:function(result){
                    if(result){
                        $('#cmdSubmit').removeAttr('disabled');
                        $("#listOfLpb tbody").append(result);
                        if(apNumber){
                            if (edit == 'true'){
                                cmdSubmit();
                            }
                        }
                    }else{
                        $('#cmdSubmit').attr('disabled','disabled');
                    }
                },
                error: function (response) {
                    //Error here
                    Swal.fire("Warning","Get list Rec failed","warning");
                }
            })
        }
    });

    $("#cmdSave").click(function(){  
        let recNumber="";
        let sumQty=0;
        $('input:checkbox[name=customCheck]:checked').each(function(){
            recNumber += $(this).data('rec-number')+",";
            sumQty += parseFloat($(this).data('sum-qty'));
        });
        recNumber=recNumber.slice(0,-1);
        let tableIsi = $('#listOfRec > tbody  tr').length;

        if(parseFloat($("#grandTotalQty").val())!=sumQty){
            Swal.fire("Warning","Data belum sesuai harus di submit ulang","warning"); 
        }else{
            if (recNumber && (tableIsi != 0)){
                if (!$("#frmAdd")[0].checkValidity()){
                    $("#frmAdd").submit();
                }else{
                    $('#cmdSave').attr('disabled','disabled');
                    $('.disabled-el').removeAttr('disabled');
                    $('#recNumberSave').val(recNumber);
                    // ambil semua data article
                    let objArtAcc= $('select[name="articleAccount[]"]');
                    let objArtCode= $('input[name="articleCode[]"]');
                    let objArtDesc= $('input[name="articleDesc[]"]');
                    let objArtCc= $('input[name="articleCc[]"]');
                    let objArtQty= $('input[name="articleQty[]"]');
                    let objArtPrice= $('input[name="articlePrice[]"]');
                    let objArtTotal= $('input[name="articleTotal[]"]');

                    let objAddAccount= $('select[name="addAccount[]"]');
                    let objAddAccountDesc= $('input[name="addAccountDesc[]"]');
                    let objAddAccountCc= $('select[name="addAccountCc[]"]');
                    let objAddAccountDebit= $('input[name="addAccountDebit[]"]');

                    let details = []; 
                    let flag=0; 
                    let pesan="";
                    let cekIsi=0;

                    objArtAcc.map(function(i) {  
                        let $this=$(this);
                        if ($this.val()){
                            let sArtAccount=$this.val();
                            let sArtCode=objArtCode.eq(i).val();
                            let sArtDesc=objArtDesc.eq(i).val();
                            let sArtCc=objArtCc.eq(i).val();
                            let sArtDebit=objArtTotal.eq(i).val().replace(/,/gi, '') || 0;

                            if ((sArtDesc!=='') && ((sArtDebit) != 0) && (sArtAccount!=='') && (sArtCc!=='')){
                                details.push({
                                    "account":sArtAccount,
                                    "description":sArtDesc,
                                    "reference":sArtCode,
                                    "cc":sArtCc,
                                    "debit":sArtDebit,
                                    "credit":0,
                                });
                            }
                            // console.log(details);

                            if ((sArtDesc =='') || (sArtCc =='') || ((sArtDebit) == 0)){
                                cekIsi++;
                            }                       
                        }
                    });

                    objAddAccount.map(function(i) {  
                        let $this=$(this);
                        if ($this.val()){
                            let sAddAccount=$this.val();
                            // let sArtCode=objArtCode.eq(i).val();
                            let sAddAccountDesc=objAddAccountDesc.eq(i).val();
                            let sAddAccountCc=objAddAccountCc.eq(i).val();
                            let sAddAccountDebit=objAddAccountDebit.eq(i).val().replace(/,/gi, '') || 0;

                            if ((sAddAccountDesc!=='') && ((sAddAccountDebit) != 0) && (sAddAccount!=='') && (sAddAccountCc!=='')){
                                details.push({
                                    "account":sAddAccount,
                                    "description":sAddAccountDesc,
                                    "reference":'',
                                    "cc":sAddAccountCc,
                                    "debit":sAddAccountDebit,
                                    "credit":0,
                                });
                            }

                            if ((sAddAccountDesc =='') || (sAddAccountCc =='') || ((sAddAccountDebit) == 0)){
                                cekIsi++;
                            }                       
                        }
                    });

                    if ((details.length == 0) || (cekIsi >0)){
                        pesan +="Detail must be filled Out completely <br>"; 
                        flag=1;
                    }

                    if (flag == 0){
                        // let myformData = $("#frmAdd").serialize();
                        let url='';
                        let apId = '';
                        let detailsData = JSON.stringify(details);
                        let myformData = $("#frmAdd").serializeArray();
                                                
                        if (dariEdit=='true'){
                            url ="{{ route('accountPayable.update') }}";
                            apId =$('#apId').val();
                        }else{
                            url ="{{ route('accountPayable.store') }}";
                        }

                        myformData.push({ name: "details", value: detailsData });
                        myformData.push({ name: "id", value: apId });
                                                
                        $.ajax({
                            type: "post",
                            url: url,
                            // data: myformData+'&details='+detailsData+'&id='+apId,
                            data: myformData,
                            dataType: "json",
                            success: function(data) {
                                if (data.status == 0 ){
                                    let message="";
                                    for(let i = 0; i < data.message.length; i++) {
                                        show_msg(data.title, data.message[i], data.alert);
                                    }                        
                                    $('#apNumber').attr('disabled','disabled');
                                }else{
                                    show_msg(data.title, data.message, data.alert);
                                    $('#apNumber').attr('disabled','disabled');
                                    $('#apNumber').val(data.apNumber);
                                    $('#cmdSave').attr('disabled','disabled');
                                    $('#addNewRow').attr('disabled','disabled');
                                    window.location.href = "{{ route('accountPayable.create') }}";
                                }
                            },
                            error: function(error) {
                                console.log(error);
                            }
                        });
                    }else{
                        $('#cmdSave').removeAttr('disabled');
                        Swal.fire('Warning..',pesan,'warning');
                    }
                }
            }else{
                Swal.fire("Warning","LPB Belum dipilih atau belum di submit","warning"); 
            }
        }
    });

    kosongkanData = () =>{
        $('#basisAmount').val(0);
        $('#basisAmountA').val(0);
        $('#currency').val("IDR").trigger("change");
        $('#rate').val("");
        $('#accountBa').val("").trigger("change");
        
        $("#listOfLpb > tbody").empty();
        $("#listOfRec > tbody").empty();
        $('#cmdSubmit').attr('disabled','disabled');
        
        if (edit == 'false'){
            $("#addItem > tbody").empty();
            add_new_row();
            add_new_row();
            add_new_row();
            add_new_row();
            $('#pph23Check').prop('checked',false);
            $('#pph21Check').prop('checked',false);
            $('#pph42Check').prop('checked',false);
            $("#vatCheck").prop("checked",false);
            $("#nilaiPPH23").text('');
            $("#nilaiPPH21").text('');
            $("#nilaiPPH42").text('');
            $('#totalPPH23').val(0);
            $('#totalPPH21').val(0);
            $('#totalPPH42').val(0);
            $('#totalDiscount').val(0);
            $("#nilaiPPN").text('');
            $('#totalPPN').val(0);
            // $("#accountHutang").val('');
            if(!$("#period").val()){
                $("#period").val('').trigger("change");
            }
        }
        hitungTotal();
    }

    cmdSubmit=()=> {
        let recNumber="";
        let apNumber=$('#apNumber').val();
        $('input:checkbox[name=customCheck]:checked').each(function(){
            recNumber += $(this).data('rec-number')+",";
        });
        recNumber=recNumber.slice(0,-1);
        $("#listOfRec > tbody").empty();
        let poNumber= $('#poNumber').val();
        if(recNumber && poNumber){
            $.ajax({
                url:"{{ route('accountPayable.detail.rec') }}",
                method:"GET",
                data:{
                    poNumber:poNumber,
                    recNumber:recNumber,
                    apNumber:apNumber
                },
                success:function(result){
                    let isiTabel= "";
                    let grandTotalQty=0;
                    // console.log(result.detailRec)
                    if(result.detailRec.length>0){
                        for(i=0;i<result.detailRec.length;i++){
                            urutanRow++;
                            isiTabel +=`<tr>
                                    <td style="padding:0px 5px 0px 5px;">
                                        <select class="form-control activateSelect2" id="articleAccount${i}" name="articleAccount[]">
                                            ${listCoa}
                                        </select>
                                    </td>
                                    <td  style="padding:0px 5px 0px 5px;">
                                        <input type="text" class="form-control-plaintext" id="articleCodeAlternative" name="articleCodeAlternative[]" value="${result.detailRec[i].article}" disabled/></td>
                                        <input type="hidden" class="form-control-plaintext disabled-el" id="articleCode" name="articleCode[]" value="${result.detailRec[i].article_code}"/></td>
                                    <td style="padding:0px 5px 0px 5px;"><input type="text" class="form-control-plaintext disabled-el" id="articleDesc" name="articleDesc[]" value="${result.detailRec[i].desc}" disabled/></td>
                                    <td style="padding:0px 5px 0px 5px;"><input type="text" class="form-control-plaintext disabled-el" id="articleCc" name="articleCc[]" value="${result.detailRec[i].dept}" style="text-align:right;" disabled/></td>
                                    <td style="padding:0px 5px 0px 5px;">${result.detailRec[i].uom}</td>
                                    <td  class="text-right" style="padding:0px 5px 0px 5px;"><input type="text" class="form-control-plaintext disabled-el" id="articleQty" name="articleQty[]" value="${humanizeNumber(parseFloat(result.detailRec[i].qty).toFixed(2))}" style="text-align:right;" disabled/></td>
                                    <td  class="text-right" style="padding:0px 5px 0px 5px;"><input type="text" class="form-control-plaintext disabled-el" id="articlePrice" name="articlePrice[]" value="${humanizeNumber(parseFloat(result.detailRec[i].price).toFixed(2))}" style="text-align:right;" disabled/></td>
                                    <td  class="text-right" style="padding:0px 5px 0px 5px;"><input type="text" class="form-control-plaintext disabled-el" id="articleTotal" name="articleTotal[]" value="${humanizeNumber(parseFloat(result.detailRec[i].total).toFixed(2))}" style="text-align:right;" disabled/></td>
                                </tr>`;
                            console.log(`Qty ${result.detailRec[i].article} : ${Number(result.detailRec[i].qty)}`);
                            // grandTotalQty+=parseFloat(result.detailRec[i].qty);
                            grandTotalQty+=Number(result.detailRec[i].qty);
                            console.log('Grand Total :'+parseFloat(grandTotalQty));
                        }                       

                        $("#listOfRec tbody").append(isiTabel);
                        for(i=0;i<result.detailRec.length;i++){
                            $('#articleAccount'+i).val(result.detailRec[i].account).trigger('change');
                        }
                        $('.activateSelect2').select2();
                        $("#grandTotalQty").val(grandTotalQty);
                        console.log('Grand Total :'+grandTotalQty);
                        let sumQty=0;
                        $('input:checkbox[name=customCheck]:checked').each(function(){
                            recNumber += $(this).data('rec-number')+",";
                            // sumQty += parseFloat($(this).data('sum-qty'));
                            sumQty += Number($(this).data('sum-qty'));
                        });

                        console.log('Grand Total :'+grandTotalQty);
                    }

                    $('#totalPO').val(humanizeNumber(result.summaryRec[0].total_amount_po));
                    $('#basisAmountA').val(humanizeNumber(parseFloat(result.summaryRec[0].basis_amount).toFixed(2)));
                    // $('#basisAmount').val(humanizeNumber(parseFloat(result.summaryRec[0].basis_amount).toFixed(2)));
                    
                    if ((result.summaryRec[0].nilai_pajak>0) && (edit=='false')){
                        $("#vatCheck").prop("checked",true);
                        $('#nilaiPPN').text(sNilaiPPN+"%");
                        $("#taxInvoiceNumber").removeAttr('disabled');
                        $("#taxInvoiceNumber").prop('required',true);
                        $("#totalPPN").removeAttr('disabled');
                        $("#totalPPN").prop('required',true);
                        $('#nilaiPPN').val(humanizeNumber(result.summaryRec[0].vat));
                        $('#totalPPN').val(humanizeNumber(parseFloat(result.summaryRec[0].nilai_pajak).toFixed(2)));
                    }                                        
                    hitungTotal();
                    edit = 'false';
                },
                error: function (response) {
                    //Error here
                    Swal.fire("Warning","Get list data failed","warning");
                }
            })
        }else{
            Swal.fire("Warning","Po atau No Receiving belum dipilih","warning");
        }
    }

    function isiCoa(dependent) {
        $.ajax({
            url:"{{route('dynamic.dependent')}}",
            method:"POST",
            data:{
                dependent:dependent
            },
            success:function(result){
                listCoa = result;
            }
        })
    }

    function isiArticle(dependent,supplierId) {
        $.ajax({
            url:"{{route('dynamic.dependent')}}",
            method:"POST",
            data:{
                dependent:dependent,
                value:supplierId
            },
            success:function(result){
                listArticle = result;
            }
        })
    }

    function changeselect(obj,accountNumber) {
        $('#'+obj).attr('disabled','disabled');
        $('#'+obj).append(listCoa);
        $('#'+obj).select2();
        $('#'+obj).val(accountNumber).trigger('change');
        $('#'+obj).removeAttr('disabled');
    }

    $("#cmdSubmit").click(function (e) {
        cmdSubmit();       
    });

    hitungGrandTotal=()=>{}
    add_new_row =()=>{
        urutanRow++;
        let isiTabel =`<tr>
                            <td width="20%" style="padding:0px 5px 0px 5px;">
                                <select class="form-control activate-select2" id="addAccount${urutanRow}" name="addAccount[]">
                                    ${listCoa}
                                </select>
                            </td>
                            <td width="30%" style="padding:0px 5px 0px 5px;">
                                <input type="text" class="form-control-plaintext" id="addAccountDesc" name="addAccountDesc[]" value="" />
                            </td>
                            <td width="20%" style="padding:0px 5px 0px 5px;">
                                <select class="form-control activate-select2" id="addAccountCc${urutanRow}" name="addAccountCc[]">
                                    ${depts}
                                </select>
                            </td>
                            <td width="10%" style="padding:0px 5px 0px 5px;">
                                <input type="text" 
                                    class="form-control-plaintext numeral-mask-digit" 
                                    id="addAccountDebit" name="addAccountDebit[]" value="" style="text-align:right;" 
                                    oninput='inputDecimal(this)' />
                            </td>
                            <td width=5%" class="text-right" style="padding:0px 5px 0px 5px;">
                                <a onmouseover="this.style.cursor='pointer'" id="deleteButton" onclick="deleteRow(this);hitungTotal()" data-toggle="tooltip" data-placement="left" title="Delete row">
                                    <i data-feather="trash-2" class="remove_button feather-24"></i>
                                </a>
                            </td>
                        </tr>`;
        $("#addItem tbody").append(isiTabel);
        mask_thousand_digit(2);
        feather.replace();
        $('.activate-select2').select2();
    }

    add_new_row_edit =(account,desc,dept,amount)=>{
        urutanRow++;
        let isiTabel =`<tr>
                            <td width="20%" style="padding:0px 5px 0px 5px;">
                                <select class="form-control activate-select2" id="addAccount${urutanRow}" name="addAccount[]">
                                    ${listCoa}
                                </select>
                            </td>
                            <td width="30%" style="padding:0px 5px 0px 5px;">
                                <input type="text" class="form-control-plaintext" id="addAccountDesc" name="addAccountDesc[]" value="${desc}" />
                            </td>
                            <td width="20%" style="padding:0px 5px 0px 5px;">
                                <select class="form-control activate-select2" id="addAccountCc${urutanRow}" name="addAccountCc[]">
                                    ${depts}
                                </select>
                            </td>
                            <td width="10%" style="padding:0px 5px 0px 5px;">
                                <input type="text" 
                                    class="form-control-plaintext numeral-mask-digit" 
                                    id="addAccountDebit" name="addAccountDebit[]" value="${amount}" style="text-align:right;" 
                                    oninput='inputDecimal(this)' />
                            </td>
                            <td width=5%" class="text-right" style="padding:0px 5px 0px 5px;">
                                <a onmouseover="this.style.cursor='pointer'" id="deleteButton" onclick="deleteRow(this);hitungTotal()" data-toggle="tooltip" data-placement="left" title="Delete row">
                                    <i data-feather="trash-2" class="remove_button feather-24"></i>
                                </a>
                            </td>   
                        </tr>`;
        $("#addItem tbody").append(isiTabel);
        $('#addAccount'+urutanRow).val(account).trigger('change');
        $('#addAccountCc'+urutanRow).val(dept).trigger('change');
        mask_thousand_digit(2);
        feather.replace();
        $('.activate-select2').select2();
        hitungTotal();
    }

    function deleteRow(obj) {
        $(obj).closest('tr').remove();
    }

</script>
