<?php
/*
Table: ProductOriginsMap

Columns:
    upc int
    originID int
    active tinyint

Depends on:
    origins
    products

Use:
Maps products to multiple origins. A product
has a single "current" origin via
products.current_origin_id but a 
product from multiple locations 
could also occur. Produce is the most
common use case.
*/
$CREATE['op.ProductOriginsMap'] = "
    CREATE TABLE ProductOriginsMap (
      originID INT,
      upc VARCHAR(13),
      active TINYINT DEFAULT 1,
      PRIMARY KEY  (originID, upc)
    )
";

