<?php

include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

if (isset($_POST['memnos'])){
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="cardData.csv"');
    
    $include = "(";
    $args = array();
    foreach ($_POST['memnos'] as $p) {
        $include .= "?,";
        $args[] = $p;
    }
    $include[strlen($include)-1] = ")";

    $fetchQ = $sql->prepare("select c.cardno,c.personnum,c.lastname,c.firstname,
        d.end_date,m.street,'',m.city,m.state,m.zip
        from custdata as c left join meminfo as m on c.cardno=m.card_no
        left join memDates as d ON c.cardno=d.card_no
        where c.cardno in $include 
        order by c.cardno,c.personnum");
    $fetchR = $sql->execute($fetchQ, $args);
    
    echo "Memberno,First Name,Second Name,Address,City/State/Zip,Exp\n";
    $curName1 = "";
    while ($fetchW = $sql->fetchRow($fetchR)){
        echo $fetchW[0].",";
        if ($fetchW[1] == 1)
            $curName1 = $fetchW[3]." ".$fetchW[2];
        echo "\"$curName1\",";
        if ($fetchW[1] != 1)
            echo "\"$fetchW[3] $fetchW[2]\",";
        else
            echo ",";
        if (strstr($fetchW[5],"\n") === False)
            echo "\"$fetchW[5]\",";
        else{
            $pts = explode("\n",$fetchW[5]);
            echo "\"$pts[0]\",\"$pts[1]\",";
        }    
        echo "\"$fetchW[7], $fetchW[8] $fetchW[9]\",";
        echo "$fetchW[4]\n";
    }
}
else if (isset($_GET['range1'])){
    $range1 = $_GET['range1'];
    $range2 = $_GET['range2'];
    if ($range2 < $range1){
        $temp = $range1;
        $range1 = $range2;
        $range2 = $temp;
    }
    echo "<form method=post action=index.php>";
    echo "<select name=memnos[] size=15 multiple>";
    $fetchQ = $sql->prepare("select cardno from custdata where cardno between ? and ? group by cardno order by cardno");
    $fetchR = $sql->execute($fetchQ, array($range1, $range2));
    while ($fetchW = $sql->fetchRow($fetchR))
        echo "<option selected>$fetchW[0]</option>";
    echo "</select><br />";
    echo "<input type=submit value=Submit />";
}
else {
?>
<form method=get action=index.php>
Enter a range of member numbers:<br />
Start:<input type=text size=5 name=range1 /> End:<input type=text size=5 name=range2 /><br />
<input type=submit value=Submit />
</form>
<?php
}

