var dgram = require('dgram');
var os = require('os');
var path = require('path');
var child_proc = require('child_process');

/**
  Run the hardware driver as its own process
  I don't know if this works especially on *nix
  with the sudo call required
var driver_exe = path.join('..', 'pos', 'is4c-nf', 'scale-drivers', 'drivers', 'NewMagellan', 'pos.exe');
if (os.platform() == 'win32') {
    var driver_cmd = driver_exe; 
    var driver_opts = [];
} else {
    var driver_cmd = 'sudo';
    var driver_opts = ['mono', driver_exe];
}
var pos = child_proc.spawn(driver_cmd, driver_opts);
pos.stdout.on(function(data) { console.log(data); });
pos.stderr.on(function(data) { console.log(data); });
*/

// tell driver to communicate strictly using UDP
var server = dgram.createSocket('udp4');
server.send('full_udp', 0, 8, 9450, '127.0.0.1');

// pass messages from the driver into the browser window
server.on('message', function(msg, client) {
    console.log(msg);
    window.nodePassThrough(msg.toString('ascii'));
});

var HOST = '127.0.0.1';
var PORT = '9451';
server.bind(PORT, HOST);

/**
  Idea: use node webkit to open a 2nd window if a second screen
  is present. In the main POS window javascript could theoretically
  call process.mainModule.exports.reloadNodeWindow() to trigger a
  refresh of the customer screen.
nw.Screen.Init();
var exports.reloadNodeWindow = function(){ 
    // null function
};
if (1 in nw.Screen.screens) { // 2nd screen present
    var screen = nw.Screen.screens[1];
    var cWin = nw.Window.open("http://localhost/IS4C/pos/is4c-nf/gui-modules/posCustDisplay.php",
        {
            x: screen['bounds']['x'],
            y: screen['bounds']['y'],
        });
    cWin.on("loaded", function() {
        cWin.enterFullScreen();
    };
    exports.reloadNodeWindow = function() {
        cWin.reload();
    }
}
*/
