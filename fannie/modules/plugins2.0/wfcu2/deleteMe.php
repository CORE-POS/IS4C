
<?php

//* create seats in class if not exists (need to check and create seats in class & waiting list. Cancellations taks care of itself (moving someone to the list populates a row)

$pCheck = $dbc->prepare("
    SELECT w.count(seat),
        p.size
    FROM wfcuRegistry AS w
        LEFT JOIN products AS p on p.upc=wfcuRegistry.upc
    WHERE w.upc = ?
;");
$rCheck = $dbc->execute($pCheck, $classUPC[$key]);
while ($row = $dbc->fetch_row($rCheck)) {
    $numSeats = $row['count(seat)'];
    $classSize = $row['size'];
}
if ($numSeats != $classSize) {
    $pAddSeat = $dbc->prepare("
        INSERT INTO wfcuRegistry
            (upc, seat)
        VALUES ");
        for ($i=$numSeats; $i<$classSize; ++$i) {
            $pAddSeat .= " ( " . $plu . ", " . $i . ") ";
            if ($i<$classSize) {
                $pAddSeat .= ", ";
            }
        }   
    $rAddSeat = $dbc->execute($pAddSeat);
}