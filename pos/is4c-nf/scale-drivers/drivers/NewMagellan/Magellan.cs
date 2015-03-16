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
using System.Collections;

#if NEWTONSOFT_JSON
using System.Linq;
using Newtonsoft.Json;
using Newtonsoft.Json.Linq;
#endif

#if CORE_RABBIT
using RabbitMQ.Client;
#endif

using CustomForms;
using CustomUDP;
using SPH;

public class Magellan : DelegateForm {

    private SerialPortHandler[] sph;
    private UDPMsgBox u;

    #if CORE_RABBIT
    ConnectionFactory rabbit_factory;
    IConnection rabbit_con;
    IModel rabbit_channel;
    #endif

    // read deisred modules from config file
    public Magellan(int verbosity)
    {
        ArrayList conf = ReadConfig();
        sph = new SerialPortHandler[conf.Count];
        for (int i = 0; i < conf.Count; i++) {
            string port = ((string[])conf[i])[0];
            string module = ((string[])conf[i])[1];
            try {
                switch (module) {
                    case "SPH_Magellan_Scale":
                        sph[i] = new SPH_Magellan_Scale(port);
                        break;
                    case "SPH_SignAndPay_USB":
                        sph[i] = new SPH_SignAndPay_USB(port);
                        break;
                    case "SPH_IngenicoRBA_RS232":
                        sph[i] = new SPH_IngenicoRBA_RS232(port);
                        break;
                    case "SPH_IngenicoRBA_IP":
                        sph[i] = new SPH_IngenicoRBA_IP(port);
                        break;
                    case "SPH_IngenicoRBA_USB":
                        sph[i] = new SPH_IngenicoRBA_USB(port);
                        break;
                    case "SPH_Parallel_Writer":
                        sph[i] = new SPH_Parallel_Writer(port);
                        break;
                    /* Windows only; needs different handing
                    case "SPH_Datacap_PDCX":
                        sph[i] = new SPH_Datacap_PDCX(port);
                        break;
                    */
                    default:
                        throw new Exception("unknown module: " + module);
                }

                sph[i].SetParent(this);
                sph[i].SetVerbose(verbosity);
            } catch (Exception ex) {
                System.Console.WriteLine(ex);
                System.Console.WriteLine("Warning: could not initialize "+port);
                System.Console.WriteLine("Ensure the device is connected and you have permission to access it.");
                sph[i] = null;
            }
        }
        FinishInit();

        #if CORE_RABBIT
        rabbit_factory = new ConnectionFactory();
        rabbit_factory.HostName = "localhost";
        rabbit_con = rabbit_factory.CreateConnection();
        rabbit_channel = rabbit_con.CreateModel();
        rabbit_channel.QueueDeclare("core-pos", false, false, false, null);
        #endif
    }

    // alternate constructor for specifying
    // desired modules at compile-time
    public Magellan(SerialPortHandler[] args)
    {
        this.sph = args;
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
        foreach(SerialPortHandler s in sph){
            if (s == null) continue;
            s.SPH_Thread.Start();
        }
    }

    public override void MsgRecv(string msg)
    {
        if (msg == "exit"){
            this.ShutDown();
        } else {
            foreach(SerialPortHandler s in sph){
                s.HandleMsg(msg);
            }
        }
    }

    public override void MsgSend(string msg)
    {
        int ticks = Environment.TickCount;
        string my_location = AppDomain.CurrentDomain.BaseDirectory;
        char sep = System.IO.Path.DirectorySeparatorChar;
        while (File.Exists(my_location + sep + "ss-output/"  + sep + ticks)) {
            ticks++;
        }

        TextWriter sw = new StreamWriter(my_location + sep + "ss-output/" +sep+"tmp"+sep+ticks);
        sw = TextWriter.Synchronized(sw);
        sw.WriteLine(msg);
        sw.Close();
        File.Move(my_location+sep+"ss-output/" +sep+"tmp"+sep+ticks,
              my_location+sep+"ss-output/" +sep+ticks);

        #if CORE_RABBIT
        byte[] body = System.Text.Encoding.UTF8.GetBytes(msg);
        rabbit_channel.BasicPublish("", "core-pos", null, body);
        #endif
    }

    public void ShutDown()
    {
        try {
            foreach(SerialPortHandler s in sph){
                s.Stop();
            }
            u.Stop();
        }
        catch(Exception ex){
            System.Console.WriteLine(ex);
        }
    }

    #if NEWTONSOFT_JSON
    private ArrayList JsonConfig()
    {
        string my_location = AppDomain.CurrentDomain.BaseDirectory;
        char sep = System.IO.Path.DirectorySeparatorChar;
        string ini_file = my_location + sep + ".." + sep + ".." + sep + ".." + sep + "ini.json";
        ArrayList al = new ArrayList();
        if (!System.IO.File.Exists(ini_file)) {
            return al;
        }

        try {
            string ini_json = System.IO.File.ReadAllText(ini_file);
            JObject o = JObject.Parse(ini_json);
            foreach (var port in o["NewMagellanPorts"]) {
                if (port["port"] == null) {
                    System.Console.WriteLine("Missing the \"port\" setting. JSON:");
                    System.Console.WriteLine(port);
                } else if (port["module"] == null) {
                    System.Console.WriteLine("Missing the \"module\" setting. JSON:");
                    System.Console.WriteLine(port);
                } else {
                    al.Add(new string[]{ (string)port["port"], (string)port["module"] });
                }
            }
        } catch (NullReferenceException) {
            // probably means now NewMagellanPorts key in ini.json
            // not a fatal problem
        } catch (Exception ex) {
            // unexpected exception
            System.Console.WriteLine(ex);
        }

        return al;
    }
    #endif

    private ArrayList ReadConfig()
    {
        /**
         * Look for settings in ini.json if it exists
         * and the library DLL exists
         */
        #if NEWTONSOFT_JSON
        ArrayList json_ports = JsonConfig();
        if (json_ports.Count > 0) {
            return json_ports;
        }
        #endif
        string my_location = AppDomain.CurrentDomain.BaseDirectory;
        char sep = System.IO.Path.DirectorySeparatorChar;
        StreamReader fp = new StreamReader(my_location + sep + "ports.conf");
        ArrayList al = new ArrayList();
        Hashtable ht = new Hashtable();
        string line;
        while( (line = fp.ReadLine()) != null){
            line = line.TrimStart(null);
            if (line == "" || line[0] == '#') continue;
            string[] pieces = line.Split(null);
            if (pieces.Length != 2) {
                System.Console.WriteLine("Warning: malformed port.conf line: "+line);
                System.Console.WriteLine("Format: <port_string> <handler_class_name>");
            } else if (ht.ContainsKey(pieces[0])) {
                System.Console.WriteLine("Warning: device already has a module attached.");
                System.Console.WriteLine("Line will be ignored: "+line);
            } else {
                al.Add(pieces);
                ht.Add(pieces[0], pieces[1]);
            }
        }    
        return al;
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
            string user_in = System.Console.ReadLine();
            if (user_in == "exit") {
                System.Console.WriteLine("stopping");
                m.ShutDown();
                exiting = true;
            }
        }
    }
}
