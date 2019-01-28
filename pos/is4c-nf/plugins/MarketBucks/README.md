### Basic plugin sketch for Market Bucks

There are two primary components here:

* A ReceiptMessage to print the issued Market Bucks onto receipts
* A TenderModule to enforce rules when the Market Bucks are redeemed

### TODO

The receipt component needs to assess whether a given transaction
should have Market Bucks issued and calculate the amount to issue.

The tender module needs to determine how much of the current transaction
can be paid for via Market Bucks.

Both of the above could tie into some kind of configuration system, or
proof-of-concept could use hardcoded logic and refactored later if 
necessary.
