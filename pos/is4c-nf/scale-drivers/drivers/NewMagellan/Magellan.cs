/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/*************************************************************
 * Magellan
 *     Main app. Starts all requested Serial Port Handlers
 * and monitors UDP for messages
 *
 * Note that exit won't work cleanly if a SerialPortHandler
 * blocks indefinitely. Use timeouts in polling reads.
*************************************************************/
using System;
using System.Threading;
using System.IO;
using System.Collections.Generic;
using System.Linq;
using System.Net.Sockets;

#if NEWTONSOFT_JSON
using Newtonsoft.Json;
using Newtonsoft.Json.Linq;
#endif

#if CORE_RABBIT
using RabbitMQ.Client;
#endif

using CustomForms;
using CustomUDP;
using SPH;
using Discover;

public class Magellan : DelegateForm 
{
    private List<SerialPortHandler> sph;
    private UDPMsgBox u;
    private bool asyncUDP = true;
    private bool disableRBA = false;
    private bool disableButtons = false;
    private Object msgLock = new Object();
    private ushort msgCount = 0;

    private bool mq_enabled = false;
    private bool full_udp = false;

    #if CORE_RABBIT
    private bool mq_available = true;
    ConnectionFactory rabbit_factory;
    IConnection rabbit_con;
    IModel rabbit_channel;
    #else
    private bool mq_available = false;
    #endif

    // read deisred modules from config file
    public Magellan(int verbosity)
    {
        var d = new Discover.Discover();
        var modules = d.GetSubClasses("SPH.SerialPortHandler");

        List<MagellanConfigPair> conf = ReadConfig();
        sph = new List<SerialPortHandler>();
        foreach (var pair in conf) {
            try {
                if (modules.Any(m => m.Name == pair.module)) {
                    var type = d.GetType("SPH." + pair.module);
                    Console.WriteLine(pair.module + ":" + pair.port);
                    SerialPortHandler s = (SerialPortHandler)Activator.CreateInstance(type, new Object[]{ pair.port });
                    s.SetParent(this);
                    s.SetVerbose(verbosity);
                    s.SetConfig("disableRBA", this.disableRBA ? "true" : "false");
                    s.SetConfig("disableButtons", this.disableButtons ? "true" : "false");
                    sph.Add(s);
                } else {
                    throw new Exception("unknown module: " + pair.module);
                }
            } catch (Exception ex) {
                Console.WriteLine(ex);
                Console.WriteLine("Warning: could not initialize "+pair.port);
                Console.WriteLine("Ensure the device is connected and you have permission to access it.");
            }
        }
        MonitorSerialPorts();
        UdpListen();

        factorRabbits();
    }

    // alternate constructor for specifying
    // desired modules at compile-time
    public Magellan(SerialPortHandler[] args)
    {
        this.sph = new List<SerialPortHandler>(args);
        MonitorSerialPorts();
        UdpListen();
    }

    private void factorRabbits()
    {
        #if CORE_RABBIT
        try {
            rabbit_factory = new ConnectionFactory();
            rabbit_factory.HostName = "localhost";
            rabbit_con = rabbit_factory.CreateConnection();
            rabbit_channel = rabbit_con.CreateModel();
            rabbit_channel.QueueDeclare("core-pos", false, false, false, null);
        } catch (Exception) {
            mq_available = false;
        }
        #endif
    }


    private UdpClient udp_client = null;
    private UdpClient getClient()
    {
        if (udp_client == null) {
            udp_client = new UdpClient();
            udp_client.Connect(System.Net.IPAddress.Parse("127.0.0.1"), 9451);
        }

        return udp_client;
    }

    private void UdpListen()
    {
        u = new UDPMsgBox(9450, this.asyncUDP);
        u.SetParent(this);
        u.My_Thread.Start();
    }

    private void MonitorSerialPorts()
    {
        var valid = sph.Where(s => s != null);
        valid.ToList().ForEach(s => { s.SPH_Thread.Start(); });
    }

    public void MsgRecv(string msg)
    {
        if (msg == "exit") {
            this.ShutDown();
        } else if (msg == "full_udp") {
            full_udp = true;
        } else if (msg == "mq_up" && mq_available) {
            mq_enabled = true;
        } else if (msg == "mq_down") {
            mq_enabled = false;
        } else if (msg == "status") {
            byte[] body = System.Text.Encoding.ASCII.GetBytes(Status());
            getClient().Send(body, body.Length); 
        } else {
            sph.ForEach(s => { s.HandleMsg(msg); });
        }
    }

    private string Status()
    {
        string ret = "";
        foreach (var s in sph) {
            ret += s.Status() + "\n";
        }

        return ret;
    }

