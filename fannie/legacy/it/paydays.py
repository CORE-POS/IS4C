#!/usr/bin/python

import datetime
import Sybase

paydays = {
	'2011-01-07' : 1,
	'2011-01-21' : 1,
	'2011-02-07' : 1,
	'2011-02-23' : 1,
	'2011-03-07' : 1,
	'2011-03-22' : 1,
	'2011-04-07' : 1,
	'2011-04-22' : 1,
	'2011-05-06' : 1,
	'2011-05-23' : 1,
	'2011-06-07' : 1,
	'2011-06-22' : 1,
	'2011-07-07' : 1,
	'2011-07-22' : 1,
	'2011-08-08' : 1,
	'2011-08-22' : 1,
	'2011-09-08' : 1,
	'2011-09-22' : 1,
	'2011-10-07' : 1,
	'2011-10-21' : 1,
	'2011-11-07' : 1,
	'2011-11-22' : 1,
	'2011-12-07' : 1,
	'2011-12-22' : 1,
	'2012-01-06' : 1,
	'2010-12-22' : 1
}

today = datetime.date.today()

if paydays.has_key(str(today)):
	fp = open('/var/www/html/git/fannie/src/Credentials/GenericDB.python')
	line = fp.read().strip()
	fp.close()
	hostname,username,pw = line.split(",")

	conn = Sybase.connect(hostname,username,pw)
	db = conn.cursor()
	db.execute('use WedgePOS')

	db.execute('INSERT INTO dtransactions SELECT * FROM staffaradjview')
	db.execute("""UPDATE custdata SET balance=balance-s.total
		FROM custdata AS c
		INNER JOIN staffArAdjView AS s
		ON c.cardno=s.card_no""")

	print "Staff IOUs are being cleared"

	conn.commit()
	conn.close()
