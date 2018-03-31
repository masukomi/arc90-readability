var style = "style-newspaper";
var size = "size-medium";
var margin = "margin-wide";

var baseHref = window.location.toString().match(/.*\//);
if (baseHref[0].match(/es\//i)) {
    baseHref = baseHref[0].replace(/es\//i, '');
}
var linkStringStart = "javascript:(function(){";
var linkStringEnd   = "';_readability_script=document.createElement('SCRIPT');_readability_script.type='text/javascript';_readability_script.src='" + baseHref + "js/readability.js?x='+(Math.random());document.getElementsByTagName('head')[0].appendChild(_readability_script);_readability_css=document.createElement('LINK');_readability_css.rel='stylesheet';_readability_css.href='" + baseHref + "css/readability.css';_readability_css.type='text/css';_readability_css.media='all';document.getElementsByTagName('head')[0].appendChild(_readability_css);_readability_print_css=document.createElement('LINK');_readability_print_css.rel='stylesheet';_readability_print_css.href='" + baseHref + "css/readability-print.css';_readability_print_css.media='print';_readability_print_css.type='text/css';document.getElementsByTagName('head')[0].appendChild(_readability_print_css);})();";

$(document).ready(function() {
    
  if($.browser.msie) {
      $("#browser-instruction-placer").hide();
      $("#browser-instruction-ie").fadeIn('100');
      $("#bookmarkletLink").css("cursor","pointer");
      $("#video-instruction").attr("href","#video-ie");
  }
  else {
      $("#browser-instruction-placer").hide();
      $("#browser-instruction").fadeIn('100');
  }
						   
	$("#bookmarkletLink").attr("href", linkStringStart + "readStyle='" + style + "';readSize='" + size + "';readMargin='" + margin + linkStringEnd);
	
	function applyChange(s,y) {
		var example = document.getElementById("example");
		var article = document.getElementById("articleContent");
		
		switch(s){
			case "style":
				style = y;
				break;
			case "size":
				size = y;
				break;
			case "margin":
				margin = y;
				break;
		}
		example.className = style;
		article.className = margin + " " + size;
		$("#bookmarkletLink").attr("href", linkStringStart + "readStyle='" + style + "';readSize='" + size + "';readMargin='" + margin + linkStringEnd);
	}
	
	$("#settings input").bind("click", function(){
		applyChange(this.name, this.value);
	});
	$("#settings input").bind("click", function(){
		applyChange(this.name, this.value);
	});
	$("#bookmarkletLink").bind("click", function(){
		if($.browser.msie){
			alert("Para empezar a usar Readability, click derecho y selecciona 'Agrega a favoritos...' para guardar este link en la barra de marcadores de tu navegador.");
		}
		else {
			alert("Para empezar a usar Readability, arrastra este link a la barra de marcadores de tu navegador.");
		}
		return false;
	});

  $('.video').fancybox({
      zoomSpeedIn: 0,
      zoomSpeedOut: 0,
      overlayShow: true,
      overlayOpacity: 0.85,
      overlayColor: "#091824",
      hideOnContentClick: false,
      frameWidth: 480,
      frameHeight: 360
  });

  $('#footnote-details').fancybox({
      zoomSpeedIn: 0,
      zoomSpeedOut: 0,
      overlayShow: true,
      overlayOpacity: 0.85,
      overlayColor: "#091824",
      hideOnContentClick: true,
      frameWidth: 480
  });
});