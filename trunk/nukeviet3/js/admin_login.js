var seccodecheck=/^([a-zA-Z0-9])+$/;if(typeof jsi=="undefined")var jsi=[];jsi[0]||(jsi[0]="vi");jsi[1]||(jsi[1]="./");jsi[2]||(jsi[2]=0);jsi[3]||(jsi[3]=6);var strHref=window.location.href;if(strHref.indexOf("?")>-1)var strHref_split=strHref.split("?"),script_name=strHref_split[0],query_string=strHref_split[1];else{script_name=strHref;query_string=""}function nv_checkadminlogin_seccode(a){return a.value.length==jsi[3]&&seccodecheck.test(a.value)?true:false}
function nv_randomPassword(a){for(var b="",c=0;c<a;c++)b+="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890".charAt(Math.floor(Math.random()*62));return b}function nv_checkadminlogin_submit(){if(jsi[2]==1){var a=document.getElementById("seccode");if(!nv_checkadminlogin_seccode(a)){alert(login_error_security);a.focus();return false}}a=document.getElementById("login");var b=document.getElementById("password");return a.value!=""&&b.value!=""?true:false}
function nv_change_captcha(){var a=document.getElementById("vimg");nocache=nv_randomPassword(10);a.src=jsi[1]+"index.php?scaptcha=captcha&nocache="+nocache;document.getElementById("seccode").value="";return false};