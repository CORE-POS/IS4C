<html>
<head>
</head>
<body onload="document.getElementById('cardno').focus()">

<form action=survey_proc.php method=post>
Mem# <input type=text name=cardno id=cardno /><br />
<b>MEMBER BENEFITS</b><br />
<?php
for ($i = 0; $i < 17; $i++){
echo chr($i+97)." <input type=text name=mem$i size=3 /><br />";
}
?>
Zipcode <input type=text name=zipcode /><br />
Gender <input type=text name=gender /><br />
Age bracket <input type=text name=age_bracket /><br />
Adults <input type=text name=adults /><br />
Children <input type=text name=children /><br />
Income <input type=text name=income /><br />
Education <input type=text name=education /><br />
Weekly <input type=text name=weekly /><br />
Weekly WFC <input type=text name=weekly_wfc /><br />
<b>WFC EXPERIENCE</b><br />
<?php
for ($i = 0; $i < 12; $i++){
echo chr($i+97)." <input type=text name=exp$i size=3 /><br />";
}
?>
Other: <input type=text name=exp_other_text /><br />
Poor: <input type=text name=exp_poor size=3 /> <input type=text name=exp_poor_text /><br />
<b>ITEMS</b><br />
1. <input type=text name=items1 /><br />
2. <input type=text name=items2 /><br />
3. <input type=text name=items3 /><br />
<b>SERVICES</b><br />
Fruit/veg <input type=text name=fruitveg size=3 /><br />
Juicebar <input type=text name=juicebar size=3 /><br />
Online <input type=text name=online size=3 /><br />
Delivery <input type=text name=delivery size=3 /><br />
Catering <input type=text name=catering size=3 /><br />
Vendor <input type=text name=vendor size=3 /><br />
Hours <input type=text name=hours /><br />
Other <input type=text name=services_other /><br />
<b>DEPTS</b><Br />
<?php
for ($i=0; $i<26; $i++){
echo chr($i+97)." <input type=text name=deptyn$i size=3 /> ";
echo "<input type=text name=deptquality$i size=3 />";
echo "<input type=text name=deptstock$i size=3 />";
echo "<input type=text name=deptprice$i size=3 />";
echo "<input type=text name=deptselection$i size=3 />";
echo "<input type=text name=deptneed$i size=3 /><br />";
}
?>
<b>CSC</b><br />
<?php
for ($i=0; $i<12; $i++){
echo chr($i+97)." <input type=text name=csc$i size=3 /><br />";
}
?>
Other: <input type=text name=csc_other_text /><br />
Poor: <input type=text name=csc_poor size=3 /> <input type=text name=csc_poor_text /><br />
<b>IMPORTANCE</b><br />
<?php
for ($i=0; $i<16; $i++){
echo chr($i+97)." <input type=text name=imp$i size=3 /><br />";
}
?>
Other: <input type=text name=imp_other_text /><br />
<b>STAFF</b><br />
<?php
for ($i=0; $i<9; $i++){
echo chr($i+97)." <input type=text name=staff$i size=3 /><br />";
}
?>
Other: <input type=text name=staff_other_text /><br />
<b>FEATURES</b><br />
Location <input type=text name=location size=3 /><br />
Parking <input type=text name=parking size=3 /><br />
Atmosphere <input type=text name=atmosphere size=3 /><br />
Staff <input type=text name=staff size=3 /><br />
Service <input type=text name=service size=3 /><br />
Cleanliness <input type=text name=cleanliness size=3 /><br />
Publictrans <input type=text name=public_trans size=3 /><br />
Organiclocal <input type=text name=organic_local size=3 /><br />
Allergy <input type=text name=allergy size=3 /><br />
ProdInfo <input type=text name=prod_info size=3 /><br />
Prices <input type=text name=prices size=3 /><br />
Coop_model <input type=text name=coop_model size=3 /><br />
Owner <input type=text name=owner size=3 /><br />
Localsupport <input type=text name=local_support size=3 /><br />
Community <input type=text name=community size=3 /><br />
Environment <input type=text name=environment size=3 /><br />
Other <input type=text name=features_other /><br />
Other Store <input type=text name=other_store size=3 /><br />
<b>Advertising</b><br />
Signage <input type=text name=signage size=3 /><br />
Flyers <input type=text name=flyers size=3 /><br />
Brochures <input type=text name=brochures size=3 /><br />
website <input type=text name=website size=3 /><br />
Newsletter <input type=text name=newsletter size=3 /><br />
Billboards <input type=text name=billboards size=3 /><br />
NPR <input type=text name=public_radio size=3 /><br />
Radio <input type=text name=radio size=3 /><br />
TV <input type=text name=tv size=3 /><br />
DNT <input type=text name=dnt size=3 /><br />
Events <input type=text name=events size=3 /><br />
Meetings <input type=text name=meetings size=3 /><br />
Booth <input type=text name=booth size=3 /><br />
Employee <input type=text name=employee size=3 /><br />
Friend <input type=text name=friend size=3 /><br />
Other <input type=text name=advert_other /><br />
<b>PARTICIPATION</b><br />
<?php
for ($i=0; $i<4; $i++){
echo chr($i+97)." <input type=text name=part$i size=3 /><br />";
}
?>
General Other <input type=text name=general_other /><br />
<input type=submit />
</form>

</body>
</html>
