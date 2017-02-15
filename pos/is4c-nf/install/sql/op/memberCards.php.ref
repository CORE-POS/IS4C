<?php
/*
Table: memberCards

Columns:
	card_no int
	upc varchar

Depends on:
	custdata (table)

Use:
WFC has barcoded member identification cards.
card_no is the member, upc is their card.
*/
$CREATE['op.memberCards'] = "
	CREATE TABLE memberCards (
		card_no int, 
		upc varchar(13),
		PRIMARY KEY(card_no),
		INDEX(upc)
	)
";
if ($dbms == 'PDOLITE'){
	$CREATE['op.memberCards'] = str_replace('INDEX(upc)','',$CREATE['op.memberCards']);
	$CREATE['op.memberCards'] = str_replace('KEY(card_no),','KEY(card_no)',$CREATE['op.memberCards']);
}
?>
