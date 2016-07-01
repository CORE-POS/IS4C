var paycardboxmsgAuth = (function($) {
    var mod = {};

    var called = false;

    var reloadOnError = function() {
        window.location = 'paycardboxMsgAuth.php';
    };

    /**
      Trying to cope with rare errors where paycard_submitWrapper's
      AJAX call ends in an error with 0 status, 0 readyState.
      Originally I tried to use singleSubmit to keep the form from
      submitting more than once but having the process be:
        submit => page reload => AJAX call fires
      seemed to still have the occasional bug. With such generic
      error information it's tough to say for sure what the problem
      is. The guess is something triggers page navigation while
      the AJAX call is processing but that really is just a guess.
    */
    mod.submitWrapper = function(e) {
        if ($('#reginput').val() === '' || called) {
            e.preventDefault();
            if (!called) {
                var validate = $.ajax({
                    data: 'validate=1',
                    dataType: 'json'
                }).done(function (resp) {
                    if (resp.valid) {
                        paycard_submitWrapper();
                    } else {
                        reloadOnError();
                    }
                }).fail(function(xhr,stat,msg) {
                    reloadOnError();
                });
            }
            called = true;

            return false;
        }

        return true;
    };

    return mod;
}(jQuery));
