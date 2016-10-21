var sigJS = (function($) {
    var mod = {};

    var canvas = false;
    var sigPad = false;

    mod.resizeCanvas = function() {
        console.log(canvas);
        // When zoomed out to less than 100%, for some very strange reason,
        // some browsers report devicePixelRatio as less than 1
        // and only part of the canvas is cleared then.
        var ratio =  Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
        sigPad.clear();
    }
    mod.clearBtn = function() {
        sigPad.clear();
    }
    mod.acceptBtn = function() {
        if (sigPad.isEmpty()) {
            showBootstrapAlert('#sign-p', 'danger', 'Please sign');
        } else {
            var inp = $('<input type="hidden" name="sig" />').val(sigPad.toDataURL("image/jpeg", 100));
            $('#sign-form').append(inp);
            $('#sign-form').submit();
        }
    }
    mod.init = function() {
        canvas = $('#sign-canvas')[0];
        sigPad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255,255,255)'
        });
        mod.resizeCanvas();
        window.onresize = mod.resizeCanvas;
        $('#btn-clear').click(mod.clearBtn);
        $('#btn-accept').click(mod.acceptBtn);
    }

    return mod;
}(jQuery));

sigJS.init();
