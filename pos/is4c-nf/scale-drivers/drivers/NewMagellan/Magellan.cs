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

    private bool mq_enabled = false;

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
        FinishInit();

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

    // alternate constructor for specifying
    // desired modules at compile-time
    public Magellan(SerialPortHandler[] args)
    {
        this.sph = new List<SerialPortHandler>();
        foreach (SerialPortHandler s in args) {
            sph.Add(s);
        }
        FinishInit();
    }

    private void FinishInit()
    {
        MonitorSerialPorts();

        u = new UDPMsgBox(9450);
        u.SetParent(this);
        u.My_Thread.Start();
    }

    private void MonitorSerialPorts()
    {
        foreach (SerialPortHandler s in sph) {
            if (s == null) continue;
            s.SPH_Thread.Start();
        }
    }

    public void MsgRecv(string msg)
    {
        if (msg == "exit") {
            this.ShutDown();
        } else if (msg == "mq_up" && mq_available) {
            mq_enabled = true;
        } else if (msg == "mq_down") {
            mq_enabled = false;
        } else {
            sph.ForEach(s => { s.HandleMsg(msg); });
        }
    }

    public void MsgSend(string msg)
    {
        if (mq_available && mq_enabled) {
            #if CORE_RABBIT
            byte[] body = System.Text.Encoding.UTF8.GetBytes(msg);
            rabbit_channel.BasicPublish("", "core-pos", null, body);
            #endif
        } else {
            int ticks = Environment.TickCount;
            string my_location = AppDomain.CurrentDomain.BaseDirectory;
            char sep = Path.DirectorySeparatorChar;
            while (File.Exists(my_location + sep + "ss-output/"  + sep + ticks)) {
                ticks++;
            }

            TextWriter sw = new StreamWriter(my_location + sep + "ss-output/" +sep+"tmp"+sep+ticks);
            sw = TextWriter.Synchronized(sw);
            sw.WriteLine(msg);
            sw.Close();
            File.Move(my_location+sep+"ss-output/" +sep+"tmp"+sep+ticks,
                  my_location+sep+"ss-output/" +sep+ticks);
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

        try {
            string ini_json = File.ReadAllText(ini_file);
            JObject o = JObject.Parse(ini_json);
            foreach (var port in o["NewMagellanPorts"]) {
                if (port["port"] == null) {
                    Console.WriteLine("Missing the \"port\" setting. JSON:");
                    Console.WriteLine(port);
                } else if (port["module"] == null) {
                    Console.WriteLine("Missing the \"module\" setting. JSON:");
                    Console.WriteLine(port);
                } else {
		    var pair = new MagellanConfigPair();
		    pair.port = (string)port["port"];
		    pair.module = (string)port["module"];
		    conf.Add(pair);
                }
            }
        } catch (NullReferenceException) {
            // probably means now NewMagellanPorts key in ini.json
            // not a fatal problem
        } catch (Exception ex) {
            // unexpected exception
            Console.WriteLine(ex);
        }

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
        Magellan m = new Magellan(verbosity);
        bool exiting = false;
        while (!exiting) {
            string user_in = Console.ReadLine();
            if (user_in == "exit") {
                Console.WriteLine("stopping");
                m.ShutDown();
                exiting = true;
            }
        }
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
