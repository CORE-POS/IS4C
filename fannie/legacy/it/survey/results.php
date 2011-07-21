<?php
include('../../../config.php');

include($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT.'src/Credentials/surveydb.wfc.php');

$r = $sql->query("select count(*) from survey_main where card_no <> 11 or card_no is NULL");
$mems = array_pop($sql->fetch_row($r));
$r = $sql->query("select count(*) from survey_main where card_no = 11");
$nonmems = array_pop($sql->fetch_row($r));

echo "Total surveys collected: ".($mems+$nonmems)."<br />";
echo "Member surveys collected: ".$mems."<br />";
echo "Non-member surveys collected: ".$nonmems."<br />";
echo "<br />";

echo "Member benefit usage<br />";
echo "Scale 1-4. 1 - Never, 4 - Every Opportunity<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Question</td><td>Avg</td><td>Std. Dev.</td><td>Total</td></tr>";
$tags = array("Special ordered a product to get Owner case discount",
	"Special ordred a product WFC doesn't carry",
	"Shopped at businesses in the Community Cooperation Program",
	"Read the Garbanzo Gazette",
	"Used my quarterly Member Appreciation Coupon",
	"Suggested someone shop at WFC",
	"Encouraged someone to become an Owner of WFC",
	"Attended a WFC class at discounted price for Owners",
	"Attended a Co-op Event",
	"Dined in the BCO",
	"Attended the Annual Owners Meeting",
	"Voted for the Board of Directors",
	"Visited WFC's website",
	"Volunteered at a WFC event or on a WFC committee",
	"Used the monthly in-store flyer listing Member-only Specials",
	"Attended a Monthly Member Mixer",
	"Benefited from a Member Only Coupon from the booklet mailed to you");
for ($i=0; $i<17;$i++){
	echo "<tr>";
	echo "<td>".chr(97+$i).". ".$tags[$i]."</td>";
	$r = $sql->query("select avg(rating+1) from member_benefits where sub_question = '".chr(97+$i)."'");
	$avg = array_pop($sql->fetch_row($r));
	$r = $sql->query("select std(rating+1) from member_benefits where sub_question = '".chr(97+$i)."'");
	$std = array_pop($sql->fetch_row($r));
	$r = $sql->query("select count(*) from member_benefits where sub_question = '".chr(97+$i)."'");
	$total = array_pop($sql->fetch_row($r));
	echo "<td>".round($avg,2)."</td>";
	echo "<td>".round($std,2)."</td>";
	echo "<td>".$total."</td>";
	echo "</tr>";
}
echo "</table>";
echo "<br />";

echo "Zip code<br />";
$remainder = 0;
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Zip Code</td><td>Total</td></tr>";
$r = $sql->query("select zipcode,count(*) from survey_main group by zipcode order by count(*) desc");
while($w = $sql->fetch_row($r)){
	if ($w[1] < 5){
		$remainder++;
		continue;
	}
	echo "<tr>";
	echo "<td>".$w[0]."</td>";
	echo "<td>".$w[1]."</td>";
	echo "</tr>";
}
echo "</table>";
echo "There were ".$remainder." other zip codes with less than five responses each<br />";
echo "<br />";

echo "Gender<br />";
$r = $sql->query("select count(*) from survey_main where gender = 0");
$male = array_pop($sql->fetch_row($r));
$r = $sql->query("select count(*) from survey_main where gender = 1");
$female = array_pop($sql->fetch_row($r));
echo "<i>Overall</i><br />";
echo "Male: ".$male."<br />";
echo "Female: ".$female."<br />";
$r = $sql->query("select count(*) from survey_main where gender = 0 and (card_no <> 11 or card_no is NULL)");
$male = array_pop($sql->fetch_row($r));
$r = $sql->query("select count(*) from survey_main where gender = 1 and (card_no <> 11 or card_no is NULL)");
$female = array_pop($sql->fetch_row($r));
echo "<i>Member</i><br />";
echo "Male: ".$male."<br />";
echo "Female: ".$female."<br />";
$r = $sql->query("select count(*) from survey_main where gender = 0 and (card_no = 11 )");
$male = array_pop($sql->fetch_row($r));
$r = $sql->query("select count(*) from survey_main where gender = 1 and (card_no = 11 )");
$female = array_pop($sql->fetch_row($r));
echo "<i>Non-Member</i><br />";
echo "Male: ".$male."<br />";
echo "Female: ".$female."<br />";
echo "<br />";

echo "Age bracket<br />";
$num = 0;
$arr = array();
$skip = 0;
$r = $sql->query("select age_bracket,count(*) from survey_main group by age_bracket");
while($w = $sql->fetch_row($r)){
	if ($w[0] == "")
		$skip = $w[1];
	else {
		$arr[$w[0]] = $w[1];
		$num += $w[1];
	}	
}
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>18-24</td><td>".round((($arr[0]/$num)*100),2)."%</td></tr>";
echo "<tr><td>25-34</td><td>".round((($arr[1]/$num)*100),2)."%</td></tr>";
echo "<tr><td>35-44</td><td>".round((($arr[2]/$num)*100),2)."%</td></tr>";
echo "<tr><td>45-54</td><td>".round((($arr[3]/$num)*100),2)."%</td></tr>";
echo "<tr><td>55-64</td><td>".round((($arr[4]/$num)*100),2)."%</td></tr>";
echo "<tr><td>65+</td><td>".round((($arr[5]/$num)*100),2)."%</td></tr>";
echo "</table>";
echo $skip." chose not to respond<br />";
echo "<br />";

echo "<i>Member</i><br />";
$num = 0;
$arr = array();
$skip = 0;
$r = $sql->query("select age_bracket,count(*) from survey_main where card_no is null or card_no <> 11 group by age_bracket");
while($w = $sql->fetch_row($r)){
	if ($w[0] == "")
		$skip = $w[1];
	else {
		$arr[$w[0]] = $w[1];
		$num += $w[1];
	}	
}
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>18-24</td><td>".round((($arr[0]/$num)*100),2)."%</td></tr>";
echo "<tr><td>25-34</td><td>".round((($arr[1]/$num)*100),2)."%</td></tr>";
echo "<tr><td>35-44</td><td>".round((($arr[2]/$num)*100),2)."%</td></tr>";
echo "<tr><td>45-54</td><td>".round((($arr[3]/$num)*100),2)."%</td></tr>";
echo "<tr><td>55-64</td><td>".round((($arr[4]/$num)*100),2)."%</td></tr>";
echo "<tr><td>65+</td><td>".round((($arr[5]/$num)*100),2)."%</td></tr>";
echo "</table>";
echo $skip." chose not to respond<br />";
echo "<br />";

echo "<i>Non-member</i><br />";
$num = 0;
$arr = array();
$skip = 0;
$r = $sql->query("select age_bracket,count(*) from survey_main where card_no = 11 group by age_bracket");
while($w = $sql->fetch_row($r)){
	if ($w[0] == "")
		$skip = $w[1];
	else {
		$arr[$w[0]] = $w[1];
		$num += $w[1];
	}	
}
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>18-24</td><td>".round((($arr[0]/$num)*100),2)."%</td></tr>";
echo "<tr><td>25-34</td><td>".round((($arr[1]/$num)*100),2)."%</td></tr>";
echo "<tr><td>35-44</td><td>".round((($arr[2]/$num)*100),2)."%</td></tr>";
echo "<tr><td>45-54</td><td>".round((($arr[3]/$num)*100),2)."%</td></tr>";
echo "<tr><td>55-64</td><td>".round((($arr[4]/$num)*100),2)."%</td></tr>";
echo "<tr><td>65+</td><td>".round((($arr[5]/$num)*100),2)."%</td></tr>";
echo "</table>";
echo $skip." chose not to respond<br />";
echo "<br />";

echo "Number of adults<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&nbsp;</td><td>Avg.</td><td>Std. Dev.</td><td>Total</td></tr>";
$r = $sql->query("select avg(num_adults) from survey_main");
$avg = round(array_pop($sql->fetch_row($r)),2);
$r = $sql->query("select std(num_adults) from survey_main");
$std = round(array_pop($sql->fetch_row($r)),2);
$r = $sql->query("select count(*) from survey_main where num_adults is not null");
$tot = array_pop($sql->fetch_row($r));
echo "<tr><td>Overall</td><td>$avg</td><td>$std</td><td>$tot</td></tr>";
$r = $sql->query("select avg(num_adults) from survey_main where card_no is null or card_no <> 11");
$avg = round(array_pop($sql->fetch_row($r)),2);
$r = $sql->query("select std(num_adults) from survey_main where card_no is null or card_no <> 11");
$std = round(array_pop($sql->fetch_row($r)),2);
$r = $sql->query("select count(*) from survey_main where num_adults is not null and (card_no is null or card_no <> 11)");
$tot = array_pop($sql->fetch_row($r));
echo "<tr><td>Member</td><td>$avg</td><td>$std</td><td>$tot</td></tr>";
$r = $sql->query("select avg(num_adults) from survey_main where card_no = 11");
$avg = round(array_pop($sql->fetch_row($r)),2);
$r = $sql->query("select std(num_adults) from survey_main where card_no = 11");
$std = round(array_pop($sql->fetch_row($r)),2);
$r = $sql->query("select count(*) from survey_main where num_adults is not null and (card_no = 11)");
$tot = array_pop($sql->fetch_row($r));
echo "<tr><td>Non-Member</td><td>$avg</td><td>$std</td><td>$tot</td></tr>";
echo "</table>";
echo "<br />";

