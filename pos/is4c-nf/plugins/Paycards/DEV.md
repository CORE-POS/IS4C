## Developer Overview

### Functional Flow

There are two(ish) separate paths of execution for the plugin: direct integration and semi-integrated.

In a direct integration:
* Card data is entered directly into POS by some method (keyboard, Sign & Pay, etc).
* Parser module(s) in the plugin read these inputs and store them in the session (CoreLocal)
* When the cashier initiates a transaction, the browser moves to a page in the plugin
  (typically paycardboxMsgAuth.php).
* On confirming the amount the transaction with the processor occurs
  * Build the request body. Formats are processor-specific but often XML based.
  * Save request information in PaycardTransactions
  * Send the request via cURL and download the response
  * Parse the response and record it in the same PaycardTransactions record
    as the corresponding request data.
  * Determine whether the result was approved, declined, or an error and
    proceed to the appropriate page. Approvals add a tender record to
    the transaction
* Errors just display a message
* Approvals proceed to the success page, paycardSuccess.php
  * If a signature is needed, this is where it's collected
  * If the transaction can be voided, this provides a stopping point to do so
* Confirming the approval goes back to pos2.php and requests a TO command
  (tender out) which will finish the transaction if the tendered amount
  was sufficient

In semi-integrated:
* When the cashier initiates a transaction, the browser moves to a page in the plugin
  to select a transaction/card type (PaycardEmvMenu.php). Plugin parsers support
  additional commands to skip directly to the next step for a given card type.
* The next screen, usually PaycardEmvPage.php, changes or confirms the amount.
* On confirming the amount the transaction with the processor occurs
  * Build the request body. Formats are processor-specific but often XML based.
  * Save request information in PaycardTransactions
  * Hand the request to NewMagellan via AJAX
  * NewMagellan passes the request into a 3rd party software layer
  * 3rd party collects card data from a terminal device, communicates with
    the processor, and returns the response to NewMagellan
  * NewMagellan passes the response back to the AJAX caller
  * The AJAX response handler builds a form to submit the response back
    into PHP
  * Parse the response and record it in the same PaycardTransactions record
    as the corresponding request data.
  * Determine whether the result was approved, declined, or an error and
    proceed to the appropriate page. Approvals add a tender record to
    the transaction
* Errors just display a message
* Approvals proceed to the success page, PaycardEmvSuccess.php
  * If a signature is needed, this is where it's collected
  * If the transaction can be voided, this provides a stopping point to do so
* Confirming the approval goes back to pos2.php and requests a TO command
  (tender out) which will finish the transaction if the tendered amount

The main reason for using Javascript instead of cURL is the process can take a lot
longer. The step where the 3rd party layer collects card data involves a human
interacting with the terminal. Bumping into execution time limits in PHP makes
for ugly and unpredictable errors where as Javascript will happily hold an AJAX
request open for a couple of minutes if need be.

### Files & Directories
* Top level: besides the customary Plugin class, the top level files are for
  building and parsing requests and responses in different processor formats
  except BasicCCModule which handles cURL execution.
* ajax: handlers for submitting transactions in the background
* card: extracts information from card data (e.g., issuing bank), validates
  card data, and parses different encrypted card data formats.
* gui: plugin scripts that draw cashier-facing pages
* js: javascript used by pages in gui
* lib: miscellaneous helper classes
* sql: wrapper classes to write requests and responses to PaycardTransactions
* xml: classes for parsing XML. Use BetterXmlData, obviously.

