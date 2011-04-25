function valid_name(e) {
	/* 50 Characters or less, following are not allowed: ' , + */
	// TODO - Wait, do we really not allow single quotes? I forget 
	var re_name = new RegExp("[+\',]", "g");

	if (e.value.length > 50 || re_name.test(e.value)) {
		e.style.color='#F00';
	} else {
		e.style.color='#000';
	} 
}