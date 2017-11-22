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

public enum RbaButtons { None, Credit, EMV, Cashback };

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
    private Object syncLock;

    private bool allowDebitCB = true;
    private string defaultMsg = "Welcome";
    private string bufferedCardType = "";

    public RBA_Stub(string p)
    {
        this.port = p;
        this.sleeper = new AutoResetEvent(false);
        this.syncLock = new Object();
    }

    public void SetEMV(RbaButtons emv)
    {
        this.emv_buttons = emv;
    }

    public void SetCashBack(bool cb)
    {
        this.allowDebitCB = cb;
    }

    public void SetDefaultMessage(string msg)
    {
        this.defaultMsg = msg;
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
        if (this.emv_buttons != RbaButtons.None) {
            try {
                initPort();
                sp.Open();
                SPH_Running = true;
                this.bufferedCardType = "";
                this.sleeper.Reset();
                this.SPH_Thread = new Thread(new ThreadStart(this.Read));
                SPH_Thread.Start();
            } catch (Exception) {}
        }
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

    public void showMessage(string msg)
    {
        try {
            stubStop();
            initPort();
            sp.Open();
            WriteMessageToDevice(SimpleMessageScreen(msg));
            sp.Close();
        } catch (Exception) {}

    }

    public void stubStop()
    {
        lock (this.syncLock) {
            SPH_Running = false;
        }
        try {
            // wake up the RBA_Stub thread if it's sleeping
            // between sequential messages
            this.sleeper.Set();
            // wait for the thread to finish
            SPH_Thread.Join();
        } catch (Exception) { }
    }

    public void hardReset()
    {
        try {
            stubStop();
            initPort();
            sp.Open();
            WriteMessageToDevice(RebootMessage());
            sp.Close();
        } catch (Exception) {}
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

    private bool ReadAndAck()
    {
        int readBytes = 0;
        try {
            while (true) {
                var next = sp.ReadByte();
                readBytes++;
            }
        } catch (Exception) { }
        if (readBytes > 1) { // exactly one byte was just an ACK or NAK
            ByteWrite(new byte[1]{0x6});

            return true;
        }
        return false;
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
            this.ReadAndAck();
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

            // standard credit/debit/ebt/gift
            string buttons = "TPROMPT6,"+defaultMsg+fs+"Bbtna,S"+fs+"Bbtnb,S"+fs+"Bbtnc,S"+fs+"Bbtnd,S";
            if (this.emv_buttons == RbaButtons.EMV) {
                // CHIP+PIN button in place of credit & debit
                buttons = "TPROMPT6,"+defaultMsg+fs+"Bbtna,S"+fs+"Bbtnb,CHIP+PIN"+fs+"Bbtnb,S"+fs+"Bbtnc,S"+fs+"Bbtnd,S";
            } else if (this.emv_buttons == RbaButtons.Cashback) {
                buttons = "TPROMPT6,"+defaultMsg+fs+"Bbtna,CASHBACK"+fs+"Bbtna,S"+fs+"Bbtnb,CREDIT"+fs+"Bbtnb,S"+fs+"Bbtnc,S"+fs+"Bbtnd,S";
            } else if (this.emv_buttons == RbaButtons.None) {
                buttons = "TPROMPT6,"+defaultMsg;
            }

            WriteMessageToDevice(UpdateScreenMessage(buttons));
            this.ReadAndAck();
        } catch (Exception) {
        }
    }

    // main read loop
    override public void Read()
    {
        showPaymentScreen();
        System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();

        ArrayList bytes = new ArrayList();
        while (true && this.emv_buttons != RbaButtons.None) {
            try {
                int b = sp.ReadByte();
                if (bytes.Count == 0 && b == 0x06) {
                    // ACK
                    if (this.verbose_mode > 0) {
                        System.Console.WriteLine("ACK!");
                    }
                } else if (bytes.Count == 0 && b == 0x15) {
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
                        WriteMessageToDevice(SimpleMessageScreen("Insert, tap, or swipe card when prompted"));
                        this.ReadAndAck();
                        // input is done; no need to keep the read thread alive
                        // and rely on cross-thread signaling to end it later
                        SPH_Running = false;
                        break;
                    }
                    bytes.Clear();
                }
            } catch (TimeoutException) {
                // expected; not an issue
            } catch (Exception ex) {
                if (this.verbose_mode > 0) {
                    System.Console.WriteLine(ex);
                }
                // This loop should stop on an exception
                // 
                SPH_Running = false;
            }
            lock (this.syncLock) {
                if (!SPH_Running && bytes.Count == 0) break;
            }
        }

        try {
            this.sp.Close();
        } catch (Exception) {}
    }

    /**
      A 24.0 message to the terminal returns a 24.0 response
      with the selected value at index 5 in the string

      The payment selection screen sends A through B
      The cashback screen sends 1 through 4 and O
    */
    private bool Choice(string str)
    {
        bool ret = false;
        if (str.Substring(1,4) == "24.0") {
            switch (str.Substring(5,1)) {
                case "A":
                    // debit
                    ret = true;
                    if (allowDebitCB) {
                        ret = false;
                        WriteMessageToDevice(GetCashBack());
                        this.ReadAndAck();
                        this.bufferedCardType = "DCDC";
                    } else {
                        parent.MsgSend("TERM:DCDC");
                    }
                    break;
                case "B":
                    // credit
                    ret = true;
                    parent.MsgSend("TERM:DCCC");
                    break;
                case "C":
                    // ebt cash
                    ret = true;
                    if (allowDebitCB) {
                        ret = false;
                        WriteMessageToDevice(GetCashBack());
                        this.ReadAndAck();
                        this.bufferedCardType = "DCEC";
                    } else {
                        parent.MsgSend("TERM:DCEC");
                    }
                    break;
                case "D":
                    // ebt food
                    parent.MsgSend("TERM:DCEF");
                    ret = true;
                    this.sendBufferedCardType();
                    break;
                case "1":
                    parent.MsgSend("TERMCB:10");
                    ret = true;
                    this.sendBufferedCardType();
                    break;
                case "2":
                    parent.MsgSend("TERMCB:20");
                    ret = true;
                    this.sendBufferedCardType();
                    break;
                case "3":
                    parent.MsgSend("TERMCB:30");
                    ret = true;
                    this.sendBufferedCardType();
                    break;
                case "4":
                    parent.MsgSend("TERMCB:40");
                    ret = true;
                    this.sendBufferedCardType();
                    break;
                case "O":
                    parent.MsgSend("TERMCB:50");
                    ret = true;
                    this.sendBufferedCardType();
                    break;
                case "N":
                    ret = true;
                    this.sendBufferedCardType();
                    break;
                default:
                    break;
            }
        }

        return ret;
    }

    private void sendBufferedCardType()
    {
        if (this.bufferedCardType.Length == 4) {
            parent.MsgSend("TERM:" + this.bufferedCardType);
            this.bufferedCardType = "";
        }
    }

    /**
        Write a message to the device
    */
    public override void WriteMessageToDevice(byte[] msg)
    {
        ByteWrite(GetLRC(msg));
    }
}

}
