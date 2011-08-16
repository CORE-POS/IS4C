/**
 * CREATED BY ADI SCOARTA
 * EMAIL: adi@codetale.com
 * Version: 1.08
 * Build Date: June 21st, 2009
 */

if(typeof(iMonth)=="undefined")
	iMonth = new Date().getMonth();
if(typeof(iYear)=="undefined")
	iYear = new Date().getFullYear();
if(typeof(iDay)=="undefined")
	iDay = new Date().getDate();
if(typeof(itype) == "undefined")
	itype = "loose" //loose->any date|strict->limit to maxDays
if(typeof(imaxDays) == "undefined")
	imaxDays = 330 //counts only if itype=strict. Enable selection imaxDays from start date
if(typeof(startDay) == "undefined")
	startDay = iDay; //enable selection from this date
if(typeof(startMonth) == "undefined")
	startMonth = iMonth;
if(typeof(startYear) == "undefined")
	startYear = iYear;
if(typeof(addZero) == "undefined")
	addZero = true; //true|false. Put 0 in front of days&months if <10
if(typeof(offX) == "undefined")
	offX = 10 // x distance from the mouse.
if(typeof(offY) == "undefined")
	offY = -10 // y distance from the mouse.
if(typeof(formatInputs) == "undefined")
	formatInputs = 1 // Gather the data from no. of inputs
if(typeof(formatSplitter) == "undefined")
	formatSplitter = "-" // Character to add betwen day/month/year
if(typeof(monthFormat) == "undefined")
	monthFormat = "mm";
if(typeof(yearFormat) == "undefined")
	yearFormat = "yyyy";
if(typeof(folowMouse) == "undefined")
	folowMouse = true;
if(typeof(formatType) == "undefined")
	formatType = yearFormat+formatSplitter+monthFormat+formatSplitter+"dd"; //Format data type
if(typeof(callNotice) == "undefined")
	callNotice = "fallsilent()"; //call another function that a date has been selected.
if(typeof(sundayOff)=="undefined")
	sundayOff = false;
if(typeof(saturdayOff)=="undefined")
	saturdayOff = false;
if(typeof(sundayFirst)=="undefined")
	sundayFirst = false;

if (window.addEventListener)
	window.addEventListener("load", createBase, false)
else if (window.attachEvent)
	window.attachEvent("onload", createBase)
else if (document.getElementById)
	window.onload=createBase



document.onmousemove = getMouseXY;
var IE = document.all?true:false
if (!IE) document.captureEvents(Event.MOUSEMOVE)

var tempX = 0
var tempY = 0

function getMouseXY(e) {
  if (IE) { // grab the x-y pos.s if browser is IE
    tempX = event.clientX + document.body.scrollLeft
    tempY = event.clientY + document.body.scrollTop
  } else {  // grab the x-y pos.s if browser is NS
    tempX = e.pageX
    tempY = e.pageY
  }  
  if (tempX < 0){tempX = 0}
  if (tempY < 0){tempY = 0}  

  return true
}


function getScrollXY() {
  var scrOfX = 0, scrOfY = 0;
  if( typeof( window.pageYOffset ) == 'number' ) {
    //Netscape compliant
    scrOfY = window.pageYOffset;
    scrOfX = window.pageXOffset;
  } else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
    //DOM compliant
    scrOfY = document.body.scrollTop;
    scrOfX = document.body.scrollLeft;
  } else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
    //IE6 standards compliant mode
    scrOfY = document.documentElement.scrollTop;
    scrOfX = document.documentElement.scrollLeft;
  }
  return [ scrOfX, scrOfY ];
}



/*
 * Shortcut functions to ease the implementation.
 */

var d = document;
function cel(obj){ return d.createElement(obj);  }
function sa(obj, atname, atprop){ return  obj.setAttribute(atname, atprop);  }
function appendc(obj, elem){ return  obj.appendChild(elem); }
function cNode(obj, txt){ return obj.appendChild(d.createTextNode(txt)); }
function getID(elem){ return d.getElementById(elem); }

var DayCol = new Array("M", "T", "W", "T", "F", "S", "S");
if(sundayFirst)
{
	newDayCol = new Array(DayCol[DayCol.length-1]);
	for(x=0; x<DayCol.length-1;x++)
		newDayCol[newDayCol.length] = DayCol[x];
	DayCol = newDayCol;
}
var MonthCol = new Array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov", "Dec")

