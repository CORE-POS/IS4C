function getdatetime() {

	today =  new Date();

	var halfday = "";
	var month = today.getMonth() + 1;
	if (month < 10) month = "0" + month;

	var date = today.getDate();
	if (date < 10) date = "0" + date;

	var year = today.getFullYear();

	var datedisplay = month + "/" + date + "/" + year;

	var hour = today.getHours();

	if (hour <= 11) halfday = "AM";
	else if (hour == 12) halfday = "PM"
	else {
		halfday = "PM";
		hour = hour - 12
	}

	if (hour < 10) hour = "0" + hour;

	var minute = today.getMinutes();
	if (minute < 10) minute = "0" + minute;

	var timedisplay = hour + ":" + minute + " " + halfday;

	var datetime = datedisplay + " " + timedisplay;
	document.getElementById("datetime").innerHTML = datetime;
}

function listen_datetime() {
	getdatetime();
	setTimeout('listen_datetime();', 1000);
}

listen_datetime();