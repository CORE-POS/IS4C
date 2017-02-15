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
public class SPH_IngenicoRBA_RS232 : SPH_IngenicoRBA_Common 
{
    protected new bool auto_state_change = false;

    public SPH_IngenicoRBA_RS232(string p) : base(p)
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
        
        sp.Open();

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
    }

    // Waits for acknowledgement from device & resends
    // if necessary. Should probably be used instead
    // of ByteWrite for most messages.
    private void ConfirmedWrite(byte[] b)
    {
        if (this.verbose_mode > 0) {
            System.Console.WriteLine("Tried to write");
        }

        int count=0;
        while (last_message != null && count++ < 5) {
            Thread.Sleep(10);
        }
        last_message = b;
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


    // main read loop
    override public void Read()
    {
        WriteMessageToDevice(OfflineMessage());
        WriteMessageToDevice(OnlineMessage());
        HandleMsg("termReset");

        ArrayList bytes = new ArrayList();
        while (SPH_Running) {
            try {
                int b = sp.ReadByte();
                if (b == 0x06) {
                    // ACK
                    if (this.verbose_mode > 0) {
                        System.Console.WriteLine("ACK!");
                    }
                    last_message = null;
                } else if (b == 0x15) {
                    // NAK
                    // re-send
                    if (this.verbose_mode > 0) {
                        System.Console.WriteLine("NAK!");
                    }
                    if (last_message != null) {
                        ByteWrite(last_message);
                    }
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
                    }
                    // deal with message, clear arraylist for the
                    // next one
                    HandleMessageFromDevice(buffer);
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
        
        if (this.verbose_mode > 0) {
            System.Console.WriteLine("Crashed?");
        }
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
