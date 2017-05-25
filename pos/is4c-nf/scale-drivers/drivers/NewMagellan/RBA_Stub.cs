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
 * SPH_Ingenico_i6550
 *     SerialPortHandler implementation for the Ingenico 
 *     signature capture devices using Retail Base
 *     Application (RBA). Tested with i6550, should work
 *     with i6580, i6770, and i6780 as well. Two other devices,
 *    i3070 and i6510, speak the same language but minor
 *    tweaking would need to be done to account for those
 *    devices not doing signature capture.
 *
 * Sets up a serial connection in the constructor
 *
 * Polls for data in Read(), writing responses back to the
 * device as needed
 *
*************************************************************/
using System;
using System.IO.Ports;
using System.Threading;
using System.Collections;
using System.Collections.Generic;
using System.Drawing;
using System.IO;
using System.Net;
using System.Net.Sockets;

using BitmapBPP;

namespace SPH {

public enum RbaButtons { None, Credit, EMV };

/**
  This class contains all the functionality for building
  and dealing with RBA protocl messages. Subclasses that
  handle a different hardware interface are responsible for
  the following:
  - method Read() that gets ACK, NACK, and message bytes
    from the device. The subclass is responsible for sending
    its own ACKs and NACKs. The message bytes starting with 
    STX and ending with ETX followed by LRC should be passed
    to the HandleMessageFromDevice() method.
  - method WriteMessageToDevice() is responsible for sending
    an array of bytes to the device. This method must add an
    LRC byte at the end as the parameter does not include it
*/
public class RBA_Stub : SPH_IngenicoRBA_Common 
{
    new private SerialPort sp = null;

    private RbaButtons emv_buttons = RbaButtons.Credit;
    // Used to signal drawing thread it's time to exit
    private AutoResetEvent sleeper;

    public RBA_Stub(string p)
    {
        this.port = p;
        this.sleeper = new AutoResetEvent(false);
    }

    public void SetEMV(RbaButtons emv)
    {
        this.emv_buttons = emv;
    }

    private void initPort()
    {
        sp = new SerialPort();
        sp.PortName = this.port;
        sp.BaudRate = 19200;
        sp.DataBits = 8;
        sp.StopBits = StopBits.One;
        sp.Parity = Parity.None;
        sp.RtsEnable = true;
        sp.Handshake = Handshake.None;
        sp.ReadTimeout = 500;
    }

    public void stubStart()
    {
        try {
            initPort();
            sp.Open();
            SPH_Running = true;
            this.sleeper.Reset();
            this.SPH_Thread = new Thread(new ThreadStart(this.Read));    
            SPH_Thread.Start();
        } catch (Exception) {}
    }

    public void showApproved()
    {
        try {
            stubStop();
            initPort();
            sp.Open();
            WriteMessageToDevice(SimpleMessageScreen("Approved"));
            sp.Close();
        } catch (Exception) {}
    }

    public void stubStop()
    {
        SPH_Running = false;
        try {
            this.sleeper.Set();
            sp.Close();
        } catch (Exception) { }
        SPH_Thread.Join();
    }

    public void addScreenMessage(string message)
    {
        try {
            WriteMessageToDevice(SetVariableMessage("104", message));
        } catch (Exception) { }
    }

    /**
      Simple wrapper to write an array of bytes
    */
    private void ByteWrite(byte[] b)
    {
        if (this.verbose_mode > 1) {
            System.Console.WriteLine("Sent:");
            foreach (byte a in b) {
                if (a < 10) {
                    System.Console.Write("0{0} ",a);
                    } else {
                        System.Console.Write("{0} ",a);
                    }
            }
            System.Console.WriteLine();
        }

        sp.Write(b,0,b.Length);

        System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
        System.Console.WriteLine(enc.GetString(b));
    }

    // Waits for acknowledgement from device & resends
    // if necessary. Should probably be used instead
    // of ByteWrite for most messages.
    private void ConfirmedWrite(byte[] b)
    {
        if (this.verbose_mode > 0) {
            System.Console.WriteLine("Tried to write");
        }

        ByteWrite(b);

        if (this.verbose_mode > 0) {
            System.Console.WriteLine("wrote");
        }
    }

