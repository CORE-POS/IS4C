/*
    WebHub.js
    Infinite Peripherals Copyright 2016

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/
function GetFunctionName(name)
{
    if(name && typeof name=="function")
    {
        name=name.toString()
        name=name.substr("function ".length);
        name=name.substr(0,name.indexOf("("));
    }else
    {
        if(!name)
            name="";
    }
    return name;
}

function NativeCall(functionName, args, callbacks, settings)
{
    for(var i=0; callbacks && i<callbacks.length; i++)
    {
        if ( callbacks[i] == null ) callbacks[i]="function(){}"; // Empty Function

        if ( callbacks[i].toString().indexOf("function (") == 0 || callbacks[i].toString().indexOf("function(")==0 )
        {
            // Anonymous Function Blocks
            callbacks[i]="("+callbacks[i].toString()+")";
        }
        else callbacks[i]=GetFunctionName(callbacks[i]);  // Named function
    }
    
    var setArray=[];
    if(settings)
    {
        var index=0;
        for(var i in settings)
        {
            setArray[index++]=i;
            setArray[index++]=GetFunctionName(settings[i]);
        }
    }
    
    var iframe = document.createElement("IFRAME");
    iframe.setAttribute("src", "js-frame:" + functionName + ":" + encodeURIComponent(JSON.stringify(callbacks)) + ":" + encodeURIComponent(JSON.stringify(args)) + ":" + encodeURIComponent(JSON.stringify(setArray)));
    document.documentElement.appendChild(iframe);
    iframe.parentNode.removeChild(iframe);
    iframe = null;
};

var _printImageData;
function getBase64Image(img)
{
    var canvas = document.createElement("canvas");
    canvas.width = img.width;
    canvas.height = img.height;
    
    var ctx = canvas.getContext("2d");
    ctx.drawImage(img, 0, 0);
    
    var dataURL = canvas.toDataURL("image/png");
    
    return dataURL.replace(/^data:image\/(png|jpg);base64,/, "");
}

/*
 Class: WebHub
 WebHub provides easy way to access native device functionality such as camera, printing, barcode scaning.
 In order to use WebHub in your web page, you have to include WebHub.js and call any of its functions like WebHub.Image.import
 */
