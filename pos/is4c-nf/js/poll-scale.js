var SCALE_REL_PRE = "";

function pollScale(rel_prefix)
{
    if (typeof isNodeWebKit === 'function' && !isNodeWebKit()) {
        SCALE_REL_PRE = rel_prefix;
        $.ajax({url: SCALE_REL_PRE+'ajax/AjaxPollScale.php',
            type: 'post',
            cache: false,
            dataType: 'json'
        }).done(scalePollSuccess).fail(scalePollError);
    }
}

function scalePollError(e1,e2,e3){
    errorLog.show(e1, e2, e3);
	rePoll();
}

function scalePollSuccess(data){
	if (data){
		if (data.scale){
			$('#scaleBottom').html(data.scale);	
		}

		if (data.scans && data.scans.indexOf && data.scans.indexOf(':') !== -1){
			// data from the cc terminal
			// run directly; don't include user input
			if (typeof runParser === 'function')
				runParser(encodeURI(data.scans), SCALE_REL_PRE);
		}
		else if ($('#reginput').length !== 0 && data.scans){
			// barcode scan input
			var v = $('#reginput').val();
            var url = document.URL;
            data.scans += ''; // convert to string
            // only add prefix when working on the main page
            // other pages that use scans (e.g., barcode as password)
            // may not be expecting this behavior
            // For efficiency, scale weight response include a UPC if there
            // is a pending item waiting for a weight. In this case the prefix
            // is not added. Filtering out scans while the scale is waiting
            // for a weight uses the prefix, so once the scale is ready
            // a UPC has to go through w/o prefix
            if (!data.scans && url.substring(url.length - 8) === 'pos2.php' && data.scans.substring(0, 3) !== 'OXA') {
                data.scans = '0XA' + data.scans;
            }
            // pos2 parseWrapper is adding current input
			parseWrapper(data.scans);
			//return; // why is this here? scale needs to keep polling...
		}
	}
	rePoll();
}

function rePoll(){
	var timeout = 100;
	setTimeout(function() { pollScale(SCALE_REL_PRE); }, timeout);
}

function subscribeToQueue(rel_prefix)
{
	SCALE_REL_PRE = rel_prefix;
    // Stomp.js boilerplate
    var ws = new SockJS('http://127.0.0.1:15674/stomp');
    var client = Stomp.over(ws);
    // SockJS does not support heart-beat: disable heart-beats
    client.heartbeat.outgoing = 0;
    client.heartbeat.incoming = 0;

    var message_callback = function(x) {
        dataCallback(x.body);
    };

    var connect_callback = function(x) {
        client.subscribe("/amq/queue/core-pos", message_callback);
    };

    var error_callback = function(x) {
        console.log(x);
    };
    client.connect('guest', 'guest', connect_callback, error_callback, '/');
}

function dataCallback(data)
{
    if (data.indexOf(":") !== -1) {
        // data from the cc terminal
        // run directly; don't include user input
        if (typeof runParser === 'function') {
            runParser(encodeURI(data), SCALE_REL_PRE);
        }
    } else if (/^S1\d+$/.test(data)) {
        $.ajax({url: SCALE_REL_PRE+'ajax/AjaxScale.php',
            type: 'post',
            cache: false
        }).done(function(resp) {
            $('#scaleBottom').html(resp);	
        });
    } else if (/^\d+$/.test(data)) {
        var v = $('#reginput').val();
        var url = document.URL;
        parseWrapper(v+data);
    } else {
        parseWrapper(data);
    }
}

window.nodePassThrough = function(data)
{
    dataCallback(data);
}

function isNodeWebKit()
{
    var isNode = (typeof process !== "undefined" && typeof require !== "undefined");
    if (isNode) {
        try {
            return (typeof require('nw.gui') !== "undefined");
        } catch(e) {
            return false;
        }
    }
}
