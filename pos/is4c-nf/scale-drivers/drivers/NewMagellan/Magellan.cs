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
 * 	Main app. Starts all requested Serial Port Handlers
 * and monitors UDP for messages
 *
 * Note that exit won't work cleanly if a SerialPortHandler
 * blocks indefinitely. Use timeouts in polling reads.
*************************************************************/
using System;
using System.Threading;
using System.IO;
using System.Collections;

using CustomForms;
using CustomUDP;
using SPH;

public class Magellan : DelegateForm {

	private SerialPortHandler[] sph;
	private UDPMsgBox u;

	// read deisred modules from config file
	public Magellan(int verbosity){
		ArrayList conf = ReadConfig();
		sph = new SerialPortHandler[conf.Count];
		for(int i = 0; i < conf.Count; i++){
			string port = ((string[])conf[i])[0];
			string module = ((string[])conf[i])[1];
			try {
				Type t = Type.GetType("SPH."+module+", SPH, Version=0.0.0.0, Culture=neutral, PublicKeyToken=null");

				sph[i] = (SerialPortHandler)Activator.CreateInstance(t, new Object[]{ port });
				sph[i].SetParent(this);
				sph[i].SetVerbose(verbosity);
			}
			catch (Exception){
				System.Console.WriteLine("Warning: could not initialize "+port);
				System.Console.WriteLine("Ensure the device is connected and you have permission to access it.");
				sph[i] = null;
			}
		}
		FinishInit();
	}

	// alternate constructor for specifying
	// desired modules at compile-time
	public Magellan(SerialPortHandler[] args){
		this.sph = args;
		FinishInit();
	}

	private void FinishInit(){
		MonitorSerialPorts();

		u = new UDPMsgBox(9450);
		u.SetParent(this);
		u.My_Thread.Start();
	}

	private void MonitorSerialPorts(){
		foreach(SerialPortHandler s in sph){
			if (s == null) continue;
			s.SPH_Thread.Start();
		}
	}

	public override void MsgRecv(string msg){
		if (msg == "exit"){
			this.ShutDown();
		}
		else {
			foreach(SerialPortHandler s in sph){
				s.HandleMsg(msg);
			}
		}
	}

    public override void MsgSend(string msg)
    {
        int ticks = Environment.TickCount;
        char sep = System.IO.Path.DirectorySeparatorChar;
        while (File.Exists("ss-output/"  + sep + ticks)) {
            ticks++;
        }

        TextWriter sw = new StreamWriter("ss-output/" +sep+"tmp"+sep+ticks);
        sw = TextWriter.Synchronized(sw);
        sw.WriteLine(msg);
        sw.Close();
        File.Move("ss-output/" +sep+"tmp"+sep+ticks,
              "ss-output/" +sep+ticks);
    }

	public void ShutDown(){
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

	/*
	private void PageChange(object sender, WebBrowserDocumentCompletedEventArgs e){
		if (e.Url == new Uri("http://localhost/bye.html")){
			try {
				u.Stop();
				foreach(SerialPortHandler s in sph){
					s.Stop();
				}
			}
			catch(Exception ex){
				System.Console.WriteLine(ex);
			}

			this.Dispose();
			Application.Exit();
		}
	}
	*/

	private ArrayList ReadConfig(){
		StreamReader fp = new StreamReader("ports.conf");
		ArrayList al = new ArrayList();
		Hashtable ht = new Hashtable();
		string line;
		while( (line = fp.ReadLine()) != null){
			line = line.TrimStart(null);
			if (line == "" || line[0] == '#') continue;
			string[] pieces = line.Split(null);
			if (pieces.Length != 2){
				System.Console.WriteLine("Warning: malformed port.conf line: "+line);
				System.Console.WriteLine("Format: <port_string> <handler_class_name>");
			}
			else if (ht.ContainsKey(pieces[0])){
				System.Console.WriteLine("Warning: device already has a module attached.");
				System.Console.WriteLine("Line will be ignored: "+line);
			}
			else {
				al.Add(pieces);
				ht.Add(pieces[0], pieces[1]);
			}
		}	
		return al;
	}

	static public void Main(string[] args){
		int verbosity = 0;
		for(int i=0;i<args.Length;i++){
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
		while(!exiting){
			string user_in = System.Console.ReadLine();
			if (user_in == "exit"){
				System.Console.WriteLine("stopping");
				m.ShutDown();
				exiting = true;
			}
		}
	}
}
