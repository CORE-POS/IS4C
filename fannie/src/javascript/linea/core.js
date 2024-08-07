/**
  Currently three differnt iOS apps are supported for scanning with the Linea
  barcode reader.

  1. Web Interface
     This app is delisted from the App Store and can no longer be installed but
     will work if it's already on a device. It has some unique features like being
     able to mute the device's beep-on-read

  2. Web Barcode
     This is another 3rd party browser app that is still available

  3. WebHub
     This is an app by Infinite Peripherals. It has the worst javascript API but
     since they manufacture the devices it's unlikely to ever be delisted from the
     App Store, be abandonned, not support future device revisions, etc
*/

/**
  Common functionality. Regardless of app, the UPC is inserted into
  the field specified by the selctor and then either the callback is
  triggered, if present, or the closest form is submitted
*/
function lineaBarcode(upc, type, selector, callback) {
    if (typeof upc === 'undefined') {
        return;
    }
    if (type == 'Code 128' && upc.length < 11) {
        // do not remove last digit
    } else {
        upc = upc.substring(0,upc.length-1);
    }
    if ($(selector).length > 0){
        $(selector).val(upc);
        if (typeof callback === 'function') {
            callback();
        } else {
            $(selector).closest('form').submit();
        }
    }
}

/**
  WebHub can't work with an actual function variable. It *requires*
  a string function name that it converts back to a function, so a couple
  values have to be shoved into global state instead of just being closed
  over.
*/
var IPC_PARAMS = { selector: false, callback: false };
function ipcWrapper(upc, typeID, typeStr) {
    lineaBarcode(upc, typeStr, IPC_PARAMS.selector, IPC_PARAMS.callback);
}

/**
  Enable linea scanner on page
  @param selector - jQuery selector for the element where
    barcode data should be entered
  @param callback [optional] function called after
    barcode scan

  If the callback is omitted, the parent <form> of the
  selector's element is submitted.
*/
function enableLinea(selector, callback) {
    Device = new ScannerDevice({
        barcodeData: function(data, type) {
            var upc = data.substring(0,data.length-1);
            if ($(selector).length > 0){
                $(selector).val(upc);
                if (typeof callback === 'function') {
                    callback();
                } else {
                    $(selector).closest('form').submit();
                }
            }
        },
        magneticCardData: function (track1, track2, track3){
        },
        magneticCardRawData: function (data){
        },
        buttonPressed: function (){
        },
        buttonReleased: function (){
        },
        connectionState: function (state){
        }
    });
    ScannerDevice.registerListener(Device);

    if (typeof WebBarcode != 'undefined') {
        WebBarcode.onBarcodeScan(function(ev) {
            var data = ev.value;
            var type = ev.type;
            lineaBarcode(data, type, selector, callback);
        });
    }

    document.addEventListener("BarcodeScanned", function (ev) {
        var data = ev.value;
        lineaBarcode(data, 'upc-a', selector, callback);
    }, false);

    // for webhub
    IPC_PARAMS.selector = selector;
    IPC_PARAMS.callback = callback;
    if (typeof WebHub != 'undefined') {
        WebHub.Settings.set({ barcodeFunction: "ipcWrapper" });
    }

    function lineaSilent() {
        if (typeof cordova.exec != 'function') {
            setTimeout(lineaSilent, 100);
        } else {
            if (Device) {
                Device.setScanBeep(false, []);
                $('#smallscreen-menu-toggle').before('<br />');
            }
        }
    }
    lineaSilent();

    var socketm = document.createElement("input");
    socketm.id = 'socketm';
    socketm.type = "hidden";
    Object.defineProperty(socketm, "value", {
        get: function() { return this._value; },
        set: function(v) {
            lineaBarcode(v, 'upc-a', selector, callback);
        }
    });
    document.body.appendChild(socketm);

    if (typeof EB != 'undefined') {
        EB.Barcode.enable({}, function (params) {
            lineaBarcode(params.data, params.type, selector, callback);
        });
    }
}


function lineaBeep() {

    if (typeof WebBarcode != 'undefined') {
        WebBarcode.emitTones([{ "tone": 2000, "duration": 500 }]);
    }

    if (typeof WebHub != 'undefined') {
        WebHub.Notify.beep();
    }
}

function lineaVibrate() {
    
    if (typeof WebBarcode != 'undefined') {
        WebBarcode.vibrateDevice();
    }

    if (typeof WebHub != 'undefined') {
        WebHub.Notify.vibrate();
    }
}

function showAnythingOnScreen(object)
{
    $('body').prepend(object);
}
