
const electron = require('electron');
const driver = require('./driver.js');

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
                    electron.dialog.showMessageBox(bw, {
                        type: "info",
                        title: "Driver Log",
                        message: driver.log()
                    });
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

