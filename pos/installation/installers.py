

from getpass import getpass
import os.path


def exec_script(connection, script_path):
    cursor = connection.cursor()
    script_path = os.path.join(os.path.dirname(__file__), "mysql", script_path)
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
    'exec_script',
    'get_user_input',
    ]
