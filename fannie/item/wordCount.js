/* word_count(), char_count() and char_count_pkg() support.
 * Needs jQuery.
 * Source: http://www.electrictoolbox.com/jquery-count-words-textarea-input/
*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * 21Jan11 EL Add #-words limit param. Add info/warnings about size limit.
 *            This does not prevent entering "too many" words, altho $(id).text(foo)
 *             could be used to do this.
*/
var wordCount = (function($) {
    var mod = {};

    var outputMsg = function(number, limit, noun) {
        var msg = "";
        var numberLeft = (limit - number);
        if ( numberLeft >= 0 ) {
            msg = 'The box can hold about ' + numberLeft + ' more ' + noun;
        } else {
            //numberLeft = (number - wordLimit);
            msg = 'Please reduce your comment by ' + Math.abs(numberLeft)  + ' ' + noun + ' to ' + limit + '.';
        }

        return msg;
    };

    /* Calculate the number of words or characters in an element and
     *  display a message about it in another element. 
     *
     * Usage: word_count("#id of textarea", "#id of element where count-of-words is to be displayed", max-words)
     *  e.g.: onkeyup='javascript:word_count("#C02ad", "#C02ad_count", 100);'
     */
    mod.word_count = function(field, count, wordLimit) {

        var number = 0;
        var numberLeft = 0;
        //var wordLimit = 100;
        var matches = $(field).val().match(/\b/g);
        if(matches) {
            // match returns 2x too many words, so divide.
            number = matches.length/2;
        }

        // ... and assign it to the text attibute of the information element.
        $(count).text(outputMsg(number, wordLimit, 'words'));
        //$(count).text( 'The box can hold about ' + numberLeft + ' more words');
        //$(count).text( number + ' word' + (number != 1 ? 's' : '') + ' approx');

    // word_count
    };

    /* char_count() like word_count for all chars, shorter message
    */
    mod.char_count = function(field, count, charLimit) {

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
    };

    /* char_count_pkg() like word_count for all chars, shorter message
     *  and allow for a "package" statement that will be appended to it.
    */
    mod.char_count_pkg = function(field, count, charLimit) {

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
    };

    /**
     * Bind counting method to a given element with the given maximum
     */
    mod.initField = function(elem, limit, countMethod) {
        var input = '#' + elem.id;
        var count = input + '_count';
        $(count).show();
        countMethod(input, count, limit);
        $(elem).keyup(function(){ countMethod(input, count, limit); });
    };

    return mod;
}(jQuery));

/* jQuery "ready" waits for DOM to load
 *  so that all objects will be available to refer to.
 *  Then go through the document and act on selected objects.
 *   This is a preliminary or setup pass.
*/ http://docs.jquery.com/Tutorials:Getting_Started_with_jQuery
$(document).ready(function() {

	// For each object with class="word_count" execute a function.
	$('.word_count').each(function() {
        wordCount.initField(this, 100, wordCount.word_count);
	});

	// For each object with class="char_count" execute a function.
	$('.char_count').each(function() {
        wordCount.initField(this, 30, wordCount.char_count);
	});
    //
	// For each object with class="char_count" execute a function.
	$('.char_count_pkg').each(function() {
        wordCount.initField(this, 30, wordCount.char_count_pkg);
	});

});

