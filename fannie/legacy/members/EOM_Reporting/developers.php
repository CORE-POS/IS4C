<?php
include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

if (isset($_GET['excel'])){
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="developers.xls"');
}

$months = array(
"Jan"=>"01",
"Feb"=>"02",
"Mar"=>"03",
"Apr"=>"04",
"May"=>"05",
"Jun"=>"06",
"Jul"=>"07",
"Aug"=>"08",
"Sep"=>"09",
"Oct"=>"10",
"Nov"=>"11",
"Dec"=>"12",
);

/***************************************************************
* SQL Note:
* The last three columns in Defectors are named fiveMonthsAgo
* fourMonthsAgo, and threeMonthsAgo. That's correct for
* defectors but wrong for developers. I'm sticking more recent
* data in those columns in this case.
*
* The two types were merged into one table so that total mailing
* per period time could be limited. The column names are
* an artificat of earlier separation
***************************************************************/
$query = "select m.card_no,c.firstname+' '+c.lastname,z.start_date,
    m.zip,d.fiveMonthsAgo,d.fourMonthsAgo,
    d.threeMonthsAgo
    from meminfo as m left join
    custdata as c on m.card_no=c.cardno
    and c.personnum=1 left join
    Defectors as d on m.card_no = d.card_no
    left join memDates as z ON m.card_no=z.card_no
    where d.card_no is not null
    and d.type = 'DEVELOPER'
    and ".$sql->monthdiff($sql->now(),'selectionDate')." = 0
    order by m.card_no";

echo "<table border=1 cellpadding=0 cellspacing=0>\n";
$headers = array('Mem Num','Name','Opening Date','Zipcode');
array_push($headers,date("M y",mktime(0,0,0,date("n")-3,1,date("Y"))));
array_push($headers,date("M y",mktime(0,0,0,date("n")-2,1,date("Y"))));
array_push($headers,date("M y",mktime(0,0,0,date("n")-1,1,date("Y"))));
echo "<tr>";
foreach($headers as $h)
    echo "<th width=120><font size=2>$h</font></th>";
echo "</tr>";

$backgrounds = array('#ffffcc','#ffffff');
$b = 0;

$result = $sql->query($query);
while($row = $sql->fetch_row($result)){
    echo "<tr>";
    echo sprintf("<td bgcolor=\"%s\">%s</td>",$backgrounds[$b],$row[0]);
    echo sprintf("<td bgcolor=\"%s\">%s</td>",$backgrounds[$b],$row[1]);
    echo sprintf("<td bgcolor=\"%s\" align=center>%s</td>",$backgrounds[$b],$row[2]);
    echo sprintf("<td bgcolor=\"%s\" align=center>%s</td>",$backgrounds[$b],substr($row[3],0,5));
    echo sprintf("<td bgcolor=\"%s\" align=right>%s</td>",$backgrounds[$b],$row[4]);
    echo sprintf("<td bgcolor=\"%s\" align=right>%s</td>",$backgrounds[$b],$row[5]);
    echo sprintf("<td bgcolor=\"%s\" align=right>%s</td>",$backgrounds[$b],$row[6]);
    echo "</tr>";
    $b = ($b+1)%2;
}
echo "</table>";

