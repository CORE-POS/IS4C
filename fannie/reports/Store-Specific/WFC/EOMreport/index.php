<?php
//header('Content-Type: application/ms-excel');
//header('Content-Disposition: attachment; filename="EOMreport.xls"');
include('../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../classlib2.0/FannieAPI.php');
}
include($FANNIE_ROOT.'src/functions.php');

if (isset($_GET["excel"])){
header('Content-Type: application/ms-excel');
header('Content-Disposition: attachment; filename="EOMreport.xls"');
$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF']; // grab excel from cache
$_SERVER['REQUEST_URI'] = str_replace("index.php","",$_SERVER['REQUEST_URI']);
}

$today = date("m/j/y");
$uoutput = "<html>
<body bgcolor='#ffffff'> <font size=2>";
$uoutput .= '<br>Report run ' . $today; 
echo $uoutput;

$year = date('Y');
$month = date('n');
$stamp = mktime(0,0,0,$month-1,1,$year);
$dlog = "is4c_trans.dlog_90_view";
$start = date("Y-m-01",$stamp);
$end = date("Y-m-t",$stamp);
$span = "'$start 00:00:00' AND '$end 23:59:59'";
$args = array($start.' 00:00:00',$end.' 23:59:59');

$output = DataCache::getFile("monthly");
if (!$output || isset($_REQUEST['recache'])){
    if (isset($_REQUEST['recache'])) {
        $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF']; // remove recache from URI
        $_SERVER['REQUEST_URI'] = str_replace("index.php","",$_SERVER['REQUEST_URI']);
    }
    ob_start();

    $date = substr($start,0,strpos($start,":")-3);
    $date1 = substr($end,0,strpos($end,":")-3);
    echo ' for period <br>from: <b>'. $date . '</b> to: <b>' . $date1 . '</b><br>';

    $query1="select t.department,
    s.superID,
    d.salesCode,d.dept_name,
    SUM(t.total)
    FROM $dlog as t LEFT JOIN
        departments as d ON t.department = d.dept_no
        LEFT JOIN MasterSuperDepts AS s
        ON s.dept_ID = d.dept_no    
    WHERE tdate BETWEEN ? AND ?
        AND t.department < 600
        AND t.department <> 0
        AND t.trans_type <> 'T'
        AND t.trans_type IN ('I', 'D')
    GROUP BY
    s.superID,t.department,d.dept_name,d.salesCode
    order by s.superID,t.department";

    $query15 = "SELECT s.superID,sum(l.total) as total 
    FROM $dlog as l left join departments as d on l.department = d.dept_no
    LEFT JOIN MasterSuperDepts AS s ON d.dept_no=s.dept_ID
    WHERE l.tdate BETWEEN ? AND ?
    AND l.department < 600 AND l.department <> 0
    AND l.trans_type <> 'T'
    AND l.trans_type IN ('I','D')
    GROUP BY s.superID
    order by s.superID";

    $query16 = "SELECT sum(l.total) as totalSales
    FROM $dlog as l 
    WHERE l.tdate BETWEEN ? AND ?
    AND l.department < 600 AND l.department <> 0
    AND l.trans_type <> 'T'";

    $query2 = "SELECT t.TenderName,-sum(d.total) as total, COUNT(d.total)
    FROM $dlog AS d
        left join tenders as t ON d.trans_subtype=t.TenderCode
    WHERE d.tdate BETWEEN ? AND ?
    AND d.trans_status <>'X'  
    AND d.trans_type='T'
    AND d.trans_subtype <> 'MA'
    and t.TenderName <> 'MAD Coupon'
    and d.total <> 0
    GROUP BY t.TenderName";

    $query3 = "SELECT c.salesCode,s.superID,sum(l.total) as total 
    FROM $dlog as l left join MasterSuperDepts AS s ON
    l.department = s.dept_ID LEFT JOIN departments AS c
    ON l.department = c.dept_no
    WHERE l.tdate BETWEEN ? AND ?
    AND l.department < 600 AND l.department <> 0
    AND l.trans_type <> 'T'
    GROUP BY c.salesCode,s.superID
    order by c.salesCode,s.superID";

    $query4 = "SELECT sum(l.total) as totalSales
    FROM $dlog as l 
    WHERE l.tdate BETWEEN ? AND ?
    AND l.department < 600 AND l.department <> 0
    AND l.trans_type <> 'T'";

    $query5 = "SELECT d.department,t.dept_name, sum(total) as total 
    FROM $dlog as d join departments as t ON d.department = t.dept_no
    LEFT JOIN MasterSuperDepts AS m ON t.dept_no=m.dept_ID
    WHERE d.tdate BETWEEN ? AND ?
    AND (d.department >300)AND d.Department <> 0
    AND m.superID = 0
    AND d.trans_type IN('I','D') and 
    (d.register_no <> 20 or d.department = 703)
    GROUP BY d.department, t.dept_name";

    $query6 = "SELECT d.card_no,t.dept_name, sum(total) as total 
    FROM $dlog as d join departments  as t ON d.department = t.dept_no
    WHERE d.tdate BETWEEN ? AND ?
    AND (d.department =991)AND d.Department <> 0
    GROUP BY d.card_no, t.dept_name";

    $query7 = "SELECT d.card_no,t.dept_name, sum(total) as total 
    FROM $dlog as d join departments  as t ON d.department = t.dept_no
    WHERE d.tdate BETWEEN ? AND ?
    AND (d.department =990)AND d.Department <> 0 and d.register_no <> 20
    GROUP BY d.card_no, t.dept_name";

    $query13 = "SELECT   m.memDesc,SUM(d.total) AS Sales
    FROM         $dlog d INNER JOIN
                  custdata c ON d.card_no = c.CardNo INNER JOIN
                  memtype m ON c.memType = m.memtype
    WHERE d.tdate BETWEEN ? AND ?
    AND (d.department < 600) AND d.department <> 0 AND (c.personnum= 1 or c.personnum is null)
    AND d.trans_type <> 'T'
    GROUP BY m.memDesc
    ORDER BY m.memDesc";

    $query21 = "SELECT m.memdesc, COUNT(d.card_no)
    FROM is4c_trans.transarchive AS d left join memtype m on d.memType = m.memtype
    WHERE datetime BETWEEN ? AND ? AND (d.memType <> 4)
    AND register_no<>99 and emp_no<>9999 AND trans_status NOT IN ('X','Z')
    AND trans_id=1 AND upc <> 'RRR'
    GROUP BY m.memdesc";

    $query20 = "SELECT   SUM(d.total) AS Sales 
            FROM $dlog d LEFT JOIN
            custdata c ON d.card_no = c.CardNo LEFT JOIN
            memtype m ON c.memType = m.memtype
            WHERE d.tdate BETWEEN ? AND ?
            AND (d.department < 600) AND d.department <> 0 
            AND d.trans_type <> 'T'
            AND (c.personnum= 1 or c.personnum is null)";

    $query12 = "SELECT d.salesCode,sum(L.total)as returns
    FROM $dlog as L,departments as d
    WHERE d.dept_no = L.department
     AND L.tdate BETWEEN ? AND ?
    AND(trans_status = 'R' OR upc LIKE '%dp606')
    GROUP BY d.salesCode";

    $query14 = "SELECT 'Total Sales', sum(l.total) as totalSales
    FROM $dlog as l 
    WHERE l.tdate BETWEEN ? AND ?
    AND l.department < 600 AND l.department <> 0
    AND l.trans_status = 'R'";

    $query8 = "SELECT     m.memDesc, SUM(d.total) AS Discount 
    FROM         $dlog d INNER JOIN
                  custdata c ON d.card_no = c.CardNo INNER JOIN
                  memtype m ON c.memType = m.memtype
    WHERE d.tdate BETWEEN ? AND ?
    AND (d.upc = 'DISCOUNT') AND c.personnum= 1
    GROUP BY c.memType, m.memDesc, d.upc
    ORDER BY c.memType";

    $query9 = "SELECT     d.upc, SUM(d.total) AS discount
    FROM         $dlog d INNER JOIN
                  custdata c ON d.card_no = c.CardNo INNER JOIN
                  memtype m ON c.memType = m.memtype
    WHERE d.tdate BETWEEN ? AND ?
    AND (d.upc = 'DISCOUNT') AND c.personnum = 1
    GROUP BY d.upc";

    $queryMAD = "select 'MAD Coupon',sum(d.total),count(*) as discount
    from $dlog as d
    where tdate BETWEEN ? AND ?
    and trans_status <> 'X'
    and trans_subtype = 'MA'";

    $query11 = "SELECT  sum(total) as tax_collected
    FROM $dlog as d 
    WHERE d.tdate BETWEEN ? AND ?
    AND (d.upc = 'tax')
    GROUP BY d.upc";

    $query23="SELECT d.salesCode,sum(l.total) as total,card_no, 
    (sum(l.total)-(sum(l.total) * d.margin)) as cost
    FROM $dlog as l left join departments as d on l.department = d.dept_no
        INNER JOIN custdata AS c ON c.CardNo=l.card_no AND c.personNum=1
    WHERE l.tdate BETWEEN ? AND ?
    AND (l.department < 600 or l.department = 902) AND l.department <> 0
    AND l.trans_type <> 'T'
    AND card_no BETWEEN 5500 AND 5950
    AND c.memType=4
    GROUP BY d.salesCode,card_no,d.margin
    order by card_no,d.salesCode";

    $query22="SELECT d.salesCode,sum(l.total) as total,
    (sum(l.total)-(sum(l.total)* d.margin)) as cost
    FROM $dlog as l left join departments as d on l.department = d.dept_no
        INNER JOIN custdata AS c ON c.CardNo=l.card_no AND c.personNum=1
    WHERE l.tdate BETWEEN ? AND ?
    AND (l.department < 600 or l.department = 902) AND l.department <> 0
    AND l.trans_type <> 'T'
    AND card_no BETWEEN 5500 AND 5950
    AND c.memType=4
    GROUP BY d.salesCode,d.margin
    order by d.salesCode";

    $queryRRR = "
    SELECT sum(case when volSpecial is null then 0 
        when volSpecial > 100 then 1
        else volSpecial end) as qty
    from
    is4c_trans.transarchive as t
    where upc = 'RRR'
    and t.datetime BETWEEN ? AND ?
    and emp_no <> 9999 and register_no <> 99
    and trans_status <> 'X'";


    echo '<font size = 3>';
    echo '<br>';
    echo 'Sales by department';
    echo '<br>---------------------------';
    echo '<table><td width=120><u><font size=2><b>Dept No</b></u></font></td>
          <td width=120><u><font size=2><b>Department</b></u></font></td>
          <td width=120><u><font size=2><b>pCode</b></u></font></td>
        <td width=120><u><font size=2><b>Group</b></u></font></td>
          <td width=120><u><font size=2><b>Sales</b></u></font></td>
        </table>';
    select_to_table($query1,$args,0,'ffffff');
    echo '<b>Total Sales by Group</b>';
    select_to_table($query15,$args,0,'ffffff');

    echo '<font size = 2>';
    echo '<br>';
    echo 'Tenders';
    echo '<br>------------------------------';
    echo '<table><td width=120><u><font size=2><b>Type</b></u></font></td>
          <td width=120><u><font size=2><b>Amount</b></u></font></td>
          <td width=120><u><font size=2><b>Count</b></u></font></td></table>';
    select_to_table($query2,$args,0,'ffffff');
    echo '<br>';
    echo 'Sales';
    echo '<br>------------------------------';
    echo '<table><td width=120><u><font size=2><b>pCode</b></u></font></td>
          <td width=120><u><font size=2><b>Sales</b></u></font></td></table>';
    select_to_table($query3,$args,0,'ffffff');
    echo '<b>Total Sales</b>';

    select_to_table($query4,$args,0,'ffffff');

    echo '<br>';
    echo 'Other income';
    echo '<br>------------------------------';
    echo '<table><td width=120><u><font size=2><b>Dept</b></u></font></td>
          <td width=120><u><font size=2><b>Description</b></u></font></td>
          <td width=120><u><font size=2><b>Amount</b></u></font></td></table>';
    select_to_table($query5,$args,0,'ffffff');
    echo 'Discounts';
    echo '<br>------------------------------';
    echo '<table><td width=120><u><font size=2><b>Mem Type</b></u></font></td>
          <td width=120><u><font size=2><b>Discounts</b></u></font></td></table>';
    select_to_table($query8,$args,0,'ffffff');
    select_to_table($query9,$args,0,'ffffff');
    select_to_table($queryMAD,$args,0,'ffffff');
    echo '<br>';
    echo 'Member Sales';
    echo '<br>------------------------------';
    echo '<table><td width=120><u><font size=2><b>Mem Type</b></u></font></td>
          <td width=120><u><font size=2><b>Sales</b></u></font></td></table>';
    select_to_table($query13,$args,0,'ffffff');
    select_to_table($query20,$args,0,'ffffff');
    echo '<br>';
    echo 'Nabs';
    echo '<br>------------------------------';
    echo '<table><td width=120><u><font size=2><b>pCode</b></u></font></td>
          <td width=120><u><font size=2><b>Retail</b></u></font></td>
          <td>Dept Number</td><td>WholeSale</td></table>';
    select_to_table($query2,$args,0,'ffffff');
    select_to_table($query22,$args,0,'ffffff');
    echo '<br>';
    echo 'Transactions';
    echo '<br>------------------------------';
    echo '<table><td width=120><u><font size=2><b>Mem Type</b></u></font></td>
          <td width=120><u><font size=2><b>Transactions</b></u></font></td></table>';
    select_to_table($query21,$args,0,'ffffff');
    echo '<br>';
    echo '<br>';
    /**
    echo 'Sales Tax';
    echo '<br>------------------------------';
    echo '<table><td width=120><u><font size=2><b>Taxable Sales</b></u></font></td>
          <td width=120><u><font size=2><b>Total Tax</b><u></font></td>
          <td width=120><u><font size=2><b>State Taxable</b></u></font></td>
          <td width=120><u><font size=2><b>State Tax</b></u></font></td>
          <td width=120><u><font size=2><b>City Taxable</b></u></font></td>
          <td width=120><u><font size=2><b>City Tax</b></u></font></td>
          <td width=120><u><font size=2><b>Deli Taxable</b></u></font></td>
          <td width=120><u><font size=2><b>Deli Tax</b></u></font></td></table>';
    $queryCorrect = "select TaxableSales,TotalTax,StateTaxable,StateTax,CityTaxable,CityTax,DeliTaxable,DeliTax
            from is4c_trans.taxReport_corrected";
    select_to_table($queryCorrect,array(),0,'ffffff');
    */
    echo '<br>';
    echo '<b>Actual Tax Collected</b>';
    select_to_table($query11,$args,0,'ffffff');

    echo '<br>';
    echo '<b>RRR Coupons Redeemed</b>';
    select_to_table($queryRRR,$args,0,'ffffff');

    echo '</font>';
    echo "</font>
        </body>
        </html>";

    $output = ob_get_contents();
    DataCache::putFile("monthly",$output);
    ob_end_clean();
}
echo $output;

    $newTaxQ = 'SELECT description,
                    SUM(regPrice) AS ttl,
                    numflag AS taxID
                FROM is4c_trans.transarchive
                WHERE datetime BETWEEN ? AND ?
                    AND upc=\'TAXLINEITEM\'
                    AND ' . DTrans::isNotTesting() . '
                GROUP BY taxID, description';
    $sql = FannieDB::get($FANNIE_OP_DB);
    $prep = $sql->prepare($newTaxQ);
    $res = $sql->execute($prep, $args);
    $collected = array(1 => 0.00, 2=>0.00);
    while ($row = $sql->fetch_row($res)) {
        $collected[$row['taxID']] = $row['ttl'];
    }
    $state = 0.06875;
    $city = 0.01;
    $deli = 0.0225;
    echo '<table border="1" cellspacing="0" cellpadding="4">';
    echo '<tr><th>Tax Collected on Regular rate items</th>
            <th>' . sprintf('%.2f', $collected[1]) . '</th>
            <th>Regular Taxable Sales</th>
            <th>' . sprintf('%.2f', $collected[1]/($state+$city)) . '</th>
            </tr>';
    $stateTax = $collected[1] * ($state/($state+$city));
    $cityTax = $collected[1] * ($city/($state+$city));
    echo '<tr>
        <td align="right">State Tax Amount</td>
        <td>' . sprintf('%.2f', $stateTax) . '</td>
        <td align="right">State Taxable Sales</td>
        <td>' . sprintf('%.2f', $stateTax / $state) . '</td>
        </tr>';
    echo '<tr>
        <td align="right">City Tax Amount</td>
        <td>' . sprintf('%.2f', $cityTax) . '</td>
        <td align="right">City Taxable Sales</td>
        <td>' . sprintf('%.2f', $cityTax / $city) . '</td>
        </tr>';

    echo '<tr><th>Tax Collected on Deli rate items</th>
            <th>' . sprintf('%.2f', $collected[2]) . '</th>
            <th>Deli Taxable Sales</th>
            <th>' . sprintf('%.2f', $collected[2]/($state+$city+$deli)) . '</th>
            </tr>';
    $stateTax = $collected[2] * ($state/($state+$city+$deli));
    $cityTax = $collected[2] * ($city/($state+$city+$deli));
    $deliTax = $collected[2] * ($deli/($state+$city+$deli));
    echo '<tr>
        <td align="right">State Tax Amount</td>
        <td>' . sprintf('%.2f', $stateTax) . '</td>
        <td align="right">State Taxable Sales</td>
        <td>' . sprintf('%.2f', $stateTax / $state) . '</td>
        </tr>';
    echo '<tr>
        <td align="right">City Tax Amount</td>
        <td>' . sprintf('%.2f', $cityTax) . '</td>
        <td align="right">City Taxable Sales</td>
        <td>' . sprintf('%.2f', $cityTax / $city) . '</td>
        </tr>';
    echo '<tr>
        <td align="right">Deli Tax Amount</td>
        <td>' . sprintf('%.2f', $deliTax) . '</td>
        <td align="right">Deli Taxable Sales</td>
        <td>' . sprintf('%.2f', $deliTax / $deli) . '</td>
        </tr>';

    $stateTax = ($collected[1] * ($state/($state+$city))) 
                + ($collected[2] * ($state/($state+$city+$deli)));
    $cityTax = ($collected[1] * ($city/($state+$city))) 
                + ($collected[2] * ($city/($state+$city+$deli)));
    $deliTax = $collected[2] * ($deli/($state+$city+$deli));
    echo '<tr><th colspan="4">State Totals</th></tr>';
    echo '<tr>
        <td align="right">Tax Collected</td>
        <td>' . sprintf('%.2f', $stateTax) . '</td>
        <td align="right">Taxable Sales</td>
        <td>' . sprintf('%.2f', $stateTax / $state) . '</td>
        </tr>';
    echo '<tr><th colspan="4">City Totals</th></tr>';
    echo '<tr>
        <td align="right">Tax Collected</td>
        <td>' . sprintf('%.2f', $cityTax) . '</td>
        <td align="right">Taxable Sales</td>
        <td>' . sprintf('%.2f', $cityTax / $city) . '</td>
        </tr>';
    echo '<tr><th colspan="4">Deli Totals</th></tr>';
    echo '<tr>
        <td align="right">Tax Collected</td>
        <td>' . sprintf('%.2f', $deliTax) . '</td>
        <td align="right">Taxable Sales</td>
        <td>' . sprintf('%.2f', $deliTax / $deli) . '</td>
        </tr>';
    echo '</table>';

?>