var WebHub=new function()
{
    this.ERR_UNSUPPORTED=1;
    this.ERR_USER_CANCEL=2;
    this.ERR_TIMEOUT=3;
    this.ERR_FAILED=4;
    
    this.OrientationMasks=
    {
    Portrait:6,
    PortraitHomeDown: 2,
    PortraitHomeUp:4,
    Landscape:24,
    LandscapeRight:8,
    LandscapeLeft:16,
    All:30
    };
    
    /*
     Class: Settings
     Functions to get/set WebHub settings
     */
    this.Settings=new function()
    {
        /*
         Function: get
         
         Retrieves WebHub settings and passes them into array
         
         Parameters:
         
         settingsRead(function) - javascript function to be called when settings are retrieved.
         The format of the function is
         > function settingsRead(settings)
         
         Parameters:
         
         settings - a structure with WebHub settings and their current values:
         (start code)
         - defaultURL(string) - the URL that will be opened when WebHub starts, set to blank to open the demonstration page
         - showNavigation(boolean) - enables or disables the navigation bar
         - enableBack(boolean) - enables or disables back button on the navigation bar
         - enableForward(boolean) - enables or disables forward button on the navigation bar
         - allowRotation(boolean) - enables or disables screen rotation
         - barcodeFunction(string) - javascript function that will be called, when barcode is scanned
         - emulateKeystrokes(boolean) - enables or disables keystroke emulation of the scanned barcode
         - submitForm(boolean) - enables or disables submitting the form after barcode have been scanned
         - barcodeInNamedField(string) - if set, barcode data will be filled into the specified field name
         - barcodeInIDField(string) - if set, barcode data will be filled into the specified field ID
         - msrFunction(string) - javascript function that will be called, when magnetic card is swiped
         - msrEncryptedFunction(string) - javascript function that will be called when magnetic card is swiped from secure head
         - externalCharging(boolean) - if enabled and Linea is attached, then Linea battery will be used to charge the iOS device battery
         - maxTTLMode(boolean) - controls charging mode. If enabled, Linea will charge often the iOS device to keep it full, this allows for
         - zoomMode(int) - 0=Standard, 1=AutoScale, User can resize, 2=AutoScale, User can not resize
         - inactivityReloadsHome(int) - Should inactivity cause the home screen to reload?  0=No, other legal values in seconds 60,120,240,300,600,900,1500,1800 will cause a reload due to no activity (screen touches)
         - codeMotionSense(boolean) - Enables or Disables the code motion sense barcodeing
         longer life of both devices, but results in frequent charge phases. If disabled, the Linea will charge the iOS device only when its battery reaches 30%
         (end)
         
         An example function to display all settings:
         (start code)
         function SettingsReceived(settings)
         {
         document.body.innerHTML+="<b>WebHub settings:</b><br/>";
         for(var i in settings)
         {
         document.body.innerHTML+="<b>"+i+": </b>"+settings[i].toString()+"<br/>";
         }
         }
         (end)
         */
        this.get=function(settingsRead)
        {
            NativeCall("settings.get",null,[settingsRead]);
        }
        /*
         Function: set
         
         Changes WebHub settings
         
         Parameters:
         
         settings(structure) - a structure of WebHub settings and their values. You can set single or multiple settings with a single call
         
         Available settings:
         (start code)
         - defaultURL(string) - the URL that will be opened when WebHub starts, set to blank to open the demonstration page
         - showNavigation(boolean) - enables or disables the navigation bar
         - enableBack(boolean) - enables or disables back button on the navigation bar
         - enableForward(boolean) - enables or disables forward button on the navigation bar
         - allowRotation(boolean) - enables or disables screen rotation
         - barcodeFunction(string) - javascript function that will be called, when barcode is scanned
         - emulateKeystrokes(boolean) - enables or disables keystroke emulation of the scanned barcode
         - submitForm(boolean) - enables or disables submitting the form after barcode have been scanned
         - barcodeInNamedField(string) - if set, barcode data will be filled into the specified field name
         - barcodeInIDField(string) - if set, barcode data will be filled into the specified field ID
         - msrFunction(string) - javascript function that will be called, when magnetic card is swiped.
         - externalCharging(boolean) - if enabled and Linea is attached, then Linea battery will be used to charge the iOS device battery
         - maxTTLMode(boolean) - controls charging mode. If enabled, Linea will charge often the iOS device to keep it full, this allows for
         longer life of both devices, but results in frequent charge phases. If disabled, the Linea will charge the iOS device only when its battery reaches 30%
         (end)
         
         An example function to change some settings:
         (start code)
         WebHub.Settings.set({showNavigation:true,enableForward:false,barcodeFunction:"Barcode"});
         (end)
         */
        this.set=function(settings)
        {
            NativeCall("settings.set",null,null,settings);
        }
        
        this.exit=function()
        {
            NativeCall("settings.exit",null,null,null);
        }
        
        //Use Orientation Masks!
        this.setOrientation=function(anOrientation)
        {
            if (anOrientation>=2 && anOrientation<=30)
                NativeCall("settings.setorientation",[anOrientation],null,null);
        }
        
        this.getBatteryLevel=function(batteryLevelFunction, errorBatteryLevelFunction)
        {
            NativeCall("settings.getbatterylevel", null, [batteryLevelFunction,errorBatteryLevelFunction],null);
        }
        
        this.clearCache=function()
        {
            NativeCall("settings.clearcache",null,null,null);
        }
        
    };
    
    this.Folder=
    {
        NONE : 0,
        TEMP : 1,
        DOCUMENTS : 2,
    };
    /*
     Class: Notifications
     Functions to present various notifications to the user, extended alert, vibration, beeps
     */
    this.Notify=new function()
    {
        /*
         Function: message
         
         Shows advanced message box on the device and returns the user response
         
         Parameters:
         
         title(string) - title string, this can be null
         
         message(string) - message to display
         
         buttons(array) - an array of button names to display, for example {"Ok","Cancel","Apply"}. This parameter can be null, in that case a single "Ok" button is displayed
         
         onDismiss(function) - javascript function that will be called when the user dismisses the messagebox.
         The format of the function is
         > function onDismiss(buttonIndex)
         
         Parameters:
         
         buttonIndex(number) - index of the button that user pressed
         
         Example:
         (start code)
         WebHub.Notify.message("You stole my teddy bear!","Did you?",["Yeah","Noes!","Nevah!"],MessageDismissed);
         (end)
         */
        this.message=function(title, message, buttons, onDismiss)
        {
            NativeCall("notify.message",[title,message,buttons],[onDismiss]);
        };
        /*
         Function: vibrate
         
         Vibrates the device
         
         Example:
         (start code)
         WebHub.Notify.vibrate();
         (end)
         */
        this.vibrate=function()
        {
            NativeCall("notify.vibrate",null);
        };
        /*
         Function: beep
         
         Plays short beep on the device
         
         Example:
         (start code)
         WebHub.Notify.beep();
         (end)
         */
        this.beep=function()
        {
            NativeCall("notify.beep",null);
        };
        /* Function: showActivityIndicator
         
         Shows a modal activity indicator indicating a long running function is in progress
         
         Example:
         (start code)
         WebHub.Notify.showActivityIndicator();
         (end)
         */
        this.showActivityIndicator=function ()
        {
            NativeCall("notify.showActivityIndicator");
        }
        /* Function: hideActivityIndicator
         
         Shows a modal activity indicator indicating a long running function is in progress
         
         Example:
         (start code)
         WebHub.Notify.hideActivityIndicator();
         (end)
         */
        this.hideActivityIndicator=function ()
        {
            NativeCall("notify.hideActivityIndicator");
        }
        /*
         Function: play
         
         Downloads the sound file and plays it on the device. WebHub checks if the file is not already present on the device and in this case uses the local copy.
         
         Parameters:
         
         url(string) - file url
         
         folder(number) - the folder index you want the sound file to be stored into, one of:
         
         - Folder.NONE - sound file will not be stored locally, every time you play it, it will be downloaded again
         - Folder.TEMP - sound file will be stored in a temporaly folder, which gets emptied once every 3 days, or when the device runs out of space
         - Folder.DOCUMENTS - sound file will be stored in an applications documents folder permanently
         
         onFailure(function) - javascript function that will be called when an error occurs. The format of the function is:
         (start code)
         function onFailure(errorCode, errorDescription)
         
         Parameters:
         
         errorCode(number) - error code
         
         errorDescription(string) - textual description of the error
         (end)
         
         Example:
         (start code)
         WebHub.Notify.play("http://www.soundjay.com/button/beep-1.wav",Folder.NONE,PlayFailed);
         (end)
         */
        this.play=function(url, folder, onFailure)
        {
            NativeCall("notify.play",[url,folder],[onFailure]);
        };
        
        this.enableLineaSpeaker=function (enabled, successFunction, errorFunction ) {
            NativeCall("notify.enableLineaSpeaker", [enabled], [successFunction, errorFunction]);
        }
        
        //Green=1
        //Red=2
        //Orange=3
        //Blue=4
        this.controlLEDsWithBitMask=function (bitmask, successFunction, errorFunction ) {
            NativeCall("notify.controlLEDsWithBitMask", [bitmask], [successFunction, errorFunction]);
        }
        
        this.externalVibrationForTime=function ( time ) {
            NativeCall("notify.externalVibrationForTime", [time]);
        }
        
    };
    
    this.SocketEvents={
    OpenCompleted:1
        , BytesAvailable: 2
        , SpaceAvailable: 4
        , ErrorOccured:8
        , EndEncountered:16
    }
    
    this.Socket=new function ()
    {
        this.open=function (socketName, host, port, statusFunction ) {
            NativeCall("socket.open",[socketName, host, port],[statusFunction]);
        }
        
        this.close=function (socketName ) {
            NativeCall("socket.close",[socketName]);
        }
        
        this.read=function( socketName, maxLength, readFunction, failFunction ) {
            NativeCall("socket.read",[socketName, maxLength], [readFunction, failFunction] );
        }
        
        this.write=function( socketName, data, failFunction ) {
            setTimeout( function() { NativeCall("socket.write",[socketName, data],[failFunction]);}, 0); // locks unless in a thread.
        }
    }
    
    this.Crypto=new function()
    {
        // Binary In, Base64 Encrypt Out
        this.tripleDesEncrypt=function(key, data, cbSuccess, cbFail)
        {
            NativeCall("crypto.tripledesencrypt", [key, data], [cbSuccess, cbFail]);
        };
        
        // Base 64 Encrypt In, Binary Out
        this.tripleDesDecrypt=function(key, data, cbSuccess, cbFail)
        {
            
            NativeCall("crypto.tripledesdecrypt", [key, data], [cbSuccess, cbFail]);
        };
        // Binary In, Base64 Encrypt Out
        this.aesEncrypt=function(key, data, cbSuccess, cbFail)
        {
            NativeCall("crypto.aesencrypt", [key, data], [cbSuccess, cbFail]);
        };
        
        // Base 64 Encrypt In, Binary Out
        this.aesDecrypt=function(key, data, cbSuccess, cbFail)
        {
            NativeCall("crypto.aesdecrypt", [key, data], [cbSuccess, cbFail]);
        };
    };
    
    this.TTS=new function()
    {
        this.say=function(whatToSay, volume, rate, voice) {
            // Only WhatToSay is Required
            // Volume 0.0-100.0.                                    Default=100.0
            // Rate,                                                Default=0.3 with greater values getting faster
            // Voice, en-AU (female), en-US (female), en-gb(male).  Default=en-US
            NativeCall("TTS.say", [whatToSay, volume, rate, voice]);
        }
    }
    
    /*
     Class: Images
     Functions to deal with images on the native device, import from camera, albums
     */
    this.ImageSource=
    {
        PHOTOLIBRARY : 0,
        SAVEDPHOTOALBUM : 1
    };
    this.Image=new function()
    {
        /*
         Function: fromCamera
         
         Uses devices camera to capture an image and send it back
         
         Parameters:
         
         onImage(function) - javascript function that will be called with the image data. The format of the function is:
         (start code)
         function onImage(image)
         
         Parameters:
         
         image(string) - base64 encoded image data
         (end)
         
         onFailure(function) - javascript function that will be called when an error occurs. The format of the function is:
         (start code)
         function onFailure(errorCode, errorDescription)
         
         Parameters:
         
         errorCode(number) - error code
         
         errorDescription(string) - textual description of the error
         (end)
         
         settings(structure) - optional structure of additional settings, that you can pass to the function. Supported settings are:
         
         - quality(number) - controls image quality, the higher quality is, the bigger result image is. Valid values are from 10 to 100, default is 75.
         Example usage:
         > WebHub.Image.fromCamera(ImageSuccess,ImageError,{quality:50})
         
         Example:
         (start code)
         function ImageSuccess(picture)
         {
         document.body.innerHTML+="<img name=\"importedImage\" src=\"data:image/jpeg;base64,"+picture+"\" alt=\"1231232.jpg\" /><br/>";
         }
         function ImageError(errorCode, errorDescription)
         {
         alert("Image failed ("+errorCode+"): "+errorDescription);
         }
         
         WebHub.Image.fromCamera(ImageSuccess,ImageError);
         (end)
         */
        this.fromCamera=function(onImage,onFailure,settings)
        {
            NativeCall("image.fromCamera",null,[onImage,onFailure],settings);
        };
        /*
         Function: fromAlbum
         
         Presents an interface so the user can select an image from the devices photo album
         
         Parameters:
         
         imageSource(number) - source album to use, supported values:
         
         - WebHub.ImageSource.PHOTOLIBRARY
         
         - WebHub.ImageSource.SAVEDPHOTOALBUM
         
         onImage(function) - javascript function that will be called with the image data. The format of the function is:
         (start code)
         function onImage(image)
         
         Parameters:
         
         image(string) - base64 encoded image data
         (end)
         
         onFailure(function) - javascript function that will be called when an error occurs. The format of the function is:
         (start code)
         function onFailure(errorCode, errorDescription)
         
         Parameters:
         
         errorCode(number) - error code
         
         errorDescription(string) - textual description of the error
         (end)
         
         settings(structure) - optional structure of additional settings, that you can pass to the function. Supported settings are:
         
         - quality(number) - controls image quality, the higher quality is, the bigger result image is. Valid values are from 10 to 100, default is 75.
         Example usage:
         > WebHub.Image.fromAlbum(WebHub.ImageSource.PHOTOLIBRARY,ImageSuccess,ImageError,{quality:50});
         
         Example:
         (start code)
         function ImageSuccess(picture)
         {
         document.body.innerHTML+="<img name=\"importedImage\" src=\"data:image/jpeg;base64,"+picture+"\" alt=\"1231232.jpg\" /><br/>";
         }
         function ImageError(errorCode, errorDescription)
         {
         alert("Image failed ("+errorCode+"): "+errorDescription);
         }
         
         WebHub.Image.fromAlbum(WebHub.ImageSource.PHOTOLIBRARY,ImageSuccess,ImageError);
         (end)
         */
        this.fromAlbum=function(imageSource,onImage,onFailure,settings)
        {
            NativeCall("image.fromAlbum",[imageSource],[onImage,onFailure],settings);
        };
        this.fromFile=function(source,onImage,onFailure,settings)
        {
            NativeCall("image.fromFile",[source],[onImage,onFailure],settings);
        };
        this.getTiff=function(width, height, data, onSuccess, onFailure)
        {
            NativeCall("image.getTiff", [width,height,data],[onSuccess,onFailure] );
        }
        /*
         Function: simulate
         
         Helper function to provide sample image for testing purposes.
         
         Parameters:
         
         onImage(function) - javascript function that will be called with the image data. The format of the function is:
         (start code)
         function onImage(image)
         
         Parameters:
         
         image(string) - base64 encoded image data
         (end)
         
         settings(structure) - optional structure of additional settings, that you can pass to the function. Supported settings are:
         
         - quality(number) - controls image quality, the higher quality is, the bigger result image is. Valid values are from 10 to 100, default is 75.
         Example usage:
         > WebHub.Image.simulate(ImageSuccess,{quality:50});
         
         Example:
         (start code)
         function ImageSuccess(picture)
         {
         document.body.innerHTML+="<img name=\"importedImage\" src=\"data:image/jpeg;base64,"+picture+"\" alt=\"1231232.jpg\" /><br/>";
         }
         
         WebHub.Image.simulate(ImageSuccess);
         (end)
         */
        this.simulate=function(onImage,settings)
        {
            NativeCall("image.simulate",null,[onImage],settings);
        };
    };
    
    /*
     Class: Barcode
     Functions to monitor and control compatible barcode scanners, attached to the device.
     WebHub automatically sends barcodes to the function, specified in the settings (barcodeFunction) and/or performs keystroke emulation.
     
     The format of the function is:
     
     function BarcodeData(barcode,type,typeText)
     
     Parameters:
     
     barcode(string) - barcode data
     
     type(integer) - barcode type number, as returned by the barcode engine, i.e. 13, refer to WebHub.BarcodeScanType for complete listing
     
     typeText(string) - barcode type converted to string for easy display, i.e. "EAN-13"
     */
    this.BarcodeScanType=
    {
    UPCA: 1,
    CODABAR: 2,
    CODE25_NI2OF5: 3,
    CODE25_I2OF5: 4,
    CODE39: 5,
    CODE93: 6,
    CODE128: 7,
    CODE11: 8,
    CPCBINARY: 9,
    DUN14: 10,
    EAN2: 11,
    EAN5: 12,
    EAN8: 13,
    EAN13: 14,
    EAN128: 15,
    GS1DATABAR: 16,
    ITF14: 17,
    LATENT_IMAGE: 18,
    PHARMACODE: 19,
    PLANET: 20,
    POSTNET: 21,
    INTELLIGENT_MAIL: 22,
    MSI_PLESSEY: 23,
    POSTBAR: 24,
    RM4SCC: 25,
    TELEPEN: 26,
    UK_PLESSEY: 27,
    PDF417: 28,
    MICROPDF417: 29,
    DATAMATRIX: 30,
    AZTEK: 31,
    QRCODE: 32,
    MAXICODE: 33,
    UPCA_2: 39,
    UPCA_5: 40,
    UPCE: 41,
    UPCE_2: 42,
    UPCE_5: 43,
    EAN13_2: 44,
    EAN13_5: 45,
    EAN8_2: 46,
    EAN8_5: 47,
    CODE39_FULL: 48,
    ITA_PHARMA: 49,
    CODABAR_ABC: 50,
    CODABAR_CX: 51,
    SCODE: 52,
    MATRIX_2OF5: 53,
    IATA: 54,
    KOREAN_POSTAL: 55,
    CCA: 56,
    CCB: 57,
    CCC: 58
    };
    
    this.Navigation=new function()
    {
        /* To take advantage of Apple iOS6 Voice Turn by Turn Instructions */
        /* Url Scheme: maps://?daddr=Cupertino has 2 problems */
        /* 1) Current Location is not automatic - you have to determine the lat/long in JavaScript and pass as the saddr */
        /* 2) Directions come up in "Overview" mode without Turn by Turn Assistance. */
        
        /* Function:navigateTo
         
         Use iOS6+ Apple Maps to provide turn by turn directions from currentLocation to toAddress
         
         Parameters:
         
         toAddress(string) - the destination address
         
         successFunction(function) - the function to call if successful
         
         errorFunction(function) - the function to call if unsuccessful
         
         Example:
         (start code)
         WebHub.Navigation.navigateTo("Irvine, CA", null, null );
         (end)
         */
        this.navigateTo=function(toAddress, successFunction, errorFunction)
        {
            NativeCall("navigation.navigateTo", [toAddress], [successFunction, errorFunction]);
        }
    }
    
    this.RFID=new function ()
    {
        this.rfInit=function (supportedCards, successFunction, errorFunction ) {
            NativeCall("rfid.rfInit",[supportedCards],[successFunction,errorFunction]);
        }
        
        this.rfClose=function (successFunction, errorFunction ) {
            NativeCall("rfid.rfClose",null,[successFunction,errorFunction]);
        }
        
        this.onRFCardDetected=function (detectFunction ) {
            NativeCall("rfid.onRFCardDetected",null,[detectFunction]);
        }
        
        this.onRFCardRemoved=function (removeFunction ) {
            NativeCall("rfid.onRFCardRemoved",null,[removeFunction]);
        }
        
        this.rfRemoveCard=function (cardIndex, onSuccessFunction, onErrorFunction) {
            NativeCall("rfid.rfRemoveCard",[cardIndex],[onSuccessFunction, onErrorFunction]);
        }
        
        // Mifare & Ultralight C Cards
        this.mfAuthByKey=function (cardIndex, address, key, onSuccessFunction, onErrorFunction )
        {
            NativeCall("rfid.mfAuthByKey", [cardIndex, address, key], [onSuccessFunction, onErrorFunction] );
        }
        
        this.mfAuthByStoredKey=function (cardIndex, type, address, keyIndex, onSuccessFunction, onErrorFunction ) {
            NativeCall("rfid.mfAuthByStoredKey", [cardIndex, type, address, keyIndex], [onSuccessFunction, onErrorFunction]);
        }
        
        this.mfRead=function( cardIndex, address, length, onSuccessFunction, onErrorFunction ) {
            NativeCall("rfid.mfRead", [cardIndex, address, length], [onSuccessFunction, onErrorFunction]);
        }
        
        this.mfStoreKeyIndex=function ( keyIndex, type, key, onSuccessFunction, onErrorFunction ) {
            NativeCall("rfid.mfStoreKeyIndex", [keyIndex, type, key], [onSuccessFunction, onErrorFunction] );
        }
        
        this.mfUlcAuthByKey=function (cardIndex, key, successFunction, errorFunction ) {
            NativeCall("rfid.mfUlcAuthByKey", [cardIndex, key], [successFunction, errorFunction]);
        }
        
        this.mfUlcSetKey=function (cardIndex, key, onSuccessFunction, onErrorFunction ) {
            NativeCall("rfid.mfUlcSetKey", [cardIndex, key], [onSuccessFunction, onErrorFunction] );
        }
        
        this.mfWrite=function (cardIndex, address, data, onSuccessFunction, onErrorFunction ) {
            NativeCall("rfid.mfWrite", [cardIndex, address, data], [onSuccessFunction, onErrorFunction]);
        }
        
        
    }
    
    /* New and Cool Feature */
    /* WebHub can now load a 2ndary non-visual page that can build functions and UI on top of any loaded page*/
    this.Controller=new function ()
    {
        this.loadPage=function (url) {
            NativeCall("controller.loadPage", [url]);
        }
        
        this.goForward=function() {
            NativeCall("controller.forward");
        }
        
        this.goBack=function() {
            NativeCall("controller.back");
        }
        
        this.goHome=function() {
            NativeCall("controller.home");
        }
        
        this.runJavascript = function(script) {
            NativeCall("controller.runJavascript", [script] );  // This is where we can add and extend loaded webpages!  Deceptively powerful.
        }
    }
    
    // Advantage of this is web app can be put in background, but WebHub=Native would still receive updates.
    this.GPS=new function()
    {
        this.setListener=function (listenerFunction)
        {
            NativeCall("gps.setListener", null, [listenerFunction], null);
        }
        
        this.startMonitoring=function (locationAccuracy, distanceFilter)
        {
            NativeCall("gps.startMonitoring", [locationAccuracy, distanceFilter],null,null);
        }
        
        this.startMonitoringSignificant=function ()
        {
            NativeCall("gps.startMonitoringSignificant", null,null,null);
        }
        
        this.stopMonitoring=function ()
        {
            NativeCall("gps.stopMonitoring", null, null, null);
        }
        
    }
    
    this.Barcode=new function()
    {
        /*
         Function: simulate
         
         Helper function to simulate barcode scan for testing purposes. The simulated barcode is sent just like normal scanned one will be.
         
         Parameters:
         
         barcode(string) - optional barcode string, you can pass null for default one
         
         type(number) - barcode type or null for EAN-13
         
         Example:
         (start code)
         WebHub.Barcode.simulate();
         WebHub.Barcode.simulate("123456789");
         WebHub.Barcode.simulate("123456789",14);
         (end)
         */
        this.simulate=function(barcode,type)
        {
            NativeCall("scanner.simulate",[barcode,type]);
        };
        /*
         Function: monitorStatus
         
         Helper function to monitor the status of the supported barcode scanners. This function is used to receive realtime notifications when supported
         scanner becomes available.
         
         Parameters:
         
         scannerStatus(function) - javascript function that will be called with information about scanner status. The format of the function is:
         (start code)
         function scannerStatus(info)
         
         Parameters:
         
         info(structure) - information about the reader:
         
         - info.connected(boolean) - indicates if supported reader is connected
         - info.name(string) - reader name
         - info.version(string) - reader firmware version
         (end)
         
         Example:
         (start code)
         function BarcodeStatus(info)
         {
         if(info.connected)
         document.body.innerHTML+="Connected barcode reader <b>"+info.name+" "+info.version+"</b>, get those barcodes rolling!<br/>";
         else
         document.body.innerHTML+="Hey I want my barcode reader back!<br/>";
         }
         
         WebHub.Barcode.monitorStatus(BarcodeStatus);
         (end)
         */
        this.monitorStatus=function(scannerStatus)
        {
            NativeCall("scanner.monitorStatus",null,[scannerStatus]);
        };
        
        this.startScan=function()
        {
            NativeCall("scanner.startScan",null,null);
        }
        
        this.stopScan=function()
        {
            NativeCall("scanner.stopScan",null,null);
        }
    };
    
    /*
     Class: MagStripe
     Functions to monitor and control compatible magnetic card readers, attached to the device.
     WebHub automatically sends magnetic card data to the function, specified in the settings (msrFunction).
     
     The format of the function is:
     
     function MagneticCardData(card)
     
     Parameters:
     
     card(structure) - structure, containing the extracted card data - tracks and financial information, if available. Structure fields:
     
     - card.tracks(array) - an array with card track data. Every track is returned as a string or null if the track was not read
     - card.cardholderName(string) - in case of valid financial card and correctly parsed track 1, cardholder name is stored here
     - card.accountNumber(string) - in case of valid financial card and correctly parsed track 1 or 2, account number is stored here
     - card.exirationMonth(number) - in case of valid financial card and correctly parsed track 1 or 2, expiration month is stored here
     - card.exirationYear(number) - in case of valid financial card and correctly parsed track 1 or 2, expiration year is stored here
     */
    this.MagStripe=new function()
    {
        /*
         Function: simulate
         
         Helper function to simulate magnetic card scan for testing purposes. The simulated magnetic card data is sent to the function in WebHubs settings.
         
         Example:
         (start code)
         WebHub.MagStripe.simulate();
         (end)
         */
        this.simulate=function()
        {
            NativeCall("msreader.simulate");
        };
        /*
         Function: monitorStatus
         
         Helper function to monitor the status of the supported magnetic card readers. This function is used to receive realtime notifications when supported
         reader becomes available.
         
         Parameters:
         
         scannerStatus(function) - javascript function that will be called with information about scanner status. The format of the function is:
         (start code)
         function readerStatus(info)
         
         Parameters:
         
         info(structure) - information about the reader:
         
         - info.connected(boolean) - indicates if supported reader is connected
         - info.name(string) - reader name
         - info.version(string) - reader firmware version
         (end)
         
         Example:
         (start code)
         function MSStatus(info)
         {
         if(info.connected)
         document.body.innerHTML+="Connected magnetic card reader <b>"+info.name+" "+info.version+"</b>, find me some cards!<br/>";
         else
         document.body.innerHTML+="Hey I want my magnetic card reader back!<br/>";
         }
         
         WebHub.MagStripe.monitorStatus(MSStatus);
         (end)
         */
        this.monitorStatus=function(readerStatus)
        {
            NativeCall("msreader.monitorStatus",null,[readerStatus]);
        };
        
        /*
         Kyle - July 17, 2015
         - Set masked credit card number
         - Set active encryption head
         - Set encryption type: IDTECH 3
         */
        
        // boolExpiration: (boolean) show expiration
        // unmaskedDigitsAtStart: (int) number of unmasked digits at start, max is 6
        // unmaskedDigitsAtEnd: (int) number of unmasked digits at end, max is 6
        // successFunction: delegate success function
        // errorFunction: delegate error function
        this.setEMSRMaskedData = function(boolExpiration, unmaskedDigitsAtStart, unmaskedDigitsAtEnd, successFunction, errorFunction)
        {
            var isExpiration = boolExpiration ? 1 : 0;
            NativeCall("msreader.setEMSRMaskedData", [isExpiration, unmaskedDigitsAtStart, unmaskedDigitsAtEnd], [successFunction, errorFunction]);
        }
        
        // headIndex: (int) currently real head is on index 0, emulated head is on 1
        // successFunction: delegate success function
        // errorFunction: delegate error function
        this.setActiveHead = function(headIndex, successFunction, errorFunction)
        {
            NativeCall("msreader.setActiveHead", [headIndex], [successFunction, errorFunction]);
        }
        
        // encryptionIndex: (int) index of encryption type. Use 3 for IDTECH 3
        // successFunction: delegate success function
        // errorFunction: delegate error function
        this.setEMSREncryption = function(encryptionIndex, successFunction, errorFunction)
        {
            NativeCall("msreader.setEMSREncryption", [encryptionIndex], [successFunction, errorFunction]);
        }
        // End
    };
    
    /*
     Class: Printer
     Functions to to print text, graphics and barcodes to the supported printers.
     */
    this.BarcodePrintType=
    {
        /*
         * Prints UPC-A barcode
         */
    UPCA: 0,
        /**
         * Prints UPC-E barcode
         */
    UPCE: 1,
        /**
         * Prints EAN-13 barcode
         */
    EAN13: 2,
        /**
         * Prints EAN-8 barcode
         */
    EAN8: 3,
        /**
         * Prints CODE39 barcode
         */
    CODE39: 4,
        /**
         * Prints ITF barcode
         */
    ITF: 5,
        /**
         * Prints CODABAR barcode
         */
    CODABAR: 6,
        /**
         * Prints CODE93 barcode
         */
    CODE93: 7,
        /**
         * Prints CODE128 barcode
         */
    CODE128: 8,
        /**
         * Prints 2D PDF-417 barcode
         */
    PDF417: 9,
        /**
         * Prints CODE128 optimized barcode. Supported only on DPP-250, DPP-350 and PP-60 printers,
         * it makes the barcode lot smaller especially when numbers only are used
         */
    CODE128AUTO: 10,
        /**
         * Prints EAN128 optimized barcode. Supported only on DPP-250, DPP-350 and PP-60 printers,
         * it makes the barcode lot smaller especially when numbers only are used
         */
    EAN128AUTO: 11
    };
    this.BarcodeHRIPosition=
    {
        /**
         * Disables HRI text
         */
    NONE: 0,
        /**
         * Prints HRI above the barcode
         */
    ABOVE: 1,
        /**
         * Prints HRI below the barcode
         */
    BELOW: 2,
        /**
         * Prints HRI both above and below the barcode
         */
    BOTH: 3
    };
    this.Align=
    {
        /**
         * Left aligning
         */
    LEFT: 0,
        /**
         * Center aligning
         */
    CENTER: 1,
        /**
         * Right aligning
         */
    RIGHT: 2,
    };
    
    this.PrinterInfo=
    {
        /**
         * Battery Voltage
         */
    INFO_BATVOLT:   0,
        /**
         * Battery Percent
         */
    INFO_BATPERCENT: 1,
        /**
         * Printer Head Temperature Celsius
         */
    INFO_TEMPC: 2,
        /**
         * Printer Head Temperature Fahrenheit
         */
    INFO_TEMPFR: 3,
        /**
         * Printer Version
         */
    INFO_PRINTERVERSION: 4,
        /**
         * Printer Model
         */
    INFO_PRINTERMODEL: 5,
        /**
         * Printer Paper Width
         */
    INFO_PAPERWIDTH: 6,
        /**
         * Printer Page Height
         */
    INFO_PAGEHEIGHT:7
    }
    
    this.Printer=new function()
    {
        /*
         Function: printText
         
         Prints text on the printer with various styles and font sizes.
         
         Parameters:
         
         text(string) - text to print. The text string can contain control symbols that define styles, font sizes, alignment. Available styles:
         (start code)
         - {==} - reverts all settings to their defaults. That includes font size, style, intensity, aligning
         - {=Fx} - selects font size. x ranges from 0 to 7 as follows:
         * 0 - FONT_9X16
         * 1 - FONT_9X32
         * 2 - FONT_18X32
         * 3 - FONT_12X24
         * 4 - FONT_24X24
         * 5 - FONT_12X48
         * 6 - FONT_24X48
         - {=L} - left text aligning
         - {=C} - center text aligning
         - {=R} - right text aligning
         - {=Rx} - text rotation as follows:
         * 0 - not rotated
         * 1 - rotated 90 degrees
         * 2 - rotated 180 degrees
         - {=Ix} - sets intensity level as follows:
         * 0 - intensity 70%
         * 1 - intensity 80%
         * 2 - intensity 90%
         * 3 - intensity 100%
         * 4 - intensity 120%
         * 5 - intensity 150%
         - {+/-B} - sets or unsets bold font style
         - {+/-I} - sets or unsets italic font style
         - {+/-U} - sets or unsets underline font style
         - {+/-V} - sets or unsets inverse font style
         - {+/-W} - sets or unsets text word-wrapping
         (end)
         
         onFailure(function) - javascript function that will be called when an error occurs. The format of the function is:
         (start code)
         function onFailure(errorCode, errorDescription)
         
         Parameters:
         
         errorCode(number) - error code
         
         errorDescription(string) - textual description of the error
         (end)
         
         Example:
         (start code)
         WebHub.Printer.printText("{=C}FONT SIZES\n{=L}{=F0}Font 9x16\n{=F1}Font 18x16\n{=F2}Font 9x32\n{=F3}Font 18x32\n",PrintFailed);
         WebHub.Printer.printText("{=F4}Font 12x24\n{=F5}Font 24x24\n{=F6}Font 12x48\n{=F7}Font 24x48\n\n",PrintFailed);
         
         WebHub.Printer.printText("{=C}FONT STYLES\n{=L}Normal\n{+B}Bold\n{+I}Bold Italic{-I}{-B}\n{+U}Underlined{-U}\n{+V}Inversed{-V}\n\n",PrintFailed);
         WebHub.Printer.printText("{=C}FONT ROTATION\n{=L}{=R1}Rotated 90 degrees\n{=R2}Rotated 180 degrees\n\n",PrintFailed);
         
         WebHub.Printer.printText("{+W}{=F0}This function demonstrates the use of the built-in word-wrapping capability\n",PrintFailed);
         (end)
         */
        this.printText=function(text,onFailure)
        {
            NativeCall("printer.printText",[text],[onFailure]);
        };
        
        this.flushCache=function(onFailure)
        {
            NativeCall("printer.flushcache",null,[onFailure]);
        }
        
        /*
         Function: printBarcode
         
         Prints text on the printer with various styles and font sizes.
         
         Parameters:
         
         type(number) - barcode type to print, please do not confuse this with the barcode type from scanning functions. Available print barcode types:
         (start code)
         - WebHub.BarcodePrintType.UPCA - prints UPC-A barcode
         - WebHub.BarcodePrintType.UPCE - prints UPC-E barcode
         - WebHub.BarcodePrintType.EAN13 - prints EAN-13 barcode
         - WebHub.BarcodePrintType.EAN8 - prints EAN-8 barcode
         - WebHub.BarcodePrintType.CODE39 - Prints Code 39 barcode
         - WebHub.BarcodePrintType.ITF - prints ITF barcode
         - WebHub.BarcodePrintType.CODABAR - prints CODABAR barcode
         - WebHub.BarcodePrintType.CODE93 - prints Code 93 barcode
         - WebHub.BarcodePrintType.CODE128 - prints CODE128 barcode
         - WebHub.BarcodePrintType.PDF417 - Prints 2D PDF-417 barcode
         - WebHub.BarcodePrintType.CODE128AUTO - prints CODE128 optimized barcode, supported only on DPP-250, DPP-350 and PP-60 printers,
         it makes the barcode lot smaller especially when numbers only are used
         - WebHub.BarcodePrintType.EAN128AUTO - prints EAN128 optimized barcode, supported only on DPP-250, DPP-350 and PP-60 printers,
         it makes the barcode lot smaller especially when numbers only are used
         (end)
         
         barcode(string) - barcode data to print. Please note, that every barcode have specific requirements about the data - be that length,
         symbols that can be printed, tables to pick from, etc, consult barcode type documentation if some barcode refuses to print
         
         onFailure(function) - javascript function that will be called when an error occurs. The format of the function is:
         (start code)
         function onFailure(errorCode, errorDescription)
         
         Parameters:
         
         errorCode(number) - error code
         
         errorDescription(string) - textual description of the error
         (end)
         
         settings(structure) - additional optional barcode settings. Supported settings are:
         - align(number) - barcode horizontal alignment, one of:
         > WebHub.Align.LEFT - left alignment
         > WebHub.Align.CENTER - centered
         > WebHub.Align.RIGHT - right alignment
         - height(number) - barcode height in millimeters, default is 9
         - hri(number) - HRI position
         
         Example:
         (start code)
         (end)
         */
        this.printBarcode=function(type,barcode,onFailure,settings)
        {
            NativeCall("printer.printBarcode",[type,barcode],[onFailure],settings);
        };
        /*
         Function: feedPaper
         
         Feeds the paper to leave blank space between prints, or to allow the paper to be teared.
         
         Parameters:
         
         amountmm(number) - the amount of millimeters to feed the paper. Passing 0 will feed the paper the nessesary amount so it can be teared by the user
         
         onFailure(function) - javascript function that will be called when an error occurs. The format of the function is:
         (start code)
         function onFailure(errorCode, errorDescription)
         
         Parameters:
         
         errorCode(number) - error code
         
         errorDescription(string) - textual description of the error
         (end)
         
         Example:
         (start code)
         //feeds the paper 1cm
         WebHub.Printer.feedPaper(10,PrintFailed);
         
         //feeds the paper so it can be teared
         WebHub.Printer.feedPaper(0,PrintFailed);
         (end)
         */
        this.feedPaper=function(amountmm,onFailure)
        {
            NativeCall("printer.feedPaper",[amountmm],[onFailure]);
        };
        /*
         Function: printImage
         
         Prints image. The image is converted to black & white using dithering to preserve image quality.
         
         Parameters:
         
         image(object) - the image to print
         
         onFailure(function) - javascript function that will be called when an error occurs. The format of the function is:
         (start code)
         function onFailure(errorCode, errorDescription)
         
         Parameters:
         
         errorCode(number) - error code
         
         errorDescription(string) - textual description of the error
         (end)
         
         Example:
         (start code)
         WebHub.Printer.printImage(document.getElementsByName("Image")[0],PrintFailed);
         (end)
         */
        this.printImage=function(image,onFailure)
        {
            _printImageData=getBase64Image(image);
            NativeCall("printer.printImage",null,[onFailure]);
        };
        
        this.printBase64Image=function( image, onFailure )
        {
            _printImageData=image.replace(/^data:image\/(png|jpg);base64,/, "");
            NativeCall("printer.printImage", null, [onFailure]);
        }
        
        this.tiffImage=function(image, onFailure )
        {
            _printImageData=getBase64Image(image);
            NativeCall("printer.tifftest",null,[onFailure]);
        }
        
        /*
         Function: printLogo
         
         Prints the logo, previously stored in the printer. If the printer does not support, of have no logo set, nothing is printed.
         
         Parameters:
         
         onFailure(function) - javascript function that will be called when an error occurs. The format of the function is:
         (start code)
         function onFailure(errorCode, errorDescription)
         
         Parameters:
         
         errorCode(number) - error code
         
         errorDescription(string) - textual description of the error
         (end)
         
         Example:
         (start code)
         WebHub.Printer.printLogo(PrintFailed);
         (end)
         */
        this.printLogo=function(onFailure)
        {
            NativeCall("printer.printLogo",null,[onFailure]);
        };
        /*
         Function: monitorStatus
         
         Helper function to monitor the status of the supported printers. This function is used to receive realtime notifications when supported
         printer becomes available.
         
         Parameters:
         
         printerStatus(function) - javascript function that will be called with information about printer status, that includes if printers paper is out, or battery is low.
         The format of the function is:
         (start code)
         function printerStatus(info)
         
         Parameters:
         
         info(structure) - information about the printer:
         
         - info.connected(boolean) - indicates if supported printer is connected
         - info.name(string) - printer name
         - info.version(string) - printer firmware version
         - info.outOfPaper(boolean) - true if printer is out of paper, false otherwise
         - info.lowBattery(boolean) - true if printers battery level is dangerously low
         (end)
         
         Example:
         (start code)
         function PrinterStatus(info)
         {
         if(info.connected==true)
         document.body.innerHTML+="Connected printer <b>"+info.name+" "+info.version+"</b>, time to put some paper to waste!<br/>";
         if(info.connected==false)
         document.body.innerHTML+="Hey I want my printer back!<br/>";
         if(info.outOfPaper==true)
         document.body.innerHTML+="Go go buy some paper!<br/>";
         if(info.outOfPaper==false)
         document.body.innerHTML+="Printer have paper<br/>";
         if(info.lowBattery==true)
         document.body.innerHTML+="Got any charger?<br/>";
         if(info.lowBattery==false)
         document.body.innerHTML+="Battery is fine<br/>";
         }
         
         WebHub.Printer.monitorStatus(PrinterStatus);
         (end)
         */
        this.monitorStatus=function(printerStatus)
        {
            NativeCall("printer.monitorStatus",null,[printerStatus]);
        };
        
        /*
         Function: getInfo
         
         Helper function to query various info items from the printer
         
         Parameters:
         
         getInfo(infoCode, infoFunction) - The javascript function will be called with the answer to the infoCode query
         
         The format of the function is:
         (start code)
         function getInfo(
         
         Parameters:
         
         info(structure) - information about the printer:
         
         - info.connected(boolean) - indicates if supported printer is connected
         - info.name(string) - printer name
         - info.version(string) - printer firmware version
         - info.outOfPaper(boolean) - true if printer is out of paper, false otherwise
         - info.lowBattery(boolean) - true if printers battery level is dangerously low
         (end)
         
         Example:
         (start code)
         function PrinterStatus(info)
         {
         if(info.connected==true)
         document.body.innerHTML+="Connected printer <b>"+info.name+" "+info.version+"</b>, time to put some paper to waste!<br/>";
         if(info.connected==false)
         document.body.innerHTML+="Hey I want my printer back!<br/>";
         if(info.outOfPaper==true)
         document.body.innerHTML+="Go go buy some paper!<br/>";
         if(info.outOfPaper==false)
         document.body.innerHTML+="Printer have paper<br/>";
         if(info.lowBattery==true)
         document.body.innerHTML+="Got any charger?<br/>";
         if(info.lowBattery==false)
         document.body.innerHTML+="Battery is fine<br/>";
         }
         
         WebHub.Printer.monitorStatus(PrinterStatus);
         (end)
         */
        this.getInfo=function(infoCmd, infoStatus )
        {
            NativeCall("printer.getInfo",[infoCmd],[infoStatus]);
        };
        
        this.advanceBlackMark=function(successFunction,errorFunction)
        {
            NativeCall("printer.calibrateBlackMark",null,[successFunction,errorFunction]);
        };
    };
    /*
     Class: AddressBook
     Functions to operate with device Address Book.
     */
    this.AddressBook=new function()
    {
        /*
         Function: pickContact
         
         Presents an interface on the device to select a contact from the Address Book and returns its details
         
         Parameters:
         
         onContact(function) - javascript function that will be called when the user selects contact. The format of the function is:
         (start code)
         function onContact(contact)
         
         Parameters:
         
         contact(structure) - structure containing contact details. Not every field is guaranteed to be there, so check for undefined/null fields. Available fields are:
         - contact.firstName
         - contact.lastName
         - contact.middleName
         - contact.prefix
         - contact.suffix
         - contact.organization
         - contact.jobTitle
         - contact.department
         - contact.note
         
         - contact.phone.mobile
         - contact.phone.iPhone
         - contact.phone.main
         - contact.phone.homeFax
         - contact.phone.workFax
         - contact.phone.pager
         - contact.phone.work
         - contact.phone.home
         
         - contact.email.work
         - contact.email.home
         
         - contact.address.work.street
         - contact.address.work.city
         - contact.address.work.state
         - contact.address.work.zip
         - contact.address.work.country
         - contact.address.work.countryCode
         
         - contact.address.home.street
         - contact.address.home.city
         - contact.address.home.state
         - contact.address.home.zip
         - contact.address.home.country
         - contact.address.home.countryCode
         (end)
         
         onFailure(function) - javascript function that will be called when an error occurs. The format of the function is:
         (start code)
         function onFailure(errorCode, errorDescription)
         
         Parameters:
         
         errorCode(number) - error code
         
         errorDescription(string) - textual description of the error
         (end)
         
         Example:
         (start code)
         WebHub.Printer.printLogo(PrintFailed);
         (end)
         */
        this.pickContact=function(onContact,onFailure)
        {
            NativeCall("addressbook.pickContact",null,[onContact,onFailure]);
        };
    };
    /*
     Class: Phone
     Functions to access phone functionality - dial a number and send an SMS.
     */
    this.Phone=new function()
    {
        /*
         Function: dial
         
         Dials a phone number on the device. On iPhone dialing a number makes the program exit and the phone program is launched with the number. The user have to confirm
         the call, then return back to the program. There are restrictions about special symbols like # when dialing programatically.
         
         Parameters:
         
         number(string) - phone number to dial
         
         onSuccess(function) - javascript function that will be called when the phone is dialed. The format of the function is:
         (start code)
         function onSuccess()
         (end)
         
         onFailure(function) - javascript function that will be called when an error occurs. The format of the function is:
         (start code)
         function onFailure(errorCode, errorDescription)
         
         Parameters:
         
         errorCode(number) - error code
         
         errorDescription(string) - textual description of the error
         (end)
         
         Example:
         (start code)
         WebHub.Phone.dial("123456",PhoneSuccess,PhoneError);
         (end)
         */
        this.dial=function(number,onSuccess,onFailure)
        {
            NativeCall("phone.dial",[number],[onSuccess,onFailure]);
        };
        /*
         Function: sendSMS
         
         Presents a dialog, filled with the SMS information - numbers to send the message to and message itself. The user have to confirm the sending.
         
         Parameters:
         
         numbers(array of strings) - phone number(s) the message will be sent to
         
         message(string) - message body
         
         onSuccess(function) - javascript function that will be called when the sms is confirmed by the user. The format of the function is:
         (start code)
         function onSuccess()
         (end)
         
         onFailure(function) - javascript function that will be called when an error occurs. The format of the function is:
         (start code)
         function onFailure(errorCode, errorDescription)
         
         Parameters:
         
         errorCode(number) - error code
         
         errorDescription(string) - textual description of the error
         (end)
         
         Example:
         (start code)
         WebHub.Phone.sendSMS(["123456","654321"],"Message body",PhoneSuccess,PhoneError);
         (end)
         */
        this.sendSMS=function(numbers,message,onSuccess,onFailure)
        {
            NativeCall("phone.sendSMS",[numbers,message],[onSuccess,onFailure]);
        };
    };
    /*
     Class: Device
     Functions to get device specific information.
     */
    this.Device=new function()
    {
        /*
         Function: getInformation
         
         Returns information about the current device such as its name, model, system version
         
         Parameters:
         
         deviceInfo(function) - javascript function that will be called with the detailed device information. The format of the function is:
         (start code)
         function deviceInfo(info)
         
         Parameters:
         
         info(structure) - structure containing device information. Available fields are:
         - info.name(string) - devices user name
         - info.serial(string) - unique device serial number
         - info.systemName(string) - devices system name
         - info.systemVersion(string) - firmware version
         - info.model(string) - model string
         (end)
         
         Example:
         (start code)
         function DeviceInfo(info)
         {
         //loop through the information and display it
         var infoStr="";
         for(var i in info)
         {
         infoStr+=i+": "+info[i]+"\n";
         }
         alert(infoStr)
         }
         function GetDeviceInfo()
         {
         WebHub.Device.getInformation(DeviceInfo);
         }
         (end)
         */
        this.getInformation=function(deviceInfo)
        {
            NativeCall("device.getInformation",null,[deviceInfo]);
        };
        
        this.getIMEI=function(successFunction) {
            NativeCall("device.getIMEI", null, [successFunction]);
        }
        
        this.getPhoneNumber=function(successFunction) {
            NativeCall("device.getPhoneNumber", null, [successFunction]);
        }
    };
    //-----------------------------------------------------------------------------------------------
    /*
     Class: Bluetooth
     Functions that expose the Linea Bluetooth Stack to Web Developers
     */
    this.Bluetooth=new function()
    {
        this.getEnabled=function(statusFunction,errorFunction)
        {
            NativeCall("bluetooth.getEnabled",null,[statusFunction,errorFunction]);
        };
        
        this.setEnabled=function(enabled,statusFunction,errorFunction)
        {
            NativeCall("bluetooth.setEnabled",[enabled],[statusFunction,errorFunction]);
        };
        
        this.monitorStatus=function(bluetoothStatus)
        {
            // 1=Connected
            // 2=Data Ready to Read
            // 8=Error
            // 16=Disconnected
            NativeCall("bluetooth.monitorStatus",null,[bluetoothStatus]);
        };
        
        this.monitorDeviceDiscovered=function(deviceDiscovered)
        {
            NativeCall("bluetooth.monitorDeviceDiscovered",null,[deviceDiscovered]);
        };
        
        this.monitorDiscoveryComplete=function(discoveryComplete)
        {
            NativeCall("bluetooth.monitorDiscoveryComplete",null,[discoveryComplete]);
        };
        
        this.discoverPrinters=function(maxDevices,maxTimeout,errorFunction)
        {
            NativeCall("bluetooth.discoverPrinters",[maxDevices,maxTimeout], [errorFunction]);
        };
        
        this.discoverDevices=function(maxDevices,maxTimeout,codTypes,errorFunction)
        {
            NativeCall("bluetooth.discoverDevices",[maxDevices,maxTimeout,codTypes], [errorFunction]);
        };
        
        this.printerConnect=function(address,pin,successFunction,errorFunction)
        {
            NativeCall("bluetooth.printerConnect",[address,pin],[successFunction,errorFunction]);
        };
        
        this.connect=function(address,pin,successFunction,errorFunction)
        {
            NativeCall("bluetooth.connect",[address,pin],[successFunction,errorFunction]);
        };
        
        this.disconnect=function(address,successFunction,errorFunction)
        {
            NativeCall("bluetooth.disconnect",[address],[successFunction,errorFunction]);
        };
        
        this.write=function(data,errorFunction)
        {
            NativeCall("bluetooth.write",[data],[errorFunction]);
        };
        ////        // Kyle: Testing new bluetooth function
        this.writeStream = function(data, errorFunction)
        {
            NativeCall("bluetooth.writeStream", [data], [errorFunction]);
        };
        
        this.writeStreamData = function(data, errorFunction)
        {
            NativeCall("bluetooth.writeStreamData", [data], [errorFunction]);
        }
        ////        // ******************************
        this.read=function(maxLength, maxTimeout, receiveFunction, errorFunction )
        {
            NativeCall("bluetooth.read",[maxLength,maxTimeout],[receiveFunction,errorFunction]);
        };
    };
    //-----------------------------------------------------------------------------------------------
    this.URL=new function()
    {
        this.requestSync=function(url, postData, uid, pwd, cbSuccess, cbFailure )
        {
            NativeCall("url.requestsync", [url, postData, uid, pwd], [cbSuccess, cbFailure]);
        }
        this.requestAsync=function(url, postData, uid, pwd, cbSuccess, cbFailure )
        {
            NativeCall("url.requestasync", [url, postData, uid, pwd], [cbSuccess, cbFailure]);
        }
    }
    //-----------------------------------------------------------------------------------------------
    /*
     Class: Licensing
     Functions to deal with licensing WebHub.
     */
    this.Licensing=new function()
    {
        /*
         Function: display
         
         Pops a dialog with current license information, registration status, expiration date.
         
         Example:
         (start code)
         WebHub.Licensing.display();
         (end)
         */
        this.display=function()
        {
            NativeCall("license.display",null,null);
        };
        /*
         Function: getInformation
         
         Returns information about WebHubs license such as key, expiration date, is registered, etc.
         
         Parameters:
         
         number(string) - phone number to dial
         
         licenseInfo(function) - javascript function that will be called with the detailed license information. The format of the function is:
         (start code)
         function licenseInfo(info)
         
         Parameters:
         
         info(structure) - structure containing device information. Available fields are:
         - info.registered(boolean) - true if WebHub is registered, false otherwise
         - info.key(string) - WebHub key, if any
         - info.enterpriseID(number) - enterprise ID, if any
         - info.expirationDate(Date) - expiration date
         (end)
         
         Example:
         (start code)
         function LicenseInfo(info)
         {
         //loop through the information and display it
         var infoStr="";
         for(var i in info)
         {
         infoStr+=i+": "+info[i]+"\n";
         }
         alert(infoStr)
         }
         function GetLicenseInfo()
         {
         WebHub.Licensing.getInformation(LicenseInfo);
         }
         (end)
         */
        this.getInformation=function(licenseInfo)
        {
            NativeCall("license.getInformation",null,[licenseInfo]);
        };
        /*
         Function: setKey
         
         Sets/updates WebHubs key
         
         Parameters:
         
         key(string) - activation key
         
         Example:
         (start code)
         WebHub.Licensing.setKey("activation_key_goes_here");
         (end)
         */
        this.setKey=function(key)
        {
            NativeCall("license.setKey",[key],null);
        };
        /*
         Function: registerOnline
         
         Navigates to MobileVision web page, where you can easily register your device
         
         Example:
         (start code)
         WebHub.Licensing.registerOnline();
         (end)
         */
        this.registerOnline=function()
        {
            NativeCall("license.registerOnline",null,null);
        };
        /*
         Function: releaseOnline
         
         Navigates to MobileVision web page, and unregisters your device.
         
         Example:
         (start code)
         WebHub.Licensing.releaseOnline();
         (end)
         */
        this.releaseOnline=function()
        {
            NativeCall("license.releaseOnline",null,null);
        };
        /*
         Function: registerOffline
         
         Pops a dialog box, where you can enter your key data. The key is generated by logging with your enterprise account on MobileVision web page
         and entering the device ID there.
         
         Example:
         (start code)
         WebHub.Licensing.registerOffline();
         (end)
         */
        this.registerOffline=function()
        {
            NativeCall("license.registerOffline",null,null);
        };
        /*
         Function: releaseOffline
         
         Pops a dialog box, where you can enter your key data. To get the release key you have to log with your enterprise account on MobileVision web page
         and enter the device ID there.
         
         Example:
         (start code)
         WebHub.Licensing.releaseOffline();
         (end)
         */
        this.releaseOffline=function()
        {
            NativeCall("license.releaseOffline",null,null);
        };
    };
};

