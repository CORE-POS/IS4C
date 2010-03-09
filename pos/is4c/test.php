<?php
$total = 80;
$runningTotal = 200;

$a = $total * .15;
$b = ($runningTotal - $total) * .02;
$c = $a + $b;

$discount = number_format(($c / $runningTotal) * 100,2);
$newTotal = number_format($runningTotal - $c,2);


echo "<html><body><h1>";
echo "\$total = " .$total. "<br>";
echo "\$a = " .$a. "<br>";
echo "\$b = " .$b. "<br>";
echo "\$c = " .$c. "<br>";

echo "runningtotal:    " .$runningTotal. "<br>";

echo "discount: " .$discount. "%<br>";
echo "grand final ultimate total: " .$newTotal;
echo "<br><br><br>";


 
echo $_SESSION["memID"] ."<br>";
echo $_SESSION["volunteerDiscount"];
echo "</h1></body></html>";

?>