echo "Number of children<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&nbsp;</td><td>Avg.</td><td>Std. Dev.</td><td>Total</td></tr>";
$r = $sql->query("select avg(num_children) from survey_main");
$avg = round(array_pop($sql->fetch_row($r)),2);
$r = $sql->query("select std(num_children) from survey_main");
$std = round(array_pop($sql->fetch_row($r)),2);
$r = $sql->query("select count(*) from survey_main where num_children is not null");
$tot = array_pop($sql->fetch_row($r));
echo "<tr><td>Overall</td><td>$avg</td><td>$std</td><td>$tot</td></tr>";
$r = $sql->query("select avg(num_children) from survey_main where card_no is null or card_no <> 11");
$avg = round(array_pop($sql->fetch_row($r)),2);
$r = $sql->query("select std(num_children) from survey_main where card_no is null or card_no <> 11");
$std = round(array_pop($sql->fetch_row($r)),2);
$r = $sql->query("select count(*) from survey_main where num_children is not null and (card_no is null or card_no <> 11)");
$tot = array_pop($sql->fetch_row($r));
echo "<tr><td>Member</td><td>$avg</td><td>$std</td><td>$tot</td></tr>";
$r = $sql->query("select avg(num_children) from survey_main where card_no = 11");
$avg = round(array_pop($sql->fetch_row($r)),2);
$r = $sql->query("select std(num_children) from survey_main where card_no = 11");
$std = round(array_pop($sql->fetch_row($r)),2);
$r = $sql->query("select count(*) from survey_main where num_children is not null and (card_no = 11)");
$tot = array_pop($sql->fetch_row($r));
echo "<tr><td>Non-Member</td><td>$avg</td><td>$std</td><td>$tot</td></tr>";
echo "</table>";
echo "<br />";

