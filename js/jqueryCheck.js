window.onload = writeScripts;
   
function writeScripts(){
  foxyCheckJQuery();
}    
function foxyCheckJQuery(){
  if (typeof jQuery == 'undefined') {
    alert( 'jquery is not here' );
    var head = document.getElementsByTagName('head')[0];
    var script = document.createElement('script');        
    script.setAttribute('type','text/javascript')
    script.setAttribute('src', 'http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js');
    head.appendChild(script);
  }
}  

function setVersion(){
  var head = document.getElementsByTagName('head')[0];
  var script = document.createElement('script');        
  script.setAttribute('type','text/javascript')
  script.setAttribute('src', 'http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js');
  head.appendChild(script);
}