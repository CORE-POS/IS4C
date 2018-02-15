This is an ElectronJS based app that turns CORE's lane into a desktop app.
Apache, MySQL, etc are all still required. This app just takes the place
of the web browser. Why?

* Remove ability to accidentally open new tabs/windows
* Finer control over window positioning & fullscreen mode

CORE is not, at this point, aware that it's running in this specialized
browser.

### Install
* Install node
* Run `node install` in this directory

### Use
Run `./node_modules/bin/electron .` in this directory.

### Config
Copy options.json.dist to options.json and edit any of the defaults.