function getDaysInMonth(mnt, yr)
{
	var DaysInMonth = new Array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	if (mnt == 1)
	  DaysInMonth[1] = ((yr % 400 == 0) || ((yr % 4 == 0) && (yr % 100 !=0))) ? 29 : 28;
	return DaysInMonth[mnt];
}
/*
 * Base object for the widget
 */  
 
var cw = {
	currMonth: iMonth,
	currYear: iYear,
	currDay: iDay,
	selMonth: iMonth,
	selYear: iYear,
	selDay: iDay,
	config: itype,
	maxDays: imaxDays,
	stMonth : startMonth,
	stYear : startYear,
	stDay : startDay,
	endMonth : 11,
	endYear : iYear,
	endDay : 31,
	addZ: addZero, 
	setMarks: function()
	{
		if(this.config=='strict')
		{
			this.stDay = startDay;
			this.stMonth = startMonth;
			this.stYear = startYear;
			this.getEnd();
		}
	},
	getConfMonths: function()
	{
		if(this.config=='strict')
			cw.setMarks();
		mthCol = cel("ul");
		mthCol.id = "months";
		k=0;
		  for(i=0;i<12; i++)
		  {
				mth = cel("li");
				if(cw.isValidMonth(i))
				{
					mth.className = "months";
					if(cw.isCurrentMonth(i))
						mth.className = "currMonth";
					mtha = cel("a");
					mtha.href = "javascript:modMonth("+this.selYear+"," + i + ")";
					mtha.innerHTML = MonthCol[i];
					appendc(mth, mtha);
				}
				else
				{
					mth.className = "monthDisabled";
					mth.innerHTML = MonthCol[i];
				}
				appendc(mthCol, mth)
				
		  }
		cw.setBrowseYears();		
		
		return mthCol ;
	},
	getConfDays: function()
	{
		dayCol = cel("ul");
		dayCol.id = "days";
		for(i=0;i<7;i++)
		{
			dayCell = cel("li");
			dayCell.className = "headDay";
			dayCell.innerHTML = DayCol[i];
			appendc(dayCol, dayCell);
		}
		var iFirstDay = new Date(this.selYear, this.selMonth, 1).getDay();
		if (!sundayFirst) {
			iFirstDay--;
		}
		if(iFirstDay<0){iFirstDay=6}
		for(i=0;i<iFirstDay;i++)
		{
			dayCell = cel('li');
			dayCell.className = "dayBlank";
			dayCell.innerHTML = "&nbsp;";
			appendc(dayCol, dayCell)
		}
		for(i=1;i<=getDaysInMonth(this.selMonth, this.selYear); i++)
		{
			dayCell = cel('li');
			if(cw.isValidDate(i))
			{
				dayCell.className = "dayNormal";
				if(cw.isWeekend(i))
					dayCell.className = "dayWeekend";
				if(cw.isCurrentDay(i))
					dayCell.className = "dayCurrent";
				dayLink = cel('a');
				dayLink.href="javascript: newDay("+ i + ");fillBackDate("+i+","+this.selMonth+","+ this.selYear+")";
				dayLink.innerHTML = i;
				appendc(dayCell, dayLink);
			}
			else
			{
				dayCell.className = "dayDisabled";
				dayCell.innerHTML = i;
			}
			appendc(dayCol, dayCell)
		}
		return dayCol;
	},
	getEnd: function()
	{
		imaxD = imaxDays - (getDaysInMonth(this.stMonth, this.stYear) - this.stDay);
		tmpM = this.stMonth;
		tmpY = this.stYear;
		if (imaxD < 0) {
			tmpD = this.stDay+(imaxD*(-1));
			this.endMonth = tmpM;
			this.endDay = tmpD;
			this.endYear = tmpY;
			return;
		}
		else if(imaxD == 0)
		{
			tmpD = getDaysInMonth(this.stMonth, this.stYear);
			this.endMonth = tmpM;
			this.endDay = tmpD;
			this.endYear = tmpY;
			return;
		}
		else if(imaxD < (getDaysInMonth(this.stMonth, this.stYear) - this.stDay))
		{
			tmpD = imaxD;
		}
		i=0;
		while(imaxD >= getDaysInMonth(tmpM, tmpY))
		{
			inc = true;
			tmpM++
			if(tmpM>11)
			{
				tmpM=0
				tmpY++;
			}
			tmpD = imaxD -= getDaysInMonth(tmpM, tmpY);
		}
		tmpM++
		if(tmpM>11){tmpM=0; tmpY++}
		this.endMonth = tmpM;
		this.endDay = tmpD;
		this.endYear = tmpY;
	},
	isValidDate: function(tDay)
	{
		if(saturdayOff || sundayOff)
		{
			sun = new Date(this.selYear, this.selMonth, tDay);
			sun = sun.getDay()
			if((sun==6 && saturdayOff) || (sun==0 && sundayOff))
				return false;
		}	
		if(this.config == "loose")
			return true;
		cdate = new Date(this.selYear, this.selMonth, tDay).getTime();
		sdate = new Date(this.stYear, this.stMonth, this.stDay).getTime();
		edate = new Date(this.endYear, this.endMonth, this.endDay).getTime();
		if(cdate<sdate || cdate>edate)
			return false;
		return true;
		if(this.selYear==this.stYear)
		{
			if(this.selMonth<this.stMonth)
				return false;
			if(this.selMonth==this.stMonth && tDay <this.stDay)
				return false;
		}
		if(this.selYear==this.endYear)
		{
			if(this.selMonth>this.endMonth)
				return false;
			if(this.selMonth==this.endMonth && tDay>this.endDay)
				return false;
				
		}
		if(this.selYear == this.endYear && this.selYear==this.stYear){
			if(this.selMonth> this.endMonth || this.selMonth<this.stMonth)
				return false;
			}
		if(this.selYear>this.endYear)
			return false;
		return true;
		
	},
	isWeekend: function(tDay)
	{
		sun = new Date(this.selYear, this.selMonth, tDay).getDay();
		if(sun==6||sun==0)
			return true;
		return false;
	},
	isCurrentDay: function(tDay)
	{
		if(this.selDay == tDay)
			return true;
		return false;
	},
	setBrowseYears: function()
	{
		brsY = cel('li');
		brsY.className = "yearBrowse";
		if(this.selYear <= this.stYear && this.config == "strict")
		{
			backB = cel('span');	
		}
		else
		{
			backB= cel('a');
			backB.href = "javascript: modYear(-1)";
		}
		backB.innerHTML = "&laquo;";
		yText = cel("b");
		yText.innerHTML = cw.selYear;
		if(this.selYear >= this.endYear && this.config == "strict")
			fwdB = cel('span');
		else
		{
			fwdB = cel('a');
			fwdB.href= "javascript: modYear(1)";
		}
		fwdB.innerHTML = "&raquo;";
		appendc(brsY, backB);
		appendc(brsY, yText);
		appendc(brsY, fwdB);
		appendc(mthCol, brsY);
	},
	isValidMonth: function(m)
	{
		if(this.config == "loose")
			return true;
		else
		{
			if(this.selYear< this.stYear)
				return false;
			if(this.selYear==this.stYear && m<this.stMonth)
				return false;
			if(this.selYear>this.endYear)
				return false;
			if(this.selYear==this.endYear && m>this.endMonth)
				return false;
		}	
		return true;
	},
	isCurrentMonth: function(i)
	{
		if(i==this.selMonth)
			return true
		return false;
	}
}

