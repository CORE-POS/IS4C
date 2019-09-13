<?php  header("Content-Type: text/plain"); ?>
<?php 
$file = fopen('fakereceipt.txt', 'r');
$receipt = '';
$begin = 0;
while ($line = fgets($file)) {
    if ($begin == 0) {
        if (strpos($line, 'www.wholefoods.coop') !== false) {
            $begin = 1; 
            $receipt .= $line;
        }
    } else {
        $receipt .= $line;
        if (strpos($line, 'returns accepted') !== false) {
            $begin = 0;
        }
    }
}
echo $receipt;
?>
