
let win = { main: null, secondary: null };

function getWindow(name) {
    return win[name];
}

function setWindow(name, w) {
    win[name] = w;
}

module.exports = { get: getWindow, set: setWindow };

