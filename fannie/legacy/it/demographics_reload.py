#!/usr/bin/python

import datetime
import Sybase

today = datetime.date.today()

fp = open('../../src/Credentials/GenericDB.python')
line = fp.read().strip()
fp.close()
hostname,username,pw = line.split(",")

conn = Sybase.connect(hostname,username,pw)
db = conn.cursor()
db.execute('use WedgePOS')

dlog = "("
curmonth = today.month
curyear = today.year
for i in xrange(12):
	curmonth -= 1
	if curmonth == 0:
		curyear -= 1
		curmonth = 12
	dlog += "select * from trans_archive.dbo.dlog"+str(curyear)+str(curmonth).zfill(2)
	if i < 11:
		dlog += " union all "
dlog += ")"

query = """INSERT INTO YTD_Patronage_Speedup 
	select d.card_no,datepart(mm,d.tdate) as month_no,
	sum(CASE WHEN d.trans_type='T' THEN d.total ELSE 0 END) as total
	from """+dlog+""" as d
	LEFT JOIN custdata as c on c.cardno=d.card_no and c.personnum=1 
	LEFT JOIN suspensions as s on s.cardno = d.card_no 
	WHERE c.memtype=1 or s.memtype1=1 
	GROUP BY d.card_no,
	datepart(yy,d.tdate), datepart(mm,d.tdate),datepart(dd,d.tdate),d.trans_num"""

db.execute("TRUNCATE TABLE YTD_Patronage_Speedup")
db.execute(query)

conn.commit()

dlog = "("
curmonth = today.month
curyear = today.year - 1
for i in xrange(12):
	curmonth -= 1
	if curmonth == 0:
		curyear -= 1
		curmonth = 12
	dlog += "select * from trans_archive.dbo.dlog"+str(curyear)+str(curmonth).zfill(2)
	if i < 11:
		dlog += " union all "
dlog += ")"

query = """INSERT INTO YTD_Patronage_Speedup_Previous 
	select d.card_no,datepart(mm,d.tdate) as month_no,
	sum(CASE WHEN d.trans_type='T' THEN d.total ELSE 0 END) as total
	from """+dlog+""" as d
	LEFT JOIN custdata as c on c.cardno=d.card_no and c.personnum=1 
	LEFT JOIN suspensions as s on s.cardno = d.card_no 
	WHERE c.memtype=1 or s.memtype1=1 
	GROUP BY d.card_no,
	datepart(yy,d.tdate), datepart(mm,d.tdate),datepart(dd,d.tdate),d.trans_num"""

db.execute("TRUNCATE TABLE YTD_Patronage_Speedup_Previous")
db.execute(query)

conn.commit()
conn.close()
