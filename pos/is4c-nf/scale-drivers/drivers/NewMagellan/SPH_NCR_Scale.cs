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

/**
Theoretical implementation. Assumes single-cable RS232 configuration with:
    STX byte: 0x2
    ETX byte: 0x3
    BCC byte: disabled
    PPD byte: disabled (aka PACESETTER)
*/

using System;
using System.IO;
using System.IO.Ports;
using System.Threading;

namespace SPH {

public class SPH_NCR_Scale : SerialPortHandler 
{

    enum WeighState { None, Motion, Over, Zero, NonZero, Under };
    private WeighState scale_state;
    private Object writeLock = new Object();
    private string last_weight;
    const byte STX = 0x2;
    const byte ETX = 0x3;

    public SPH_NCR_Scale(string p) : base(p)
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
                // ASCII: 10
                sp.Write(new byte[]{ STX, 0x31, 0x30, ETX }, 0, 4);
                Thread.Sleep(5000);
                // ASCII: 14
                sp.Write(new byte[]{ STX, 0x31, 0x34, ETX }, 0, 4);
            }
        }
    }

    private void Beeps(int num)
    {
        lock (writeLock) {
            int count = 0;
            while(count < num) {
                // ASCII: 334
                sp.Write(new byte[]{ STX, 0x33, 0x33, 0x34, ETX }, 0, 5);
                Thread.Sleep(150);
                count++;
            }
        }
    }

    private void GetStatus()
    {
        lock (writeLock) {
            // ASCII: 14
            sp.Write(new byte[]{ STX, 0x31, 0x34, ETX }, 0, 4);
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
                if (b == ETX) {
                    // message complete
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
                } else if (b == STX) {
                    // skip STX byte; converting to character doesn't really work
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
        if (s.Substring(0,1) == "0") { // scanner message
            if (s.Substring(0,3) == "08A" || s.Substring(0,3) == "08F") { // UPC-A or EAN-13
                return s.Substring(3);
            } else if (s.Substring(0,3) == "08E") { // UPC-E
                return this.ExpandUPCE(s.Substring(3));
            } else if (s.Substring(0,3) == "08R") { // GTIN / GS1
                return "GS1~"+s.Substring(2);
            } else if (s.Substring(0,4) == "08B1") { // Code39
                return s.Substring(4);
            } else if (s.Substring(0,4) == "08B2") { // Interleaved 2 of 5
                return s.Substring(4);
            } else if (s.Substring(0,4) == "08B3") { // Code128
                return s.Substring(4);
            } else {
                return s; // catch all
            }
        } else if (s.Substring(0,1) == "1") { // scale message
            /**
              The scale supports two primary commands:
              11 is "get stable weight". This tells the scale to return
              the next stable non-zero weight.
              14 is "get state". This tells the scale to return its
              current state.

              The "scale_state" variable tracks all six known scale states.
              The state is only changed if the status response is different
              than the current state and this only returns a non-null string
              when the state changes. The goal is to only pass a message back
              to POS once per state change. The "last_weight" is tracked in
              case the scale jumps directly from one stable, non-zero weight
              to another without passing through another state in between.
            */
            if (s.Substring(0,2) == "11") { // stable weight following weight request
                GetStatus();
                if (scale_state != WeighState.NonZero || last_weight != s.Substring(2)) {
                    scale_state = WeighState.NonZero;
                    last_weight = s.Substring(2);
                    return "S"+s;
                }
            } else if (s.Substring(0,3) == "140") { // scale not ready
                GetStatus();
                if (scale_state != WeighState.None) {
                    scale_state = WeighState.None;
                    return "S140";
                }
            } else if (s.Substring(0,3) == "141") { // weight not stable
                GetStatus();
                if (scale_state != WeighState.Motion) {
                    scale_state = WeighState.Motion;
                    return "S141";
                }
            } else if (s.Substring(0,3) == "142") { // weight over max
                GetStatus();
                if (scale_state != WeighState.Over) {
                    scale_state = WeighState.Over;
                    return "S142";
                }
            } else if (s.Substring(0,3) == "143") { // stable zero weight
                GetStatus();
                if (scale_state != WeighState.Zero) {
                    scale_state = WeighState.Zero;
                    return "S110000";
                }
            } else if (s.Substring(0,3) == "144") { // stable non-zero weight
                GetStatus();
                if (scale_state != WeighState.NonZero || last_weight != s.Substring(3)) {
                    scale_state = WeighState.NonZero;
                    last_weight = s.Substring(3);
                    return "S11"+s.Substring(3);
                }
            } else if (s.Substring(0,3) == "145") { // scale under zero weight
                GetStatus();
                if (scale_state != WeighState.Under) {
                    scale_state = WeighState.Under;
                    return "S145";
                }
            } else {
                GetStatus();
                return "S"+s; // catch all
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
