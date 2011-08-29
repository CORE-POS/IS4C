#!/usr/bin/python

import MySQLdb

fp = open('/var/www/html/git/fannie/src/Credentials/ExternalDB.python')
line = fp.read().strip()
fp.close()
hostname,username,pw,dbname = line.split(",")

conn = MySQLdb.connect(hostname,username,pw,dbname,use_unicode=True)
db = conn.cursor()

for i in xrange(1,8,1):
	fetchQ = u"select dayname,menu from delimenu where day="+str(i+7)
	db.execute(fetchQ)
	row = db.fetchone()
	dayname = row[0]
	menu = row[1].replace("'","''")
	
	upQ1 = u"update delimenu set dayname='"+dayname+"',menu='"+menu+"' where day="+str(i)
	upQ2 = u"update delimenu set dayname='',menu='' where day="+str(i+7)	

	db.execute(upQ1)
	db.execute(upQ2)

checkQ = "select menu from delimenu where day=1"
db.execute(checkQ)
row = db.fetchone()
if row[0] == '':
	import smtplib
	server = smtplib.SMTP('localhost')
	toaddr = 'gohanman@gmail.com'
	fromaddr = 'andy@wholefoods.coop'
	subject = 'Deli Menu'
	headers = "From: %s\r\nTo: %s\r\nSubject: %s\r\n\r\n" % (fromaddr,toaddr,subject)	
	server.sendmail(fromaddr,toaddr,headers+'You forgot the deli menu')
