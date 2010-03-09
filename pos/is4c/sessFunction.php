<?

function sessionReset(){
	$_SESSION["runningTotal"] = 0;
	$_SESSION["subtotal"] = 0;
	$_SESSION["discounttotal"] = 0;
	$_SESSION["taxTotal"] = 0;
	$_SESSION["tenderTotal"] =0;
	$_SESSION["memberID"] = 0;
	$_SESSION["LastID"] = 0;
	$_SESSION["ttlflag"] = 0;
	$_SESSION["ttlrequested"] = 0;
	$_SESSION["End"] = 0;
	$_SESSION["refund"] = 0;
	$_SESSION["foodstamptotal"] = 0;

	$_SESSION["fntlflag"] = 0;
	$_SESSION["fstaxable"] = 0;
	$_SESSION["fstendered"] = 0;
	$_SESSION["fsTaxExempt"] = 0;
	$_SESSION["percentDiscount"] = 0;

	$_SESSION["tare"] = 0;
	$_SESSION["toggletax"] = 0;
	$_SESSION["togglefoodstamp"] = 0;
	$_SESSION["TaxExempt"] = 0;
	$_SESSION["Scale"] = 0;
	$_SESSION["casediscount"] = 0;
	$_SESSION["memMsg"] = 0;
	$_SESSION["void"] = 0;

	$_SESSION["cktendered"] = 0;
	$_SESSION["chargeOk"] = 0;
	$_SESSION["transDiscount"] = 0;
	$_SESSION["repeat"] = 0;
	$_SESSION["isStaff"] = 0;
	$_SESSION["isMember"] = 0;
	$_SESSION["SSI"] = 0;
	$_SESSION["discountcap"] = 0;
	$_SESSION["mfcoupon"] = 0;
	$_SESSION["memSpecial"] = 0;
	$_SESSION["currentid"] = 0;

	$_SESSION["strEntered"] = 0;
	$_SESSION["strRemembered"] = 0;
	$_SESSION["msgrepeat"] = 0;
	$_SESSION["beep"] = 0;
	$_SESSION["scan"] = 0;


	$_SESSION["multiple"] = 0;
	$_SESSION["franking"] = 0;
	$_SESSION["datetimestamp"] = 0;
	$_SESSION["refundTotal"] =0 ;
	$_SESSION["mirequested"] = 0;
	$_SESSION["SNR"] = 0;
	$_SESSION["amtdue"] =0 ;
	$_SESSION["memCouponTTL"] = 0;
	$_SESSION["yousaved"] = 0;
	$_SESSION["couldhavesaved"] = 0;
	$_SESSION["plainmsg"] = 0;
	$_SESSION["screset"] = "staycool";
	$_SESSION["specials"] = 0;
	$_SESSION["waitforScale"] = 0;
	$_SESSION["discounttype"] = 0;
	$_SESSION["discountable"] = 0;
	$_SESSION["memType"] = 0;
	$_SESSION["availBal"] = 0;
	$_SESSION["madCoup"] = 0;
	$_SESSION["madCoupYes"] = 0;
	$_SESSION["memChargeTotal"] = 0;
	$_SESSION["scAmtDue"] = 0;
	$_SESSION["scDiscount"] = 0;
	$_SESSION["sc"] = 0;
	$_SESSION["nd"] = 0;
	$_SESSION["paymentTotal"] = 0;
	$_SESSION["balance"] = 0;
	$_SESSION["itemPD"] = 0;
	$_SESSION["ccAmt"] = 0;
	$_SESSION["ccAmtEntered"] = 0;
	$_SESSION["ccTender"] = 0;
	$_SESSION["troutd"] = 0;
	$_SESSION["trackPatronage"] = 0;
	$_SESSION["togglePatronage"] = 0;

}


function rsv($var){
	$_SESSION["$var"] = 0;
}

function ssv($var,$val){
	$v = $_SESSION[$var];
	$v = $val;
	$_SESSION[$var]=$v;
}
	