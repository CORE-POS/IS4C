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

using System;
using System.IO.Ports;
using System.Threading;
using System.Net;
using System.Net.Sockets;
using CustomForms;

namespace SPH {

public class SPH_IngenicoRBA_IP : SPH_IngenicoRBA_Common {

    private TcpClient device = null;
    private string device_host = null;

    public SPH_IngenicoRBA_IP(string p) : base(p)
    { 
        this.SPH_Running = true;
        this.verbose_mode = 0;
        this.device = new TcpClient();
        this.device_host = p;
    }

    private bool ReConnect()
    {
        if (this.device == null) {
            this.device = new TcpClient();
        } else if (this.device.Connected) {
            return true;
        }

        try {
            this.device.Connect(this.device_host, 12000);
        } catch (Exception ex) {
            System.Console.WriteLine("Connect error: " + ex.ToString());

            return false;
        }

        return true;
    }
    
    public override void Read()
    { 
        ReConnect();
        WriteMessageToDevice(OnlineMessage());
        HandleMsg("termReset");
        this.device.ReceiveTimeout = 5000;
        NetworkStream stream = device.GetStream();
        byte[] buffer = new byte[512];
        int buffer_position = 0;
        int bytes_read = 0; 
        while (SPH_Running) {
            try {
                bytes_read = stream.Read(buffer, buffer_position, buffer.Length);
                if (bytes_read > 0) {
                    buffer_position += bytes_read;
                    if (buffer[0] == 0x6) {
                        // ACK message
                        buffer_position = 0;
                    } else if (buffer[0] == 0x15) {
                        // NACK message
                        buffer_position = 0;
                    } else if (buffer[0] == 0x2 && buffer_position > 2) {
                        // device protocol mesage
                        // check for ETX byte to ensure
                        // all data has been received
                        if (buffer[buffer_position-2] == 0x3) {
                            // copy message and clear buffer
                            byte[] copy = new byte[buffer_position]; 
                            Array.Copy(buffer, 0, copy, 0, buffer_position);
                            buffer_position = 0;
                            // send ACK to device
                            stream.Write(new byte[]{0x6}, 0, 1);
                            // deal with the data received
                            HandleMessageFromDevice(copy);
                        }
                    }
                }
            } catch (TimeoutException) {
                // timeout is fine; just loop
            } catch (Exception ex) {
                System.Console.WriteLine("Socket Exception: " + ex.ToString());
            }
        }

        if (this.device.Connected) {
            WriteMessageToDevice(OfflineMessage());
        }
    }

    // add STX, CRC, and ETX bytes to message
    public override void WriteMessageToDevice(byte[] msg)
    {
        byte[] actual = new byte[msg.Length+2];
        actual[0] = 0x2; // STX byte
        byte crc = actual[0];
        for (int i=0; i<msg.Length; i++) {
            crc ^= msg[i];
            actual[i+1] = msg[i];
        }

        actual[msg.Length] = 0x3;
        crc ^= actual[msg.Length];
        actual[msg.Length+1] = crc;

        NetworkStream stream = device.GetStream();
        stream.Write(actual, 0, actual.Length);
    }
}

}

