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

