#!/usr/bin/env python


import warnings

from installers import *


def install_server_db(username, password, sample_data=False):
    import MySQLdb
    connection = MySQLdb.connect("localhost", username, password)

    exec_script(connection, "script/create_server_db.sql")

    warnings.filterwarnings("ignore", "^Unknown table '.*'$")

    exec_scripts(connection, 'is4c_log/tables/*.table')
    exec_scripts(connection, 'is4c_log/views/*.viw', first_paths=[
            'is4c_log/views/dlog.viw',
            'is4c_log/views/tendertape.viw',
            ])

    exec_scripts(connection, 'is4c_op/tables/*.table')
    exec_scripts(connection, 'is4c_op/views/*.viw')

    if sample_data:
        warnings.filterwarnings("ignore", "^Data (?:too long|truncated) for column '(?:description|subdept_name)' at row \d+$")
        exec_scripts(connection, 'is4c_op/data/*.insert')

    warnings.resetwarnings()

    exec_script(connection, "script/create_server_acct.sql")

    remove_bind_restriction_prompt()


if __name__ == "__main__":
    user_input = get_user_input()
    if user_input:
        install_server_db(*user_input)
        print "Done"
