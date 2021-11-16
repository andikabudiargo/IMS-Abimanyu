/*
*
*   Untuk custom Js, kalo ada yang mau di centralize 
*
*
*/

"use strict";

function show_msg(title, message, status) {
    toastr[status](message, title,{
        closeButton: true,
        tapToDismiss: false
    })
}