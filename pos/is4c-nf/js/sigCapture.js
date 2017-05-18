
/**
  Javascript module to manage signature capture.
  Currently makes plenty of assumptions about the
  provided DOM.
*/
var sigCapture = (function($) {

    var mod = {};
    var formSubmitted = false;

    /**
      Set URL to CORE root directory to locate other
      items by URL
    */
    var urlBase = '../';
    mod.setUrl = function(u) {
        urlBase = u;
    };

    /**
      Set receipt number to use with paper receipts
    */
    var receipt ='';
    mod.setReceipt = function(r) {
        receipt = r;
    };

    /**
      Set path where signature bitmaps will be located
    */
    var bmpPath = '../scale-drivers/drivers/NewMagellan/ss-output/tmp/';
    mod.setBmpPath = function(b) {
        bmpPath = b;
    };

    /**
      Shorthand setter for properties above
    */
    mod.init = function(u, r, b) {
        mod.setUrl(u);
        mod.setReceipt(r);
        mod.setBmpPath(b);
    };

    /**
      List of options to display when a signature is available
      for approval. Enter to accept is implicit.
    */
    var options = ['[Subtotal] to re-request signature'];
    mod.addOption = function(o) {
        options.push(o);
    };

    /**
      Transform option list into HTML
    */
    var optionText = function() {
        return options.reduce(function(acc, i) {
            return acc + "" + i + "<br />";
        });
    };

    /**
      Wrapper for main form. Decides what inputs can be handled
      purely in javascript vs. what should be submitted back to
      the PHP script.
    */
    mod.submitWrapper = function() {
        var str = $('#reginput').val();

        // RP means a request for a paper sig
        // If a paper signature slip is requested during
        // electronic signature capture, abort capture
        // Paper slip will be used instead.
        if (str.toUpperCase() == 'RP'){
            $.ajax({url: urlBase + 'ajax-callbacks/AjaxEnd.php',
                cache: false,
                type: 'post',
                data: 'receiptType='+$('#rp_type').val()+'&ref='+receipt
            }).done(function(data) {
                if ($('input[name=doCapture]').length != 0) {
                    $('input[name=doCapture]').val(0);    
                    $('div.boxMsgAlert').html('Verify Signature');
                    $('#sigInstructions').html('[enter] to approve<br />' + optionText());
                }
            });
            $('#reginput').val('');
            return false;
        }

        // limit when form submission is available
        // If capturing a signature, only allow enter when an image is present
        // Enter in non-capture or with image present approves signature
        // The PHP page *may* heed the following:
        //   CL might be used to cancel the operation
        //   VD might be used to void the operation
        //   TL might be used to re-request digital signature
        if (str == '' && $('input[name=bmpfile]').length == 0 && $('input[name=doCapture]').val()) {
            return false;
        } else if (str != '' && str.toUpperCase() != 'CL' && str.toUpperCase() != 'TL' && str.toUpperCase() != 'VD') {
            $('#reginput').val('');
            $('#reginput').focus();
            return false;
        }

        // avoid double submit
        if (!formSubmitted) {
            formSubmitted = true;
            return true;
        }

        return false;
    };

    /**
      The primary hardware communication AJAX loop invokes a
      parseWrapper method to handle input. This watches for bitmap
      inputs and updates the screen when one is found.
    */
    mod.parseWrapper = function(str) {
        if (str.substring(0, 7) == 'TERMBMP') {
            var fn = bmpPath + str.substring(7);
            $('<input>').attr({
                type: 'hidden',
                name: 'bmpfile',
                value: fn
            }).appendTo('#formlocal');

            var img = $('<img>').attr({
                src: fn,
                width: 250 
            });
            $('#imgArea').append(img);
            $('.boxMsgAlert').html('Approve Signature');
            $('#sigInstructions').html('[enter] to approve<br />' + optionText());
        } 
    };

    /**
      Add a hidden <input> with name "n" and value "v"
    */
    mod.addToForm = function(n, v) {
        $('<input>').attr({
            name: n,
            value: v,
            type: 'hidden'
        }).appendTo('#formlocal');
    };

    return mod;

}(jQuery));

