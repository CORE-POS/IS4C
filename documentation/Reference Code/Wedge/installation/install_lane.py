#!/usr/bin/env python


import warnings

from installers import *


def install_lane_db(username, password, sample_data=False):
    import MySQLdb
    connection = MySQLdb.connect("localhost", username, password)

    exec_script(connection, "script/create_lane_db.sql")

    warnings.filterwarnings("ignore", "^Unknown table '.*'$")

    exec_scripts(connection, 'translog/tables/*.table')
    exec_scripts(connection, 'translog/views/*.viw', first_paths=[
            'translog/views/lttsummary.viw',
            ])
    
    exec_scripts(connection, 'opdata/tables/*.table')
    
    if sample_data:
        warnings.filterwarnings("ignore", "^Data (?:too long|truncated) for column 'description' at row \d+$")
        exec_scripts(connection, 'opdata/data/*.insert', ignore_paths=[
                'opdata/data/subdepts.insert',
                ])

    exec_scripts(connection, 'opdata/views/*.viw')
        
    warnings.resetwarnings()

    exec_script(connection, "script/create_lane_acct.sql")

    remove_bind_restriction_prompt()


if __name__ == "__main__":
    user_input = get_user_input()
    if user_input:
        install_lane_db(*user_input)
        print "Done"