cw.setMarks();
function createBase()
{
	var el = cel('div');
	el.id="calendar";
	el.style.display="none";
	if(typeof(elToAppend) == "undefined")
		tDocument = document.getElementsByTagName('body').item(0);
	else
	{
		var tt = elToAppend;
		tDocument = document.getElementById(tt);
	}
	appendc(tDocument, el);	
}


function createCalendarElements()
{
	var el = 'calendar';
	var calCon = cel('div');
	calCon.id = "elements";
	while(document.getElementById(el).firstChild)
		document.getElementById(el).removeChild(document.getElementById(el).firstChild);
	appendc(document.getElementById(el), calCon);
	mthCol = cw.getConfMonths();
	appendc(calCon, mthCol);
	dayStruct = cw.getConfDays();
	appendc(calCon, dayStruct);

	closeBtn = cel('div');
	closeBtn.id = "closeBtn";
	closeBtna = cel('a');
	closeBtna.href = "javascript: closeCalendar()";
	closeBtna.innerHTML = "close";
	appendc(closeBtn, closeBtna);
	appendc(document.getElementById(el), closeBtn);
}
function modMonth(newY, newM)
{
	cw.selYear = newY;
	cw.selMonth = newM;
	createCalendarElements();
}
function newDay(newD)
{
	cw.selDay = newD;
	createCalendarElements();
}
function modYear(way)
{
	cw.selYear = parseInt(cw.selYear) + parseInt(way);
	createCalendarElements();	
}
var datas;
var elem1;
var elem2;
var elem3;
var mA=0;
var yA=0;
var mm = new Array('mm', 'mmm');
var yy = new Array('yy', 'yyyy');

