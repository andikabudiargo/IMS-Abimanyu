<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');
    let poAda;
    // let recAda;
    // let status ="{{ Session::get('status') ? Session::get('status'): '' }}";
    let sNilaiPPN= "{{ $nilaiPPN }}";
    let sNilaiPPH23= "{{ $nilaiPPH23 }}";
    let sNilaiPPH21= "{{ $nilaiPPH21 }}";
    let sNilaiPPH42= "{{ $nilaiPPH42 }}";
    let sNilaiPpnPembilang= "{{ $ppnPembilang }}";
    let sNilaiPpnPenyebut= "{{ $ppnPenyebut }}";
    let showDetail="";
    let listArticle="";
    let listArticleNp="";
    let listCoa="";
    let edit="";
    let dariEdit="";
    let apType="PO";
    let urutanRow = 0;
    let urutanRowNp = 0;
    let depts = '{!! $depts !!}';

    $("#ppnValue").val(sNilaiPPN);
    $("#pembilangNumber").val(sNilaiPpnPembilang);
    $("#penyebutNumber").val(sNilaiPpnPenyebut);

    // let ppnPenyebut = "{{ $ppnPenyebut }}";
    // let ppnPembilang = "{{ $ppnPembilang }}"; 

    invoiceDate = $('#invoiceDate');
    if (invoiceDate.length) {
        invoiceDate.flatpickr({
            dateFormat: "d-m-Y"
        });
    }

    let delayTimer;
    let delayTimerTax;
    let delayTimerLain;
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

    hitungPpn = () => {
        let aInvDate = invoiceDate.val();
        if(aInvDate){
            getActivePpn(aInvDate).done(function (result) {
                if(result){
                    sNilaiPPN = result.ppnValue;
                    sNilaiPpnPembilang = result.pembilang;
                    sNilaiPpnPenyebut = result.penyebut;
                    $("#ppnValue").val(sNilaiPPN);
                    $("#pembilangNumber").val(sNilaiPpnPembilang);
                    $("#penyebutNumber").val(sNilaiPpnPenyebut);
                    // sNilaiPPN = result;
                    // console.log(sNilaiPPN);
                }
            })
        }
        
        let basisAmount = parseFloat($('#basisAmount').val().replace(/,/gi, '')) || 0;

        if($("#totalDppNilaiLain").val()){
            basisAmount = $("#totalDppNilaiLain").val().replace(/,/gi, '');
        }

        let zTotalPPn = Math.round(basisAmount * (sNilaiPPN/100));
        console.log(`BA Tanpa pembulatan dari ppn:${basisAmount * (sNilaiPPN/100)}`);
        // $("#totalPPN").val(parseFloat(basisAmount * (sNilaiPPN/100)).toFixed(2));
        $("#totalPPN").val(parseFloat(zTotalPPn).toFixed(2));
        $("#nilaiPPN").text(sNilaiPPN+'%');
        $("#totalPPN").removeAttr('disabled');
        $("#taxInvoiceNumber").removeAttr('disabled');
        $("#taxInvoiceNumber").prop('required',true);
        $("#totalPPN").prop('required',true);
        $("#totalPPN").focus().select();
        mask_thousand_digit(2);
        mask_thousand();
        hitungTotal();
    }

    $("#vatCheck").change(function() {
        let aInvDate = invoiceDate.val();
        if (aInvDate){
            if(this.checked) {
                hitungPpn();
            }else{
                $("#totalPPN").val('');
                $("#nilaiPPN").text('');
                $("#taxInvoiceNumber").val('');
                $("#taxInvoiceNumber").attr('disabled','disabled');
                $("#taxInvoiceNumber").prop('required',false);
                $("#totalPPN").prop('required',false);
                $("#totalPPN").attr('disabled','disabled');
                $("#nilaiDppLain").text('');
                $("#totalDppNilaiLain").val('');
                $("#nilaiLainCheck").prop('checked', false);
                hitungTotal();
            }
        }else{
            swal.fire('Warning',"Invoice date belum diisi !!",'warning');
            $("#vatCheck").prop('checked', false);
            $("#nilaiLainCheck").prop('checked', false);
        }
    });

    hitungNilaiLain = () =>{
        let aInvDate = invoiceDate.val();
        if(aInvDate){
            getActivePpn(aInvDate).done(function (result) {
                if(result){
                    sNilaiPPN = result.ppnValue;
                    sNilaiPpnPembilang = result.pembilang;
                    sNilaiPpnPenyebut = result.penyebut;
                    $("#ppnValue").val(sNilaiPPN);
                    $("#pembilangNumber").val(sNilaiPpnPembilang);
                    $("#penyebutNumber").val(sNilaiPpnPenyebut);

                    // console.log(sNilaiPPN);
                }
            })
        }
        
        /*
            jika ada DPP nilai lain maka perhituangan DPP lain-lain
            rumus 11/12* 
            dan untuk PPN 12% nya dihitung dari DPP Nilai Lain * 12%
        */

        let basisAmount = parseFloat($('#basisAmount').val().replace(/,/gi, '')) || 0;
        let zDppNilaiLain = basisAmount * (sNilaiPpnPembilang/sNilaiPpnPenyebut);

        $("#totalDppNilaiLain").val(parseFloat(zDppNilaiLain).toFixed(2));
        $("#nilaiDppLain").text(`${sNilaiPpnPembilang}/${sNilaiPpnPenyebut}`);
        basisAmount = zDppNilaiLain;
        let zTotalPPn = Math.round(basisAmount * (sNilaiPPN/100));
        console.log(`BA Tanpa pembulatan dari nilai lain:${basisAmount * (sNilaiPPN/100)}`);
        $("#vatCheck").prop('checked', true);
        $("#totalPPN").val(parseFloat(zTotalPPn).toFixed(2));
        $("#nilaiPPN").text(sNilaiPPN+'%');
        $("#totalPPN").removeAttr('disabled');
        $("#taxInvoiceNumber").removeAttr('disabled');
        $("#taxInvoiceNumber").prop('required',true);
        $("#totalPPN").prop('required',true);
        $("#totalPPN").focus().select();
        mask_thousand_digit(2);
        mask_thousand();
        hitungTotal();
    }

    $("#nilaiLainCheck").change(function() {
        let aInvDate = invoiceDate.val();
        if (aInvDate){
            if(this.checked) {
                hitungNilaiLain();
            }else{
                $("#totalDppNilaiLain").val('');
                $("#nilaiDppLain").text('');
                $("#taxInvoiceNumber").prop('disabled',true);
                $("#taxInvoiceNumber").prop('required',false);
                hitungTotal();
                if($('#vatCheck').is(':checked')) {
                    hitungPpn();
                }
            }
        }else{
            swal.fire('Warning',"Invoice date belum diisi !!",'warning');
            $("#vatCheck").prop('checked', false);
            $("#nilaiLainCheck").prop('checked', false);
            $("#totalDppNilaiLain").val('');
            $("#nilaiDppLain").text('');
        }
    });

    hitungTotal = () => {
        // console.log(edit);
        let ba = parseFloat($('#basisAmount').val().replace(/[^0-9.]/g, '')) || 0;
        let baA = parseFloat($('#basisAmountA').val().replace(/[^0-9.]/g, '')) || 0;
        // let dppNilaiLain = parseFloat($('#dppNilaiLain').val().replace(/[^0-9.]/g, '')) || 0;

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
                    sNilaiPPN = $("#ppnValue").val();
                    let zDppNilaiLain = ba * (sNilaiPpnPembilang/sNilaiPpnPenyebut);
                    let zba = zDppNilaiLain ? zDppNilaiLain : ba;
                    clearTimeout(delayTimerTax);
                    delayTimerTax = setTimeout(function() {
                        let qTotalPpn = Math.round(zba * (sNilaiPPN/100));
                        $("#totalPPN").val(humanizeNumber(parseFloat(qTotalPpn).toFixed(2)));
                    }, 2100);
                }
                vat = parseFloat($('#totalPPN').val().replace(/[^0-9.]/g, '')) || 0;
            }

            if ($("#nilaiLainCheck").is(':checked')) {
                let zDppNilaiLain = ba * (sNilaiPpnPembilang/sNilaiPpnPenyebut);
                // clearTimeout(delayTimerLain);
                // delayTimerLain = setTimeout(function() {
                    $("#totalDppNilaiLain").val(humanizeNumber(parseFloat(zDppNilaiLain).toFixed(2)));
                    let qTotalPpn = Math.round(zDppNilaiLain * (sNilaiPPN/100));
                    $("#totalPPN").val(humanizeNumber(parseFloat(qTotalPpn).toFixed(2)));
                // }, 2100);
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
        // $('#dppNilaiLain').val(humanizeNumber(parseFloat(dppNilaiLain).toFixed(2)))
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
   
    getActivePpn = (tanggal) => {
        return $.ajax({
            async: false,
            url:"{{route('setting.lastPpn')}}",
            method:"GET",
            data:{
                tanggal:tanggal,
            },
            success:function(result){
            }
        });
    }

    invoiceDate.change(function () {
        let aInvoiceDate = $(this).val();
        getActivePpn(aInvoiceDate).done(function (result) {
            if(result){
                sNilaiPPN = result.ppnValue;
                sNilaiPpnPembilang = result.pembilang;
                sNilaiPpnPenyebut = result.penyebut;

                $("#ppnValue").val(sNilaiPPN);
                $("#pembilangNumber").val(sNilaiPpnPembilang);
                $("#penyebutNumber").val(sNilaiPpnPenyebut);
                // sNilaiPPN = result;
                $("#nilaiPPN").text(`${sNilaiPPN}%`);
                $("#nilaiDppLain").text(`${sNilaiPpnPembilang}/${sNilaiPpnPenyebut}`);
                
                $("#nilaiLainCheck").prop('checked',true).change();

                // if($("#nilaiLainCheck").is(':checked')){
                //     $("#nilaiLainCheck").change();
                // }

                if($("#vatCheck").is(':checked')){
                    $("#vatCheck").change();
                }
            }
        })
    });

    apDate = $('#apDate');
    if (apDate.length) {
        apDate.flatpickr({
            dateFormat: "d-m-Y"
        });
    }

    let dueDatePicker = $('#dueDate');
    if (dueDatePicker.length) {
        dueDatePicker.flatpickr({
            dateFormat: "d-m-Y"
        });
    }

    function calcDueDate() {
        let apDateVal = $('#apDate').val();
        let termDays = parseInt($('#term').val()) || 0;
        if (!apDateVal) {
            return;
        }
        let parts = apDateVal.split('-');
        if (parts.length !== 3) {
            return;
        }
        let d = new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0]));
        d.setDate(d.getDate() + termDays);
        let dd = ('0' + d.getDate()).slice(-2);
        let mm = ('0' + (d.getMonth() + 1)).slice(-2);
        let yyyy = d.getFullYear();
        $('#dueDate').val(dd + '-' + mm + '-' + yyyy);
    }

    $('#apDate').on('change', function () {
        calcDueDate();
    });

    let bupotDatePicker = $('#BupotDate');
    if (bupotDatePicker.length) {
        bupotDatePicker.flatpickr({
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

    $('#supplier').change(function(e, isSync){
        let value= $(this).val();
        let obj = 'poNumber';
        let term = $(this).find(":selected").data("term");
        let coa = $(this).find(":selected").data("coa");
        let coaDesc = $(this).find(":selected").data("coa-desc");
        isiArticleNp(value);
        if(coa){
            $('#term').val(term);
            $('#accountHutang').val(coa + (coaDesc ? ' - '+coaDesc : ''));
            $('#accountHutangCode').val(coa);
            // isSync: re-sync setelah load data edit (bukan aksi user), jangan timpa due_date yang sudah tersimpan
            if(!isSync){
                calcDueDate();
            }
            kosongkanData();
            $.ajax({
                url:"{{ route('accountPayable.list.po') }}",
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

        //Bandingkan hanya angka bulanya saja (tidak berlaku untuk Non-PO, tidak ada LPB yang ditarik)
        let qtyBelumSesuai = (apType === 'NONPO') ? false : (Math.trunc(parseFloat($("#grandTotalQty").val()||0))!=Math.trunc(sumQty));
        if(qtyBelumSesuai){
            Swal.fire("Warning","Data belum sesuai harus di submit ulang","warning");
        }else{
            let siapDisimpan = (apType === 'NONPO') ? (tableIsi != 0) : (recNumber && (tableIsi != 0));
            if (siapDisimpan){
                if (!$("#frmAdd")[0].checkValidity()){
                    $("#frmAdd").submit();
                }else{
                    $('#cmdSave').attr('disabled','disabled');
                    $('.disabled-el').removeAttr('disabled');
                    $('#recNumberSave').val(recNumber);
                    // ambil semua data article
                    let objArtAcc= $('select[name="articleAccount[]"]');
                    let objArtCode= $('[name="articleCode[]"]');
                    let objArtDesc= $('input[name="articleDesc[]"]');
                    let objArtCc= $('[name="articleCc[]"]');
                    let objArtQty= $('input[name="articleQty[]"]');
                    let objArtPrice= $('input[name="articlePrice[]"]');
                    let objArtTotal= $('input[name="articleTotal[]"]');
                    let objArtUom= $('[name="articleUom[]"]');

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
                                    "qty": apType === 'NONPO' ? (objArtQty.eq(i).val()||'').toString().replace(/,/gi, '') : null,
                                    "price": apType === 'NONPO' ? (objArtPrice.eq(i).val()||'').toString().replace(/,/gi, '') : null,
                                    "uom": apType === 'NONPO' ? (objArtUom.eq(i).val()||'') : null,
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
                Swal.fire("Warning", apType === 'NONPO' ? "Detail item belum diisi" : "LPB Belum dipilih atau belum di submit", "warning");
            }
        }
    });

    kosongkanData = () =>{
        $('#basisAmount').val(0);
        $('#basisAmountA').val(0);
        $('#dppNilainLain').val(0);
        $('#dppNilainLainA').val(0);
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
        $("#cmdSubmit").attr('disabled','disabled');
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
                                    <td style="padding:0px 5px 0px 5px;">
                                        <select class="form-control activateSelect2 disabled-el" id="articleCc${i}" name="articleCc[]" disabled>
                                            ${depts}
                                        </select>
                                    </td>
                                    <td style="padding:0px 5px 0px 5px;">${result.detailRec[i].uom}</td>
                                    <td  class="text-right" style="padding:0px 5px 0px 5px;"><input type="text" class="form-control-plaintext disabled-el" id="articleQty" name="articleQty[]" value="${humanizeNumber(parseFloat(result.detailRec[i].qty).toFixed(2))}" style="text-align:right;" disabled/></td>
                                    <td  class="text-right" style="padding:0px 5px 0px 5px;"><input type="text" class="form-control-plaintext disabled-el" id="articlePrice" name="articlePrice[]" value="${humanizeNumber(parseFloat(result.detailRec[i].price).toFixed(2))}" style="text-align:right;" disabled/></td>
                                    <td  class="text-right" style="padding:0px 5px 0px 5px;"><input type="text" class="form-control-plaintext disabled-el" id="articleTotal" name="articleTotal[]" value="${humanizeNumber(parseFloat(result.detailRec[i].total).toFixed(2))}" style="text-align:right;" disabled/></td>
                                    <td style="padding:0px 5px 0px 5px;"></td>
                                </tr>`;
                            grandTotalQty+=Number(result.detailRec[i].qty);
                        }                       

                        $("#listOfRec tbody").append(isiTabel);
                        for(i=0;i<result.detailRec.length;i++){
                            $('#articleAccount'+i).val(result.detailRec[i].account).trigger('change');
                            $('#articleCc'+i).val(result.detailRec[i].dept).trigger('change');
                        }
                        $('.activateSelect2').select2();
                        $("#grandTotalQty").val(grandTotalQty);
                        // console.log('Grand Total :'+Math.trunc(grandTotalQty));
                        let sumQty=0;
                        $('input:checkbox[name=customCheck]:checked').each(function(){
                            recNumber += $(this).data('rec-number')+",";
                            // sumQty += parseFloat($(this).data('sum-qty'));
                            sumQty += Number($(this).data('sum-qty'));
                        });
                        // console.log('Cek Grand Total :'+Math.trunc(sumQty));
                        $("#cmdSubmit").removeAttr('disabled');
                    }

                    $('#totalPO').val(humanizeNumber(result.summaryRec[0].total_amount_po));
                    $('#basisAmountA').val(humanizeNumber(parseFloat(result.summaryRec[0].basis_amount).toFixed(2)));
                    // let dppNilainLain = (11/12)*result.summaryRec[0].basis_amount;
                    // $('#dppNilaiLainA').val(humanizeNumber(parseFloat(dppNilainLain.toFixed(2))));
                    // $('#basisAmount').val(humanizeNumber(parseFloat(result.summaryRec[0].basis_amount).toFixed(2)));
                    // let zNilaiPPN = $("#ppnValue").val();
                    // if ((result.summaryRec[0].nilai_pajak>0) && (edit=='false')){
                    //     // $("#vatCheck").prop("checked",true); 
                                                
                    //     $('#nilaiPPN').text(zNilaiPPN+"%");
                    //     $("#taxInvoiceNumber").removeAttr('disabled');
                    //     $("#taxInvoiceNumber").prop('required',true);
                    //     $("#totalPPN").removeAttr('disabled');
                    //     $("#totalPPN").prop('required',true);
                                               
                    //     // $('#nilaiPPN').val(humanizeNumber(result.summaryRec[0].vat));
                    //     // $('#totalPPN').val(humanizeNumber(parseFloat(result.summaryRec[0].nilai_pajak).toFixed(2)));
                    // }                                        
                    hitungTotal();
                    edit = 'false';
                },
                error: function (response) {
                    //Error here
                    Swal.fire("Warning","Get list data failed","warning");
                    $("#cmdSubmit").removeAttr('disabled');
                }
            })
        }else{
            Swal.fire("Warning","Po atau No Receiving belum dipilih","warning");
            $("#cmdSubmit").removeAttr('disabled');
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

    function isiArticleNp(supplierId) {
        $.ajax({
            url:"{{route('dynamic.dependent')}}",
            method:"POST",
            data:{
                dependent:'article_ap_np',
                value:supplierId
            },
            success:function(result){
                listArticleNp = result;
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

    function applyApTypeUi(type) {
        if (type === 'NONPO') {
            $('#poNumber').prop('required', false);
            $('#poNumber').prop('disabled', true);
            $('#lpbSection').addClass('d-none');
            $('#addArticleNpBtn').removeClass('d-none');
            $('#detailLabel').text('Detail Item (Non-PO)');
            $('#currency').val('IDR').trigger('change');
        } else {
            $('#poNumber').prop('required', true);
            $('#poNumber').prop('disabled', false);
            $('#lpbSection').removeClass('d-none');
            $('#addArticleNpBtn').addClass('d-none');
            $('#detailLabel').text('Detail receiving');
        }
    }

    $('body').on('change', 'select[name=apType]', function () {
        apType = $(this).val();
        applyApTypeUi(apType);
        $("#listOfRec > tbody").empty();
        urutanRowNp = 0;
        $('#poNumber').val('').trigger('change');
        hitungTotalNp();
    });

    add_new_row_np = () => {
        urutanRowNp++;
        let isiTabel = `<tr>
                            <td width="17%" style="padding:0px 5px 0px 5px;">
                                <select class="form-control activate-select2" id="articleAccountNp${urutanRowNp}" name="articleAccount[]">
                                    ${listCoa}
                                </select>
                            </td>
                            <td width="11%" style="padding:0px 5px 0px 5px;">
                                <select class="form-control activate-select2" id="articleCodeNp${urutanRowNp}" name="articleCode[]">
                                    ${listArticleNp}
                                </select>
                            </td>
                            <td style="padding:0px 5px 0px 5px;">
                                <input type="text" class="form-control-plaintext" name="articleDesc[]" value="" />
                            </td>
                            <td width="10%" style="padding:0px 5px 0px 5px;">
                                <select class="form-control activate-select2" id="articleCcNp${urutanRowNp}" name="articleCc[]">
                                    ${depts}
                                </select>
                            </td>
                            <td width="5%" style="padding:0px 5px 0px 5px;text-align:center;">
                                <span class="npUomText"></span>
                                <input type="hidden" name="articleUom[]" value="" />
                            </td>
                            <td width="7%" style="padding:0px 5px 0px 5px;">
                                <input type="text" class="form-control-plaintext numeral-mask-digit npQty" name="articleQty[]" value="" style="text-align:right;" oninput="calcNpRowTotal(this)" />
                            </td>
                            <td width="9%" style="padding:0px 5px 0px 5px;">
                                <input type="text" class="form-control-plaintext numeral-mask-digit npPrice" name="articlePrice[]" value="" style="text-align:right;" oninput="calcNpRowTotal(this)" />
                            </td>
                            <td width="11%" class="text-right" style="padding:0px 5px 0px 5px;">
                                <input type="text" class="form-control-plaintext disabled-el npTotal" name="articleTotal[]" value="0" style="text-align:right;" disabled />
                            </td>
                            <td width="4%" class="text-right" style="padding:0px 5px 0px 5px;">
                                <a onmouseover="this.style.cursor='pointer'" onclick="deleteRow(this);hitungTotalNp();" data-toggle="tooltip" data-placement="left" title="Delete row">
                                    <i data-feather="trash-2" class="remove_button feather-24"></i>
                                </a>
                            </td>
                        </tr>`;
        $("#listOfRec tbody").append(isiTabel);
        mask_thousand_digit(2);
        feather.replace();
        $('.activate-select2').select2();
    }

    add_new_row_np_edit = (row) => {
        add_new_row_np();
        let $tr = $('#articleCodeNp' + urutanRowNp).closest('tr');
        $('#articleAccountNp' + urutanRowNp).val(row.account).trigger('change');
        $('#articleCodeNp' + urutanRowNp).val(row.reference).trigger('change');
        $('#articleCcNp' + urutanRowNp).val(row.cost_center).trigger('change');
        $tr.find('input[name="articleDesc[]"]').val(row.description || '');
        $tr.find('input[name="articleUom[]"]').val(row.uom || '');
        $tr.find('.npUomText').text(row.uom || '');
        $tr.find('.npQty').val(humanizeNumber(parseFloat(row.qty || 0).toFixed(2)));
        $tr.find('.npPrice').val(humanizeNumber(parseFloat(row.price || 0).toFixed(2)));
        $tr.find('.npTotal').val(humanizeNumber(parseFloat(row.debit || 0).toFixed(2)));
        mask_thousand_digit(2);
    }

    $('body').on('change', 'select[name="articleCode[]"]', function () {
        let $tr = $(this).closest('tr');
        let detail = $(this).find(':selected').data('detail');
        let parts = detail ? detail.toString().split('|') : ['', '', ''];
        let uom = parts[1] || '';
        let desc = parts[2] || '';
        $tr.find('input[name="articleDesc[]"]').val(desc);
        $tr.find('input[name="articleUom[]"]').val(uom);
        $tr.find('.npUomText').text(uom);
    });

    function calcNpRowTotal(el) {
        let $tr = $(el).closest('tr');
        let qty = parseFloat(($tr.find('.npQty').val() || '').toString().replace(/,/gi, '')) || 0;
        let price = parseFloat(($tr.find('.npPrice').val() || '').toString().replace(/,/gi, '')) || 0;
        let total = qty * price;
        $tr.find('.npTotal').val(humanizeNumber(parseFloat(total).toFixed(2)));
        hitungTotalNp();
    }

    function hitungTotalNp() {
        let totals = $('.npTotal').map(function () { return ($(this).val() || '0').toString().replace(/,/gi, ''); }).get();
        let sum = sumFromArray(totals);
        $('#basisAmountA').val(sum);
        hitungTotal();
    }

</script>
