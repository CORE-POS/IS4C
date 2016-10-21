/*
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/lgpl.html>.
*/
/**
* Used for helpful debugging info.
* 
* @type Boolean Weather to show events when bad stuff happens.
*/
Debugging = true;
/**
* ScannerDevice library to listen and set options to the Linea device. Note that you must then register the object after it's been
* constructed or no events will be passed to function.
* 
* @param {Object} mappings If you wish to set listeners, you do it here by passing the listener function here.
* @example DeviceObject = ScannerDevice({
	barcodeData: function (data, bar_type){
		alert('Barcode scanned with with data: '+data+' and of type: '+bar_type);
	},
	magneticCardData: function (track1, track2, track3){
		alert('Card read with following data: '+track1+'\n'+track2+'\n'+track3);
	},
	magneticCardRawData: function (data){
		alert('Raw data read from card reader: '+data);
	},
	buttonPressed: function (button){
		// Currently device only registers 1 button.
		alert('Button was pressed');
	},
	buttonReleased: function (button){
		// Currently device only registers 1 button.
		alert('Button was released');
	},
	connectionState: function (state){
		alert('Scanner is now in state: '+state);
	}
});
ScannerDevice.registerListener(Device);
* @namespace ScannerDevice
* @constructor
*/
ScannerDevice = function (mappings){
	/* BEGIN PRIVATE VARS */
	/**
	* Used to save memory by using the same ref for every empty function
	* instead of createing a new instance every time
	* @ignore
	*/
	var emptyFn = function (){};
	/**
	* Used as a default callback if none is specified. Used only for error
	* callback.
	* 
	* @param {Array} params Error returned by cordova or scanner.
	* @ignore
	*/
	var emptyErrFn = function (params){
		if(Debugging)
			alert(params);
	};
	/* END PRIVATE VARS */
	/**
	* Helper function to return the constant name of a barcode by it's ID.
	* *Note this function only works for non extended barcodes.
	* 
	* @param {Int} barcode_id Barcode ID.
	* @returns {String|Null} Constant name of barcode if exists.
	*/
	this.getBarcodeConstName = function(barcode_id){
		barcode_id = parseInt(barcode_id);
		var i;
		for(i in ScannerDevice.CONSTANTS.BAR_TYPES){
			if(ScannerDevice.CONSTANTS.BAR_TYPES[i] == barcode_id){
				return i;
			}
		}
		return null;
	};
	/**
	* @see ScannerDevice#unregisterListener
	*/
	this.destroy = function (){
		ScannerDevice.unregisterListener(this);
	};
	/**
	* Sends a command though cordova to the Linea Device. See each individual functions
	* for available commands
	* 
	* @param {String} command Command to send to device.
	* @param {Array} args Arguments to send along with command.
	* @param {Function} callback Success callback function. Callback contains 1 arguments containing an array of arguments.
	* @param {Function} errorCallback Error callback function. Callback contains 1 arguments containing an array of arguments.
	*/
	this.sendCommand = function (command, args, callback, errorCallback){
		switch(command){
			case 'enableBarcode':
				ScannerDevice.lastSettings.barcodeStatus[parseInt(args[0])] = args[1] ? true : false;
				break;
			case 'playSound': break;
			case 'startScan':
				ScannerDevice.LAZER_ON = true;
				break;
			case 'stopScan':
				ScannerDevice.LAZER_ON = false;
				break;
			case 'setScanMode':
				ScannerDevice.lastSettings.SCAN_MODE = parseInt(args[0]);
				break;
			case 'setScanBeep':
				ScannerDevice.lastSettings.SCAN_BEEP_ENABLED = args[0] ? true : false;
				ScannerDevice.lastSettings.SCAN_BEEP = args[2];
				break;
			case 'setScanButtonMode':
				ScannerDevice.lastSettings.BUTTON_ENABLED = args[0] ? true : false;
				break;
			case 'setMSCardDataMode':
				ScannerDevice.lastSettings.MS_MODE = parseInt(args[0]);
				break;
			case 'setBarcodeTypeMode':
				ScannerDevice.lastSettings.BARCODE_TYPE = parseInt(args[0]);
				break;
			case 'getBatteryCapacity': break;
			case 'getBatteryVoltage': break;
			case 'isBarcodeEnabled': break;
			case 'isBarcodeSupported': break;
			case 'getMSCardDataMode': break;
			case 'getCharging': break;
			case 'setCharging':
				ScannerDevice.lastSettings.CHARGING = args[0] ? true : false;
				break;
			case 'getSyncButtonMode': break;
			case 'msProcessFinancialCard': break;
			case 'getBarcodeTypeMode': break;
			case 'barcodeEnginePowerControl':
				ScannerDevice.lastSettings.BARCODE_ENGINE_POWER = args[0] ? true : false;
				break;
			case 'barcodeType2Text': break;
			case 'getConnectionState': break;
		}
		if(ScannerDevice.allowedFunctions.indexOf(command) == -1){
			if(Debugging)
				alert('Command not found: '+command);
			return false;
		}
		cordova.exec(callback || emptyFn, errorCallback || emptyErrFn, "LineaDevice", command, args || []);
		return true;
	};
	/**
	* Enables or Disables a single barcode by it's ID.
	* @param {Int} Integer of barcode enableing/disableing (see ScannerDevice.CONSTANTS.BAR_TYPES for mappings).
	* @param {Boolean} enabled To enable or disable barcode.
	*/
	this.enableBarcode = function (barcode, enabled){
		this.sendCommand('enableBarcode', [barcode, enabled]);
	};
	/**
	* Plays a sound from the linea device. 
	* 
	* @param {Array} sounds Sound to play. This can be in the following formats: [frequency,duration,frequency,duration,...] or [[frequency,duration], [frequency,duration], ...] or [{frequency: xxx, duration: xxx}, {frequency: xxx, duration: xxx}, ...]
	* 	Note: This argument cannot exceed 5 sounds at a time (10 array elements).
	*/
	this.playSound = function (sounds){
		if(!(sounds instanceof Array)){
			return false;
		}
		var newSounds = [];
		for(var i=0;i<sounds.length;i++){
			if(sounds[i] instanceof Array){
				newSounds.push(parseInt(sounds[i][0] || 0));
				newSounds.push(parseInt(sounds[i][1] || 0));
			}else if(sounds[i] instanceof Object){
				newSounds.push(parseInt(sounds[i].frequency || 0));
				newSounds.push(parseInt(sounds[i].duration || 0));
			}else{
				newSounds.push(parseInt(sounds[i] || 0));
				newSounds.push(parseInt(sounds[i+1] || 0));
				i++;
			}
		}
		this.sendCommand('playSound', [100, newSounds]);
	};
	/**
	* Starts the scanner on device.
	*/
	this.startScan = function (){
		this.sendCommand('startScan');
	};
	/**
	* Stops the scanner on device. (if scanning)
	*/
	this.stopScan = function (){
		this.sendCommand('stopScan');
	};
	/**
	* Sets if the scanner will remain active after scanning barcode.
	* 
	* @param {Int} mode Mode to scan in. See: ScannerDevice.CONSTANTS.MODE_*
	*/
	this.setScanMode = function (mode){
		this.sendCommand('setScanMode', [mode]);
	};
	/**
	* Sets the beep for when a barcode is scanned.@argument
	*
	* @param {Boolean} enabled Weather scanner beep is enabled or not.
	* @param {Array} See ScannerLibrary#playSound for more info.
	*/
	this.setScanBeep = function (enabled, sounds){
		if(!sounds instanceof Array){
			return false;
		}
		var newSounds = [];
		for(var i=0;i<sounds.length;i++){
			if(sounds[i] instanceof Array){
				newSounds.push(parseInt(sounds[i][0] || 0));
				newSounds.push(parseInt(sounds[i][1] || 0));
			}else if(sounds[i] instanceof Object){
				newSounds.push(parseInt(sounds[i].frequency || 0));
				newSounds.push(parseInt(sounds[i].duration || 0));
			}else{
				newSounds.push(parseInt(sounds[i] || 0));
				newSounds.push(parseInt(sounds[i+1] || 0));
				i++;
			}
		}
		this.sendCommand('setScanBeep', [enabled ? true : false, 100, newSounds]);
	};
	/**
	* Sets if the button is enabled or disabled on the scanner.
	* 
	* @param {Boolean} enabled Weather to enable or disable the button.
	*/
	this.setScanButtonMode = function (enabled){
		this.sendCommand('setScanButtonMode', [enabled ? true : false]);
	};
	/**
	* Sets the mode for the card swipper.
	*
	* @param {Int} Mode to put the swipper in. See: ScannerDevice.CONSTANTS.MS_* for availale options.
	*/
	this.setMSCardDataMode = function (mode){
		this.sendCommand('setScanButtonMode', [mode]);
	};
	/**
	* Sets the mode to receive the barcodes in.
	* 
	* @param {Int} type The mode to receive the barcodes in. See: ScannerDevice.CONSTANTS.BARCODE_TYPE_*
	*/
	this.setBarcodeTypeMode = function (type){
		this.sendCommand('setBarcodeTypeMode', [type]);
	};
	/**
	* Retrieves the battery capacity in percent of Linea device.
	* @param {Function} callback Callback to execute after info is received. Example: function (params) { var percent = params[0]; }
	*/
	this.getBatteryCapacity = function (callback){
		this.sendCommand('getBatteryCapacity', [], function (params){
			callback(params[0]);
		});
	};
	/**
	* Gets the voltage of the battery.
	* @param {Function} callback Callback to execute after info is received. Example: function (params) { var voltage = params[0]; }
	*/
	this.getBatteryVoltage = function (callback){
		this.sendCommand('getBatteryVoltage', [], function (params){
			callback(params[0]);
		});
	};
	/**
	* Check weather barcode is enabled or not.
	* 
	* @param {Function} callback Callback to execute after info is received. Example: function (params) { var enabled = params[0]; }
	* @param {Int} barcode Barcode to check if enabled. See ScannerDevice.CONSTANTS.BAR_TYPES.* for available values.
	*/
	this.isBarcodeEnabled = function (callback, barcode){
		this.sendCommand('isBarcodeEnabled', [barcode], function (params){
			callback(params[0]);
		});
	};
	/**
	* Checks if barcode is supported.
	* 
	* @param {Function} callback Callback to execute after info is received. Example: function (params) { var supported = params[0]; }
	* @param {Int} barcode Barcode to check if supported. See ScannerDevice.CONSTANTS.BAR_TYPES.* for available values.
	*/
	this.isBarcodeSupported = function (callback, barcode){
		this.sendCommand('isBarcodeSupported', [barcode], function (params){
			callback(params[0]);
		});
	};
	/**
	* Checks if device is charging.
	* 
	* @param {Function} callback Callback to execute after info is received. Example: function (params) { var charging = params[0]; }
	*/
	this.getCharging = function (callback){
		this.sendCommand('getCharging', [], function (params){
			callback(Boolean(paseInt(params[0])));
		});
	};
	/**
	* Set if you wish to charge iphone/ipod/ipad.
	* 
	* @param {Boolean} enabled Weather to enable or disable charging.
	*/
	this.setCharging = function (enabled){
		this.sendCommand('setCharging', [enabled ? true : false]);
	};
	/**
	* Retreives the sync button mode (Very little documentation is given on this, so I am not even sure what it does.)
	* 
	* @param {Function} callback Callback to execute after info is received. Example: function (params) { var sync_mode = params[0]; }
	*/
	this.getSyncButtonMode = function (callback){
		this.sendCommand('getSyncButtonMode', [], function (params){
			callback(parseInt(params[0]));
		});
	};
	/**
	* Tries to process credit card info from a swipped card.
	*
	* @param {Function} callback Callback to execute after info is received. Example: function (params) {
				var accountNumber = params[0].accountNumber,
					cardholderName = params[0].cardholderName,
					discretionaryData = params[0].discretionaryData,
					exirationMonth = params[0].exirationMonth,
					exirationYear = params[0].exirationYear,
					firstName = params[0].firstName,
					lastName = params[0].lastName,
					serviceCode = params[0].serviceCode;
			}
	* @param {String} track1 Data from track1.
	* @param {String} track2 Data from track2.
	* @param {String} track3 Data from track3.
	*/
	this.msProcessFinancialCard = function (callback, track1, track2, track3){
		this.sendCommand('msProcessFinancialCard', [track1 || '', track2 || '', track3 || ''], function (params){
			callback(params[0]);
		});
	};
	/**
	* Gets the mode cards are received in. See: ScannerDevice.CONSTANTS.MS_* for more info.
	* 
	* @param {Function} callback Callback to execute after info is received. Example: function (params) { var mode = params[0]; } 
	*/
	this.getMSCardDataMode = function (callback){
		this.sendCommand('getMSCardDataMode', [], function (params){
			callback(params[0]);
		});
	};
	/**
	* Gets the mode barcodes will be scanned in. See: ScannerDevice.CONSTANTS.BARCODE_TYPE_*
	* @param {Function} callback Callback to execute after info is received. Example: function (params) { var bar_type_mode = params[0]; }
	*/
	this.getBarcodeTypeMode = function (type, callback){
		this.sendCommand('getBarcodeTypeMode', [], function (){
			callback(params[0]);
		});
	};
	/**
	* You can turn the barcode engine off with this function. When the engine is turned back on it may take up to 2 seconds for device to be
	* responsive again. (Don't play with this unless you know what you are doing.)
	* 
	* @param {Boolean} enabled Weather to enable or disable engine.
	*/
	this.barcodeEnginePowerControl = function (enabled){
		this.sendCommand('barcodeEnginePowerControl', []);
	};
	/**
	* Helper function to get the name of barcodes by their code.
	* 
	* @param {Int} type Barcode type to get name. See: ScannerDeice.CONSTANTS.BAR_TYPES.* for available barcodes.
	*/
	this.barcodeType2Text = function (type, callback){
		this.sendCommand('barcodeType2Text', [], function (params){
			callback(params[0]);
		});
	};
	/**
	* Gets the connection state of the device.
	* 
	* @param {Function} callback Callback to execute after info is received. Example: function (params) { var state = params[0]; } // See ScannerDevice.CONSTANTS.CONN_*
	*/
	this.getConnectionState = function (callback){
		this.sendCommand('getConnectionState', [], function (params){
			callback(params[0]);
		});
	}
	var i;
	for(i in mappings){
		if(ScannerDevice.allowedCallbacks.indexOf(i) != -1 && mappings[i] && mappings[i] instanceof Function){
			this[i] = mappings[i];
		}
	}
};
/**
* Deligate functions device will automatically execute when an event happens.
*/
ScannerDevice.allowedCallbacks = [
	'barcodeData',
	'magneticCardData',
	'magneticCardRawData',
	'buttonPressed',
	'buttonReleased',
	'connectionState'
];
/**
* Allowed functions that scanner accepts.
*/
ScannerDevice.allowedFunctions = [
	'enableBarcode',
	'playSound',
	'startScan',
	'stopScan',
	'setScanMode',
	'setScanBeep',
	'setScanButtonMode',
	'setMSCardDataMode',
	'setBarcodeTypeMode',
	'getBatteryCapacity',
	'getBatteryVoltage',
	'isBarcodeEnabled',
	'isBarcodeSupported',
	'getMSCardDataMode',
	'getCharging',
	'setCharging',
	'getSyncButtonMode',
	'msProcessFinancialCard',
	'getBarcodeTypeMode',
	'barcodeEnginePowerControl',
	'barcodeType2Text',
	'getConnectionState',
];
/**
* List of objects listeneing on device.
* 
* @static
* @private
*/
ScannerDevice.listeners = [];
/**
* Registers a listener to device. 
* 
* @param {ScannerDevice} obj ScannerDevice object to listen to device on.
* @static
*/
ScannerDevice.registerListener = function (obj){
	if(obj instanceof ScannerDevice){
		var i = 0,
			objs = ScannerDevice.listeners,
			len = objs.length;
		for(;i<len;i++){
			if(objs[i] === obj){
				return false; // Already assigned
			}
		}
		ScannerDevice.listeners.push(obj);
		return true;
	}else{
		return false;
	}
};
/**
* Unregisters a listener to device.
* 
* @param {ScannerDevice} obj ScannerDevice object to unregister with library.
*/
ScannerDevice.unregisterListener = function (obj){
	if(obj instanceof ScannerDevice){
		var i = 0,
			objs = ScannerDevice.listeners,
			len = objs.length;
		for(;i<len;i++){
			if(objs[i] === obj){
				objs.splice(i, 1);
				return true;
			}
		}
		return false;
	}else{
		return false;
	}
};
/**
* Master function to listen to events. Do not override this unless you wish to write your own library.
* 
* @param {Array} params Params passed from device.
* @static
* @private
*/
ScannerDevice.triggerEvent = function (params){
	var event = params[0],
		listeners = ScannerDevice.listeners,
		len = listeners.length, i, obj, args = [], emptyFn = function (){},
		emptyErrFn = function (params){
			if(window.Debugging)
				alert(params);
		};
	if(!event || ScannerDevice.allowedCallbacks.indexOf(event) == -1){
		return false;
	}
	switch(event){
		case 'connectionState':
			ScannerDevice.CONNECTION_STATE = parseInt(params[1]); // Set local Connection state variable
			break;
		case 'buttonPressed':
			if(ScannerDevice.lastSettings.BUTTON_ENABLED == ScannerDevice.CONSTANTS.BUTTON_ENABLED){
				ScannerDevice.LAZER_ON = true;
			}
			ScannerDevice.BUTTON_PRESSED = true;
			break;
		case 'buttonReleased':
			if(ScannerDevice.lastSettings.BUTTON_ENABLED == ScannerDevice.CONSTANTS.BUTTON_ENABLED){
				ScannerDevice.LAZER_ON = false;
			}
			ScannerDevice.BUTTON_PRESSED = false;
	}
	for(i=1;i<params.length;i++){
		args.push(params[i]);
	}
	for(i=0;i<len;i++){
		obj = listeners[i];
		if(obj[event] && obj[event] instanceof Function){
			try{
				obj[event].apply(obj[event], args);
			}catch(e){
				if(Debugging){
					alert(e);
				}
			}
		}
	}
	if(event == 'connectionState'){
		if(ScannerDevice.CONNECTION_STATE == ScannerDevice.CONSTANTS.CONN_CONNECTED){
			cordova.exec(emptyFn, emptyErrFn, "LineaDevice", 'configureAllSettings', [ScannerDevice.lastSettings]);
		}
	}
};
/**
* Usefull constants.
* @static
*/
ScannerDevice.CONSTANTS = {
	/* BEGIN CONN STATES */
	CONN_DISCONNECTED: 0,
	CONN_CONNECTING: 1,
	CONN_CONNECTED: 2,
	/* END CONN STATES */
	/* BEGIN SCAN MODES */
	MODE_SINGLE_SCAN: 0,
	MODE_MULTI_SCAN: 1,
	/* END SCAN MODES */
	/* BEGIN BUTTON STATES */
	BUTTON_DISABLED:0,
	BUTTON_ENABLED:1,
	/* END BUTTON STATES */
	/* BEGIN MS MODES */
	MS_PROCESSED_CARD_DATA:0,
	MS_RAW_CARD_DATA:1,
	/* BEGIN BARCODE TYPES MODE */
	BARCODE_TYPE_DEFAULT:0,
	BARCODE_TYPE_EXTENDED:1,


	/* BEGIN BARCODE TYPES */
	BAR_TYPES: {
		BAR_ALL: 0,
		BAR_UPC: 1,
		BAR_CODABAR: 2,
		BAR_CODE25_NI2OF5: 3,
		BAR_CODE25_I2OF5: 4,
		BAR_CODE39: 5,
		BAR_CODE93: 6,
		BAR_CODE128: 7,
		BAR_CODE11: 8,
		BAR_CPCBINARY: 9,
		BAR_DUN14: 10,
		BAR_EAN2: 11,
		BAR_EAN5: 12,
		BAR_EAN8: 13,
		BAR_EAN13: 14,
		BAR_EAN128: 15,
		BAR_GS1DATABAR: 16,
		BAR_ITF14: 17,
		BAR_LATENT_IMAGE: 18,
		BAR_PHARMACODE: 19,
		BAR_PLANET: 20,
		BAR_POSTNET: 21,
		BAR_INTELLIGENT_MAIL: 22,
		BAR_MSI: 23,
		BAR_POSTBAR: 24,
		BAR_RM4SCC: 25,
		BAR_TELEPEN: 26,
		BAR_PLESSEY: 27,
		BAR_PDF417: 28,
		BAR_MICROPDF417: 29,
		BAR_DATAMATRIX: 30,
		BAR_AZTEK: 31,
		BAR_QRCODE: 32,
		BAR_MAXICODE: 33,
		BAR_LAST: 34,

		BAR_EX_ALL: 0,
		BAR_EX_UPCA: 1,
		BAR_EX_CODABAR: 2,
		BAR_EX_CODE25_NI2OF5: 3,
		BAR_EX_CODE25_I2OF5: 4,
		BAR_EX_CODE39: 5,
		BAR_EX_CODE93: 6,
		BAR_EX_CODE128: 7,
		BAR_EX_CODE11: 8,
		BAR_EX_CPCBINARY: 9,
		BAR_EX_DUN14: 10,
		BAR_EX_EAN2: 11,
		BAR_EX_EAN5: 12,
		BAR_EX_EAN8: 13,
		BAR_EX_EAN13: 14,
		BAR_EX_EAN128: 15,
		BAR_EX_GS1DATABAR: 16,
		BAR_EX_ITF14: 17,
		BAR_EX_LATENT_IMAGE: 18,
		BAR_EX_PHARMACODE: 19,
		BAR_EX_PLANET: 20,
		BAR_EX_POSTNET: 21,
		BAR_EX_INTELLIGENT_MAIL: 22,
		BAR_EX_MSI_PLESSEY: 23,
		BAR_EX_POSTBAR: 24,
		BAR_EX_RM4SCC: 25,
		BAR_EX_TELEPEN: 26,
		BAR_EX_UK_PLESSEY: 27,
		BAR_EX_PDF417: 28,
		BAR_EX_MICROPDF417: 29,
		BAR_EX_DATAMATRIX: 30,
		BAR_EX_AZTEK: 31,
		BAR_EX_QRCODE: 32,
		BAR_EX_MAXICODE: 33,
		BAR_EX_RESERVED1: 34,
		BAR_EX_RESERVED2: 35,
		BAR_EX_RESERVED3: 36,
		BAR_EX_RESERVED4: 37,
		BAR_EX_RESERVED5: 38,
		BAR_EX_UPCA_2: 39,
		BAR_EX_UPCA_5: 40,
		BAR_EX_UPCE: 41,
		BAR_EX_UPCE_2: 42,
		BAR_EX_UPCE_5: 43,
		BAR_EX_EAN13_2: 44,
		BAR_EX_EAN13_5: 45,
		BAR_EX_EAN8_2: 46,
		BAR_EX_EAN8_5: 47,
		BAR_EX_CODE39_FULL: 48,
		BAR_EX_ITA_PHARMA: 49,
		BAR_EX_CODABAR_ABC: 50,
		BAR_EX_CODABAR_CX: 51,
		BAR_EX_SCODE: 52,
		BAR_EX_MATRIX_2OF5: 53,
		BAR_EX_IATA: 54,
		BAR_EX_KOREAN_POSTAL: 55,
		BAR_EX_CCA: 56,
		BAR_EX_CCB: 57,
		BAR_EX_CCC: 58,
		BAR_EX_LAST: 59
	}
};
/**
* This object contains info on the current state of device. These variables will change based on when functions
* are executed out of library. Also when device disconnects and re-connects it will auto re-assign all options
* you already set.
*/
ScannerDevice.lastSettings = {
	SCAN_BEEP_ENABLED: true,
	SCAN_BEEP: [600,150,900,200],
	SCAN_MODE: ScannerDevice.CONSTANTS.MODE_SINGLE_SCAN,
	BUTTON_ENABLED: ScannerDevice.CONSTANTS.BUTTON_ENABLED,
	MS_MODE: ScannerDevice.CONSTANTS.MS_PROCESSED_CARD_DATA,
	BARCODE_TYPE: ScannerDevice.CONSTANTS.BARCODE_TYPE_DEFAULT,
	BARCODE_ENGINE_POWER: true,
	CHARGING: false,
	barcodeStatus: {
	}
};
(function (){
	var i;
	for(i in ScannerDevice.CONSTANTS.BAR_TYPES){
		if(i != 'BAR_EX_LAST' && i != 'BAR_LAST' && i != 'BAR_EX_ALL' && i != 'BAR_ALL'){
			ScannerDevice.lastSettings.barcodeStatus[ScannerDevice.CONSTANTS.BAR_TYPES[i]] = true;
		}
	}
})();

ScannerDevice.LAZER_ON = false;
ScannerDevice.CONNECTION_STATE = ScannerDevice.CONSTANTS.CONN_DISCONNECTED;
ScannerDevice.BUTTON_PRESSED = false;
/**
* @ignore
*/
document.addEventListener('deviceready', function (){
	cordova.exec(ScannerDevice.triggerEvent, function (){}, "LineaDevice", "monitor", []);	
}, false);
