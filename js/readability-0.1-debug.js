(function(){
	var objOverlay = document.createElement("div");
	var objinnerDiv = document.createElement("div");
	var debugControls = document.createElement("div");
	var mainDiv;
	
	objOverlay.id = "readOverlay";
	objinnerDiv.id = "readInner";
	
	// Apply user-selected styling:
	document.body.className = style;
	objOverlay.className = style;
	objinnerDiv.className = margin + " " + size;
	
	debugControls.className = "debug-controls";
	debugControls.innerHTML = "<input type='button' onclick='grabArticle();' value='Seek Prose' style='font-size: 16px; margin: 10px;'/> <input style='font-size: 16px; margin: 10px;' type='button' onclick='killDivs(mainDiv);' value='Remove Junk' />";
	document.body.insertBefore(debugControls, document.body.firstChild);
	
	// objinnerDiv.appendChild(grabArticle());		// Get the article and place it inside the inner Div
	// objOverlay.appendChild(objinnerDiv);		// Insert the inner div into the overlay
	
	// This removes everything else on the page. Requires a page refresh to undo it.
	// I tried the damn overlay on top - but had rendering issues:
	// document.body.innerHTML = "";
	
	// Inserts the new content :
	// document.body.insertBefore(objOverlay, document.body.firstChild);
})()


function grabArticle() {
	var allParagraphs = document.getElementsByTagName("p");
	var topDivCount = 0;
	var topDiv;
	var topDivParas;
	
	var articleContent = document.createElement("DIV");
	var articleTitle = document.createElement("H1");
	var articleFooter = document.createElement("DIV");
	
	// Replace all doubled-up <BR> tags with <P> tags :
	var pattern =  new RegExp ("<br/?>[ \r\n\s]*<br/?>", "g");
	document.body.innerHTML = document.body.innerHTML.replace(pattern, "</p><p>");
	
	// Grab the title from the <title> tag and inject it as the title.
	articleTitle.textContent = document.title;
	articleContent.appendChild(articleTitle);
	
	// Study all the paragraphs and find the chunk that has the most <p>'s and keep it:
	for (var j=0; j	< allParagraphs.length; j++) {
		var tempParas = allParagraphs[j].parentNode.getElementsByTagName("p");
		if ( tempParas.length > topDivCount && allParagraphs[j].parentNode.textContent.split(',').length >= tempParas.length ) {
			topDivCount = tempParas.length;
			topDiv = allParagraphs[j].parentNode;
			topDiv.className = "bug-yellow";
		}
	}
	topDiv.className = "bug-green";
	
	/*
	// REMOVES ALL STYLESHEETS ...
	for (var k=0;k < document.styleSheets.length; k++) {
		if (document.styleSheets[k].href != null && document.styleSheets[k].href.lastIndexOf("arc90.com") == -1) {
			document.styleSheets[k].disabled = true;
		}
	}
	*/

	// cleanStyles(topDiv);					// Removes all style attributes
	// topDiv = killDivs(topDiv);				// Goes in and removes DIV's that have more non <p> stuff than <p> stuff
	
	// Cleans out junk from the topDiv just in case:
	/*
	topDiv = clean(topDiv, "form");
	topDiv = clean(topDiv, "object");
	topDiv = clean(topDiv, "table");
	topDiv = clean(topDiv, "h1");
	topDiv = clean(topDiv, "h2");
	topDiv = clean(topDiv, "iframe");
	*/
	// Add the footer and contents:
	
	/*
	articleFooter.id = "readFooter";
	articleFooter.innerHTML = "<a href='http://www.arc90.com'><img src='http://lab.arc90.com/experiments/readability/images/footer.png'></a>";
	
	articleContent.appendChild(topDiv);
	articleContent.appendChild(articleFooter);
	
	return topDiv;
	*/
	mainDiv = topDiv;
	
}

function cleanAll() {
	mainDiv = clean(mainDiv, "form");
	mainDiv = clean(mainDiv, "object");
	mainDiv = clean(mainDiv, "table");
	mainDiv = clean(mainDiv, "h1");
	mainDiv = clean(mainDiv, "h2");
	mainDiv = clean(mainDiv, "iframe");
	
}

function cleanStyles( e ) {
    e = e || document;
    var cur = e.firstChild;

    // Go until there are no more child nodes
    while ( cur != null ) {
		if ( cur.nodeType == 1 ) {
			// Remove style attribute(s) :
			cur.removeAttribute("style");
			cleanStyles( cur );
		}
		cur = cur.nextSibling;
	}
}

function killDivs ( e ) {
	var divsList = e.getElementsByTagName( "div" );
	var curDivLength = divsList.length;
	
	// Gather counts for other typical elements embedded within :
	for (var i=0; i < curDivLength; i ++) {
		var p = divsList[i].getElementsByTagName("p").length;
		var img = divsList[i].getElementsByTagName("img").length;
		var li = divsList[i].getElementsByTagName("li").length;
		var a = divsList[i].getElementsByTagName("a").length;
		var embed = divsList[i].getElementsByTagName("embed").length;
	
	// If the number of commas is less than 10 (bad sign) ...
	if (divsList[i].textContent.split(',').length < 10) {
			// And the number of non-paragraph elements is more than paragraphs 
			// or other ominous signs :
			if ( img > p || li > p || a > p || p == 0 || embed > 0) {
				divsList[i].className = "bug-red";
				divsList[i].color = "red";
				// divsList[i].style.display = "none";
			}
		}
	}

	return e;
}

function clean(e, tags, minWords) {
	var targetList = e.getElementsByTagName( tags );
	minWords = minWords || 1000000;

	for (var y=0; y < targetList.length; y++) {
		// If the text content isn't laden with words, remove the child:
		if (targetList[y].textContent.split(' ').length < minWords) {
			targetList[y].className = "bug-red";
			// targetList[y].parentNode.removeChild(targetList[y]);
		}
	}
	return e;
}