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
 * SerialPortHandler
 * 	Abstract class to manage a serial port in a separate
 * thread. Allows top-level app to interact with multiple, 
 * different serial devices through one class interface.
 * 
 * Provides Stop() and SetParent(DelegateBrowserForm) functions.
 *
 * Subclasses must implement Read() and PageLoaded(Uri).
 * Read() is the main polling loop, if reading serial data is
 * required. PageLoaded(Uri) is called on every WebBrowser
 * load event and provides the Url that was just loaded.
 *
*************************************************************/
using System;
using System.IO.Ports;
using System.Threading;
using System.Net;
using System.Net.Sockets;
using CustomForms;

namespace SPH {

public class SPH_iSC250_IP : SerialPortHandler {

    private TcpClient device = null;
    private string device_host = null;

	private string terminal_serial_number;
	private string pos_trans_no;

	public SPH_iSC250_IP(string p) : base(p)
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
	
	public override  void Read()
    { 
        ReConnect();
        SendToDevice(OnlineCommand());
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
                            ParseDeviceMessage(copy);
                        }
                    }
                }
            } catch (TimeoutException) {
                // timeout is fine; just loop
            } catch (Exception ex) {
                System.Console.WriteLine("Socket Excpetion: " + ex.ToString());
            }
        }

        if (this.device.Connected) {
            SendToDevice(OfflineCommand());
        }
    }

    private void ParseDeviceMessage(byte[] data)
    {
        int message_code = ((data[1]-0x30)*10) + (data[2]-0x30);
        switch (message_code) {
            case 1:
                // response after sending online command
                break;
            case 50:
                // card & transaction data
                break;
        }
    }

    // add STX, CRC, and ETX bytes to message
    private void SendToDevice(byte[] msg)
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

    private byte[] OnlineCommand()
    {
        // ASCII: 01.00000000
        return new byte[]{
            0x30,
            0x31,
            0x2e,
            0x30,
            0x30,
            0x30,
            0x30,
            0x30,
            0x30,
            0x30,
            0x30
        };
    }

    private byte[] OfflineCommand()
    {
        // ASCII: 00.00000000
        return new byte[]{
            0x30,
            0x30,
            0x2e,
            0x30,
            0x30,
            0x30,
            0x30
        };
    }

    private byte[] ResetCommand()
    {
        // ASCII: 10.
        return new byte[]{
            0x31,
            0x30,
            0x2e
        };
    }

	// amount format: 5.99 = 599 (just like POS input)
    private byte[] AmountCommand(string amt)
    {
		byte[] a = new System.Text.ASCIIEncoding().GetBytes(amt);
		byte[] msg = new byte[3 + a.Length];

        msg[0] = 0x31;
        msg[1] = 0x33;
        msg[2] = 0x2e;

        for (int i=0; i < a.Length; i++) {
            msg[i+3] = a[i];
        }

        return msg;
    }

    private byte[] ApprovalCommand(string approval_code)
    {
		System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
		string display_message = "Thank You!";
        if (approval_code.Length != 6) {
            approval_code = "000000";
        }

        byte[] msg = new byte[31 + display_message.Length];

		msg[0] = 0x35; // Auth Code
		msg[1] = 0x30;
		msg[2] = 0x2e;

		byte[] tmp = enc.GetBytes(terminal_serial_number);
        for (int i=0; i<8; i++) {
            msg[i+3] = tmp[i];
        }

        msg[11] = 0x0;

		tmp = enc.GetBytes(pos_trans_no);
        for (int i=0; i<4; i++) {
            msg[i+12] = tmp[i];
        }

        if (approval_code == "denied") {
            msg[17] = 0x45;
            msg[18] = 0x3f;
        } else {
            msg[17] = 0x41;
            msg[18] = 0x3f;
        }

		tmp = enc.GetBytes(approval_code);
		for (int i=0;i<6;i++) {
			msg[i+18] = tmp[i];
        }

		string today = String.Format("(0:yyMMdd)",DateTime.Today);
		tmp = enc.GetBytes(today);
		for (int i=0;i<4;i++) {
			msg[i+24] = tmp[i];
        }

		tmp = enc.GetBytes(display_message);
		for (int i=0;i<tmp.Length;i++) {
			msg[i+30] = tmp[i];
        }

        msg[msg.Length-1] = 0x1c; // Field separator

        return msg;
    }


	public override void HandleMsg(string msg)
    {
        switch (msg) {
            case "termReset":
            case "termReboot":
                SendToDevice(ResetCommand());
                break;
            case "termApproved":
                SendToDevice(ApprovalCommand("000000"));
                break;
        }
    }

}

}

