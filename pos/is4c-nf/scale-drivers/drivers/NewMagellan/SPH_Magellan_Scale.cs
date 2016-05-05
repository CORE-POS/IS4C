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

namespace SPH {

public class SPH_Magellan_Scale : SerialPortHandler 
{

    enum WeighState { None, Motion, Over, Zero, NonZero, Under };
    private WeighState scale_state;
    private Object writeLock = new Object();
    private string last_weight;

    public SPH_Magellan_Scale(string p) : base(p)
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
        
        scale_state = WeighState.None;
        last_weight = "0000";

        sp.Open();
    }

    override public void HandleMsg(string msg)
    {
        if (msg == "errorBeep") {
            Beeps(3);
        } else if (msg == "beepTwice") {
            Beeps(2);
        } else if (msg == "goodBeep") {
            Beeps(1);
        } else if (msg == "twoPairs") {
            Thread.Sleep(300);
            Beeps(2);
            Thread.Sleep(300);
            Beeps(2);
        } else if (msg == "rePoll") {
            /* ignore these commands on purpose
            scale_state = WeighState.None;
            GetStatus();
            */
        } else if (msg == "wakeup") {
            scale_state = WeighState.None;
            GetStatus();
        } else if (msg == "reBoot") {
            scale_state = WeighState.None;
            lock (writeLock) {
                sp.Write("S10\r");
                Thread.Sleep(5000);
                sp.Write("S14\r");
            }
        }
    }

    private void Beeps(int num)
    {
        lock (writeLock) {
            int count = 0;
            while(count < num) {
                sp.Write("S334\r");
                Thread.Sleep(150);
                count++;
            }
        }
    }

    private void GetStatus()
    {
        lock (writeLock) {
            sp.Write("S14\r");
        }
    }

    override public void Read()
    {
        string buffer = "";
        if (this.verbose_mode > 0) {
            System.Console.WriteLine("Reading serial data");
        }
        GetStatus();
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
        } else if (s.Substring(0,2) == "S1") { // scale message
            /**
              The scale supports two primary commands:
              S11 is "get stable weight". This tells the scale to return
              the next stable non-zero weight.
              S14 is "get state". This tells the scale to return its
              current state.

              The "scale_state" variable tracks all six known scale states.
              The state is only changed if the status response is different
              than the current state and this only returns a non-null string
              when the state changes. The goal is to only pass a message back
              to POS once per state change. The "last_weight" is tracked in
              case the scale jumps directly from one stable, non-zero weight
              to another without passing through another state in between.
            */
            if (s.Substring(0,3) == "S11") { // stable weight following weight request
                GetStatus();
                if (scale_state != WeighState.NonZero || last_weight != s.Substring(3)) {
                    scale_state = WeighState.NonZero;
                    last_weight = s.Substring(3);
                    return s;
                }
            } else if (s.Substring(0,4) == "S140") { // scale not ready
                GetStatus();
                if (scale_state != WeighState.None) {
                    scale_state = WeighState.None;
                    return "S140";
                }
            } else if (s.Substring(0,4) == "S141") { // weight not stable
                GetStatus();
                if (scale_state != WeighState.Motion) {
                    scale_state = WeighState.Motion;
                    return "S141";
                }
            } else if (s.Substring(0,4) == "S142") { // weight over max
                GetStatus();
                if (scale_state != WeighState.Over) {
                    scale_state = WeighState.Over;
                    return "S142";
                }
            } else if (s.Substring(0,4) == "S143") { // stable zero weight
                GetStatus();
                if (scale_state != WeighState.Zero) {
                    scale_state = WeighState.Zero;
                    return "S110000";
                }
            } else if (s.Substring(0,4) == "S144") { // stable non-zero weight
                GetStatus();
                if (scale_state != WeighState.NonZero || last_weight != s.Substring(4)) {
                    scale_state = WeighState.NonZero;
                    last_weight = s.Substring(4);
                    return "S11"+s.Substring(4);
                }
            } else if (s.Substring(0,4) == "S145") { // scale under zero weight
                GetStatus();
                if (scale_state != WeighState.Under) {
                    scale_state = WeighState.Under;
                    return "S145";
                }
            } else {
                GetStatus();
                return s; // catch all
            }
        } else { // not scanner or scale message
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
