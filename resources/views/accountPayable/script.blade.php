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
    let edit="";

    let delayTimer;
    function inputDecimal(ele) {
        clearTimeout(delayTimer);
        delayTimer = setTimeout(function() {
            let nilai = ele.value.replace(/,/gi, '') || 0;;
            ele.value = humanizeNumber(parseFloat(nilai).toFixed(2)).toString();
        }, 1100); 
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
        let ba = parseFloat($('#basisAmount').val().replace(/[^0-9.]/g, '')) || 0;
        let vat = parseFloat($('#totalPPN').val().replace(/[^0-9.]/g, '')) || 0;
        let pph23 = parseFloat($('#totalPPH23').val().replace(/[^0-9.]/g, '')) || 0;
        let pph21 = parseFloat($('#totalPPH21').val().replace(/[^0-9.]/g, '')) || 0;
        let pph42 = parseFloat($('#totalPPH42').val().replace(/[^0-9.]/g, '')) || 0;
        let od = parseFloat($('#totalDiscount').val().replace(/[^0-9.]/g, '')) || 0;
        let total;
        
        if(vat){
            total = ba? (ba-od)+vat-(pph23+pph21+pph42) : '';
        }else{
            total = ba? (ba-od)-(pph23+pph21+pph42) : '';
        }
    
        $('#grandTotal').val(humanizeNumber(parseFloat(total).toFixed(2)));
        // mask_thousand_digit(2);
        // mask_thousand();
    }

    $("#basisAmount,#totalPPN,#totalPPH23,#totalPPH21,#totalPPH42,#totalDiscount").keyup(function(){
        hitungTotal();
    })
   
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
                url:"{{ route('ap.list.rec') }}",
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
        let accountHutang = $('#accountHutang').val();

        if (accountHutang){

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
                        $("#cmdSave").attr('disabled','disabled')
                        $('.disabled-el').removeAttr('disabled');
                        $('#recNumberSave').val(recNumber);
                        $("#frmAdd").submit();
                    }
                }else{
                    Swal.fire("Warning","LPB Belum dipilih atau belum di submit","warning"); 
                }
            }
        }else{
            Swal.fire("Warning","Supplier belum memiliki COA Hutang","warning"); 
        }
       
    });

    kosongkanData = () =>{
        $('#basisAmount').val("");
        $('#currency').val("IDR").trigger("change");
        $('#rate').val("");
        $('#accountBa').val("").trigger("change");
        
        $("#listOfLpb > tbody").empty();
        $("#listOfRec > tbody").empty();
        $('#cmdSubmit').attr('disabled','disabled');
        $('#basisAmount').val(0);
        
        if (edit == 'false'){
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
        }
        hitungTotal();
    }

    cmdSubmit=()=> {
        let recNumber="";
        $('input:checkbox[name=customCheck]:checked').each(function(){
            recNumber += $(this).data('rec-number')+",";
        });
        recNumber=recNumber.slice(0,-1);
        $("#listOfRec > tbody").empty();
        let poNumber= $('#poNumber').val();
        if(recNumber && poNumber){
            $.ajax({
                url:"{{ route('ap.detail.rec') }}",
                method:"GET",
                data:{
                    poNumber:poNumber,
                    recNumber:recNumber
                },
                success:function(result){
                    let isiTabel= "";
                    let grandTotalQty=0;
                    if(result.detailRec.length>0){
                        for(i=0;i<result.detailRec.length;i++){
                            isiTabel +=`<tr>
                                    <td>${result.detailRec[i].article}</td>
                                    <td>${result.detailRec[i].desc}</td>
                                    <td>${result.detailRec[i].uom}</td>
                                    <td class="text-right">${humanizeNumber(result.detailRec[i].qty)}</td>
                                    <td class="text-right">${humanizeNumber(parseFloat(result.detailRec[i].price).toFixed(2))}</td>
                                    <td class="text-right">${humanizeNumber(parseFloat(result.detailRec[i].total).toFixed(2))}</td>
                                </tr>`;
                            grandTotalQty+=parseFloat(result.detailRec[i].qty);
                        }

                        $("#listOfRec tbody").append(isiTabel);
                        $("#grandTotalQty").val(grandTotalQty);
                    }

                    $('#totalPO').val(humanizeNumber(result.summaryRec[0].total_amount_po));
                    $('#basisAmount').val(humanizeNumber(parseFloat(result.summaryRec[0].basis_amount).toFixed(2)));
                    
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
                    edit == false;

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

    $("#cmdSubmit").click(function (e) {
        cmdSubmit();        
    });


</script>
