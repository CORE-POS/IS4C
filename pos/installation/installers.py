

from getpass import getpass
import os
from glob import glob
import sys
import re


bind_pattern = re.compile(r'^\s*bind-address\s*=\s*(\S+)\s*$')


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
        import MySQLdb
    except ImportError:
        print "Unable to import MySQLdb.  You might try installing it with the command:"
        print ""
        print "  sudo easy_install MySQL-Python"
        print ""
        return None

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


def remove_bind_restriction_prompt():
    if not sys.platform.startswith('linux'):
        return

    conf_path = '/etc/mysql/my.cnf'
    if not os.path.exists(conf_path):
        return

    bound_to = None
    conf_file = open(conf_path)
    for line in conf_file:
        match = bind_pattern.match(line)
        if match:
            # Don't break here, since MySQL uses the last occurrence of
            # bind-address when determining its own configuration...
            bound_to = match.group(1)
    conf_file.close()
    if bound_to is None:
        return

    print ""
    print "According to the config file at: %s" % conf_path
    print "your MySQL server is currently bound to: %s." % bound_to
    print ""
    try:
        remove_bind = raw_input("Would you like me to unbind it (Y/N)? [default N]: ")
    except KeyboardInterrupt:
        return
    if not remove_bind.strip().upper().startswith("Y"):
        return

    remove_bind_restriction(conf_path)


def remove_bind_restriction(conf_path):
    conf_path_old = conf_path + '.is4c_backup'
    try:
        os.rename(conf_path, conf_path_old)
    except OSError, error:
        print error
        return

    conf_file_old = open(conf_path_old)
    conf_file = open(conf_path, 'w')
    for line in conf_file_old:
        line = line.strip()
        if bind_pattern.match(line):
            line = '# ' + line
        print >> conf_file, line
    conf_file.close()
    conf_file_old.close()

    import subprocess
    try:
        subprocess.call(['/etc/init.d/mysql', 'restart'])
    except OSError, error:
        print "I modified the config file, but couldn't restart the MySQL server:"
        print error


__all__ = [
    'abspath',
    'exec_script',
    'exec_scripts',
    'get_user_input',
    'remove_bind_restriction_prompt',
    ]