function fPopCalendar(param)
{
	tmpString = new String();
	elem1 = param;
	tmpString = document.getElementById(elem1).value;
	datas  = tmpString.split(formatSplitter);
	tmpo = formatType.split(formatSplitter);
	dC="";tC="";
	if(datas.length == tmpo.length)
	{
		for(i=0;i<datas.length;i++)
		{
			if(datas[i].length<2)
				datas[i] = "0"+datas[i];
			dC +=datas[i];
			tC +=tmpo[i]; 
		}
		if(dC.length == tC.length)
			orderData();
	}
	else
		datas = new Array(cw.selDay, cw.selMonth, cw.selYear);
	createCalendarElements();
	offsets = getScrollXY();
	document.getElementById('calendar').style.display = "block";

	if(folowMouse)
	{
		var browser=navigator.appName;
		if(browser=="Microsoft Internet Explorer")
		{
			document.getElementById('calendar').style.left = parseInt(tempX)+parseInt(offX)+parseInt(offsets[0]) + 'px';
		    document.getElementById('calendar').style.top = parseInt(tempY)+parseInt(offY)+parseInt(offsets[1]) + 'px';
		}
		else
		{
			document.getElementById('calendar').style.left = parseInt(tempX)+parseInt(offX)+ 'px';
		    document.getElementById('calendar').style.top = parseInt(tempY)+parseInt(offY)+ 'px';
		}
	}
	order = new String(formatType).split(formatSplitter);

	for(i=0;i<mm.length;i++)
	{
		for(j=0;j<order.length;j++)
		{
			if(mm[i] == order[j])
				mA = i;
			if(yy[i] == order[j])
				yA = i;
		}
	}
}

function orderData()
{

	order = new String(formatType).split(formatSplitter);

	for(i=0;i<order.length;i++)
	{
		for(j=0;j<mm.length;j++)
		{
			if(mm[j] == order[i])
			{
				cw.selMonth = datas[i];
				if(cw.selMonth.slice(0, 1) == 0)
					cw.selMonth = parseInt(cw.selMonth.slice(1, cw.selMonth.length))-1;
				else if(cw.selMonth.length<3)
					cw.selMonth = parseInt(cw.selMonth)-1;
				if(j==1)
				{
					for(k=0;k<MonthCol.length;k++)
					{
						if(MonthCol[k].toLowerCase() == cw.selMonth.toLowerCase() )
						{
							cw.selMonth = k;
							break;
						}
					}
				}
			}
			if(yy[j] == order[i])
			{
				cw.selYear = datas[i];
				if(cw.selYear.slice(0, 1) == 0)
					cw.selYear = parseInt(cw.selYear.slice(1, cw.selYear.length));
				if(j==0)
					cw.selYear =2000 + parseInt(cw.selYear);
			}				
		}
		if(order[i].toLowerCase() == 'dd')
		{
			cw.selDay = datas[i];
			if(cw.selDay.slice(0, 1) == 0)
				cw.selDay = parseInt(cw.selDay.slice(1, cw.selDay.length));
		}
	}
}

function fillBackDate(tDay, tMonth, tYear)
{
	if(mA==1)
		tMonth = MonthCol[tMonth];
	if(mA==0)
	{
		tMonth++;
		if(tMonth<10  && cw.addZ == true)
			tMonth = "0"+tMonth;
	}
	if(yA==0)
		tYear = new String(tYear).slice(2,4);
	if(tDay<10 && cw.addZ == true)
	{
		tDay = "0"+tDay;
	}
	
	order = new String(formatType).split(formatSplitter);
	vali = "";
	
	for(x = 0; x <order.length; x++)
	{
		if (order[x] == "yy" || order[x] == "yyyy") 
			vali += tYear;
		if (order[x] == "mm" || order[x] == "mmm") 
			vali += tMonth;
		else if(order[x] == "dd")
			vali += tDay;
		if(x < order.length-1)
			vali += formatSplitter;
	}
	
	document.getElementById(elem1).value = vali
	setTimeout(callNotice, 0);
	
	closeCalendar();
}

function closeCalendar()
{
	var el = 'calendar';
	document.getElementById(el).style.display = "none";
}
function fallsilent(){}