    // computes check character and appends it
    // to the array
    private byte[] GetLRC(byte[] b)
    {
        byte[] ret = new byte[b.Length+1];
        ret[0] = b[0]; // STX
        byte lrc = 0;
        for (int i=1; i < b.Length; i++) {
            lrc ^= b[i];
            ret[i] = b[i];
        }
        ret[b.Length] = lrc;

        return ret;
    }

    // use an AutoResetEvent to pause to 2 seconds
    // if the event is signalled that means RBA_Stub
    // should exit and release the serial port so the
    // second command is only set if the event times out
    // without being signalled
    private void showPaymentScreen()
    {
        try {
            WriteMessageToDevice(GetCardType());
            if (this.sleeper.WaitOne(2000) == false) {
                addPaymentButtons();
            }
        } catch (Exception) {
        }
    }

    private void addPaymentButtons()
    {
        try {
            char fs = (char)0x1c;
            string store_name = "Whole Foods Co-op";

            // standard credit/debit/ebt/gift
            string buttons = "TPROMPT6,"+store_name+fs+"Bbtna,S"+fs+"Bbtnb,S"+fs+"Bbtnc,S"+fs+"Bbtnd,S";
            if (this.emv_buttons == RbaButtons.EMV) {
                // CHIP+PIN button in place of credit & debit
                buttons = "TPROMPT6,"+store_name+fs+"Bbtnb,CHIP+PIN"+fs+"Bbtnb,S"+fs+"Bbtnc,S"+fs+"Bbtnd,S";
            } else if (this.emv_buttons == RbaButtons.None) {
                buttons = "TPROMPT6,"+store_name;
            }

            WriteMessageToDevice(UpdateScreenMessage(buttons));
        } catch (Exception) {
        }
    }

    // main read loop
    override public void Read()
    {
        showPaymentScreen();
        System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();

        ArrayList bytes = new ArrayList();
        while (SPH_Running) {
            try {
                int b = sp.ReadByte();
                if (b == 0x06) {
                    // ACK
                    if (this.verbose_mode > 0) {
                        System.Console.WriteLine("ACK!");
                    }
                } else if (b == 0x15) {
                    // NAK
                    // Do not re-send
                    // RBA_Stub is not vital functionality
                    if (this.verbose_mode > 0) {
                        System.Console.WriteLine("NAK!");
                    }
                    //WriteMessageToDevice(HardResetMessage());
                } else {
                    // part of a message
                    // force to be byte-sized
                    bytes.Add(b & 0xff); 
                }
                if (bytes.Count > 2 && (int)bytes[bytes.Count-2] == 0x3) {
                    // end of message, send ACK
                    ByteWrite(new byte[1]{0x6}); 
                    // convoluted casting required to get
                    // values out of ArrayList and into
                    // a byte array
                    byte[] buffer = new byte[bytes.Count];
                    for (int i=0; i<bytes.Count; i++) {
                        buffer[i] = (byte)((int)bytes[i] & 0xff);
                        System.Console.Write(buffer[i] + " ");
                    }
                    if (Choice(enc.GetString(buffer))) {
                        WriteMessageToDevice(SimpleMessageScreen("Swipe card when prompted"));
                    }
                    bytes.Clear();
                }
            } catch (TimeoutException) {
                // expected; not an issue
            } catch (Exception ex) {
                if (this.verbose_mode > 0) {
                    System.Console.WriteLine(ex);
                }
            }
        }
    }

    private bool Choice(string str)
    {
        bool ret = false;
        if (str.Substring(1,4) == "24.0") {
            switch (str.Substring(5,1)) {
                case "A":
                    // debit
                    ret = true;
                    parent.MsgSend("TERM:DCDC");
                    break;
                case "B":
                    // credit
                    ret = true;
                    parent.MsgSend("TERM:DCCC");
                    break;
                case "C":
                    // ebt cash
                    parent.MsgSend("TERM:DCEC");
                    ret = true;
                    break;
                case "D":
                    // ebt food
                    parent.MsgSend("TERM:DCEF");
                    ret = true;
                    break;
                default:
                    break;
            }
        }

        return ret;
    }

    /**
        Write a message to the device
    */
    public override void WriteMessageToDevice(byte[] msg)
    {
        ConfirmedWrite(GetLRC(msg));
    }
}

}
