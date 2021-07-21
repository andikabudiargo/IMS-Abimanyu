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



function angka5(){
$(".angka5").keypress(function (e) {
    //if the letter is not digit then display error and don't type anything
    if (e.which != 8 && e.which != 0 &&  e.which != 32 && (e.which < 48 || e.which > 57)) {
    //display error message
    $("#errmsg").html("Digits Only").show().fadeOut("slow");
        return false;
    }
});
}
  
$(".angka").keypress(function (e) {
    //if the letter is not digit then display error and don't type anything
    //angka bulat alias integer, tidak boleh ada karakter lain
    if (e.which != 8 && e.which != 0 &&  e.which != 32 && (e.which < 48 || e.which > 57)) {
        //display error message
        $("#errmsg").html("Digits Only").show().fadeOut("slow");
        return false;
    }
});
  
$(".angka2").keypress(function (e) {
    //if the letter is not digit then display error and don't type anything
    if (e.which != 8 && e.which != 46  && e.which != 0  && e.which != 32 && (e.which < 48 || e.which > 57)) {
        //display error message
        $("#errmsg").html("Digits Only").show().fadeOut("slow");
        return false;
    }
});
  
//bisa pake tanda . dan hanya bisa input dua digit desimal
$('.angka3').keypress(function(event) {
    var $this = $(this);
    if ((event.which != 46 || $this.val().indexOf('.') != -1) &&
        ((event.which < 48 || event.which > 57) &&
        (event.which != 0 && event.which != 8))) {
            event.preventDefault();
    }

    var text = $(this).val();
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
  
//angka bisa negative, decimal, maksimum 2 digit decimal
function angkaNegativeDesimal(element) {
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

