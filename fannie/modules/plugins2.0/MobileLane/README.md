# MobileLane Plugin

This plugin is a stripped down, barebones mobile interface for ringing up items. This is a client-server setup with Office serving the UI to mobile clients. Hardware integration beyond barcode scanning may require either an additional host machine in a convenient location or additional mobile add-ons that are accessible via javascript.

## Install
1. Enable the plugin
2. Go the Updates tab of install/config and create Mobile- tables

## Supports (in theory)
* Sign in/out
* Entering items
  * Regular price and sales for everyone are the only price mechanisms
  * Quantity is implicitly 1
  * Scale items are simply rejected as invalid
  * Linea integration probably works along with any other HID-style scanner
* Canceling a transaction
* Suspending a transaction
* Tender entry
* Member entry (accessed via Tender Out)

## TODO
* Must-haves
  * Basic sales tax calculations
  * Void item entry
* Nice-to-haves
  * Resume transaction
  * Scale items w/ keyed weight
  * Open ring mechanism as last-resort entry option.
  * House coupons
  * Member-only sale prices (additional complexity isn't trivial)
* Hardware question marks
  * Some way to print or email a receipt

## Dev
Multiple devices should be able to connect to the interface at once as long as they are logged in as different cashiers. All the current transaction data co-exists in MobileTrans. There is no session (and I'd like to avoid adding one) so employee and register numbers get passed from page to page as "e" and "r".
