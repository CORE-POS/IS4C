var CustomerDisplay = (function($) {

    var customerURL = "";
    var win = null;
    var mod = {};

    mod.setURL = function(url) {
        customerURL = url;
    };

    mod.exists = function() {
        return (win !== null && $.isWindow(win)) ? true : false;
    };

    var launchCustomerDisplay = function() {
        win = window.open(customerURL, 'Customer_Display');
    };

    mod.updateCustomerDisplay = function(identifier, content) {
        var curElem = $(document.activeElement);

        if (mod.exists()) {
            win.$(identifier).html(content);
        }

        if (curElem.length) {
            curElem.focus();
        }
    };

    mod.reloadCustomerDisplay = function() {
        var curElem = $(document.activeElement);

        if (!mod.exists()) {
            launchCustomerDisplay();
        } else {
            win.location.reload();
        }

        if (curElem.length) {
            curElem.focus();
        }
    };

    return mod;

}(jQuery));
