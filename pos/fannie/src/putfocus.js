function putFocus(formInst, elementInst) {
	if (document.forms.length > 0) {
		document.forms[formInst].elements[elementInst].focus();
	}
}

// The second number in the "onLoad" command in the body
// tag determines the form's focus. Counting starts with '0'