
function doubleBeep() {
    if (typeof cordova.exec != 'function') {
        setTimeout('doubleBeep()', 500);
    } else if (Device) {
        Device.playSound([500, 100, 0, 100, 1000, 100, 0, 100, 500, 100]);
    }
}

Device = new ScannerDevice({
    barcodeData: function (data, type){
        var upc = data.substring(0,data.length-1);
        if ($('#upc_in').length > 0){
            $('#upc_in').val(upc);
            $('#goBtn').click();
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

if (typeof WebBarcode == 'object') {
    WebBarcode.onBarcodeScan(function(ev) {
        var data = ev.value;
        var upc = data.substring(0,data.length-1);
        $('#upc_in').val(upc);
        $('#goBtn').click();
    });
}

