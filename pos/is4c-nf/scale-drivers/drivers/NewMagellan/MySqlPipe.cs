using System;
using System.Reflection;

using MySql.Data;
using MySql.Data.MySqlClient;

[assembly: AssemblyVersion("1.0.*")]

namespace MySqlPipe {

public class MySqlPipe {

    private string connStr;

    public MySqlPipe(string h, string u, string p, string d) {
        this.connStr = "server=" + h + ";user=" + u + ";password=" + p + ";database=" + d + ";";
    }

    public void logValue(string key, string val) {
        var con = this.getConnection();
        var cmd = new MySqlCommand("INSERT INTO MagellanLog (tdate, entryKey, entry) VALUES (NOW(), @key, @entry)", con);
        cmd.Parameters.AddWithValue("@key", key);
        cmd.Parameters.AddWithValue("@entry", val);
        cmd.ExecuteNonQuery();
        con.Close();
    }

    private MySqlConnection getConnection() {
        var con = new MySqlConnection(this.connStr);
        con.Open();

        return con;
    }
}

}

