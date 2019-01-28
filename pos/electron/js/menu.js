
const electron = require('electron');
const { BrowserWindow } = require('electron');
const path = require('path')
const driver = require('./driver.js');
const windowManager = require('./windows.js');

module.exports.template = [
    {
        label: "File",
        submenu: [
            { role: "quit" }
        ]
    },
    {
        label: 'Driver',
        submenu: [
            {
                label: "Status",
                click: (mi, bw, ev) => {
                    electron.dialog.showMessageBox(bw, {
                        type: "info",
                        title: "Driver Status",
                        message: "Driver status: " + driver.status()
                    });
                }
            },
            {
                label: "Start",
                click: (mi, bw, ev) => {
                    driver.start();
                },
            },
            {
                label: "Stop",
                click: (mi, bw, ev) => {
                    driver.stop();
                }
            },
            {
                label: "Log",
                click: (mi, bw, ev) => {
                    let win = new BrowserWindow({
                        width: 600,
                        height: 500,
                        parent: windowManager.get("main"),
                        modal: true
                    });
                    win.setMenu(null);
                    const url = 'file://' + path.join(__dirname, '..', 'html', 'log.html');
                    let html = [
                        "<body>",
                        "<pre>" + driver.log() + "</pre>",
                        "</body>",
                    ].join("");
                    win.loadURL("data:text/html;charset=utf-8," + encodeURI(html));
                }
            }
        ],
    },
    {
        label: 'Edit',
        submenu: [
            {role: 'undo'},
            {role: 'redo'},
            {type: 'separator'},
            {role: 'cut'},
            {role: 'copy'},
            {role: 'paste'},
            {role: 'pasteandmatchstyle'},
            {role: 'delete'},
            {role: 'selectall'}
        ]
    },
    {
        label: 'View',
        submenu: [
            {role: 'reload'},
            {role: 'forcereload'},
            {role: 'toggledevtools'},
            {type: 'separator'},
            {role: 'resetzoom'},
            {role: 'zoomin'},
            {role: 'zoomout'},
            {type: 'separator'},
            {role: 'togglefullscreen'}
        ]
    },
    {
        role: 'window',
        submenu: [
            {role: 'minimize'},
            {role: 'close'}
        ]
    },
];

