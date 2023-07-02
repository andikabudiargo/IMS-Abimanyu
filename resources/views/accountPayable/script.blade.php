<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');
    let poAda;
    // let recAda;
    // let status ="{{ Session::get('status') ? Session::get('status'): '' }}";
    let sNilaiPPN= "{{ $nilaiPPN }}";
    let sNilaiPPH= "{{ $nilaiPPH }}";
    let showDetail="";
    let edit="";
    
    $("#pph23Check").change(function() {
        if(this.checked) {
            let basisAmount = parseInt($('#basisAmount').val().replace(/,/gi, '')) || 0;
            $("#totalPPH").val(basisAmount * (sNilaiPPH/100));
            $("#nilaiPPH").text(sNilaiPPH+'%');
            mask_thousand();
            hitungTotal();
        }else{
            $("#totalPPH").val(0);
            $("#nilaiPPH").text('');
            hitungTotal();  
        }
    });

    $("#vatCheck").change(function() {
        if(this.checked) {
            let basisAmount = parseInt($('#basisAmount').val().replace(/,/gi, '')) || 0;
            $("#totalPPN").val(basisAmount * (sNilaiPPN/100));
            $("#nilaiPPN").text(sNilaiPPN+'%');
            mask_thousand();
            hitungTotal();
        }else{
            $("#totalPPN").val(0);
            $("#nilaiPPN").text('');
            hitungTotal();
        }
    });

    hitungTotal = () => {
        let ba = parseInt($('#basisAmount').val().replace(/,/gi, '')) || 0;
        let vat = parseInt($('#totalPPN').val().replace(/,/gi, '')) || 0;
        let pph23 = parseInt($('#totalPPH').val().replace(/,/gi, '')) || 0;
        let od = parseInt($('#totalDiscount').val().replace(/,/gi, '')) || 0;
        let total;
        
        if(vat){
            total = ba? (ba-od)+vat-pph23 : '';
        }else{
            total = ba? (ba-od)-(pph23) : '';
        }

        $('#grandTotal').val(total);
        mask_thousand();
    }

    $("#basisAmount,#totalPPN,#totalPPH,#totalDiscount").keyup(function(){
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
                            cmdSubmit();
                        }
                    }else{
                        $('#cmdSubmit').attr('disabled','disabled');
                    }
    
                    // $('#'+obj).html(result);
                    // recAda ? $('#'+obj).val(recAda).trigger('change'):$('#'+obj).val('').trigger('change');
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
            sumQty += parseInt($(this).data('sum-qty'));
        });

        recNumber=recNumber.slice(0,-1);
        let tableIsi = $('#listOfRec > tbody  tr').length;
        if(parseInt($("#grandTotalQty").val())!=sumQty){
            Swal.fire("Warning","Data belum sesuai harus di submit ulang","warning"); 
        }else{
            if (recNumber && (tableIsi != 0)){
                if (!$("#frmAdd")[0].checkValidity()){
                    $("#frmAdd").submit();
                }else{
                    $('.disabled-el').removeAttr('disabled');
                    
                    $('#recNumberSave').val(recNumber);
                    $("#frmAdd").submit();
                }
            }else{
                Swal.fire("Warning","LPB Belum dipilih atau belum di submit","warning"); 
            }
        }
    });

    kosongkanData = () =>{
        $('#basisAmount').val("");
        $('#currency').val("IDR").trigger("change");
        $('#rate').val("");
        
        // $('#apDate').val(currentDate);

        $('#accountBa').val("").trigger("change");

        $('#pph23Check').prop('checked',false);
        $("#totalPPH").val(0);
        $("#nilaiPPH").text('');

        $("#vatCheck").prop("checked",false);
        $("#nilaiPPN").text('');
        
        // $("#sewa").prop("checked", true);
        // $("#tipePPH23").toggleClass("d-none");

        $("#listOfLpb > tbody").empty();
        $("#listOfRec > tbody").empty();
        $('#cmdSubmit').attr('disabled','disabled');
        $('#basisAmount').val(0);
        $('#totalPPN').val(0);
        $('#totalPPH').val(0);
        $('#totalDiscount').val(0);
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
                                    <td class="text-right">${humanizeNumber(result.detailRec[i].price)}</td>
                                    <td class="text-right">${humanizeNumber(result.detailRec[i].total)}</td>
                                </tr>`;
                            grandTotalQty+=parseInt(result.detailRec[i].qty);

                        }

                        $("#listOfRec tbody").append(isiTabel);
                        $("#grandTotalQty").val(grandTotalQty);
                    }

                    $('#totalPO').val(humanizeNumber(result.summaryRec[0].total_amount_po));
                    $('#basisAmount').val(humanizeNumber(result.summaryRec[0].basis_amount));
                    $('#totalPPN').val(humanizeNumber(result.summaryRec[0].nilai_pajak));
                    
                    if (result.summaryRec[0].nilai_pajak>0){
                        $("#vatCheck").prop("checked",true );
                        $('#nilaiPPN').text(sNilaiPPN+"%");
                    }

                    if (result.summaryRec[0].pph22>0){
                        $('#pph23Check').prop('checked',true);
                        $('#nilaiPPH').text(sNilaiPPH+"%");
                    }
                    
                    $('#nilaiPPN').val(humanizeNumber(result.summaryRec[0].vat));
                    $('#totalPPH').val(humanizeNumber(result.summaryRec[0].pph22));

                    hitungTotal();

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
