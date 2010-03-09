#!/bin/sh

# TODO - Why not import opdata data

# TODO - Better warning about needing to be root
CMD="mysql --defaults-file=/etc/mysql/debian.cnf"

cd /pos/installation/mysql/script
     
${CMD} < create_lane_db.sql

cd /pos/installation/mysql/translog/tables

${CMD} < activities.table
${CMD} < activitylog.table
${CMD} < activitytemplog.table
${CMD} < alog.table
${CMD} < dtransactions.table
${CMD} < localtemptrans.table
${CMD} < localtrans.table
${CMD} < localtransarchive.table
${CMD} < suspended.table

cd /pos/installation/mysql/translog/views

${CMD} < localtranstoday.viw
${CMD} < suspendedtoday.viw
${CMD} < suspendedlist.viw

${CMD} < lttsummary.viw
${CMD} < lttsubtotals.viw
${CMD} < subtotals.viw

${CMD} < ltt_receipt.viw
${CMD} < receipt.viw

${CMD} < rp_ltt_receipt.viw
${CMD} < rp_receipt_header.viw
${CMD} < rp_receipt.viw
${CMD} < rp_list.viw

${CMD} < screendisplay.viw

${CMD} < memdiscountadd.viw
${CMD} < memdiscountremove.viw
${CMD} < staffdiscountadd.viw
${CMD} < staffdiscountremove.viw

${CMD} < memchargetotals.viw

cd /pos/installation/mysql/opdata/tables

${CMD} < chargecode.table
${CMD} < couponcodes.table
${CMD} < custdata.table
${CMD} < departments.table
${CMD} < employees.table
${CMD} < globalvalues.table
${CMD} < products.table
${CMD} < promomsgs.table
${CMD} < tenders.table

#cd ../data

#${CMD} < couponcodes.insert
#${CMD} < custdata.insert
#${CMD} < departments.insert
#${CMD} < employees.insert
#${CMD} < globalvalues.insert
#${CMD} < products.insert
#${CMD} < tenders.insert

cd /pos/installation/mysql/opdata/views

${CMD} < chargecodeview.viw
${CMD} < memchargebalance.viw

cd /pos/installation/mysql/is4c_op/tables

${CMD} < chargecode.table
${CMD} < couponcodes.table
${CMD} < custdata.table
${CMD} < departments.table
${CMD} < employees.table
${CMD} < products.table
${CMD} < tenders.table

cd /pos/installation/mysql/script/

${CMD} < create_lane_acct.sql
