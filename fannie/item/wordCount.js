/* word_count(), char_count() and char_count_pkg() support.
 * Needs jQuery.
 * Source: http://www.electrictoolbox.com/jquery-count-words-textarea-input/
*/

/* Calculate the number of words or characters in an element and
 *  display a message about it in another element. 
 *
 * Usage: word_count("#id of textarea", "#id of element where count-of-words is to be displayed", max-words)
 *  e.g.: onkeyup='javascript:word_count("#C02ad", "#C02ad_count", 100);'
 *
 * --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * 21Jan11 EL Add #-words limit param. Add info/warnings about size limit.
 *            This does not prevent entering "too many" words, altho $(id).text(foo)
 *             could be used to do this.
*/
function word_count(field, count, wordLimit) {

	var number = 0;
	var numberLeft = 0;
	//var wordLimit = 100;
	var msg = "";
	var matches = $(field).val().match(/\b/g);
	if(matches) {
		// match returns 2x too many words, so divide.
		number = matches.length/2;
	}

	// Compose the message ...
	numberLeft = (wordLimit - number);
	if ( numberLeft >= 0 ) {
		msg = 'The box can hold about ' + numberLeft + ' more words';
	} else {
		//numberLeft = (number - wordLimit);
		msg = 'Please reduce your comment by ' + Math.abs(numberLeft)  + ' words to ' + wordLimit + '.';
	}

	// ... and assign it to the text attibute of the information element.
	$(count).text(msg);
	//$(count).text( 'The box can hold about ' + numberLeft + ' more words');
	//$(count).text( number + ' word' + (number != 1 ? 's' : '') + ' approx');

// word_count
}

/* jQuery "ready" waits for DOM to load
 *  so that all objects will be available to refer to.
 *  Then go through the document and act on selected objects.
 *   This is a preliminary or setup pass.
*/ http://docs.jquery.com/Tutorials:Getting_Started_with_jQuery
$(document).ready(function() {

	// For each object with class="word_count" execute a function.
	$('.word_count').each(function() {
			var input = '#' + this.id;
			var count = input + '_count';
			// count contains e.g. "#C02ad_count", i.e. an ID reference
			// I assume this changes the word-count display area (div) display:none to display:block
			//  I don't what the point of setting it to none in the first place was.
			$(count).show();
			// Do an initial call to word_count on this object to display the current count.
			word_count(input, count, 100);
			// Bind a call to word_count to the keyup action in the object.
			//  This is the equivalent of onkeyup='javascript:word_count("#C02ad", "#C02ad_count", 100);'
			//                         or onkeyup='javascript:word_count("#" + this.id, "#" + this.id + "_count", 100);'
			//  so that the function will be executed as the user changes the field.
			//   Only works for keyboard entry, not cut/paste, which does not fire keyup. ?mouseup
			$(this).keyup(function() { word_count(input, count, 100) });
	});

});

/* char_count() like word_count for all chars, shorter message
*/
function char_count(field, count, charLimit) {

	var number = 0;
	var numberLeft = 0;
	var msg = "";
	number = $(field).val().length;

	// Compose the message ...
	numberLeft = (charLimit - number);
	if ( numberLeft >= 0 ) {
		msg = numberLeft;
	} else {
		msg = numberLeft;
	}

	// ... and assign it to the text attibute of the information element.
	$(count).text(msg);

// char_count
}

/* jQuery "ready" waits for DOM to load
 *  so that all objects will be available to refer to.
 *  Then go through the document and act on selected objects.
 *   This is a preliminary or setup pass.
*/ http://docs.jquery.com/Tutorials:Getting_Started_with_jQuery
$(document).ready(function() {

	// For each object with class="char_count" execute a function.
	$('.char_count').each(function() {
			var input = '#' + this.id;
			var count = input + '_count';
			// count contains e.g. "#C02ad_count", i.e. an ID reference
			// I assume this changes the char-count display area (div) display:none to display:block
			//  I don't what the point of setting it to none in the first place was.
			$(count).show();
			// Do an initial call to char_count on this object to display the current count.
			char_count(input, count, 30);
			// Bind a call to char_count to the keyup action in the object.
			//  This is the equivalent of onkeyup='javascript:char_count("#C02ad", "#C02ad_count", 100);'
			//                         or onkeyup='javascript:char_count("#" + this.id, "#" + this.id + "_count", 100);'
			//  so that the function will be executed as the user changes the field.
			//   Only works for keyboard entry, not cut/paste, which does not fire keyup. ?mouseup
			$(this).keyup(function() { char_count(input, count, 30) });
	});

});

/* char_count_pkg() like word_count for all chars, shorter message
 *  and allow for a "package" statement that will be appended to it.
*/
function char_count_pkg(field, count, charLimit) {

	var number = 0;
	var numberLeft = 0;
	var msg = "";
	number = $(field).val().length;
	// Allow for the package spec that is appended: " 220g"
	var uom = $(unitofmeasure).val().length;
	var sze = $(size).val().length;
	number = number + (uom + sze + 1);

	// Compose the message ...
	numberLeft = (charLimit - number);
	if ( numberLeft >= 0 ) {
		msg = numberLeft;
	} else {
		msg = numberLeft;
	}

	// ... and assign it to the text attibute of the information element.
	$(count).text(msg);

// char_count_pkg
}

/* jQuery "ready" waits for DOM to load
 *  so that all objects will be available to refer to.
 *  Then go through the document and act on selected objects.
 *   This is a preliminary or setup pass.
*/ http://docs.jquery.com/Tutorials:Getting_Started_with_jQuery
$(document).ready(function() {

	// For each object with class="char_count" execute a function.
	$('.char_count_pkg').each(function() {
			var input = '#' + this.id;
			var count = input + '_count';
			// count contains e.g. "#C02ad_count", i.e. an ID reference
			// I assume this changes the char-count display area (div) display:none to display:block
			//  I don't what the point of setting it to none in the first place was.
			$(count).show();
			// Do an initial call to char_count on this object to display the current count.
			char_count_pkg(input, count, 30);
			// Bind a call to char_count to the keyup action in the object.
			//  This is the equivalent of onkeyup='javascript:char_count("#C02ad", "#C02ad_count", 100);'
			//                         or onkeyup='javascript:char_count("#" + this.id, "#" + this.id + "_count", 100);'
			//  so that the function will be executed as the user changes the field.
			//   Only works for keyboard entry, not cut/paste, which does not fire keyup. ?mouseup
			$(this).keyup(function() { char_count_pkg(input, count, 30) });
	});

});
