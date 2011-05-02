#!/usr/bin/python

import cookielib,urllib2,urllib
import re

# CONFIGURATION
# Put in your username and password
# Choose what type you'd like cownloaded (CSV, TAB, or FLAT)
# Choose which directory downloads should be stored in (w/ trailing slash)
# Requires python >= 2.4
TYPE="CSV"
USERNAME=""
PASSWORD=""
DL_DIR="csvs/"

LOGIN_URL="http://www.unfi.com/Default.aspx"
LOGIN_CB_URL="https://east.unfi.com/public/LogonPost.aspx"
INVOICE_URL="http://east.unfi.com/invoices/listinvoices2.aspx"
TYPES = { "CSV":"rdoCSV","TAB":"rdoTab","FLAT":"rdoFlat" }

cj = cookielib.CookieJar()
opener = urllib2.build_opener(urllib2.HTTPCookieProcessor(cj))
fp = opener.open(LOGIN_URL)

vs_pat = re.compile("id=\"__VIEWSTATE\" value=\"(.*?)\"")
ev_pat = re.compile("id=\"__EVENTVALIDATION\" value=\"(.*?)\"")
pv_pat = re.compile("id=\"__PREVIOUSPAGE\" value=\"(.*?)\"")
args = []
#print "Getting hidden input values for login"
# __VIEWSTATS and __EVENTVALIDATION are required POST arguments
for line in fp.readlines():
	match = vs_pat.search(line)
	if match:
		args.append(("__VIEWSTATE",match.group(1)))		

	match = ev_pat.search(line)
	if match:
		args.append(("__EVENTVALIDATION",match.group(1)))

        match = pv_pat.search(line)
        if match:
                args.append(("__PREVIOUSPAGE",match.group(1)))

	if len(args) >= 3: break
fp.close()

# add remaining POST arguments
args.append(("__EVENTTARGET",""))
args.append(("__EVENTARGUMENT",""))
args.append(("__LASTFOCUS",""))
args.append(("ctl00$PlaceHolderMain$ucLogin$rblCustSupp","0"))
args.append(("ctl00$PlaceHolderMain$ucLogin$txtUserName",USERNAME))
args.append(("ctl00$PlaceHolderMain$ucLogin$txtPassword",PASSWORD))
args.append(("ctl00$PlaceHolderMain$ucLogin$btnLogin","Login"))
args.append(("ctl00$PlaceHolderMain$ucLogin$ddlWarehouses","E-8"))

#print "Logging in"
fp = opener.open(LOGIN_CB_URL,urllib.urlencode(args))
fp.close()

fp = opener.open(INVOICE_URL)
args = []
dates = []
opt_pat = re.compile("option value=\"(.*?)\"")
#print "Getting available invoice dates"
# Again, __VIEWSTATE and __EVENTVALIDATION are required
# Also get available invoice dates from the <select>
for line in fp.readlines():
	match = vs_pat.search(line)
	if match:
		args.append(("__VIEWSTATE",match.group(1)))		

	match = ev_pat.search(line)
	if match:
		args.append(("__EVENTVALIDATION",match.group(1)))

	match = opt_pat.search(line)
	if match and match.group(1) != "0":
		dates.append(match.group(1))
fp.close()

# add remaining POST arguments *except* date
args.append(("__LASTFOCUS",""))
args.append(("__EVENTTARGET","ctl00$PlaceHolderMain$btnSubmit"))
args.append(("__EVENTARGUMENT",""))
args.append(("ctl00$ucProductsMenu$txtProductSearch",""))
args.append(("ctl00$PlaceHolderMain$grp1",TYPES[TYPE]))
args.append(("ctl00$PlaceHolderMain$btnSubmit","Submit"))

# for each available day, add the date to the POST arguments
# and download the file
# For local storage, slashes in the date are converted to
# underscores
date_pat = re.compile("\/")
for date in dates:
	outfile=DL_DIR+"invoice_"+date_pat.sub("_",date)+".zip"
	ofp = open(outfile,'w')
	
	args_with_date = args + [("ctl00$PlaceHolderMain$ddlInvoiceDate",date)]
	#print "Downloading invoice for "+date
	ifp = opener.open(INVOICE_URL,urllib.urlencode(args_with_date))
	for line in ifp.readlines(): ofp.write(line)

	ifp.close()
	ofp.close()
