<?php
/*
Table: PurchaseOrderItems

Columns:
    orderID int
    sku varchar
    quantity int
    unitCost money
    caseSize int
    receivedDate datetime
    receivedQty int
    receivedTotalCost money
    unitSize varchar
    brand varchar
    description varchar
    internalUPC varchar

Depends on:
    PurchaseOrder (table)
    vendorItems (table) 

Use:
Contains items to be purchased as part
of an order from a vendor.

quantity is the number of cases ordered.
unitCost corresponds to vendorItems.cost
and caseSize corresponds to vendorItems.units.
The estimated cost of puchase for the line
will be quantity * unitCost * caseSize.

The received fields are for when the items
are actually delivered. receivedQty may not
match quantity and receivedTotalCost may
not match the estimated cost. 

unitSize, brand, description, and internalUPC
are simply copied from vendorItems. If the
vendor discontinues a SKU or switches it to a
different product, this record will still 
accurately represent what was ordered.
*/
$CREATE['op.PurchaseOrderItems'] = "
    create table PurchaseOrderItems (
        orderID INT,
        sku VARCHAR(13),    
        quantity INT,
        unitCost DECIMAL(10,2),
        caseSize INT,
        receivedDate DATETIME,
        receivedQty INT,
        receivedTotalCost DECIMAL(10,2),
        unitSize VARCHAR(25),
        brand VARCHAR(50),
        description VARCHAR(50),
        internalUPC VARCHAR(13),    
        PRIMARY KEY(orderID,sku)
    )
";
?>
