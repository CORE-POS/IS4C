const electron = require('electron');
const {app, BrowserWindow, Menu, ipcMain} = require('electron');
const path = require('path')
const url = require('url');
const fs = require('fs');
const menu = require('./js/menu.js');
const driver = require('./js/driver.js');
const windowManager = require('./js/windows.js');

const optionsFile = path.join(__dirname, 'options.json');
var options = {
    url: "http://localhost/IS4C/pos/is4c-nf/",
    fullscreen: true,
    driver: true,
    dualDisplay: false,
    secondaryURL: "http://localhost/IS4C/pos/is4c-nf/gui-modules/posCustDisplay.php"
};
if (fs.existsSync(optionsFile)) {
    options = require(optionsFile);
}

const built = Menu.buildFromTemplate(menu.template);
Menu.setApplicationMenu(built);

/**
  @param name [string] name of window (main or secondary)
  @param display [Display] Electron object representing a monitor or screen
  @param url [string] URL to show in the window
  @param parent [Window] make this a child window with given parent
*/
function createWindow(name, display, url, parent=null) {
    // Create the browser window.
    let loc = centerOnDisplay(display, 800, 600);
    let win = windowManager.get(name);
    win = new BrowserWindow({width: 800, height: 600, x: loc.x, y: loc.y, parent: parent});
          
    win.loadURL(url);
    if (options.fullscreen) {
        win.maximize();
        win.setFullScreen(true);
        win.setAutoHideMenuBar(true);
    }
    windowManager.set(name, win);
                                          
    // Emitted when the window is closed.
    win.on('closed', () => {
        // Dereference the window object, usually you would store windows
        // in an array if your app supports multi windows, this is the time
        // when you should delete the corresponding element.
        windowManager.set(name, null);
    });
}

/**
  @return object
   - primary [Display] the primary display
   - secondary [Display] the first non-primary display (if one exists)
*/
function getScreens() {
    let primary = electron.screen.getPrimaryDisplay();
    let others = electron.screen.getAllDisplays().filter(d => d.id != primary.id);
    let ret = { primary: primary, secondary: false };
    if (others.length > 0) {
        ret.secondary = others[0];
    }

    return ret;
}

/**
  Calculate x,y to center a window on a display
  @param display [Display]
  @param width [int]
  @param height [int]
  @return object
   - x [int]
   - y [int]
*/
function centerOnDisplay(display, width, height) {
    let x = (display.bounds.width - width) / 2;    
    let y = (display.bounds.height - height) / 2;    

    return { x: display.bounds.x + x, y: display.bounds.y + y };
}

// This method will be called when Electron has finished
// initialization and is ready to create browser windows.
// Some APIs can only be used after this event occurs.
app.on('ready', () => {
    let screens = getScreens();
    createWindow("main", screens.primary, options.url);
    if (options.dualDisplay && screens.secondary) {
        createWindow("secondary", screens.secondary, options.secondaryURL, windowManager.get("main"));
        setTimeout(() => { windowManager.get("secondary").reload(); }, 2500);
    }
    if (options.driver) {
        driver.start();
    }
});

ipcMain.on('core-pos', (ev, args) => {
    let win = windowManager.get("secondary");
    if (win) {
        win.reload();
    }
});
          
// Quit when all windows are closed.
app.on('window-all-closed', () => {
    driver.stop();
    app.quit();
});

