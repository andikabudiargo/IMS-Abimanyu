<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');
    let poAda;
    let recAda
    let status ="{{ Session::get('status') ? Session::get('status'): '' }}";
    
    $("#pph23Check").change(function() {
        if(this.checked) {
            let basisAmount = parseInt($('#basisAmount').val().replace(/,/gi, '')) || 0;
            $("#pph23").val(basisAmount * 0.2);
            mask_thousand();
            $("#tipePPH23").removeClass("d-none");
            hitungTotal();
        }else{
            $("#pph23").val(0);
            $("#sewa").prop("checked", true);
            $("#tipePPH23").toggleClass("d-none");
            hitungTotal();  
        }
    });

    $("#vatCheck").change(function() {
        if(this.checked) {
            let basisAmount = parseInt($('#basisAmount').val().replace(/,/gi, '')) || 0;
            $("#vat").val(basisAmount * 0.11);
            mask_thousand();
            $("#tipeVat").removeClass("d-none");
            hitungTotal();
        }else{
            $("#vat").val(0);
            $("#tipeVat").toggleClass("d-none");
            hitungTotal();  
        }
    });

    hitungTotal = () => {
        let ba = parseInt($('#basisAmount').val().replace(/,/gi, '')) || 0;
        let vat = parseInt($('#vat').val().replace(/,/gi, '')) || 0;
        let pph23 = parseInt($('#pph23').val().replace(/,/gi, '')) || 0;
        let od = parseInt($('#otherDeduct').val().replace(/,/gi, '')) || 0;
        let total;
        
        if(vat){
            total = ba? (ba+vat)-(pph23+od) : '';
        }else{
            total = ba? (ba)-(pph23+od) : '';
        }

        $('#grandTotal').val(total);
        mask_thousand();
    }

    $("#basisAmount,#vat,#pph23,#otherDeduct").keyup(function(){
        hitungTotal();
    })
    
    invoiceDate = $('#invoiceDate');
    if (invoiceDate.length) {
        invoiceDate.flatpickr({
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
            },
            success:function(result){
                $('#'+obj).html(result);
                poAda ? $('#'+obj).val(poAda).trigger('change'):$('#'+obj).val('').trigger('change');
            },
            error: function (response) {
                //Error here
                Swal.fire("Warning","Get list PO failed","warning");
            }
        })
    });

    $('#poNumber').change(function(){
        let value = $(this).val();
        let poDate = $(this).find(":selected").data("po-date");
        let obj = 'recNumber';
        $('#poDate').val(poDate);
        $.ajax({
            url:"{{ route('ap.list.rec') }}",
            method:"GET",
            data:{
                value:value,
            },
            success:function(result){
                $('#'+obj).html(result);
                recAda ? $('#'+obj).val(recAda).trigger('change'):$('#'+obj).val('').trigger('change');
            },
            error: function (response) {
                //Error here
                Swal.fire("Warning","Get list Rec failed","warning");
            }
        })
    });

    $('#recNumber').change(function(){
        let poNumber= $('#poNumber').val();
        let recNumber = $(this).val();
        if(recNumber && poNumber){
            $.ajax({
                url:"{{ route('ap.detail.rec') }}",
                method:"GET",
                data:{
                    poNumber:poNumber,
                },
                success:function(result){
                    // let {po_number,nama,pro_inv_num,total_po,basis_amount,due_date,rec_date,po_balance,currency,kurs} = result;
                    $('#poNumberDet').val(result[0].po_number);
                    $('#suppCode').val(result[0].nama);
                    $('#profInvoice').val(result[0].pro_inv_num);
                    $('#totalPO').val(result[0].total_po);
                    $('#basisAmount').val(result[0].basis_amount);
                    // $('#vat').val(result[0].basis_amount*(result[0].vat/100));
                    $('#dueDate').val(result[0].due_date);
                    $('#recDate').val(result[0].rec_date);
                    $('#balance').val(result[0].po_balance);
                    if (status != 'Saved'){
                        $('#currency').val(result[0].currency).trigger('change');
                        $('#rate').val(result[0].kurs);
                    }
                    hitungTotal();
                    $('#invoiceNumber').focus();
                },
                error: function (response) {
                    //Error here
                    Swal.fire("Warning","Get list data failed","warning");
                }
            })
        }
    });

    $("#cmdSave").click(function(){     
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{
            $('.disabled-el').removeAttr('disabled');
            $("#frmAdd").submit();
        }
    });

    kosongkanData = () =>{
        $('#poNumberDet').val("");
        $('#profInvoice').val("");
        $('#suppCode').val("");
        $('#totalPO').val("");
        $('#basisAmount').val("");
        $('#vat').val("");
        $('#dueDate').val("");
        $('#recDate').val("");
        $('#balance').val("");
        $('#currency').val("IDR").trigger("change");
        $('#rate').val("");
        $('#invoiceNumber').val();
        $('#invoiceDate').val(currentDate);
        $('#taxInvoiceNumber').val("");
        $('#accountBa').val("").trigger("change");
        // $('#account').val("").trigger("change");
        $('#otherDeduct').val("");
        $('#pph23Check').prop('checked', false);
        $("#pph23").val(0);
        $("#sewa").prop("checked", true);
        $("#tipePPH23").toggleClass("d-none");
        hitungTotal();
    }


</script>
