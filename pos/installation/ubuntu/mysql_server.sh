#!/bin/sh

# TODO - Better warning about needing to be root
CMD="mysql --defaults-file=/etc/mysql/debian.cnf"

cd /pos/installation/mysql/script

${CMD} < create_server_db.sql

cd /pos/installation/mysql/is4c_log/tables/

${CMD} < activitylog.table
${CMD} < batchMerges.table
${CMD} < dtransactions.table
${CMD} < productsLog.table
${CMD} < suspended.table
${CMD} < synchronizationLog.table


cd /pos/installation/mysql/is4c_log/views/

${CMD} < dlog.viw
${CMD} < tendertape.viw
${CMD} < buspasstotals.viw
${CMD} < cctenders.viw
${CMD} < cctendertotal.viw
${CMD} < cktenders.viw
${CMD} < cktendertotal.viw
${CMD} < dctenders.viw
${CMD} < dctendertotal.viw
${CMD} < memchargebalance.viw
${CMD} < memchargetotals.viw
${CMD} < mitenders.viw
${CMD} < mitendertotal.viw
${CMD} < suspendedtoday.viw

cd /pos/installation/mysql/is4c_op/tables/

${CMD} < batchHeaders.table
${CMD} < batchProducts.table
${CMD} < batchTypes.table
${CMD} < chargecode.table
${CMD} < couponcodes.table
${CMD} < custdata.table
${CMD} < departments.table
${CMD} < employees.table
${CMD} < error_log.table
${CMD} < globalvalues.table
${CMD} < likecodes.table
${CMD} < meminfo.table
${CMD} < memtype.table
${CMD} < newMembers.table
${CMD} < products.table
${CMD} < prodUpdate.table
${CMD} < promomsgs.table
${CMD} < subdepts.table
${CMD} < tenders.table
${CMD} < UNFI.table
${CMD} < upclike.table

cd /pos/installation/mysql/is4c_op/views/

${CMD} < chargecodeview.viw
${CMD} < memchargebalance.viw
${CMD} < subdeptIndex.viw
${CMD} < volunteerDiscounts.viw

cd /pos/installation/mysql/is4c_op/data/

${CMD} < batchTypes.insert
${CMD} < custdata.insert
${CMD} < departments.insert
${CMD} < employees.insert
${CMD} < globalvalues.insert
${CMD} < memtype.insert
${CMD} < products.insert
${CMD} < subdepts.insert
${CMD} < tenders.insert

cd /pos/installation/mysql/script/

${CMD} < create_server_acct.sql
