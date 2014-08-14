<?php
/*
Table: prodPhysicalLocation

Columns:
    upc varchar
    store_id smallint
    section smallint
    subsection smallint
    shelf_set smallint
    shelf smallint
    location int

Depends on:
    products (table)

Use:
Storing physical location of products within a store.

Section and/or subsection represents a set of shelves.
In a lot of cases this would be one side of an aisle but
it could also be an endcap or a cooler or something against
a wall that isn't formally an aisle. A store can use either
or both. For example, section could map to aisle numbering
and subsection could indicate the left or right side of
that aisle. Another option would be to map section to a
super department (e.g., grocery) and subsection to an aisle-side
within that department.

"Shelf set" is a division within a subsection. It could be
one physical shelving unit or a freezer door.

Shelf indicates the vertical shelf location. Bottom to
top numbering is recommended.

Location is the horizontal location on the shelf.
*/
$CREATE['op.prodPhysicalLocation'] = "
    CREATE TABLE prodPhysicalLocation (
        upc varchar(13),
        store_id smallint,
        section smallint,
        subsection smallint,
        shelf_set smallint,
        shelf smallint,
        location int,   
        PRIMARY KEY (upc)
    )
"; 
?>
