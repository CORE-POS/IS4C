
export default function enableScanner(callback) {
    if (typeof ScannerDevice != "undefined") { 
        Device = new ScannerDevice({
            barcodeData: function(data, type) {
                const upc = data.substring(0,data.length-1);
                callback(upc);
            },
            magneticCardData: function (track1, track2, track3){},
            magneticCardRawData: function (data){},
            buttonPressed: function (){},
            buttonReleased: function (){},
            connectionState: function (state){}
        });
        ScannerDevice.registerListener(Device);
    }

    if (typeof WebBarcode == 'object') {
        WebBarcode.onBarcodeScan(function(ev) {
            const data = ev.value;
            const upc = data.substring(0,data.length-1);
            callback(upc);
        });
    }
}