echo "Income<br />";
$num = 0;
$arr = array();
$skip = 0;
$r = $sql->query("select income,count(*) from survey_main group by income");
while($w = $sql->fetch_row($r)){
	if ($w[0] == "")
		$skip = $w[1];
	else {
		$arr[$w[0]] = $w[1];
		$num += $w[1];
	}	
}
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&lt;$10k</td><td>".round((($arr[0]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$10k-$15k</td><td>".round((($arr[1]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$15k-$25k</td><td>".round((($arr[2]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$25k-35k</td><td>".round((($arr[3]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$35k-$50k</td><td>".round((($arr[4]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$50k-$75k</td><td>".round((($arr[5]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$75k-$100k</td><td>".round((($arr[6]/$num)*100),2)."%</td></tr>";
echo "<tr><td>&gt;$100k</td><td>".round((($arr[7]/$num)*100),2)."%</td></tr>";
echo "</table>";
echo $skip." chose not to respond<br />";
echo "<br />";

echo "Member<br />";
$num = 0;
$arr = array();
$skip = 0;
$r = $sql->query("select income,count(*) from survey_main where card_no is null or card_no <> 11 group by income");
while($w = $sql->fetch_row($r)){
	if ($w[0] == "")
		$skip = $w[1];
	else {
		$arr[$w[0]] = $w[1];
		$num += $w[1];
	}	
}
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&lt;$10k</td><td>".round((($arr[0]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$10k-$15k</td><td>".round((($arr[1]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$15k-$25k</td><td>".round((($arr[2]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$25k-35k</td><td>".round((($arr[3]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$35k-$50k</td><td>".round((($arr[4]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$50k-$75k</td><td>".round((($arr[5]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$75k-$100k</td><td>".round((($arr[6]/$num)*100),2)."%</td></tr>";
echo "<tr><td>&gt;$100k</td><td>".round((($arr[7]/$num)*100),2)."%</td></tr>";
echo "</table>";
echo $skip." chose not to respond<br />";
echo "<br />";

echo "Non-Member<br />";
$num = 0;
$arr = array();
$skip = 0;
$r = $sql->query("select income,count(*) from survey_main where card_no = 11 group by income");
while($w = $sql->fetch_row($r)){
	if ($w[0] == "")
		$skip = $w[1];
	else {
		$arr[$w[0]] = $w[1];
		$num += $w[1];
	}	
}
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&lt;$10k</td><td>".round((($arr[0]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$10k-$15k</td><td>".round((($arr[1]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$15k-$25k</td><td>".round((($arr[2]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$25k-35k</td><td>".round((($arr[3]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$35k-$50k</td><td>".round((($arr[4]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$50k-$75k</td><td>".round((($arr[5]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$75k-$100k</td><td>".round((($arr[6]/$num)*100),2)."%</td></tr>";
echo "<tr><td>&gt;$100k</td><td>".round((($arr[7]/$num)*100),2)."%</td></tr>";
echo "</table>";
echo $skip." chose not to respond<br />";
echo "<br />";

echo "Education<br />";
$num = 0;
$arr = array();
$skip = 0;
$r = $sql->query("select education,count(*) from survey_main group by education");
while($w = $sql->fetch_row($r)){
	if ($w[0] == "")
		$skip = $w[1];
	else {
		$arr[$w[0]] = $w[1];
		$num += $w[1];
	}	
}
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Not a HS grad</td><td>".round((($arr[0]/$num)*100),2)."%</td></tr>";
echo "<tr><td>HS Grad/GED</td><td>".round((($arr[1]/$num)*100),2)."%</td></tr>";
echo "<tr><td>Some college/tech</td><td>".round((($arr[2]/$num)*100),2)."%</td></tr>";
echo "<tr><td>College Grad</td><td>".round((($arr[3]/$num)*100),2)."%</td></tr>";
echo "<tr><td>Post-grad</td><td>".round((($arr[4]/$num)*100),2)."%</td></tr>";
echo "</table>";
echo $skip." chose not to respond<br />";
echo "<br />";

echo "Member<br />";
$num = 0;
$arr = array();
$skip = 0;
$r = $sql->query("select education,count(*) from survey_main where card_no is null or card_no <> 11 group by education");
while($w = $sql->fetch_row($r)){
	if ($w[0] == "")
		$skip = $w[1];
	else {
		$arr[$w[0]] = $w[1];
		$num += $w[1];
	}	
}
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Not a HS grad</td><td>".round((($arr[0]/$num)*100),2)."%</td></tr>";
echo "<tr><td>HS Grad/GED</td><td>".round((($arr[1]/$num)*100),2)."%</td></tr>";
echo "<tr><td>Some college/tech</td><td>".round((($arr[2]/$num)*100),2)."%</td></tr>";
echo "<tr><td>College Grad</td><td>".round((($arr[3]/$num)*100),2)."%</td></tr>";
echo "<tr><td>Post-grad</td><td>".round((($arr[4]/$num)*100),2)."%</td></tr>";
echo "</table>";
echo $skip." chose not to respond<br />";
echo "<br />";

echo "Non-Member<br />";
$num = 0;
$arr = array();
$skip = 0;
$r = $sql->query("select education,count(*) from survey_main where card_no = 11 group by education");
while($w = $sql->fetch_row($r)){
	if ($w[0] == "")
		$skip = $w[1];
	else {
		$arr[$w[0]] = $w[1];
		$num += $w[1];
	}	
}
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Not a HS grad</td><td>".round((($arr[0]/$num)*100),2)."%</td></tr>";
echo "<tr><td>HS Grad/GED</td><td>".round((($arr[1]/$num)*100),2)."%</td></tr>";
echo "<tr><td>Some college/tech</td><td>".round((($arr[2]/$num)*100),2)."%</td></tr>";
echo "<tr><td>College Grad</td><td>".round((($arr[3]/$num)*100),2)."%</td></tr>";
echo "<tr><td>Post-grad</td><td>".round((($arr[4]/$num)*100),2)."%</td></tr>";
echo "</table>";
echo $skip." chose not to respond<br />";
echo "<br />";

echo "Weekly spending<br />";
$num = 0;
$arr = array();
$skip = 0;
$r = $sql->query("select weekly_spending,count(*) from survey_main group by weekly_spending");
while($w = $sql->fetch_row($r)){
	if ($w[0] == "")
		$skip = $w[1];
	else {
		$arr[$w[0]] = $w[1];
		$num += $w[1];
	}	
}
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Up to $50</td><td>".round((($arr[0]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$51 to $75</td><td>".round((($arr[1]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$76 to $100</td><td>".round((($arr[2]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$101 to $150</td><td>".round((($arr[3]/$num)*100),2)."%</td></tr>";
echo "<tr><td>Over $150</td><td>".round((($arr[4]/$num)*100),2)."%</td></tr>";
echo "</table>";
echo $skip." chose not to respond<br />";
echo "<br />";

echo "Member<br />";
$num = 0;
$arr = array();
$skip = 0;
$r = $sql->query("select weekly_spending,count(*) from survey_main where card_no is null or card_no <> 11 group by weekly_spending");
while($w = $sql->fetch_row($r)){
	if ($w[0] == "")
		$skip = $w[1];
	else {
		$arr[$w[0]] = $w[1];
		$num += $w[1];
	}	
}
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Up to $50</td><td>".round((($arr[0]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$51 to $75</td><td>".round((($arr[1]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$76 to $100</td><td>".round((($arr[2]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$101 to $150</td><td>".round((($arr[3]/$num)*100),2)."%</td></tr>";
echo "<tr><td>Over $150</td><td>".round((($arr[4]/$num)*100),2)."%</td></tr>";
echo "</table>";
echo $skip." chose not to respond<br />";
echo "<br />";

echo "Non-Member<br />";
$num = 0;
$arr = array();
$skip = 0;
$r = $sql->query("select weekly_spending,count(*) from survey_main where card_no = 11 group by weekly_spending");
while($w = $sql->fetch_row($r)){
	if ($w[0] == "")
		$skip = $w[1];
	else {
		$arr[$w[0]] = $w[1];
		$num += $w[1];
	}	
}
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Up to $50</td><td>".round((($arr[0]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$51 to $75</td><td>".round((($arr[1]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$76 to $100</td><td>".round((($arr[2]/$num)*100),2)."%</td></tr>";
echo "<tr><td>$101 to $150</td><td>".round((($arr[3]/$num)*100),2)."%</td></tr>";
echo "<tr><td>Over $150</td><td>".round((($arr[4]/$num)*100),2)."%</td></tr>";
echo "</table>";
echo $skip." chose not to respond<br />";
echo "<br />";

echo "% Weekly spending @ WFC<br />";
$num = 0;
$arr = array();
$skip = 0;
$r = $sql->query("select weekly_spending_wfc,count(*) from survey_main group by weekly_spending");
while($w = $sql->fetch_row($r)){
	if ($w[0] == "")
		$skip = $w[1];
	else {
		$arr[$w[0]] = $w[1];
		$num += $w[1];
	}	
}
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Under 10%</td><td>".round((($arr[0]/$num)*100),2)."%</td></tr>";
echo "<tr><td>11%-25%</td><td>".round((($arr[1]/$num)*100),2)."%</td></tr>";
echo "<tr><td>26%-50%</td><td>".round((($arr[2]/$num)*100),2)."%</td></tr>";
echo "<tr><td>51%-75%</td><td>".round((($arr[3]/$num)*100),2)."%</td></tr>";
echo "<tr><td>Over 75%</td><td>".round((($arr[4]/$num)*100),2)."%</td></tr>";
echo "</table>";
echo $skip." chose not to respond<br />";
echo "<br />";

echo "Member<br />";
$num = 0;
$arr = array();
$skip = 0;
$r = $sql->query("select weekly_spending_wfc,count(*) from survey_main where card_no is null or card_no <> 11 group by weekly_spending");
while($w = $sql->fetch_row($r)){
	if ($w[0] == "")
		$skip = $w[1];
	else {
		$arr[$w[0]] = $w[1];
		$num += $w[1];
	}	
}
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Under 10%</td><td>".round((($arr[0]/$num)*100),2)."%</td></tr>";
echo "<tr><td>11%-25%</td><td>".round((($arr[1]/$num)*100),2)."%</td></tr>";
echo "<tr><td>26%-50%</td><td>".round((($arr[2]/$num)*100),2)."%</td></tr>";
echo "<tr><td>51%-75%</td><td>".round((($arr[3]/$num)*100),2)."%</td></tr>";
echo "<tr><td>Over 75%</td><td>".round((($arr[4]/$num)*100),2)."%</td></tr>";
echo "</table>";
echo $skip." chose not to respond<br />";
echo "<br />";

echo "Non-member<br />";
$num = 0;
$arr = array();
$skip = 0;
$r = $sql->query("select weekly_spending_wfc,count(*) from survey_main where card_no = 11 group by weekly_spending");
while($w = $sql->fetch_row($r)){
	if ($w[0] == "")
		$skip = $w[1];
	else {
		$arr[$w[0]] = $w[1];
		$num += $w[1];
	}	
}
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Under 10%</td><td>".round((($arr[0]/$num)*100),2)."%</td></tr>";
echo "<tr><td>11%-25%</td><td>".round((($arr[1]/$num)*100),2)."%</td></tr>";
echo "<tr><td>26%-50%</td><td>".round((($arr[2]/$num)*100),2)."%</td></tr>";
echo "<tr><td>51%-75%</td><td>".round((($arr[3]/$num)*100),2)."%</td></tr>";
echo "<tr><td>Over 75%</td><td>".round((($arr[4]/$num)*100),2)."%</td></tr>";
echo "</table>";
echo $skip." chose not to respond<br />";
echo "<br />";

echo "Rate WFC experience<br />";
echo "Scale 1-5: 1 - Poor, 5 - Excellent<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Question</td><td>Avg</td><td>Std. Dev</td><td>Total</td></tr>";
$tags = array("Store Lighting",
	"Store music",
	"Store cleanliness",
	"Variety of products",
	"Products clearly priced",
	"Easy to shop layout",
	"Uncluttered aisles",
	"Assistance available",
	"Product information available",
	"Accuracy at checkout",
	"Speed of checkout",
	"Overall shopping experience");
for ($i = 0; $i < 12; $i++){
	$r = $sql->query("select avg(rating) from shopping_experience where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL");
	$avg = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select std(rating) from shopping_experience where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL");
	$std = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select count(*) from shopping_experience where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL");
	$tot = array_pop($sql->fetch_row($r));
	echo "<tr>";
	echo "<td>".chr(97+$i).". ".$tags[$i]."</td>";
	echo "<td>$avg</td><td>$std</td><td>$tot</td>";
	echo "</tr>";
}
echo "</table>";
echo "<br />";

echo "Experience, Member<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Question</td><td>Avg</td><td>Std. Dev</td><td>Total</td></tr>";
for ($i = 0; $i < 12; $i++){
	$r = $sql->query("select avg(rating) from shopping_experience as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no is null or s.card_no <> 11)");
	$avg = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select std(rating) from shopping_experience as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no is null or s.card_no <> 11)");
	$std = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select count(*) from shopping_experience as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no is null or s.card_no <> 11)");
	$tot = array_pop($sql->fetch_row($r));
	echo "<tr>";
	echo "<td>".chr(97+$i).". ".$tags[$i]."</td>";
	echo "<td>$avg</td><td>$std</td><td>$tot</td>";
	echo "</tr>";
}
echo "</table>";
echo "<br />";

echo "Experience, Non-Member<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Question</td><td>Avg</td><td>Std. Dev</td><td>Total</td></tr>";
for ($i = 0; $i < 12; $i++){
	$r = $sql->query("select avg(rating) from shopping_experience as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no = 11)");
	$avg = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select std(rating) from shopping_experience as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no = 11)");
	$std = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select count(*) from shopping_experience as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no = 11)");
	$tot = array_pop($sql->fetch_row($r));
	echo "<tr>";
	echo "<td>".chr(97+$i).". ".$tags[$i]."</td>";
	echo "<td>$avg</td><td>$std</td><td>$tot</td>";
	echo "</tr>";
}
echo "</table>";
echo "<br />";

echo "Shopping experience \"Other\" comments, Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select comment from experience_poor as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='m' and (s.card_no is null or s.card_no <> 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]."<br /><br />";
echo "</div>";
echo "Shopping experience \"Other\" comments, Non-Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select comment from experience_poor as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='m' and (s.card_no = 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]."<br /><br />";
echo "</div>";

echo "Shopping experience \"Poor\" comments, Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select sub_question,comment from experience_poor as e left join survey_main as s on e.surveyID=s.surveyID where sub_question<>'m' and (s.card_no is null or s.card_no <> 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]." - ".$w[1]."<br /><br />";
echo "</div>";
echo "Shopping experience \"Poor\" comments, Non-Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select sub_question,comment from experience_poor as e left join survey_main as s on e.surveyID=s.surveyID where sub_question<>'m' and (s.card_no = 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]." - ".$w[1]."<br /><br />";
echo "</div>";
echo "<br />";

echo "Rate CSC experience<br />";
echo "Scale 1-5: 1 - Poor, 5 - Excellent<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Question</td><td>Avg</td><td>Std. Dev</td><td>Total</td></tr>";
$tags = array("Return a product for refund",
	"Request a rain check",
	"Purchase a gift card",
	"Purchase stamps",
	"Request product information",
	"Receive sale/event information",
	"Register for a WFC class",
	"Place a special order",
	"Pick up a special order",
	"Request member information",
	"Resolve a member benefit issue",
	"Purchase WFC logo products");
for ($i = 0; $i < 12; $i++){
	$r = $sql->query("select avg(rating) from csc where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL");
	$avg = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select std(rating) from csc where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL");
	$std = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select count(*) from csc where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL");
	$tot = array_pop($sql->fetch_row($r));
	echo "<tr>";
	echo "<td>".chr(97+$i).". ".$tags[$i]."</td>";
	echo "<td>$avg</td><td>$std</td><td>$tot</td>";
	echo "</tr>";
}
echo "</table>";
echo "<br />";

echo "CSC, Member<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Question</td><td>Avg</td><td>Std. Dev</td><td>Total</td></tr>";
for ($i = 0; $i < 12; $i++){
	$r = $sql->query("select avg(rating) from csc as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no <> 11 or s.card_no is null)");
	$avg = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select std(rating) from csc as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no <> 11 or s.card_no is null)");
	$std = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select count(*) from csc as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no <> 11 or s.card_no is null)");
	$tot = array_pop($sql->fetch_row($r));
	echo "<tr>";
	echo "<td>".chr(97+$i).". ".$tags[$i]."</td>";
	echo "<td>$avg</td><td>$std</td><td>$tot</td>";
	echo "</tr>";
}
echo "</table>";
echo "<br />";

echo "CSC, Non-Member<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Question</td><td>Avg</td><td>Std. Dev</td><td>Total</td></tr>";
for ($i = 0; $i < 12; $i++){
	$r = $sql->query("select avg(rating) from csc as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no = 11)");
	$avg = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select std(rating) from csc as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no = 11)");
	$std = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select count(*) from csc as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no = 11)");
	$tot = array_pop($sql->fetch_row($r));
	echo "<tr>";
	echo "<td>".chr(97+$i).". ".$tags[$i]."</td>";
	echo "<td>$avg</td><td>$std</td><td>$tot</td>";
	echo "</tr>";
}
echo "</table>";
echo "<br />";

echo "CSC experience \"Other\" comments, Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select comment from csc_poor as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='m' and (s.card_no is null or s.card_no <> 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]."<br /><br />";
echo "</div>";
echo "CSC experience \"Other\" comments, Non-Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select comment from csc_poor as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='m' and (s.card_no = 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]."<br /><br />";
echo "</div>";

echo "CSC experience \"Poor\" comments, Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select sub_question,comment from csc_poor as e left join survey_main as s on e.surveyID=s.surveyID where sub_question<>'m' and (s.card_no is null or s.card_no <> 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]." - ".$w[1]."<br /><br />";
echo "</div>";
echo "CSC experience \"Poor\" comments, Non-Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select sub_question,comment from csc_poor as e left join survey_main as s on e.surveyID=s.surveyID where sub_question<>'m' and (s.card_no = 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]." - ".$w[1]."<br /><br />";
echo "</div>";
echo "<br />";

echo "How important are the following items in making food choices?<br />";
echo "Scale 1-4: 1 - Not important, 4 - Very Important<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Question</td><td>Avg</td><td>Std. Dev</td><td>Total</td></tr>";
$tags = array("Certified organic",
	"Locally/regionally grown/produced",
	"Certified fair trade",
	"Preservative-free",
	"Free of genetically engineered ingredients",
	"Vegetarian",
	"Vegan",
	"Free of common food allergens",
	"Free of antibiotics & growth hormones",
	"Price",
	"Brands I trust",
	"Minimal and/or recyclable packaging",
	"Quality of products",
	"Variety of products",
	"Taste/Flavor",
	"Ready to eat");
for ($i = 0; $i < 16; $i++){
	$r = $sql->query("select avg(rating) from importance where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL");
	$avg = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select std(rating) from importance where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL");
	$std = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select count(*) from importance where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL");
	$tot = array_pop($sql->fetch_row($r));
	echo "<tr>";
	echo "<td>".chr(97+$i).". ".$tags[$i]."</td>";
	echo "<td>$avg</td><td>$std</td><td>$tot</td>";
	echo "</tr>";
}
echo "</table>";
echo "<br />";

echo "Importance, Member<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Question</td><td>Avg</td><td>Std. Dev</td><td>Total</td></tr>";
for ($i = 0; $i < 12; $i++){
	$r = $sql->query("select avg(rating) from importance as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no <> 11 or s.card_no is null)");
	$avg = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select std(rating) from importance as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no <> 11 or s.card_no is null)");
	$std = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select count(*) from importance as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no <> 11 or s.card_no is null)");
	$tot = array_pop($sql->fetch_row($r));
	echo "<tr>";
	echo "<td>".chr(97+$i).". ".$tags[$i]."</td>";
	echo "<td>$avg</td><td>$std</td><td>$tot</td>";
	echo "</tr>";
}
echo "</table>";
echo "<br />";

echo "Importance, Non-Member<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Question</td><td>Avg</td><td>Std. Dev</td><td>Total</td></tr>";
for ($i = 0; $i < 12; $i++){
	$r = $sql->query("select avg(rating) from importance as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no = 11)");
	$avg = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select std(rating) from importance as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no = 11)"); 
	$std = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select count(*) from importance as e left join survey_main as s on e.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating > 0 and rating is not NULL and (s.card_no = 11 )");
	$tot = array_pop($sql->fetch_row($r));
	echo "<tr>";
	echo "<td>".chr(97+$i).". ".$tags[$i]."</td>";
	echo "<td>$avg</td><td>$std</td><td>$tot</td>";
	echo "</tr>";
}
echo "</table>";
echo "<br />";

echo "Food choices \"Other\" comments, Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select comment from extra_other as e left join survey_main as s on e.surveyID=s.surveyID where question=16 and (s.card_no is null or s.card_no <> 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]."<br /><br />";
echo "</div>";
echo "Food choices \"Other\" comments, Non-Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select comment from extra_other as e left join survey_main as s on e.surveyID=s.surveyID where question=16 and (s.card_no = 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]."<br /><br />";
echo "</div>";
echo "<br />";

echo "Items WFC doesn't carry, Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select item from desired_items as e left join survey_main as s on e.surveyID=s.surveyID where (s.card_no is null or s.card_no <> 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]."<br /><br />";
echo "</div>";
echo "Items WFC doesn't carry, Non-Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select item from desired_items as e left join survey_main as s on e.surveyID=s.surveyID where (s.card_no = 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]."<br /><br />";
echo "</div>";
echo "<br />";

echo "Food services<br />";
$r = $sql->query("select sum(fruit_veg),sum(juice_bar),sum(online_orders),sum(delivery),sum(catering),sum(vendor),count(*) from services");
$w = $sql->fetch_row($r);
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&nbsp;</td><td>Yes</td><td>No</td></tr>";
echo "<tr><td>Cut/packaged produce</td><td>".$w[0]."</td><td>".($w[6]-$w[0])."</td></tr>";
echo "<tr><td>Frush juice bar</td><td>".$w[1]."</td><td>".($w[6]-$w[1])."</td></tr>";
echo "<tr><td>Online ordering</td><td>".$w[2]."</td><td>".($w[6]-$w[2])."</td></tr>";
echo "<tr><td>Home delivery</td><td>".$w[3]."</td><td>".($w[6]-$w[3])."</td></tr>";
echo "<tr><td>Catering</td><td>".$w[4]."</td><td>".($w[6]-$w[4])."</td></tr>";
echo "<tr><td>Food Vendor</td><td>".$w[5]."</td><td>".($w[6]-$w[5])."</td></tr>";
$r = $sql->query("select hours,count(*) from services where hours<>'' and hours <> 'NULL' group by hours");
while ($w = $sql->fetch_row($r)){
	echo "<tr>";
	echo "<td>Hours: ".$w[0]."</td>";
	echo "<td>$w[1]</td><td>&nbsp;</td></tr>";
}
echo "</table><br />";

echo "Services, Members<br />";
$r = $sql->query("select sum(fruit_veg),sum(juice_bar),sum(online_orders),sum(delivery),sum(catering),sum(vendor),count(*) from services as v left join survey_main as s on v.surveyID = s.surveyID where s.card_no <> 11 or s.card_no is null");
$w = $sql->fetch_row($r);
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&nbsp;</td><td>Yes</td><td>No</td></tr>";
echo "<tr><td>Cut/packaged produce</td><td>".$w[0]."</td><td>".($w[6]-$w[0])."</td></tr>";
echo "<tr><td>Frush juice bar</td><td>".$w[1]."</td><td>".($w[6]-$w[1])."</td></tr>";
echo "<tr><td>Online ordering</td><td>".$w[2]."</td><td>".($w[6]-$w[2])."</td></tr>";
echo "<tr><td>Home delivery</td><td>".$w[3]."</td><td>".($w[6]-$w[3])."</td></tr>";
echo "<tr><td>Catering</td><td>".$w[4]."</td><td>".($w[6]-$w[4])."</td></tr>";
echo "<tr><td>Food Vendor</td><td>".$w[5]."</td><td>".($w[6]-$w[5])."</td></tr>";
$r = $sql->query("select hours,count(*) from services as v left join survey_main as s on v.surveyID=s.surveyID where (s.card_no <> 11 or s.card_no is null) and hours<>'' and hours <> 'NULL' group by hours");
while ($w = $sql->fetch_row($r)){
	echo "<tr>";
	echo "<td>Hours: ".$w[0]."</td>";
	echo "<td>$w[1]</td><td>&nbsp;</td></tr>";
}
echo "</table><br />";

echo "Services, Non-Members<br />";
$r = $sql->query("select sum(fruit_veg),sum(juice_bar),sum(online_orders),sum(delivery),sum(catering),sum(vendor),count(*) from services as v left join survey_main as s on v.surveyID = s.surveyID where s.card_no = 11 ");
$w = $sql->fetch_row($r);
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&nbsp;</td><td>Yes</td><td>No</td></tr>";
echo "<tr><td>Cut/packaged produce</td><td>".$w[0]."</td><td>".($w[6]-$w[0])."</td></tr>";
echo "<tr><td>Frush juice bar</td><td>".$w[1]."</td><td>".($w[6]-$w[1])."</td></tr>";
echo "<tr><td>Online ordering</td><td>".$w[2]."</td><td>".($w[6]-$w[2])."</td></tr>";
echo "<tr><td>Home delivery</td><td>".$w[3]."</td><td>".($w[6]-$w[3])."</td></tr>";
echo "<tr><td>Catering</td><td>".$w[4]."</td><td>".($w[6]-$w[4])."</td></tr>";
echo "<tr><td>Food Vendor</td><td>".$w[5]."</td><td>".($w[6]-$w[5])."</td></tr>";
$r = $sql->query("select hours,count(*) from services as v left join survey_main as s on v.surveyID=s.surveyID where (s.card_no = 11 ) and hours<>'' and hours <> 'NULL' group by hours");
while ($w = $sql->fetch_row($r)){
	echo "<tr>";
	echo "<td>Hours: ".$w[0]."</td>";
	echo "<td>$w[1]</td><td>&nbsp;</td></tr>";
}
echo "</table><br />";

echo "Services Other, Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select other from services as e left join survey_main as s on e.surveyID=s.surveyID where other <> 'NULL' and other <> '' and (s.card_no is null or s.card_no <> 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]."<br /><br />";
echo "</div>";
echo "Services Other, Non-Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select other from services as e left join survey_main as s on e.surveyID=s.surveyID where other <> 'NULL' and other <> '' and (s.card_no = 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]."<br /><br />";
echo "</div>";
echo "<br />";

echo "Department ratings<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&nbsp;</td><td colspan=2>Buy at WFC</td><td colspan=5>Reason(s)</td></tr>";
echo "<tr><td>Dept.</td><td>Yes</td><td>No</td><td>Quality</td><td>Out of stock</td><td>Price</td><td>Selection</td><td>Other</td></tr>";
$r = $sql->query("select sub_question,sum(buy_at_wfc),count(*)-sum(buy_at_wfc),sum(poor_quality),sum(out_of_stock),sum(price),sum(selection),sum(no_need) from dept_ratings group by sub_question order by sub_question");
$tags = array("Baby food",
	"Bulk coffee",
	"Bulk foods",
	"Bulk herbs & spices",
	"Bulk tea",
	"Cheese",
	"Deli coffee bar",
	"Deli desserts/baked goods",
	"Deli prepared foods",
	"Fresh breads",
	"Hot bar",
	"Frozen food",
	"Refrigerated/dairy",
	"Poultry/meat - fresh",
	"Poultry/meat - frozen",
	"Packaged grocery",
	"Produce",
	"Cleaning supplies",
	"Paper products",
	"Pet food",
	"Body care",
	"Supplements",
	"Books",
	"Greeting cards",
	"Housewares",
	"Magazines");
while ($w = $sql->fetch_row($r)){
	echo "<tr>";
	echo "<td>".$w[0].". ".$tags[ord($w[0])-97]."</td>";
	for ($i = 1; $i < 8; $i++)
		echo "<td>".$w[$i]."</td>";
	echo "</tr>";
}
echo "</table><br />";

echo "Department ratings, Member<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&nbsp;</td><td colspan=2>Buy at WFC</td><td colspan=5>Reason(s)</td></tr>";
echo "<tr><td>Dept.</td><td>Yes</td><td>No</td><td>Quality</td><td>Out of stock</td><td>Price</td><td>Selection</td><td>Other</td></tr>";
$r = $sql->query("select sub_question,sum(buy_at_wfc),count(*)-sum(buy_at_wfc),sum(poor_quality),sum(out_of_stock),sum(price),sum(selection),sum(no_need) from dept_ratings as d left join survey_main as s on s.surveyID=d.surveyID where s.card_no <> 11 or s.card_no is null group by sub_question order by sub_question");
while ($w = $sql->fetch_row($r)){
	echo "<tr>";
	echo "<td>".$w[0].". ".$tags[ord($w[0])-97]."</td>";
	for ($i = 1; $i < 8; $i++)
		echo "<td>".$w[$i]."</td>";
	echo "</tr>";
}
echo "</table><br />";

echo "Department ratings, Non-Member<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&nbsp;</td><td colspan=2>Buy at WFC</td><td colspan=5>Reason(s)</td></tr>";
echo "<tr><td>Dept.</td><td>Yes</td><td>No</td><td>Quality</td><td>Out of stock</td><td>Price</td><td>Selection</td><td>Other</td></tr>";
$r = $sql->query("select sub_question,sum(buy_at_wfc),count(*)-sum(buy_at_wfc),sum(poor_quality),sum(out_of_stock),sum(price),sum(selection),sum(no_need) from dept_ratings as d left join survey_main as s on s.surveyID=d.surveyID where s.card_no = 11 group by sub_question order by sub_question");
while ($w = $sql->fetch_row($r)){
	echo "<tr>";
	echo "<td>".$w[0].". ".$tags[ord($w[0])-97]."</td>";
	for ($i = 1; $i < 8; $i++)
		echo "<td>".$w[$i]."</td>";
	echo "</tr>";
}
echo "</table><br />";

echo "Rate WFC staff<br />";
echo "Scale 1-5: 1 - Strongly disagree, 5 - Strongly agree<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Question</td><td>Avg</td><td>Std. Dev</td><td>Total</td></tr>";
$tags = array("Identifiable (name tag visible",
	"Available",
	"Approachable",
	"Knowledgeable",
	"Converned with doing a good job",
	"Recognizes me",
	"Responsive to my concerns",
	"Smiles",
	"Asks if I need assistance");
for ($i = 0; $i < 9; $i++){
	$r = $sql->query("select avg(rating+1) from staff_rating where sub_question='".chr(97+$i)."' and rating is not NULL");
	$avg = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select std(rating+1) from staff_rating where sub_question='".chr(97+$i)."' and rating is not NULL");
	$std = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select count(*) from staff_rating where sub_question='".chr(97+$i)."' and rating is not NULL");
	$tot = array_pop($sql->fetch_row($r));
	echo "<tr>";
	echo "<td>".chr(97+$i).". ".$tags[$i]."</td>";
	echo "<td>$avg</td><td>$std</td><td>$tot</td>";
	echo "</tr>";
}
echo "</table>";
echo "<br />";

echo "Staff rating, Member<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Question</td><td>Avg</td><td>Std. Dev</td><td>Total</td></tr>";
for ($i = 0; $i < 9; $i++){
	$r = $sql->query("select avg(rating+1) from staff_rating as r left join survey_main as s on r.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating is not NULL and (s.card_no is null or s.card_no <> 11)");
	$avg = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select std(rating+1) from staff_rating as r left join survey_main as s on r.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating is not NULL and (s.card_no is null or s.card_no <> 11)");
	$std = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select count(*) from staff_rating as r left join survey_main as s on s.surveyID=r.surveyID where sub_question='".chr(97+$i)."' and rating is not NULL and (s.card_no is not null or s.card_no <> 11)");
	$tot = array_pop($sql->fetch_row($r));
	echo "<tr>";
	echo "<td>".chr(97+$i).". ".$tags[$i]."</td>";
	echo "<td>$avg</td><td>$std</td><td>$tot</td>";
	echo "</tr>";
}
echo "</table>";
echo "<br />";

echo "Staff rating, Non-Member<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>Question</td><td>Avg</td><td>Std. Dev</td><td>Total</td></tr>";
for ($i = 0; $i < 9; $i++){
	$r = $sql->query("select avg(rating+1) from staff_rating as r left join survey_main as s on r.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating is not NULL and (s.card_no = 11)");
	$avg = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select std(rating+1) from staff_rating as r left join survey_main as s on r.surveyID=s.surveyID where sub_question='".chr(97+$i)."' and rating is not NULL and (s.card_no = 11)");
	$std = round(array_pop($sql->fetch_row($r)),2);
	$r = $sql->query("select count(*) from staff_rating as r left join survey_main as s on s.surveyID=r.surveyID where sub_question='".chr(97+$i)."' and rating is not NULL and (s.card_no = 11)");
	$tot = array_pop($sql->fetch_row($r));
	echo "<tr>";
	echo "<td>".chr(97+$i).". ".$tags[$i]."</td>";
	echo "<td>$avg</td><td>$std</td><td>$tot</td>";
	echo "</tr>";
}
echo "</table>";
echo "<br />";

echo "Reaspons for choosing WFC<br />";
$r = $sql->query("select sum(location),sum(parking),sum(atmosphere),sum(staff),sum(service),sum(cleanliness),sum(public_trans),sum(organic_local),sum(allergy_dietary),sum(product_info),sum(prices),sum(coop_model),sum(owner),sum(local_support),sum(community),sum(environment),count(*) from features");
$w = $sql->fetch_row($r);
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&nbsp;</td><td>Yes</td><td>No</td></tr>";
echo "<tr><td>Convenient Location</td><td>".$w[0]."</td><td>".($w[16]-$w[0])."</td></tr>";
echo "<tr><td>Accessible Parking</td><td>".$w[1]."</td><td>".($w[16]-$w[1])."</td></tr>";
echo "<tr><td>Atmosphere</td><td>".$w[2]."</td><td>".($w[16]-$w[2])."</td></tr>";
echo "<tr><td>Knowledgeable Staff</td><td>".$w[3]."</td><td>".($w[16]-$w[3])."</td></tr>";
echo "<tr><td>Friendly Service</td><td>".$w[4]."</td><td>".($w[16]-$w[4])."</td></tr>";
echo "<tr><td>Cleanliness of store</td><td>".$w[5]."</td><td>".($w[16]-$w[5])."</td></tr>";
echo "<tr><td>Proximity to public transportation</td><td>".$w[6]."</td><td>".($w[16]-$w[6])."</td></tr>";
echo "<tr><td>Organic and local products</td><td>".$w[7]."</td><td>".($w[16]-$w[7])."</td></tr>";
echo "<tr><td>Allergy or dietary choices</td><td>".$w[8]."</td><td>".($w[16]-$w[8])."</td></tr>";
echo "<tr><td>Product info</td><td>".$w[9]."</td><td>".($w[16]-$w[9])."</td></tr>";
echo "<tr><td>Prices</td><td>".$w[10]."</td><td>".($w[16]-$w[10])."</td></tr>";
echo "<tr><td>Co-op business model</td><td>".$w[11]."</td><td>".($w[16]-$w[11])."</td></tr>";
echo "<tr><td>I am an owner</td><td>".$w[12]."</td><td>".($w[16]-$w[12])."</td></tr>";
echo "<tr><td>Support local growers</td><td>".$w[13]."</td><td>".($w[16]-$w[13])."</td></tr>";
echo "<tr><td>Support community</td><td>".$w[14]."</td><td>".($w[16]-$w[14])."</td></tr>";
echo "<tr><td>Support environment</td><td>".$w[15]."</td><td>".($w[16]-$w[15])."</td></tr>";
echo "</table><br />";

echo "Reaspons for choosing WFC, Member<br />";
$r = $sql->query("select sum(location),sum(parking),sum(atmosphere),sum(staff),sum(service),sum(cleanliness),sum(public_trans),sum(organic_local),sum(allergy_dietary),sum(product_info),sum(prices),sum(coop_model),sum(owner),sum(local_support),sum(community),sum(environment),count(*) from features as f left join survey_main as s on f.surveyID = s.surveyID where s.card_no is null or s.card_no <> 11");
$w = $sql->fetch_row($r);
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&nbsp;</td><td>Yes</td><td>No</td></tr>";
echo "<tr><td>Convenient Location</td><td>".$w[0]."</td><td>".($w[16]-$w[0])."</td></tr>";
echo "<tr><td>Accessible Parking</td><td>".$w[1]."</td><td>".($w[16]-$w[1])."</td></tr>";
echo "<tr><td>Atmosphere</td><td>".$w[2]."</td><td>".($w[16]-$w[2])."</td></tr>";
echo "<tr><td>Knowledgeable Staff</td><td>".$w[3]."</td><td>".($w[16]-$w[3])."</td></tr>";
echo "<tr><td>Friendly Service</td><td>".$w[4]."</td><td>".($w[16]-$w[4])."</td></tr>";
echo "<tr><td>Cleanliness of store</td><td>".$w[5]."</td><td>".($w[16]-$w[5])."</td></tr>";
echo "<tr><td>Proximity to public transportation</td><td>".$w[6]."</td><td>".($w[16]-$w[6])."</td></tr>";
echo "<tr><td>Organic and local products</td><td>".$w[7]."</td><td>".($w[16]-$w[7])."</td></tr>";
echo "<tr><td>Allergy or dietary choices</td><td>".$w[8]."</td><td>".($w[16]-$w[8])."</td></tr>";
echo "<tr><td>Product info</td><td>".$w[9]."</td><td>".($w[16]-$w[9])."</td></tr>";
echo "<tr><td>Prices</td><td>".$w[10]."</td><td>".($w[16]-$w[10])."</td></tr>";
echo "<tr><td>Co-op business model</td><td>".$w[11]."</td><td>".($w[16]-$w[11])."</td></tr>";
echo "<tr><td>I am an owner</td><td>".$w[12]."</td><td>".($w[16]-$w[12])."</td></tr>";
echo "<tr><td>Support local growers</td><td>".$w[13]."</td><td>".($w[16]-$w[13])."</td></tr>";
echo "<tr><td>Support community</td><td>".$w[14]."</td><td>".($w[16]-$w[14])."</td></tr>";
echo "<tr><td>Support environment</td><td>".$w[15]."</td><td>".($w[16]-$w[15])."</td></tr>";
echo "</table><br />";

echo "Reaspons for choosing WFC, Non-Member<br />";
$r = $sql->query("select sum(location),sum(parking),sum(atmosphere),sum(staff),sum(service),sum(cleanliness),sum(public_trans),sum(organic_local),sum(allergy_dietary),sum(product_info),sum(prices),sum(coop_model),sum(owner),sum(local_support),sum(community),sum(environment),count(*) from features as f left join survey_main as s on f.surveyID = s.surveyID where s.card_no = 11");
$w = $sql->fetch_row($r);
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&nbsp;</td><td>Yes</td><td>No</td></tr>";
echo "<tr><td>Convenient Location</td><td>".$w[0]."</td><td>".($w[16]-$w[0])."</td></tr>";
echo "<tr><td>Accessible Parking</td><td>".$w[1]."</td><td>".($w[16]-$w[1])."</td></tr>";
echo "<tr><td>Atmosphere</td><td>".$w[2]."</td><td>".($w[16]-$w[2])."</td></tr>";
echo "<tr><td>Knowledgeable Staff</td><td>".$w[3]."</td><td>".($w[16]-$w[3])."</td></tr>";
echo "<tr><td>Friendly Service</td><td>".$w[4]."</td><td>".($w[16]-$w[4])."</td></tr>";
echo "<tr><td>Cleanliness of store</td><td>".$w[5]."</td><td>".($w[16]-$w[5])."</td></tr>";
echo "<tr><td>Proximity to public transportation</td><td>".$w[6]."</td><td>".($w[16]-$w[6])."</td></tr>";
echo "<tr><td>Organic and local products</td><td>".$w[7]."</td><td>".($w[16]-$w[7])."</td></tr>";
echo "<tr><td>Allergy or dietary choices</td><td>".$w[8]."</td><td>".($w[16]-$w[8])."</td></tr>";
echo "<tr><td>Product info</td><td>".$w[9]."</td><td>".($w[16]-$w[9])."</td></tr>";
echo "<tr><td>Prices</td><td>".$w[10]."</td><td>".($w[16]-$w[10])."</td></tr>";
echo "<tr><td>Co-op business model</td><td>".$w[11]."</td><td>".($w[16]-$w[11])."</td></tr>";
echo "<tr><td>I am an owner</td><td>".$w[12]."</td><td>".($w[16]-$w[12])."</td></tr>";
echo "<tr><td>Support local growers</td><td>".$w[13]."</td><td>".($w[16]-$w[13])."</td></tr>";
echo "<tr><td>Support community</td><td>".$w[14]."</td><td>".($w[16]-$w[14])."</td></tr>";
echo "<tr><td>Support environment</td><td>".$w[15]."</td><td>".($w[16]-$w[15])."</td></tr>";
echo "</table><br />";

echo "Other grocery store";
$stores = array("CUB","Super One - Lakeside","Super One - Kenwood","Super One - Mall Area","Super One - Plaza Shopping Center","Super One - West End","Super One - Superior","Super One - Two Harbors","Super Valu - Homecroft","Mount Royal Fine Foods","Piggly Wiggly/Woodland","Target/Duluth","Target/Superior","Wal-Mart Super Center/Superior","Sam's Club/Duluth","Convenience Store");
$r = $sql->query("select store,count(*) from other_stores group by store order by count(*) desc");
echo "<table cellspacing=0 cellpadding=4 border=1>";
while ($w = $sql->fetch_row($r)){
	echo "<tr>";
	echo "<td>".$stores[$w[0]]."</td>";
	echo "<td>".$w[1]."</td>";
	echo "</tr>";
}
echo "</table><br />";

echo "Other grocery store, Member";
$stores = array("CUB","Super One - Lakeside","Super One - Kenwood","Super One - Mall Area","Super One - Plaza Shopping Center","Super One - West End","Super One - Superior","Super One - Two Harbors","Super Valu - Homecroft","Mount Royal Fine Foods","Piggly Wiggly/Woodland","Target/Duluth","Target/Superior","Wal-Mart Super Center/Superior","Sam's Club/Duluth","Convenience Store");
$r = $sql->query("select store,count(*) from other_stores as o left join survey_main as s on o.surveyID=s.surveyID where s.card_no is null or s.card_no <> 11 group by store order by count(*) desc");
echo "<table cellspacing=0 cellpadding=4 border=1>";
while ($w = $sql->fetch_row($r)){
	echo "<tr>";
	echo "<td>".$stores[$w[0]]."</td>";
	echo "<td>".$w[1]."</td>";
	echo "</tr>";
}
echo "</table><br />";

echo "Other grocery store, Non-Member";
$stores = array("CUB","Super One - Lakeside","Super One - Kenwood","Super One - Mall Area","Super One - Plaza Shopping Center","Super One - West End","Super One - Superior","Super One - Two Harbors","Super Valu - Homecroft","Mount Royal Fine Foods","Piggly Wiggly/Woodland","Target/Duluth","Target/Superior","Wal-Mart Super Center/Superior","Sam's Club/Duluth","Convenience Store");
$r = $sql->query("select store,count(*) from other_stores as o left join survey_main as s on o.surveyID=s.surveyID where s.card_no = 11 group by store order by count(*) desc");
echo "<table cellspacing=0 cellpadding=4 border=1>";
while ($w = $sql->fetch_row($r)){
	echo "<tr>";
	echo "<td>".$stores[$w[0]]."</td>";
	echo "<td>".$w[1]."</td>";
	echo "</tr>";
}
echo "</table><br />";

echo "Other store write-in, Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select comment from extra_other as e left join survey_main as s on e.surveyID=s.surveyID where question=22 and (s.card_no is null or s.card_no <> 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]."<br /><br />";
echo "</div>";
echo "Other store write-in, Non-Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select comment from extra_other as e left join survey_main as s on e.surveyID=s.surveyID where question=22 and (s.card_no = 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]."<br /><br />";
echo "</div>";
echo "<br />";

echo "Advertising<br />";
$r = $sql->query("select sum(signage),sum(flyers),sum(brochures),sum(website),sum(newsletter),sum(billboards),sum(public_radio),sum(radio),sum(tv),sum(dnt),sum(event),sum(meeting),sum(booth),sum(employee),sum(friend),count(*) from advertising");
$w = $sql->fetch_row($r);
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&nbsp;</td><td>Yes</td><td>No</td></tr>";
echo "<tr><td>Signs in the store</td><td>".$w[0]."</td><td>".($w[15]-$w[0])."</td></tr>";
echo "<tr><td>Flyers in the store</td><td>".$w[1]."</td><td>".($w[15]-$w[1])."</td></tr>";
echo "<tr><td>Brochures in the store</td><td>".$w[2]."</td><td>".($w[15]-$w[2])."</td></tr>";
echo "<tr><td>WFC's web site</td><td>".$w[3]."</td><td>".($w[15]-$w[3])."</td></tr>";
echo "<tr><td>WFC's newsletter</td><td>".$w[4]."</td><td>".($w[15]-$w[4])."</td></tr>";
echo "<tr><td>Billboards</td><td>".$w[5]."</td><td>".($w[15]-$w[5])."</td></tr>";
echo "<tr><td>Public Radio</td><td>".$w[6]."</td><td>".($w[15]-$w[6])."</td></tr>";
echo "<tr><td>Commercial Radio</td><td>".$w[7]."</td><td>".($w[15]-$w[7])."</td></tr>";
echo "<tr><td>Television</td><td>".$w[8]."</td><td>".($w[15]-$w[8])."</td></tr>";
echo "<tr><td>Duluth News-Tribune</td><td>".$w[9]."</td><td>".($w[15]-$w[9])."</td></tr>";
echo "<tr><td>WFC sponsored event</td><td>".$w[10]."</td><td>".($w[15]-$w[10])."</td></tr>";
echo "<tr><td>Halloween,Annual Owners Meeting, in-store classes</td><td>".$w[11]."</td><td>".($w[15]-$w[11])."</td></tr>";
echo "<tr><td>WFC booth</td><td>".$w[12]."</td><td>".($w[15]-$w[12])."</td></tr>";
echo "<tr><td>A WFC employee</td><td>".$w[13]."</td><td>".($w[15]-$w[13])."</td></tr>";
echo "<tr><td>A friend</td><td>".$w[14]."</td><td>".($w[15]-$w[14])."</td></tr>";
echo "</table><br />";

echo "Advertising, Member<br />";
$r = $sql->query("select sum(signage),sum(flyers),sum(brochures),sum(website),sum(newsletter),sum(billboards),sum(public_radio),sum(radio),sum(tv),sum(dnt),sum(event),sum(meeting),sum(booth),sum(employee),sum(friend),count(*) from advertising as a left join survey_main as s on a.surveyID=s.surveyID where s.card_no is null or s.card_no <> 11");
$w = $sql->fetch_row($r);
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&nbsp;</td><td>Yes</td><td>No</td></tr>";
echo "<tr><td>Signs in the store</td><td>".$w[0]."</td><td>".($w[15]-$w[0])."</td></tr>";
echo "<tr><td>Flyers in the store</td><td>".$w[1]."</td><td>".($w[15]-$w[1])."</td></tr>";
echo "<tr><td>Brochures in the store</td><td>".$w[2]."</td><td>".($w[15]-$w[2])."</td></tr>";
echo "<tr><td>WFC's web site</td><td>".$w[3]."</td><td>".($w[15]-$w[3])."</td></tr>";
echo "<tr><td>WFC's newsletter</td><td>".$w[4]."</td><td>".($w[15]-$w[4])."</td></tr>";
echo "<tr><td>Billboards</td><td>".$w[5]."</td><td>".($w[15]-$w[5])."</td></tr>";
echo "<tr><td>Public Radio</td><td>".$w[6]."</td><td>".($w[15]-$w[6])."</td></tr>";
echo "<tr><td>Commercial Radio</td><td>".$w[7]."</td><td>".($w[15]-$w[7])."</td></tr>";
echo "<tr><td>Television</td><td>".$w[8]."</td><td>".($w[15]-$w[8])."</td></tr>";
echo "<tr><td>Duluth News-Tribune</td><td>".$w[9]."</td><td>".($w[15]-$w[9])."</td></tr>";
echo "<tr><td>WFC sponsored event</td><td>".$w[10]."</td><td>".($w[15]-$w[10])."</td></tr>";
echo "<tr><td>Halloween,Annual Owners Meeting, in-store classes</td><td>".$w[11]."</td><td>".($w[15]-$w[11])."</td></tr>";
echo "<tr><td>WFC booth</td><td>".$w[12]."</td><td>".($w[15]-$w[12])."</td></tr>";
echo "<tr><td>A WFC employee</td><td>".$w[13]."</td><td>".($w[15]-$w[13])."</td></tr>";
echo "<tr><td>A friend</td><td>".$w[14]."</td><td>".($w[15]-$w[14])."</td></tr>";
echo "</table><br />";

echo "Advertising, Non-Member<br />";
$r = $sql->query("select sum(signage),sum(flyers),sum(brochures),sum(website),sum(newsletter),sum(billboards),sum(public_radio),sum(radio),sum(tv),sum(dnt),sum(event),sum(meeting),sum(booth),sum(employee),sum(friend),count(*) from advertising as a left join survey_main as s on a.surveyID=s.surveyID where s.card_no = 11");
$w = $sql->fetch_row($r);
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&nbsp;</td><td>Yes</td><td>No</td></tr>";
echo "<tr><td>Signs in the store</td><td>".$w[0]."</td><td>".($w[15]-$w[0])."</td></tr>";
echo "<tr><td>Flyers in the store</td><td>".$w[1]."</td><td>".($w[15]-$w[1])."</td></tr>";
echo "<tr><td>Brochures in the store</td><td>".$w[2]."</td><td>".($w[15]-$w[2])."</td></tr>";
echo "<tr><td>WFC's web site</td><td>".$w[3]."</td><td>".($w[15]-$w[3])."</td></tr>";
echo "<tr><td>WFC's newsletter</td><td>".$w[4]."</td><td>".($w[15]-$w[4])."</td></tr>";
echo "<tr><td>Billboards</td><td>".$w[5]."</td><td>".($w[15]-$w[5])."</td></tr>";
echo "<tr><td>Public Radio</td><td>".$w[6]."</td><td>".($w[15]-$w[6])."</td></tr>";
echo "<tr><td>Commercial Radio</td><td>".$w[7]."</td><td>".($w[15]-$w[7])."</td></tr>";
echo "<tr><td>Television</td><td>".$w[8]."</td><td>".($w[15]-$w[8])."</td></tr>";
echo "<tr><td>Duluth News-Tribune</td><td>".$w[9]."</td><td>".($w[15]-$w[9])."</td></tr>";
echo "<tr><td>WFC sponsored event</td><td>".$w[10]."</td><td>".($w[15]-$w[10])."</td></tr>";
echo "<tr><td>Halloween,Annual Owners Meeting, in-store classes</td><td>".$w[11]."</td><td>".($w[15]-$w[11])."</td></tr>";
echo "<tr><td>WFC booth</td><td>".$w[12]."</td><td>".($w[15]-$w[12])."</td></tr>";
echo "<tr><td>A WFC employee</td><td>".$w[13]."</td><td>".($w[15]-$w[13])."</td></tr>";
echo "<tr><td>A friend</td><td>".$w[14]."</td><td>".($w[15]-$w[14])."</td></tr>";
echo "</table><br />";

echo "Advertising \"Other\", Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select other from advertising as e left join survey_main as s on e.surveyID=s.surveyID where other <> '' and other <> 'NULL' and (s.card_no is null or s.card_no <> 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]."<br /><br />";
echo "</div>";
echo "Advertising \"Other\", Non-Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select other from advertising as e left join survey_main as s on e.surveyID=s.surveyID where other<>'NULL' and other<>'' and (s.card_no = 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]."<br /><br />";
echo "</div>";
echo "<br />";

echo "Would you participate in:<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&nbsp;</td><td>Yes</td><td>No</td><td>Maybe</td><td>No response</td></tr>";
echo "<tr><td>a. Local farm tours</td>";
$r = $sql->query("select rating,count(*) from participation where sub_question='a' group by rating order by rating desc");
while ($w = $sql->fetch_row($r))
	echo "<td>$w[1]</td>";
echo "</tr>";
echo "<tr><td>b. Book group</td>";
$r = $sql->query("select rating,count(*) from participation where sub_question='b' group by rating order by rating desc");
while ($w = $sql->fetch_row($r))
	echo "<td>$w[1]</td>";
echo "</tr>";
echo "<tr><td>c. Lecture Series</td>";
$r = $sql->query("select rating,count(*) from participation where sub_question='c' group by rating order by rating desc");
while ($w = $sql->fetch_row($r))
	echo "<td>$w[1]</td>";
echo "</tr>";
echo "<tr><td>d. Film Series</td>";
$r = $sql->query("select rating,count(*) from participation where sub_question='d' group by rating order by rating desc");
while ($w = $sql->fetch_row($r))
	echo "<td>$w[1]</td>";
echo "</tr>";
echo "</table><br />";

echo "Participation, Member<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&nbsp;</td><td>Yes</td><td>No</td><td>Maybe</td><td>No response</td></tr>";
echo "<tr><td>a. Local farm tours</td>";
$r = $sql->query("select rating,count(*) from participation as p left join survey_main as s on p.surveyID=s.surveyID where (s.card_no is null or s.card_no <> 11) and sub_question='a' group by rating order by rating desc");
while ($w = $sql->fetch_row($r))
	echo "<td>$w[1]</td>";
echo "</tr>";
echo "<tr><td>b. Book group</td>";
$r = $sql->query("select rating,count(*) from participation as p left join survey_main as s on p.surveyID=s.surveyID where (s.card_no is null or s.card_no <> 11) and sub_question='b' group by rating order by rating desc");
while ($w = $sql->fetch_row($r))
	echo "<td>$w[1]</td>";
echo "</tr>";
echo "<tr><td>c. Lecture Series</td>";
$r = $sql->query("select rating,count(*) from participation as p left join survey_main as s on p.surveyID=s.surveyID where (s.card_no is null or s.card_no <> 11) and sub_question='c' group by rating order by rating desc");
while ($w = $sql->fetch_row($r))
	echo "<td>$w[1]</td>";
echo "</tr>";
echo "<tr><td>d. Film Series</td>";
$r = $sql->query("select rating,count(*) from participation as p left join survey_main as s on p.surveyID=s.surveyID where (s.card_no is null or s.card_no <> 11) and sub_question='d' group by rating order by rating desc");
while ($w = $sql->fetch_row($r))
	echo "<td>$w[1]</td>";
echo "</tr>";
echo "</table><br />";

echo "Participation, Non-Member<br />";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><td>&nbsp;</td><td>Yes</td><td>No</td><td>Maybe</td><td>No response</td></tr>";
echo "<tr><td>a. Local farm tours</td>";
$r = $sql->query("select rating,count(*) from participation as p left join survey_main as s on p.surveyID=s.surveyID where (s.card_no = 11) and sub_question='a' group by rating order by rating desc");
while ($w = $sql->fetch_row($r))
	echo "<td>$w[1]</td>";
echo "</tr>";
echo "<tr><td>b. Book group</td>";
$r = $sql->query("select rating,count(*) from participation as p left join survey_main as s on p.surveyID=s.surveyID where (s.card_no = 11) and sub_question='b' group by rating order by rating desc");
while ($w = $sql->fetch_row($r))
	echo "<td>$w[1]</td>";
echo "</tr>";
echo "<tr><td>c. Lecture Series</td>";
$r = $sql->query("select rating,count(*) from participation as p left join survey_main as s on p.surveyID=s.surveyID where (s.card_no = 11) and sub_question='c' group by rating order by rating desc");
while ($w = $sql->fetch_row($r))
	echo "<td>$w[1]</td>";
echo "</tr>";
echo "<tr><td>d. Film Series</td>";
$r = $sql->query("select rating,count(*) from participation as p left join survey_main as s on p.surveyID=s.surveyID where (s.card_no = 11) and sub_question='d' group by rating order by rating desc");
while ($w = $sql->fetch_row($r))
	echo "<td>$w[1]</td>";
echo "</tr>";
echo "</table><br />";

echo "Other comments, Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select overall_comment from survey_main where overall_comment <> 'NULL' and overall_comment <> '' and (card_no is null or card_no <> 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]."<br /><br />";
echo "</div>";
echo "Other comments, Non-Member<br />";
echo "<div style=\"background:#ffcccc;\">";
$r = $sql->query("select overall_comment from survey_main where overall_comment <> 'NULL' and overall_comment <> '' and (card_no = 11)");
while ($w = $sql->fetch_array($r))
	echo $w[0]."<br /><br />";
echo "</div>";
?>
