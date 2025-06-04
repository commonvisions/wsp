function getCookie(w) {
    var pCOOKIES = new Array(); pCOOKIES = document.cookie.split('; ');
    if (w!='') {
        var cName = "";
        for(bb = 0; bb < pCOOKIES.length; bb++){
            NmeVal  = new Array();
            NmeVal  = pCOOKIES[bb].split('=');
            if(NmeVal[0] == w){
                cName = unescape(NmeVal[1]);
                }
            }
        return cName;
        }
    else {
        cName = new Array();
        for(bb = 0; bb < pCOOKIES.length; bb++){
            NmeVal  = new Array();
            NmeVal  = pCOOKIES[bb].split('=');
            cName.push(unescape(NmeVal[0]));
            }
        return cName;
        }
    }
 
function setCookie(name, value, expires, path, domain, secure) {
    var cookieVal = name + "=" + escape(value) + "; ";
    if (expires) {
        cookieVal += "expires=" + expires.toUTCString() + "; ";
    }
if (path) {
  cookieVal += "path=" + path + "; ";
}
if (domain) {
  cookieVal += "domain=" + domain + "; ";
}
if (secure) {
  cookieVal += "secure" + "; ";
}
document.cookie = cookieVal;
}

function gaOptoutCV(disableStr) {
    var expiryDate = new Date();
    expiryDate.setDate(expiryDate.getDate() + 5 * 365);
    setCookie(disableStr, 'true', expiryDate, '/'); 
    window[disableStr] = true; 
    }

function gaOptinCV(enableStr) {
    var expiryDate = new Date();
    expiryDate.setDate(expiryDate.getDate() + 5 * 365);
    setCookie(disableStr, 'false', expiryDate, '/'); 
    window[disableStr] = false; 
    }

function setTimeOut() {
    setTimeout(function() {
        $("#cookiePolicy").fadeOut("slow", function () {
            $("#cookiePolicy").remove();
            });
        }, 15000);
}

function removeDivs(){
    $("#cookiePolicy").fadeOut("slow", function () {
        $("#cookiePolicy").remove();
        });
}
