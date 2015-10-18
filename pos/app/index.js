var dgram = require('dgram');

var HOST = '127.0.0.1';
var PORT = '9451';

var server = dgram.createSocket('udp4');
server.send('full_udp', 0, 8, 9450, '127.0.0.1');

server.on('message', function(msg, client) {
    console.log(msg);
    window.nodePassThrough(msg.toString('ascii'));
});

server.bind(PORT, HOST);

