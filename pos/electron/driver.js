
const path = require('path')
const { spawn } = require('child_process');

let driver = null;
let driverTimeStamp = Date.now() - 5000;
let driverStatus = "stopped";

/**
  Start pos.exe and restart it if it fails
  Tracks how long each driver process survives. If it
  dies in under a second the automatic restarts stop.
*/
function startDriver() {
    const exe = path.join(__dirname, '..', 'is4c-nf', 'scale-drivers', 'drivers', 'NewMagellan', 'pos.exe');
    const now = Date.now();
    if (driver == null && now - driverTimeStamp > 1000) {
        driverTimeStamp = now;
        driver = spawn(exe);
        driverStatus = "running";
        driver.on('close', (code) => {
            driverStatus = "stopped";
            startDriver();
        });
    } else if (driver == null) {
        driverStatus = "disabled";
    }
}

function stopDriver() {
    if (driver) {
        driver.kill();
        driverStatus = "stopped";
    }
}

function getStatus() {
    return driverStatus;
}

module.exports = { status: getStatus, start: startDriver, stop: stopDriver };

