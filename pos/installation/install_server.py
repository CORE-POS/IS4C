#!/usr/bin/env python


import MySQLdb
import warnings

from installers import *


def install_server_db(username, password, sample_data=False):
    connection = MySQLdb.connect("localhost", username, password)

    exec_script(connection, "script/create_server_db.sql")

    warnings.filterwarnings("ignore", "^Unknown table '.*'$")

    exec_script(connection, "is4c_log/tables/activitylog.table")
    exec_script(connection, "is4c_log/tables/dtransactions.table")
    exec_script(connection, "is4c_log/tables/suspended.table")

    exec_script(connection, "is4c_log/views/dlog.viw")
    exec_script(connection, "is4c_log/views/tendertape.viw")
    exec_script(connection, "is4c_log/views/buspasstotals.viw")
    exec_script(connection, "is4c_log/views/cctenders.viw" )
    exec_script(connection, "is4c_log/views/cctendertotal.viw")
    exec_script(connection, "is4c_log/views/cktenders.viw")
    exec_script(connection, "is4c_log/views/cktendertotal.viw")
    exec_script(connection, "is4c_log/views/dctenders.viw")
    exec_script(connection, "is4c_log/views/dctendertotal.viw")
    exec_script(connection, "is4c_log/views/memchargebalance.viw")
    exec_script(connection, "is4c_log/views/memchargetotals.viw")
    exec_script(connection, "is4c_log/views/mitenders.viw")
    exec_script(connection, "is4c_log/views/mitendertotal.viw")
    exec_script(connection, "is4c_log/views/suspendedtoday.viw")

    exec_script(connection, "is4c_op/tables/chargecode.table")
    exec_script(connection, "is4c_op/tables/couponcodes.table")
    exec_script(connection, "is4c_op/tables/custdata.table")
    exec_script(connection, "is4c_op/tables/departments.table")
    exec_script(connection, "is4c_op/tables/employees.table")
    exec_script(connection, "is4c_op/tables/error_log.table")
    exec_script(connection, "is4c_op/tables/globalvalues.table")
    exec_script(connection, "is4c_op/tables/likecodes.table")
    exec_script(connection, "is4c_op/tables/meminfo.table")
    exec_script(connection, "is4c_op/tables/memtype.table")
    exec_script(connection, "is4c_op/tables/newMembers.table")
    exec_script(connection, "is4c_op/tables/products.table")
    exec_script(connection, "is4c_op/tables/prodUpdate.table")
    exec_script(connection, "is4c_op/tables/promomsgs.table")
    exec_script(connection, "is4c_op/tables/subdepts.table")
    exec_script(connection, "is4c_op/tables/tenders.table")
    exec_script(connection, "is4c_op/tables/UNFI.table")
    exec_script(connection, "is4c_op/tables/upclike.table")

    exec_script(connection, "is4c_op/views/chargecodeview.viw")
    exec_script(connection, "is4c_op/views/memchargebalance.viw")
    exec_script(connection, "is4c_op/views/subdeptIndex.viw")
    exec_script(connection, "is4c_op/views/volunteerDiscounts.viw")

    if sample_data:
        warnings.filterwarnings("ignore", "^Data too long for column '(?:description|subdept_name)' at row \d+$")

        exec_script(connection, "is4c_op/data/custdata.insert")
        exec_script(connection, "is4c_op/data/departments.insert")
        exec_script(connection, "is4c_op/data/employees.insert")
        exec_script(connection, "is4c_op/data/globalvalues.insert")
        exec_script(connection, "is4c_op/data/memtype.insert")
        exec_script(connection, "is4c_op/data/products.insert")
        exec_script(connection, "is4c_op/data/subdepts.insert")
        exec_script(connection, "is4c_op/data/tenders.insert")

    warnings.resetwarnings()

    exec_script(connection, "script/create_server_acct.sql")


if __name__ == "__main__":
    user_input = get_user_input()
    if user_input:
        install_server_db(*user_input)
        print "Done"
