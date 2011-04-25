	// Attached to body.onload
function body_onload() {
	document.search.q.focus();
	
	if (document.getElementById('results_similar_products')) {
		set_boxes();
	}
}

	// Called after similar products table is made
	// TODO - Add to body.onresize and error check if table exists
function set_boxes() {
	var a=window.innerHeight;
	var b=document.getElementById('page_top').offsetHeight;
	var c=document.getElementById('page_foot').offsetHeight;
	var d=document.search.offsetHeight;
	var e=0;
		if (document.edit) {
			e=document.edit.offsetHeight;
		}
	
		// TODO - Only add the 26 margin when the offsetHeight is positive?
	var f=document.getElementById('page_panel_statuses').offsetHeight+26;
	
	var g=a-b-c-d-e-f-24;
	
	// If the similar results box will fit and display at least 76px then resize it, if not, set a decent size and force a vertical scroll
	if (g>76) {
		document.getElementById('results_similar_products_wrap').style.height=g+'px';
	} else {
		document.getElementById('results_similar_products_wrap').style.height='300px';
	}
}

function valid_description(e) {
	/* 30 Characters or less, following are not allowed: ' , + */
	// TODO - Wait, do we really not allow single quotes? I forget 
	var re_description = new RegExp("[+\',]", "g");

	if (e.value.length > 30 || re_description.test(e.value)) {
		e.style.color='#F00';
	} else {
		e.style.color='#000';
	} 
}

function valid_price(e) {
	/* SQL Data Type 'Money' */
	// TODO: Add more logic to regex 
	var re_price = new RegExp("[^\$\.0-9]", "g");
 	
	if (e.value.length > 10 || re_price.test(e.value)) {
		e.style.color='#F00';
	} else {
		e.style.color='#000';
	} 
}

function valid_tax(e) {
	 /* SQL Data Type 'smallint' */
	 
 	var re_tax = new RegExp("[^0-9]", "g");
 	
 	if (e.value.length > 5 || e.value > 32767 || (e.value.length > 0 && e.value < 0) || re_tax.test(e.value)) {
		e.style.color='#F00';
	} else {
		e.style.color='#000';
	} 
}

function valid_tareweight(e) {
	 /* SQL Data Type 'double', but bound to change */
	 
	var re_tareweight = new RegExp("[^0-9\.]", "g");
	
	if (e.value.length > 5 || e.value > 32767 || (e.value.length > 0 && e.value < 0) || re_tareweight.test(e.value)) {
		e.style.color='#F00';
	} else {
		e.style.color='#000';
	} 
}

function valid_size(e) {
	 /* 9 Characters or less, following are not allowed: ' , + */
	var re_size = new RegExp("[+\',]", "g");

	if (e.value.length > 9 || re_size.test(e.value)) {
		e.style.color='#F00';
	} else {
		e.style.color='#000';
	} 
}

function valid_unitofmeasure(e) {
	 /* 15 Characters or less, following are not allowed: ' , + */
	var re_unitofmeasure = new RegExp("[+\',]", "g");

	if (e.value.length > 15 || re_unitofmeasure.test(e.value)) {
		e.style.color='#F00';
	} else {
		e.style.color='#000';
	} 
}

function valid_deposit(e) {
	/* SQL Data Type 'Money' */
	// TODO: Add more logic to regex 
	var re_deposit = new RegExp("[^\$\.0-9]", "g");
 	
	if (e.value.length > 10 || re_deposit.test(e.value)) {
		e.style.color='#F00';
	} else {
		e.style.color='#000';
	} 
}