

from getpass import getpass
import os
from glob import glob


def abspath(path):
    return os.path.abspath(os.path.join(os.path.dirname(__file__), 'mysql', path))


def exec_script(connection, script_path, absolute=False):
    cursor = connection.cursor()
    if not absolute:
        script_path = abspath(script_path)
    sql = ""
    script_file = open(script_path)
    for line in script_file:
        sql += " " + line.strip()
        if sql.endswith(";"):
            cursor.execute(sql)
            sql = ""
    script_file.close()
    if sql.strip():
        cursor.execute(sql)
    cursor.close()


def exec_scripts(connection, script_pattern, first_paths=[], ignore_paths=[]):
    for i in range(len(ignore_paths)):
        ignore_paths[i] = abspath(ignore_paths[i])
        
    for script_path in first_paths:
        script_path = abspath(script_path)
        exec_script(connection, script_path)
        ignore_paths.append(script_path)

    script_pattern = abspath(script_pattern)
    for script_path in sorted(glob(script_pattern)):
        if script_path not in ignore_paths:
            exec_script(connection, script_path, absolute=True)


def get_user_input():
    try:
        username = raw_input("MySQL user account [default root]: ")
    except KeyboardInterrupt:
        return None
    username = username.strip() or "root"

    try:
        password = getpass("MySQL password for %s@localhost [default (none)]: " % username)
    except KeyboardInterrupt:
        return None
    password = password.strip()

    try:
        sample_data = raw_input("Install sample data (Y/N)? [default Y]: ")
    except KeyboardInterrupt:
        return None
    sample_data = not sample_data.strip().upper().startswith('N')

    return username, password, sample_data


__all__ = [
    'abspath',
    'exec_script',
    'exec_scripts',
    'get_user_input',
    ]
