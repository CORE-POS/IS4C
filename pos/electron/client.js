
const { ipcRenderer } = require('electron');

module.exports = function ejsSecondaryRefresh() {
    ipcRenderer.send("core-pos", "refresh");
};