    public void MsgSend(string msg)
    {
        if (full_udp) {
            byte[] body = System.Text.Encoding.UTF8.GetBytes(msg);
            getClient().Send(body, body.Length); 
        } else if (mq_available && mq_enabled) {
            #if CORE_RABBIT
            byte[] body = System.Text.Encoding.UTF8.GetBytes(msg);
            rabbit_channel.BasicPublish("", "core-pos", null, body);
            #endif
        } else {
            lock (msgLock) {
                string filename = System.Guid.NewGuid().ToString();
                string my_location = AppDomain.CurrentDomain.BaseDirectory;
                char sep = Path.DirectorySeparatorChar;
                /**
                  Depending on msg rate I may replace "1" with a bigger value
                  as long as the counter resets at least once per 65k messages
                  there shouldn't be sequence issues. But real world disk I/O
                  may be trivial with a serial message source
                */
                if (msgCount % 1 == 0 && Directory.GetFiles(my_location+sep+"ss-output/").Length == 0) {
                    msgCount = 0;
                }
                filename = msgCount.ToString("D5") + filename;
                msgCount++;

                TextWriter sw = new StreamWriter(my_location + sep + "ss-output/" +sep+"tmp"+sep+filename);
                sw = TextWriter.Synchronized(sw);
                sw.WriteLine(msg);
                sw.Close();
                File.Move(my_location+sep+"ss-output/" +sep+"tmp"+sep+filename,
                      my_location+sep+"ss-output/" +sep+filename);
            }
        }
    }

    public void ShutDown()
    {
        try {
            sph.ForEach(s => { s.Stop(); });
            u.Stop();
        }
        catch(Exception ex) {
            Console.WriteLine(ex);
        }
    }

    #if NEWTONSOFT_JSON
    private List<MagellanConfigPair> JsonConfig()
    {
        string my_location = AppDomain.CurrentDomain.BaseDirectory;
        char sep = Path.DirectorySeparatorChar;
        string ini_file = my_location + sep + ".." + sep + ".." + sep + ".." + sep + "ini.json";
        List<MagellanConfigPair> conf = new List<MagellanConfigPair>();
        if (!File.Exists(ini_file)) {
            return conf;
        }

        string ini_json = File.ReadAllText(ini_file);
        try {
            JObject o = JObject.Parse(ini_json);
            // filter list to valid entries
            var valid = o["NewMagellanPorts"].Where(p=> p["port"] != null && p["module"] != null);
            // map entries to ConfigPair objects
            var pairs = valid.Select(p => new MagellanConfigPair(){port=(string)p["port"], module=(string)p["module"]});
            conf = pairs.ToList();

            // print errors for invalid entries
            o["NewMagellanPorts"].Where(p => p["port"] == null).ToList().ForEach(p => {
                Console.WriteLine("Missing the \"port\" setting. JSON:");
                Console.WriteLine(p);
            });

            // print errors for invalid entries
            o["NewMagellanPorts"].Where(p => p["module"] == null).ToList().ForEach(p => {
                Console.WriteLine("Missing the \"module\" setting. JSON:");
                Console.WriteLine(p);
            });
        } catch (NullReferenceException) {
            // probably means no NewMagellanPorts key in ini.json
            // not a fatal problem
        } catch (Exception ex) {
            // unexpected exception
            Console.WriteLine(ex);
        }
        try {
            JObject o = JObject.Parse(ini_json);
            var ua = (bool)o["asyncUDP"];
            this.asyncUDP = ua;
        } catch (Exception) {}
        try {
            JObject o = JObject.Parse(ini_json);
            var drb = (bool)o["disableRBA"];
            this.disableRBA = drb;
        } catch (Exception) {}
        try {
            JObject o = JObject.Parse(ini_json);
            var dbt = (bool)o["disableButtons"];
            this.disableButtons = dbt;
        } catch (Exception) {}

        return conf;
    }
    #endif

    private List<MagellanConfigPair> ReadConfig()
    {
        /**
         * Look for settings in ini.json if it exists
         * and the library DLL exists
         */
        #if NEWTONSOFT_JSON
        List<MagellanConfigPair> json_ports = JsonConfig();
        if (json_ports.Count > 0) {
            return json_ports;
        }
        #endif

        string my_location = AppDomain.CurrentDomain.BaseDirectory;
        char sep = Path.DirectorySeparatorChar;
        StreamReader fp = new StreamReader(my_location + sep + "ports.conf");
        List<MagellanConfigPair> conf = new List<MagellanConfigPair>();
        HashSet<string> hs = new HashSet<string>();
        string line;
        while( (line = fp.ReadLine()) != null) {
            line = line.TrimStart(null);
            if (line == "" || line[0] == '#') continue;
            string[] pieces = line.Split(null);
            if (pieces.Length != 2) {
                Console.WriteLine("Warning: malformed port.conf line: "+line);
                Console.WriteLine("Format: <port_string> <handler_class_name>");
            } else if (hs.Contains(pieces[0])) {
                Console.WriteLine("Warning: device already has a module attached.");
                Console.WriteLine("Line will be ignored: "+line);
            } else {
	        var pair = new MagellanConfigPair();
                pair.port = pieces[0];
                pair.module = pieces[1];
                conf.Add(pair);
                hs.Add(pieces[0]);
            }
        }    

        return conf;
    }

    static public void Main(string[] args)
    {
        int verbosity = 0;
        for (int i=0;i<args.Length;i++){
            if (args[i] == "-v"){
                verbosity = 1;    
                if (i+1 < args.Length){
                    try { verbosity = Int32.Parse(args[i+1]); }
                    catch{}
                }
            }
        }
        new Magellan(verbosity);
        Thread.Sleep(Timeout.Infinite);
    }
}

/**
 Helper class representing a config setting
*/
public class MagellanConfigPair
{
    public string port { get; set; }
    public string module { get; set; }
}
