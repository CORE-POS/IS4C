# MobileLane Plugin

This plugin is a stripped down, barebones mobile interface for ringing up items. This is a client-server setup with Office serving the UI to mobile clients. Hardware integration beyond barcode scanning may require either an additional host machine in a convenient location or additional mobile add-ons that are accessible via javascript.

## Install
1. Enable the plugin
2. Go the Updates tab of install/config and create Mobile- tables
3. In this directory run `npm install`
4. Run `./node_modules/.bin/webpack -p` to build the app

## Supports (in theory)
* Sign in/out
* Entering items
  * Regular price and sales for everyone are the only price mechanisms
  * Quantity is implicitly 1
  * Scale items are simply rejected as invalid
  * Linea integration probably works along with any other HID-style scanner
* Void item entry
* Canceling a transaction
* Suspending a transaction
* Tender entry
* Member entry (accessed via Tender Out)

## TODO
* Must-haves
  * Basic sales tax calculations
* Nice-to-haves
  * Resume transaction
  * Scale items w/ keyed weight
  * Open ring mechanism as last-resort entry option.
  * House coupons
  * Member-only sale prices (additional complexity isn't trivial)
* Hardware question marks
  * Some way to print or email a receipt

