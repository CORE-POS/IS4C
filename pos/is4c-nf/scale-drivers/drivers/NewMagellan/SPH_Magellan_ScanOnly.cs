/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
 * SPH_Magellan_Scale
 *     SerialPortHandler implementation for the magellan scale
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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 27Oct2012 Eric Lee Added Code 39 handling to ParseData()

*/

using System;
using System.IO;
using System.IO.Ports;
using System.Threading;
using System.Diagnostics;
using System.Collections.Generic;

namespace SPH {

public class SPH_Magellan_ScanOnly : SerialPortHandler 
{

    public SPH_Magellan_ScanOnly(string p) : base(p)
    {
        sp = new SerialPort();
        sp.PortName = this.port;
        sp.BaudRate = 9600;
        sp.DataBits = 7;
        sp.StopBits = StopBits.One;
        sp.Parity = Parity.Odd;
        sp.RtsEnable = true;
        sp.Handshake = Handshake.None;
        sp.ReadTimeout = 500;
        
        sp.Open();
    }

    override public void HandleMsg(string msg)
    {
        // currently assuming all writes down the other line
    }

    override public void Read()
    {
        string buffer = "";
        if (this.verbose_mode > 0) {
            System.Console.WriteLine("Reading serial scan data");
        }
        while (SPH_Running) {
            try {
                int b = sp.ReadByte();
                if (b == 13) {
                    if (this.verbose_mode > 0) {
                        System.Console.WriteLine("RECV FROM SCALE: "+buffer);
                    }
                    buffer = this.ParseData(buffer);
                    if (buffer != null) {
                        if (this.verbose_mode > 0) {
                            System.Console.WriteLine("PASS TO POS: "+buffer);
                        }
                        this.PushOutput(buffer);
                    }
                    buffer = "";
                } else {
                    buffer += ((char)b).ToString();
                }
            } catch {
                Thread.Sleep(100);
            }
        }
    }

    private void PushOutput(string s)
    {
        parent.MsgSend(s);
    }

    private string ParseData(string s)
    {
        if (s.Substring(0,2) == "S0") { // scanner message
            if (s.Substring(0,4) == "S08A" || s.Substring(0,4) == "S08F") { // UPC-A or EAN-13
                return s.Substring(4);
            } else if (s.Substring(0,4) == "S08E") { // UPC-E
                return this.ExpandUPCE(s.Substring(4));
            } else if (s.Substring(0,4) == "S08R") { // GTIN / GS1
                return "GS1~"+s.Substring(3);
            } else if (s.Substring(0,5) == "S08B1") { // Code39
                return s.Substring(5);
            } else if (s.Substring(0,5) == "S08B2") { // Interleaved 2 of 5
                return s.Substring(5);
            } else if (s.Substring(0,5) == "S08B3") { // Code128
                return s.Substring(5);
            } else {
                return s; // catch all
            }
        } else { // not scanner message
            return s; // catch all
        }

        return null;
    }

    private string ExpandUPCE(string upc)
    {
        string lead = upc.Substring(0,upc.Length-1);
        string tail = upc.Substring(upc.Length-1);

        if (tail == "0" || tail == "1" || tail == "2") {
            return lead.Substring(0,3)+tail+"0000"+lead.Substring(3);
        } else if (tail == "3") {
            return lead.Substring(0,4)+"00000"+lead.Substring(4);
        } else if (tail == "4") {
            return lead.Substring(0,5)+"00000"+lead.Substring(5);
        } else {
            return lead+"0000"+tail;
        }
    }
}

}
