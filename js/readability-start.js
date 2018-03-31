var style     = "style-newspaper",
    size      = "size-medium",
    margin    = "margin-wide",
    footnotes = false,
    iOS       = (navigator.userAgent.match(/safari/i) && navigator.userAgent.match(/mobile/i)) ? true : false;

var baseHref = window.location.toString().match(/.*\//);
var linkStringStart = "javascript:(function(){";
var linkStringEnd   = "';_readability_script=document.createElement('script');_readability_script.type='text/javascript';_readability_script.src='" + baseHref + "js/readability.js?x='+(Math.random());document.documentElement.appendChild(_readability_script);_readability_css=document.createElement('link');_readability_css.rel='stylesheet';_readability_css.href='" + baseHref + "css/readability.css';_readability_css.type='text/css';_readability_css.media='all';document.documentElement.appendChild(_readability_css);_readability_print_css=document.createElement('link');_readability_print_css.rel='stylesheet';_readability_print_css.href='" + baseHref + "css/readability-print.css';_readability_print_css.media='print';_readability_print_css.type='text/css';document.getElementsByTagName('head')[0].appendChild(_readability_print_css);})();";

$(document).ready(function() {
    /* Prepare content instructions for specific browsers, */
    if($.browser.msie) {
        $('#bookmarkletLink').html('<img src="images/badge-readability.png" width="174" height="40" alt="Readability" title="Readability" />');
        $("#browser-instruction-placer").hide();
        $("#browser-instruction-ie").fadeIn('100');
        $("#bookmarkletLink").css("cursor","pointer");
        $("#video-instruction").attr("href","#video-ie");
    }
    else if (iOS) {
        $("#browser-instruction-placer,#bookmarkletLink").hide();
        $("#browser-instruction-ios").fadeIn('100');
        $("#bookmarklet p:last-child").before('<textarea id="bookmarkletText">' + linkStringStart + "readConvertLinksToFootnotes=" + footnotes + ";readStyle='" + style + "';readSize='" + size + "';readMargin='" + margin + linkStringEnd + '</textarea>')
    }
    else {
        $("#browser-instruction-placer").hide();
        $("#browser-instruction").fadeIn('100');
    }

    $("#footer").after('<p>' + navigator.userAgent + '</p>');

	$("#bookmarkletLink").attr("href", linkStringStart + "readConvertLinksToFootnotes=" + footnotes + ";readStyle='" + style + "';readSize='" + size + "';readMargin='" + margin + linkStringEnd);

	/* Apply the user's styles to the demonstration. */
	function applyChange(s,y) {
		var example    = $('#example'),
		    article    = $('#articleContent'),
		    references = $('#references');
		
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
			case "footnotes":
				footnotes = y;
		}
		example.attr('className', style);
		article.attr('className', margin + " " + size);
		example.toggleClass('showNotes', footnotes);

        if (iOS) {
    		$("#bookmarklet textarea").val(linkStringStart + "readConvertLinksToFootnotes=" + (footnotes ? 'true' : 'false') + ";readStyle='" + style + "';readSize='" + size + "';readMargin='" + margin + linkStringEnd);
		}
        else {
    		$("#bookmarkletLink").attr("href", linkStringStart + "readConvertLinksToFootnotes=" + (footnotes ? 'true' : 'false') + ";readStyle='" + style + "';readSize='" + size + "';readMargin='" + margin + linkStringEnd);
        }
	}
	
	$("#settings input[type='radio']").bind("click", function(){
		applyChange(this.name, this.value);
	});

	$("#settings input[type='checkbox']").bind("click", function() {
		applyChange(this.name, this.checked);
	});

	$("#bookmarkletLink").bind("click", function(){
		if($.browser.msie){
			alert("To start using Readability, right-click and select 'Add To Favorites...' to save this link to your browser's bookmarks toolbar.");
		}
		else {
			alert("To start using Readability, drag this link to your browser's bookmarks toolbar.");
		}
		return false;
	});

    /* Modal Windows */
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
        padding: 40,
        hideOnContentClick: false,
        frameHeight: 240
    });
});