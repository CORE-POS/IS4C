/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/


/*************************************************************
 * SPH_Magellan_Scale
 * 	SerialPortHandler implementation for the magellan scale
 *
 * Sets up a serial connection in the constructor
 *
 * Polls for data in Read(), writing responses back to the
 * scale as needed and pushing data into the correct
 * WebBrowser frame
 *
 * Sends beep requests to the scale in PageLoaded(Uri) as
 * determined by frame #1
*************************************************************/
using System;
using System.IO;
using System.IO.Ports;
using System.Windows.Forms;
using System.Threading;

#define MAGELLAN_OUTPUT_FILE "/var/www/html/git/IS4C/pos/is4c-nf/scale-drivers/drivers/NewMagellan/scanner-scale.data"
#define MAGELLAN_LOCK_FILE "/var/www/html/git/IS4C/pos/is4c-nf/scale-drivers/drivers/NewMagellan/scanner-scale.lock"

namespace SPH {

public class SPH_Magellan_Scale : SerialPortHandler {
	private bool got_weight;

	public SPH_Magellan_Scale(string p) : base(p){
		sp = new SerialPort();
		sp.PortName = this.port;
		sp.BaudRate = 9600;
		sp.DataBits = 7;
		sp.StopBits = StopBits.One;
		sp.Parity = Parity.Odd;
		sp.RtsEnable = true;
		sp.Handshake = Handshake.None;
		sp.ReadTimeout = 500;
		
		got_weight = false;

		sp.Open();
	}

	override public void HandleMsg(string msg){
		if (msg == "errorBeep"){
			Beeps(3);
		}
		else if (msg == "beepTwice"){
			Beeps(2);
		}
		else if (msg == "goodBeep"){
			Beeps(1);
		}
		else if (msg == "twoPairs"){
			Thread.Sleep(300);
			Beeps(2);
			Thread.Sleep(300);
			Beeps(2);
		}
		else if (msg == "rePoll"){
			got_weight = false;
			sp.Write("S14\r");
		}
	}

	private void Beeps(int num){
		int count = 0;
		while(count < num){
			sp.Write("S334\r");
			Thread.Sleep(150);
			count++;
		}
	}

	override public void Read(){
		string buffer = "";
		System.Console.WriteLine("Reading serial data");
		sp.Write("S14\r");
		while(SPH_Running){
			try {
				int b = sp.ReadByte();
				if (b == 13){
					buffer = this.ParseData(buffer);
					if (buffer != null){
						System.Console.WriteLine(buffer);
						this.PushOutput(buffer);
					}
					buffer = "";
				}
				else{
					buffer += ((char)b).ToString();
				}

			}
			catch{}
		}
	}

	private void PushOutput(string s){
		while (File.Exists(MAGELLAN_LOCK_FILE)){
			Thread.Sleep(50);
		}
		File.Create(MAGELLAN_LOCK_FILE).Dispose();
		StreamWriter sw = File.AppendText(MAGELLAN_OUTPUT_FILE);
		sw.WriteLine(s);
		sw.Close();
		File.Delete(MAGELLAN_LOCK_FILE);
	}

	private string ParseData(string s){
		if (s.Substring(0,3) == "S11")
			sp.Write("S14\r");
		else if(s.Substring(0,4) == "S141"){
			sp.Write("S14\r");
			if(got_weight){
				got_weight = false;
				return "S141";
			}
		}
		else if(s.Substring(0,4) == "S142"){
			sp.Write("S11\r");
			return "S142";
		}
		else if(s.Substring(0,4) == "S143"){
			sp.Write("S11\r");
			return "S110000";
		}
		else if(s.Substring(0,4) == "S144"){
			sp.Write("S14\r");
			if (!got_weight){
				got_weight = true;
				return "S11"+s.Substring(4);
			}
		}
		else if(s.Substring(0,4) == "S145"){
			sp.Write("S11\r");
			return "S145";
		}
		else if (s.Substring(0,4) == "S08A" ||
			 s.Substring(0,4) == "S08F")
			return s.Substring(4);
		else if (s.Substring(0,4) == "S08E")
			return this.ExpandUPCE(s.Substring(4));
		else
			return s;

		return null;
	}

	private string ExpandUPCE(string upc){
		string lead = upc.Substring(0,upc.Length-1);
		string tail = upc.Substring(upc.Length-1);

		if (tail == "0" || tail == "1" || tail == "2")
			return lead.Substring(0,3)+tail+"0000"+lead.Substring(3);
		else if (tail == "3")
			return lead.Substring(0,4)+"00000"+lead.Substring(4);
		else if (tail == "4")
			return lead.Substring(0,5)+"00000"+lead.Substring(5);
		else
			return lead+"0000"+tail;
	}
}

}
