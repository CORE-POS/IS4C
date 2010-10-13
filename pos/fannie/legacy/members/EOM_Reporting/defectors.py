#!/usr/bin/python

import datetime

today = datetime.datetime.today()
year = today.year
month = today.month

dlog = "("
for i in xrange(5):
	month -= 1
	if month == 0:
		month = 12
		year -= 1
	dlog += "SELECT * FROM dlog_archive.dbo.dlog_%d_%s" % (year, str(month).zfill(2))
	if i != 4: dlog += " UNION ALL "
	else: dlog += ")"


query = """
INSERT INTO Defectors 
select d.card_no,'DEFECTOR',getdate(),
sum(case when datediff(mm,getdate(),tdate)=-5
	and trans_type in ('D','I') then d.total else 0 end) as month1,
sum(case when datediff(mm,getdate(),tdate)=-4
	and trans_type in ('D','I') then d.total else 0 end) as month2,
sum(case when datediff(mm,getdate(),tdate)=-3
	and trans_type in ('D','I') then d.total else 0 end) as month3
from
%s as d 
LEFT JOIN DefectionNotices as n
on d.card_no = n.card_no
LEFT JOIN custdata as c
on d.card_no=c.cardno and c.personnum=1
where noticesThisYear <= 1 and noticesThisQuarter = 0
and c.type <> 'TERM' and c.memType not in (3,9)
group by d.card_no
having 
sum(case when datediff(mm,getdate(),tdate)=-5 then 1 else 0 end) <> 0 and
sum(case when datediff(mm,getdate(),tdate)=-4 then 1 else 0 end) <> 0 and
sum(case when datediff(mm,getdate(),tdate)=-3 then 1 else 0 end) <> 0 and
sum(case when datediff(mm,getdate(),tdate)=-2 then 1 else 0 end) = 0 and
sum(case when datediff(mm,getdate(),tdate)=-1 then 1 else 0 end) = 0""" % dlog

print query

import Sybase
conn = Sybase.connect()
db = conn.cursor()
db.execute("use WedgePOS")
db.execute(query)
conn.commit()
