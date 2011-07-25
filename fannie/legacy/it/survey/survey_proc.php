<?php
include('../../../config.php');

include($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT.'src/Credentials/surveydb.wfc.php');

function getval($str){
	if (isset($_POST[$str]) && $_POST[$str] != "")
		return $_POST[$str];
	else
		return "NULL";
}

$idQ = "select max(surveyID) from survey_main";
$idR = $sql->query($idQ);
$id = 0;
if ($sql->num_rows($idR) > 0)
	$id = array_pop($sql->fetch_row($idR));
$id++;

$mainQ = "insert survey_main values ("
	.$id.","
	.getval("cardno").","
	.getval("zipcode").","
	.getval("gender").","
	.getval("age_bracket").","
	.getval("adults").","
	.getval("children").","
	.getval("income").","
	.getval("education").","
	.getval("weekly").","
	.getval("weekly_wfc").","
	."'".getval("general_other")."')";
$sql->query($mainQ);

for ($i = 0; $i < 9; $i++){
	if (getval("staff$i") != "NULL"){
		$q = "insert staff_rating values ($id,'".chr($i+97)."',".getval("staff$i").")";
		$sql->query($q);
	}
}
if (getval("staff_other_text") != "NULL"){
	$q = "insert extra_other values ($id,20,'".getval("staff_other_text")."')";
	$sql->query($q);
}

for ($i = 0; $i < 12; $i++){
	if (getval("exp$i") != "NULL"){
		$q = "insert shopping_experience values ($id,'".chr($i+97)."',".getval("exp$i").")";
		$sql->query($q);
	}
}
if (getval("exp_other_text") != "NULL"){
	$q = "insert experience_poor values ($id,'m','".getval("exp_other_text")."')";
	$sql->query($q);
}
if (getval("exp_poor_text") != "NULL"){
	$q = "insert experience_poor values ($id,'".getval("exp_poor")."','".getval("exp_poor_text")."')";
	$sql->query($q);
}

$servicesQ = "insert services values ($id,"
	.getval("fruitveg").","
	.getval("juicebar").","
	.getval("online").","
	.getval("delivery").","
	.getval("catering").","
	.getval("vendor").","
	."'".getval("hours")."',"
	."'".getval("services_other")."')";
$sql->query($servicesQ);

for ($i = 0; $i < 4; $i++){
	if (getval("part$i") != "NULL"){
		$q = "insert participation values ($id,'".chr($i+97)."',".getval("part$i").")";
		$sql->query($q);
	}
}

if (getval("other_store") != "NULL"){
	$otherQ = "insert other_stores values ($id,".getval("other_store").")";
	$sql->query($otherQ);
}

for ($i = 0; $i < 17; $i++){
	if (getval("mem$i") != "NULL"){
		$q = "insert member_benefits values ($id,'".chr($i+97)."',".getval("mem$i").")";
		$sql->query($q);
	}
}

for ($i = 0; $i < 16; $i++){
	if (getval("imp$i") != "NULL"){
		$q = "insert importance values ($id,'".chr($i+97)."',".getval("imp$i").")";
		$sql->query($q);
	}
}
if (getval("imp_other_text") != "NULL"){
	$q = "insert extra_other values ($id,16,'".getval("imp_other_text")."')";
	$sql->query($q);
}

$featuresQ = "insert features values ($id,"
	.getval("location").","
	.getval("parking").","
	.getval("atmosphere").","
	.getval("staff").","
	.getval("service").","
	.getval("cleanliness").","
	.getval("public_trans").","
	.getval("orgainc_local").","
	.getval("allergy").","
	.getval("prod_info").","
	.getval("prices").","
	.getval("coop_model").","
	.getval("owner").","
	.getval("local_support").","
	.getval("community").","
	.getval("environment").","
	."'".getval("features_other")."')";
$sql->query($featuresQ);

for ($i = 1; $i <= 3; $i++){
	if (getval("items$i") != "NULL"){
		$q = "insert desired_items values ($id,'".getval("items$i")."')";
		$sql->query($q);
	}
}

for ($i = 0; $i < 26; $i++){
	if (getval("deptyn$i") != "NULL"){
		$q = "insert dept_ratings values ($id,'".chr($i+97)."',"
			.getval("deptyn$i").","
			.getval("deptquality$i").","
			.getval("deptstock$i").","
			.getval("deptprice$i").","
			.getval("deptselection$i").","
			.getval("deptneed$i").")";
		$sql->query($q);
	}
}

for ($i = 0; $i < 12; $i++){
	if (getval("csc$i") != "NULL"){
		$q = "insert csc values ($id,'".chr($i+97)."',".getval("csc$i").")";
		$sql->query($q);
	}
}
if (getval("csc_other_text") != "NULL"){
	$q = "insert csc_poor values ($id,'m','".getval("csc_other_text")."')";
	$sql->query($q);
}
if (getval("csc_poor_text") != "NULL"){
	$q = "insert csc_poor values ($id,'".getval("csc_poor")."','".getval("csc_poor_text")."')";
	$sql->query($q);
}

$advertQ = "insert advertising values ($id,"
	.getval("signage").","
	.getval("flyers").","
	.getval("brochures").","
	.getval("website").","
	.getval("newsletter").","
	.getval("billboards").","
	.getval("public_radio").","
	.getval("radio").","
	.getval("tv").","
	.getval("dnt").","
	.getval("events").","
	.getval("meetings").","
	.getval("booth").","
	.getval("employee").","
	.getval("friend").","
	."'".getval("advert_other")."')";
$sql->query($advertQ);

header("Location: index.php");
?>
