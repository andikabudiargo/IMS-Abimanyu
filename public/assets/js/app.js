    'use strict';

    /* 
    loading spinner
    setiap buka page akan keluar ini
    */
    // start spinner
    $(".loading-spinner-container").addClass("-show");
    $(window).on('load', function(){
        if (feather) {
            feather.replace({
                width: 14,
                height: 14
            });
        }
        setTimeout(function () {
            $(".loading-spinner-container").removeClass("-show");
        }, 1000);
    });
    // end spinner

    let inactivityTimeout
    let inActiveTime = configku.env.inActiveTime;
    let autoLogoutTime = configku.env.autoLogoutTime;

    let hour=0;
    let minute=0;
    let second=0;
    let texthour ="";
    let textminute ="";
    let textSecond ="";
    let timer;

    let sudahKeluarAlert="No";

    function alertOn() {
        // document.getElementById("boxOverlay").style.display = "block";
        $('#mdlSessionTimeout').modal('show'); 
        sudahKeluarAlert = 'Yes';
        // console.log('alert harusnya keluar');
    }

    function alertOff() {
        // document.getElementById("boxOverlay").style.display = "none";
        $('#mdlSessionTimeout').modal('hide'); 
        sudahKeluarAlert = 'No';
        resetTimer();
        clearInterval(timer);
    }
        
    function setupInactivity() {
        window.addEventListener('mousemove', resetTimer, false);
        window.addEventListener('mousedown', resetTimer, false);
        window.addEventListener('keypress', resetTimer, false);
        window.addEventListener('DOMMouseScroll', resetTimer, false);
        window.addEventListener('mousewheel', resetTimer, false);
        window.addEventListener('touchmove', resetTimer, false);
        window.addEventListener('MSPointerMove', resetTimer, false);
        if (sudahKeluarAlert=="No"){
            startInactivityTimer();
        }
    }

    setupInactivity();

    function startInactivityTimer() {
        // console.log("Start Inactivity Timer");
        // wait inActiveTime seconds before calling goInactive
        inactivityTimeout = window.setTimeout(goInactive,inActiveTime*1000);
        hour=0;
        minute=0;
        second=0;
        texthour ="";
        textminute ="";
        textSecond ="";
    }

    function resetTimer(e) {
        // console.log("Sudah keluar Alert:"+sudahKeluarAlert);
        if (sudahKeluarAlert=="No"){
            // console.log("Nah kesini dia");
            window.clearTimeout(inactivityTimeout);
            goActive();
        }
    }

    function goInactive() {    
        // do something
        // console.log("kesini");
        var pathname = window.location.pathname.split('/')[1];
        var exceptPaths = [
        'login'
        ];

        clearInterval(timer);

        if ((pathname == '' || pathname.length) && exceptPaths.indexOf(pathname) < 0) {
            let closeInSeconds="";
            closeInSeconds = autoLogoutTime;
            let displayText = "in #1";
            let persen=0;

            alertOn();
            timer = setInterval(function() {
                closeInSeconds--;   
                if (closeInSeconds < 0) {
                    clearInterval(timer);
                    // alertOff();
                    goLogout();
                }
                persen = (closeInSeconds/(autoLogoutTime-1))*100;
                // console.log(closeInSeconds+"/"+(autoLogoutTime-1)+"="+persen.toFixed());
                let detik = closeInSeconds*1000;
                hour = Math.floor((detik) /3600000);
                minute = Math.floor(((detik) - hour*3600000)/60000);
                second = ((detik) - (hour*3600000 + minute*60000))/1000;

                // console.log(hour+':'+minute+':'+second);

                $("#progressTimeout").css("width", persen.toFixed()+'%');
                
                if (closeInSeconds > 60){
                    if (hour == 0 ){
                        textSecond = second > 1 ? " Seconds " : " Second " ;
                        textminute = minute > 1 ? " Minutes " : " Minute " ;               
                        $('#textoverlay').text(displayText.replace(/#1/, + minute + textminute + second + textSecond));
                    }else{
                        textminute = minute > 1 ? " Minutes " : " Minute ";
                        texthour = hour > 1 ? " Hours " : " Hour ";
                        textSecond = second > 1 ? " Seconds " : " Second " ;
                        $('#textoverlay').text(displayText.replace(/#1/, + hour + texthour + minute + textminute + second + textSecond));
                    }   
                }else{
                    textSecond = second > 1 ? " Seconds" : " Second " ;
                    $('#textoverlay').text(displayText.replace(/#1/, closeInSeconds + textSecond));
                } 

            }, 1000);
        }
    }

    function goLogout() {
        let pathname = window.location.pathname.split('/')[1];
        let exceptPaths = [
            'login'
        ];

        if ((pathname == '' || pathname.length) && exceptPaths.indexOf(pathname) < 0) {
            // window.location.href = configku.env.autoLogout;
            document.getElementById('logout-form').submit();
        }
    }

    function goActive() {
        startInactivityTimer();
    }

    $("#cmdHelpOk").click(function (e) {
        alertOff();
    });

    $("#cmdHelpLogout").click(function (e) {
        goLogout();
    });

    $(".angka").on("input", function(evt) {
        //angka bulat alias integer, tidak boleh ada karakter lain
        let self = $(this);
        self.val(self.val().replace(/\D/g, ""));
        if ((evt.which < 48 || evt.which > 57)){
            evt.preventDefault();
        }
    });

    function activate_angka(){
        $(".angka").on("input", function(evt) {
            //angka bulat alias integer, tidak boleh ada karakter lain
            let self = $(this);
            self.val(self.val().replace(/\D/g, ""));
            if ((evt.which < 48 || evt.which > 57)){
                evt.preventDefault();
            }
        });
    }

    function angka_dua_decimal(){
        $(".angka-dua-decimal").on("keypress", function(event) {
            //bisa pake tanda . dan hanya bisa inpur dua digit desimal
            var self = $(this);
            if ((event.which != 46 || self.val().indexOf('.') != -1) &&
                ((event.which < 48 || event.which > 57) &&
                (event.which != 0 && event.which != 8))) {
                    event.preventDefault();
            }
        
            var text = $(this).val();
            console.log(event.which);
            if ((event.which == 46) && (text.indexOf('.') == -1)) {
                setTimeout(function() {
                    if (self.val().substring(self.val().indexOf('.')).length > 3) {
                        self.val(self.val().substring(0, self.val().indexOf('.') + 3));
                    }
                }, 1);
            }
        
            if ((text.indexOf('.') != -1) &&
                (text.substring(text.indexOf('.')).length > 2) &&
                (event.which != 0 && event.which != 8) &&
                ($(this)[0].selectionStart >= text.length - 2)) {
                    event.preventDefault();
            }
        });
    }    
    
    $(".angka-dua-decimal").on("keypress", function(event) {
        //bisa pake tanda . dan hanya bisa inpur dua digit desimal
        var self = $(this);
        if ((event.which != 46 || self.val().indexOf('.') != -1) &&
            ((event.which < 48 || event.which > 57) &&
            (event.which != 0 && event.which != 8))) {
                event.preventDefault();
        }
    
        var text = $(this).val();
        console.log(event.which);
        if ((event.which == 46) && (text.indexOf('.') == -1)) {
            setTimeout(function() {
                if (self.val().substring(self.val().indexOf('.')).length > 3) {
                    self.val(self.val().substring(0, self.val().indexOf('.') + 3));
                }
            }, 1);
        }
    
        if ((text.indexOf('.') != -1) &&
            (text.substring(text.indexOf('.')).length > 2) &&
            (event.which != 0 && event.which != 8) &&
            ($(this)[0].selectionStart >= text.length - 2)) {
                event.preventDefault();
        }
    });

    $(".angka-decimal").on("keypress", function(event) {
        //bisa pake tanda . dan hanya bisa inpur dua digit desimal
        var self = $(this);
        if ((event.which != 46 || self.val().indexOf('.') != -1) &&
            ((event.which < 48 || event.which > 57) &&
            (event.which != 0 && event.which != 8))) {
                event.preventDefault();
        }
    
        var text = $(this).val();
        console.log(event.which);
        if ((event.which == 46) && (text.indexOf('.') == -1)) {
            setTimeout(function() {
                if (self.val().substring(self.val().indexOf('.')).length > 3) {
                    self.val(self.val().substring(0, self.val().indexOf('.') + 3));
                }
            }, 1);
        }
    });

    function forceNumber(element) {
    //angka bisa negative, deciml, maksimum 2 digit decimal
        element
          .data("oldValue", '')
          .bind("paste", function(e) {
            var validNumber = /^[-]?\d+(\.\d{1,2})?$/;
            element.data('oldValue', element.val())
            setTimeout(function() {
              if (!validNumber.test(element.val()))
                element.val(element.data('oldValue'));
            }, 0);
          });
        element
          .keypress(function(event) {
            var text = $(this).val();
            if ((event.which != 46 || text.indexOf('.') != -1) && //if the keypress is not a . or there is already a decimal point
              ((event.which < 48 || event.which > 57) && //and you try to enter something that isn't a number
                (event.which != 45 || (element[0].selectionStart != 0 || text.indexOf('-') != -1)) && //and the keypress is not a -, or the cursor is not at the beginning, or there is already a -
                (event.which != 0 && event.which != 8))) { //and the keypress is not a backspace or arrow key (in FF)
              event.preventDefault(); //cancel the keypress
            }
      
            if ((text.indexOf('.') != -1) && (text.substring(text.indexOf('.')).length > 2) && //if there is a decimal point, and there are more than two digits after the decimal point
              ((element[0].selectionStart - element[0].selectionEnd) == 0) && //and no part of the input is selected
              (element[0].selectionStart >= element.val().length - 2) && //and the cursor is to the right of the decimal point
              (event.which != 45 || (element[0].selectionStart != 0 || text.indexOf('-') != -1)) && //and the keypress is not a -, or the cursor is not at the beginning, or there is already a -
              (event.which != 0 && event.which != 8)) { //and the keypress is not a backspace or arrow key (in FF)
              event.preventDefault(); //cancel the keypress
            }
        });
    }
    
    function activate_angka2(){
        //sama seperti angka2 tapi di aktifkan sama function
        //angka bulat alias integer, tidak boleh ada karakter lain bisa pake titik
        $(".angka2").keypress(function (e) {
            if (e.which != 8 && e.which != 46  && e.which != 0  && e.which != 32 && (e.which < 48 || e.which > 57)) {
                return false;
            }
        });
    }
    
    $('.angka-negative-desimal').keypress(function(event) {
        //bisa pake tanda . dan hanya bisa input dua digit desimal
        let $this = $(this);
        if ((event.which != 46 || $this.val().indexOf('.') != -1) &&
            ((event.which < 48 || event.which > 57) &&
            (event.which != 0 && event.which != 8))) {
                event.preventDefault();
        }

        let text = $(this).val();
        if ((event.which == 46) && (text.indexOf('.') == -1)) {
            setTimeout(function() {
                if ($this.val().substring($this.val().indexOf('.')).length > 3) {
                    $this.val($this.val().substring(0, $this.val().indexOf('.') + 3));
                }
            }, 1);
        }

        if ((text.indexOf('.') != -1) &&
            (text.substring(text.indexOf('.')).length > 2) &&
            (event.which != 0 && event.which != 8) &&
            ($(this)[0].selectionStart >= text.length - 2)) {
                event.preventDefault();
        }
    });
    
    function angkaNegativeDesimal(element) {
        //angka bisa negative, decimal, maksimum 2 digit decimal
        element
            .data("oldValue", '')
            .bind("paste", function(e) {
            var validNumber = /^[-]?\d+(\.\d{1,2})?$/;
            element.data('oldValue', element.val())
            setTimeout(function() {
                if (!validNumber.test(element.val()))
                element.val(element.data('oldValue'));
            }, 0);
            });
        element
        .keypress(function(event) {
        var text = $(this).val();
        if ((event.which != 46 || text.indexOf('.') != -1) && //if the keypress is not a . or there is already a decimal point
            ((event.which < 48 || event.which > 57) && //and you try to enter something that isn't a number
            (event.which != 45 || (element[0].selectionStart != 0 || text.indexOf('-') != -1)) && //and the keypress is not a -, or the cursor is not at the beginning, or there is already a -
            (event.which != 0 && event.which != 8))) { //and the keypress is not a backspace or arrow key (in FF)
            event.preventDefault(); //cancel the keypress
        }

        if ((text.indexOf('.') != -1) && (text.substring(text.indexOf('.')).length > 2) && //if there is a decimal point, and there are more than two digits after the decimal point
            ((element[0].selectionStart - element[0].selectionEnd) == 0) && //and no part of the input is selected
            (element[0].selectionStart >= element.val().length - 2) && //and the cursor is to the right of the decimal point
            (event.which != 45 || (element[0].selectionStart != 0 || text.indexOf('-') != -1)) && //and the keypress is not a -, or the cursor is not at the beginning, or there is already a -
            (event.which != 0 && event.which != 8)) { //and the keypress is not a backspace or arrow key (in FF)
            event.preventDefault(); //cancel the keypress
        }
        });
    }

    function humanizeNumber(n) {
        n = n.toString()
        while (true) {
          var n2 = n.replace(/(\d)(\d{3})($|,|\.)/g, '$1,$2$3')
          if (n == n2) break
          n = n2
        }
        return n
    }

    function validateForm(form){
    // Validasi form, setiap save / submit akan di validasi dulu form nya sesuai dengan validasinya
        $("#"+form).validate({
            invalidHandler: function(event, validator) {
            let errors = validator.numberOfInvalids();
            if (errors) {
                let message = errors == 1
                    ? 'You missed 1 field. It has been highlighted'
                    : 'You missed ' + errors + ' fields. They have been highlighted';
                $("#alert-message .alert-body").html(message);
                $("#alert-message").show();
                $("#alert-message").fadeTo(5000, 500).slideUp(500, function(){
                    $("#alert-message").slideUp(500);
                });
            } else {
                $("#alert-message").hide();
            }
        }
        }).settings.ignore = "";
    }

    function validateFormToast(form){
        // Validasi form, setiap save / submit akan di validasi dulu form nya sesuai dengan validasinya
        // Alert menggunakan toasts
        $("#"+form).validate({
            invalidHandler: function(event, validator) {
                let errors = validator.numberOfInvalids();
                if (errors) {
                    let message = errors == 1
                        ? 'You missed 1 field. It has been highlighted'
                        : 'You missed ' + errors + ' fields. They have been highlighted';
                    show_msg('Validate Form', message, 'warning');
                } 
                }
        }).settings.ignore = "";
    }

    // function mask_time(){
    //     $('.time-mask').toArray().forEach(function(field){
    //         new Cleave(field, {
    //             time: true,
    //             timePattern: ['h', 'm', 's']
    //         });
    //     });   
    // }
    
    //masking menggunakan thousand separator
    function mask_thousand(){
        $('.numeral-mask').toArray().forEach(function(field){
            new Cleave(field, {
                numeral: true,
                numeralThousandsGroupStyle: 'thousand',
                numeralDecimalScale: 0
            });
        });   
    }

    //masking menggunakan thousand separator dengan digit di belakang koma
    function mask_thousand_digit(digit){
        $('.numeral-mask-digit').toArray().forEach(function(field){
            new Cleave(field, {
                numeral: true,
                numeralThousandsGroupStyle: 'thousand',
                numeralDecimalScale: digit
            });
        });   
    }

    /* 
        aktifkan masking dengan thousand separator, dengan digit koma di belakangnya, bedasarkan id dari element nya
        kalo sudah pernah pake masking sebkelumnya di destroy dulu supaya bisa dibikin masking yang baru
        misalkan yang tadinya KG bisa pake koma tapi kalo udah jadi PCS ngk bisa pake koma, dan sebaliknya
    */
    function mask_thousand_digit_by_id(id,digit){
            if (window['mask'+id] != null) {
                window['mask'+id].destroy();
                window['mask'+id] = null;
            }
            window['mask'+id] = new Cleave('#'+id, {
            numeral: true,
            numeralThousandsGroupStyle: 'thousand',
            numeralDecimalScale: digit
        });
    }

    //aktifkan masking dengan thousand separator, an nilainya satuan tanpa koma, denga class numeral-mask-satuan
    function mask_thousand_satuan(){
        $('.numeral-mask-satuan').toArray().forEach(function(field){
            new Cleave(field, {
                numeral: true,
                numeralThousandsGroupStyle: 'thousand',
                numeralDecimalScale: 0
            });
        });   
    }

    function todayDate(formatnya){
        let d = new Date();
        let month = d.getMonth()+1;
        let day = d.getDate();
        let tanggal;
    
        if (formatnya=='yyyymmdd'){
            tanggal = d.getFullYear()+(month<10 ? '0' : '') + month+(day<10 ? '0' : '') + day;
        }

        if (formatnya=='dd-mm-yyyy'){
            tanggal = (day<10 ? '0' : '') + day+ '-' +(month<10 ? '0' : '') + month + '-' +d.getFullYear() ;
          }
    
        if (formatnya=='dd/mm/yyyy'){
            tanggal = (day<10 ? '0' : '') + day+ '/' +(month<10 ? '0' : '') + month + '/' +d.getFullYear() ;
        }
        return tanggal;
    }

    // validasi untuk select2, supaya setelah validsi warna bacground nya berubah
    $(".select2").on('change', function() {
        $(this).valid();
    });

    /*
      Tombol panah atas bawah, untuk pindah antar field
      Apabila ada di kolom terkahir dan di tekan tombol bawah maka akan menambah 1 baris lagi
    */
    $(document).on("keyup", '.tombol-panah', function(e) { 
        let nama = $(this).attr('name');
        let obj = $('input[name="'+nama+'"]');
        let indexnya = obj.index(this);
        let namaKiri = obj.data("nama-el-kiri");
        let elKiri = obj.data("type-el-kiri");
        let objKiri = $(elKiri+'[name="'+namaKiri+'[]"]');
        let namaKanan = obj.data("nama-el-kanan");
        let elKanan = obj.data("type-el-kanan");
        let objKanan = $(elKanan+'[name="'+namaKanan+'[]"]');
        let indexTarget;
        let jumObj =  obj.length -1;
        indexnya=parseInt(indexnya);
        if (e.keyCode == 37) {
            //panah kiri
            elKiri == 'select' ? objKiri.eq(indexnya).select2('open') : objKiri.eq(indexnya).focus().select();
            return false;
        }

        if (e.keyCode == 39) {
            //panah kanan
            elKanan == 'select' ? objKanan.eq(indexnya).select2('open') : obj.eq(indexnya).focus().select();
            return false;
        }

        if (e.keyCode == 38) {
            //panah atas
            indexTarget = indexnya-1;
            obj.eq(indexTarget).focus().select();
            return false;
        }
        if (e.keyCode == 40) {
            //panah bawah
            indexTarget = indexnya+1;
            if (indexTarget == jumObj){
                add_new_row();    
            }
            obj.eq(indexTarget).focus().select();    
            return false;
        }
    });

    $(document).on("keyup", '.pindah-cell', function(e) { 
        let nama = $(this).attr('name');
        let obj = $('input[name="'+nama+'"]');
        let indexnya = obj.index(this);
        let namaKiri = obj.data("nama-el-kiri");
        let maxcColumn= obj.data("max-coloumn");
        let elKiri = obj.data("type-el-kiri");
        let objKiri = $(elKiri+'[name="'+namaKiri+'[]"]');
        let namaKanan = obj.data("nama-el-kanan");
        let elKanan = obj.data("type-el-kanan");
        let objKanan = $(elKanan+'[name="'+namaKanan+'[]"]');
        let indexTarget;
        let jumObj =  obj.length -1;
        indexnya=parseInt(indexnya);
        if (e.keyCode == 37) {
            //panah kiri
            // console.log("Panah Kiri");
            indexTarget = indexnya-1;
            obj.eq(indexTarget).focus().select();
            return false;
        }

        if (e.keyCode == 39) {
            //panah kanan
            // console.log("Panah Kanan");
            indexTarget = indexnya+1;
            obj.eq(indexTarget).focus().select();    
            return false;
        }

        if (e.keyCode == 38) {
            //panah atas
            // console.log("Panah Atas");
            indexTarget = indexnya-maxcColumn;
            obj.eq(indexTarget).focus().select()
            return false;
        }
        if (e.keyCode == 40) {
            //panah bawah
            // console.log("Panah Bawah");
            indexTarget = indexnya+maxcColumn;
            obj.eq(indexTarget).focus().select();
            return false;
        }
    });


    // tombol panah atas bawah, untuk pindah antar field
    function tombolPanah(objname){
        // function kalo mau pindah filed dari atas ke bawah atau sebaliknya
        let obj = $('input[name="'+objname+'[]"]');
        let indexnya,indexTarget;
        let jumObj =  obj.length;
        obj.keyup(function(e) {
            indexnya= obj.index(this);
            indexnya=parseInt(indexnya);
            if (e.keyCode == 38) {
                //panah atas
                indexTarget = indexnya-1;
                obj.eq(indexTarget).focus().select();
                return false;
            }
            if (e.keyCode == 40) {
                //panah bawah
                indexTarget = indexnya+1;
                obj.eq(indexTarget).focus().select();
                return false;
            }
        });
    }

    // let totalFromArray=(...data)=>{
    //     let total = 0;
    //     let itemm = 0;
    //     for(const item of data){
    //         itemm = item.replace(/[^0-9]/gi, '')||0;
    //         total += parseFloat(itemm);
    //     }
    //     return total
    // }

    let sumFromArray = (arr1,arr2 = []) =>{
        let jumlah=0;
        let nilai1;
        let nilai2 = 1;
        if (arr1.length>0){
            for (let i=0;i<arr1.length;i++){
                nilai1 = arr1[i].replace(/,/gi, '') || 0;
                if (arr2.length>0){
                    nilai2 = arr2[i].replace(/,/gi, '') || 0;
                }
                jumlah += parseFloat(nilai1)*parseInt(nilai2); 
            }
        }
        return jumlah;
    }

    let changeSelect = (opt) => {
        opt = $.extend({
            dependent:"",
            obj:"",
            url:""
        }, opt);

        $.ajax({
            url:opt.url,
            method:"POST",
            data:{
                dependent:opt.dependent
            },
            success:function(result){
                $('#'+opt.obj).html(result);
                $('#'+opt.obj).val('').trigger('change');
            }
        })
    }
