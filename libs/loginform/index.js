for(let i=0;i<document.getElementsByClassName("gspb-login-form").length;i++){let e=document.getElementsByClassName("gspb-login-form")[i];e.addEventListener("submit",function(t){t.preventDefault();let s=e.querySelector("button[type=submit]");s.style.display="none";let n=new FormData(e);var r=new XMLHttpRequest;r.addEventListener("readystatechange",()=>{if(4===r.readyState&&200===r.status){let t=JSON.parse(r.response);e.querySelector(".form-errors").innerHTML=t.message,!1==t.error?window.setTimeout(function(){location.reload()},200):s.style.display="block"}}),r.open("POST",gspbloginvars.ajax_url),r.send(n)})}