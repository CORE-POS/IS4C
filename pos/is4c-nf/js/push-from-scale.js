
function pollScale(rel_prefix)
{
    var ws = new WebSocket('ws://localhost:8888', 'core');

    ws.onmessage = function(event) {
        var data = $.parseJSON(event.data);
        if (data.scale) {
            $.ajax({
                url: rel_prefix + "ajax-callbacks/ajax-scale.php"
                data: "input=" + data.scale,
                success: function(resp) {
                    $('#scaleBottom').html(resp);
                }
            });
        }

		if (data.scans && data.scans.indexOf && data.scans.indexOf(':') !== -1) {
			// data from the cc terminal
			// run directly; don't include user input
			if (typeof runParser == 'function') {
				runParser(data.scans, rel_prefix);
            }
		} else if ($('#reginput').length != 0 && data.scans) {
			// barcode scan input
			var v = $('#reginput').val();
			parseWrapper(v+data.scans);
		}
    }
}

