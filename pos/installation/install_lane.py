#!/usr/bin/env python


import MySQLdb
import warnings

from installers import *


def install_lane_db(username, password, sample_data=False):
    connection = MySQLdb.connect("localhost", username, password)

    exec_script(connection, "script/create_lane_db.sql")

    warnings.filterwarnings("ignore", "^Unknown table '.*'$")

    exec_script(connection, "translog/tables/activities.table")
    exec_script(connection, "translog/tables/activitylog.table")
    exec_script(connection, "translog/tables/activitytemplog.table")
    exec_script(connection, "translog/tables/alog.table")
    exec_script(connection, "translog/tables/dtransactions.table")
    exec_script(connection, "translog/tables/localtemptrans.table")
    exec_script(connection, "translog/tables/localtrans.table")
    exec_script(connection, "translog/tables/localtransarchive.table")
    exec_script(connection, "translog/tables/suspended.table")

    exec_script(connection, "translog/views/localtranstoday.viw")
    exec_script(connection, "translog/views/suspendedtoday.viw")
    exec_script(connection, "translog/views/suspendedlist.viw")
    exec_script(connection, "translog/views/lttsummary.viw")
    exec_script(connection, "translog/views/lttsubtotals.viw")
    exec_script(connection, "translog/views/subtotals.viw")
    exec_script(connection, "translog/views/ltt_receipt.viw")
    exec_script(connection, "translog/views/receipt.viw")
    exec_script(connection, "translog/views/rp_ltt_receipt.viw")
    exec_script(connection, "translog/views/rp_receipt_header.viw")
    exec_script(connection, "translog/views/rp_receipt.viw")
    exec_script(connection, "translog/views/rp_list.viw")
    exec_script(connection, "translog/views/screendisplay.viw")
    exec_script(connection, "translog/views/memdiscountadd.viw")
    exec_script(connection, "translog/views/memdiscountremove.viw")
    exec_script(connection, "translog/views/staffdiscountadd.viw")
    exec_script(connection, "translog/views/staffdiscountremove.viw")
    exec_script(connection, "translog/views/memchargetotals.viw")
    
    exec_script(connection, "opdata/tables/chargecode.table")
    exec_script(connection, "opdata/tables/couponcodes.table")
    exec_script(connection, "opdata/tables/custdata.table")
    exec_script(connection, "opdata/tables/departments.table")
    exec_script(connection, "opdata/tables/employees.table")
    exec_script(connection, "opdata/tables/globalvalues.table")
    exec_script(connection, "opdata/tables/products.table")
    exec_script(connection, "opdata/tables/promomsgs.table")
    exec_script(connection, "opdata/tables/tenders.table")
    
    if sample_data:
        warnings.filterwarnings("ignore", "^Data too long for column 'description' at row \d+$")

        exec_script(connection, "opdata/data/couponcodes.insert")
        exec_script(connection, "opdata/data/custdata.insert")
        exec_script(connection, "opdata/data/departments.insert")
        exec_script(connection, "opdata/data/employees.insert")
        exec_script(connection, "opdata/data/globalvalues.insert")
        exec_script(connection, "opdata/data/products.insert")
        exec_script(connection, "opdata/data/tenders.insert")

    exec_script(connection, "opdata/views/chargecodeview.viw")
    exec_script(connection, "opdata/views/memchargebalance.viw")
        
    warnings.resetwarnings()

    exec_script(connection, "script/create_lane_acct.sql")


if __name__ == "__main__":
    user_input = get_user_input()
    if user_input:
        install_lane_db(*user_input)
        print "Done"
