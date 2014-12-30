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
using System.IO;
using System.IO.Ports;
using System.Threading;
using CustomForms;

using USBLayer;

namespace SPH {

public class SPH_IngenicoRBA_USB : SPH_IngenicoRBA_RS232 
{

	/**
	 * USB protocol notes
	 *
	 * The USB layer seems to add a couple extra bytes to
	 * the protocol defined in RBA documentation.
	 *
	 * Offsets:
	 * 0 => endpoint id? always 1 on messages from device to pos,
	 * 		always 2 on messages from pos to the device
	 * 1 => length of data that follows
	 * 2 => data
	 *
	 * Report size is 32 bytes - 2 control bytes, up to 30 data
	 * bytes. Any buffer written should be zero padded to 32 bytes.
	 * The same is true of buffers returned by reads.
	 *
	 * Not sure yet what happens when the RBA message data exceeds
	 * 32 bytes. USB byte 1 could theoretically contain the length
	 * of the full message or it might just be 30 for data length
	 * in that packet.
	 */

	private USBWrapper usb_port;
	private bool read_continues;
	private byte[] long_buffer;
    private byte[] last_message;
	private int long_pos;
	private FileStream usb_fs;
	private int usb_report_size;
	private byte[] ack;
	private byte[] nack;

    /** change screen states automatically
        if false, screen only changes in response
        to POS commands
    */
    //private bool auto_state_change = false;

    /*
	private const int LCD_X_RES = 320;
	private const int LCD_Y_RES = 240;

	private const int STATE_START_TRANSACTION = 1;
	private const int STATE_SELECT_CARD_TYPE = 2;
	private const int STATE_ENTER_PIN = 3;
	private const int STATE_WAIT_FOR_CASHIER = 4;
	private const int STATE_SELECT_EBT_TYPE = 5;
    private const int STATE_SELECT_CASHBACK = 6;
    private const int STATE_GET_SIGNATURE = 7;
	private const int STATE_MANUAL_PAN = 11;
	private const int STATE_MANUAL_EXP = 12;
	private const int STATE_MANUAL_CVV = 13;

	private const int BUTTON_CREDIT = 5;
	private const int BUTTON_DEBIT = 6;
	private const int BUTTON_EBT = 7;
	private const int BUTTON_GIFT = 8;
	private const int BUTTON_EBT_FOOD = 9;
	private const int BUTTON_EBT_CASH = 10;
    private const int BUTTON_000  = 0;
    private const int BUTTON_500  = 5;
    private const int BUTTON_1000 = 10;
    private const int BUTTON_2000 = 20;
    private const int BUTTON_3000 = 30;
    private const int BUTTON_4000 = 40;
    private const int BUTTON_SIG_ACCEPT = 1;
    private const int BUTTON_SIG_RESET = 2;
	private const int BUTTON_HARDWARE_BUTTON = 0xff;

    private const int DEFAULT_WAIT_TIMEOUT = 1000;
    private const int FONT_SET = 4;
    private const int FONT_WIDTH = 16;
    private const int FONT_HEIGHT = 18;
    private string last_message = "";

    private string sig_message = "";

	private int current_state;
	private int ack_counter;
    */

    /**
      Does card type screen include foodstamp option

      The idea here is if you are *not* using auto_state_change,
      the commands coming from POS can can dictate which screens
      are displayed without recompiling the driver all the
      time.
    private bool type_include_fs = true;
    */

	private string usb_devicefile;

	public SPH_IngenicoRBA_USB(string p) : base(p)
    { 
		read_continues = false;
		long_pos = 0;
		usb_fs = null;
        verbose_mode = 1;
        last_message = null;
		
		#if MONO
		usb_devicefile = p;
		#else
		int vid = 0x0b00;
		int pid = 0x0074;
		usb_devicefile = string.Format("{0}&{1}",vid,pid);
		#endif
	}

	private void GetHandle()
    {
		usb_fs = null;
		usb_report_size = 32;

		/** prebuild ack and nack messages */
		nack = new byte[usb_report_size];
		ack = new byte[usb_report_size];
		nack[0] = 0x2;
		nack[1] = 0x1;
		nack[2] = 0x15;
		ack[0] = 0x2;
		ack[1] = 0x1;
		ack[2] = 0x6;
		for (int i=3; i<usb_report_size; i++) {
			nack[i] = 0;
			ack[i] = 0;
		}

		#if MONO
		usb_port = new USBWrapper_Posix();
		#else
		usb_port = new USBWrapper_Win32();
		#endif
		while(usb_fs == null){
			usb_fs = usb_port.GetUSBHandle(usb_devicefile,usb_report_size);
			if (usb_fs == null){
                if (this.verbose_mode > 0)
                    System.Console.WriteLine("No device");
				System.Threading.Thread.Sleep(5000);
			}
			else {
                if (this.verbose_mode > 0)
                    System.Console.WriteLine("USB device found");
            }
		}
		//AsyncRead();
	}

	public override void Read()
    { 
		// needs changes for mono. Async probably still doesn't work.
		GetHandle();
        AsyncRead();
        WriteMessageToDevice(OnlineMessage());
        WriteMessageToDevice(SwipeCardScreen());
	}

    public void WriteMessagetoDevice(byte[] msg)
    {
        ConfirmedWrite(msg);
    }

    private void AsyncRead()
    {
        if (this.verbose_mode > 0) {
            System.Console.WriteLine("waiting for input");
        }
		byte[] buf = new byte[usb_report_size];
		try {
			usb_fs.BeginRead(buf, 0, usb_report_size, new AsyncCallback(ReadCallback), buf);
        } catch(Exception ex){
			System.Console.WriteLine("BeginRead exception:");
			System.Console.WriteLine(ex);
		}
    }

	private void ReadCallback(IAsyncResult iar)
    {
		byte[] input = null;
		try {
            input = (byte[])iar.AsyncState;
			usb_fs.EndRead(iar);
        } catch(Exception ex){
			System.Console.WriteLine("EndRead exception:");
			System.Console.WriteLine(ex);
		}

        try {
            /* Data received, as bytes
            */
            if (this.verbose_mode > 0) {
                System.Console.WriteLine("");
                System.Console.WriteLine("IN BYTES:");
                for(int i=0;i<input.Length;i++){
                    if (i>0 && i %16==0) System.Console.WriteLine("");
                    System.Console.Write("{0:x} ",input[i]);
                }
                System.Console.WriteLine("");
                System.Console.WriteLine("");
            }
            if (input.Length > 3 && input[2] == 0x6) { // ACK 0x1 0x1 0x6
                last_message = null;
                System.Console.WriteLine("ACK : DEVICE");
            } else if (input.Length > 3 && input[2] == 0x15) { // NACK 0x1 0x1 0x15
                System.Console.WriteLine("NACK : DEVICE");
                // resend message?
            } else if (read_continues && input.Length > 0) {
                foreach (byte b in input) {
                    long_buffer[long_pos] = b;
                    long_pos++;
                }
                if (long_buffer[long_buffer.Length-2] == 0x3) {
                    read_continues = false;
                    SendAck();
                    byte[] sliced = new byte[input.Length - 2];
                    Array.Copy(input, 2, sliced, 0, sliced.Length);
                    HandleMessageFromDevice(sliced);
                }
            } else if (input.Length > 3 && input[1]+2 <= usb_report_size && input[2] == 0x2) {
                // single report message
				// 0x1 {message_length_byte} {message_data_bytes}
                System.Console.WriteLine("ACK : POS");
                SendAck();
                byte[] sliced = new byte[input.Length - 2];
                Array.Copy(input, 2, sliced, 0, sliced.Length);
                HandleMessageFromDevice(sliced);
            } else if (input.Length > 3 && input[1]+2 > usb_report_size && input[2] == 0x2) {
                // long message begins
                read_continues = true;
                long_buffer = input;
                long_pos = input.Length;
            } else {
                SendNack();
				System.Console.WriteLine("unknown message");
            }
        } catch(Exception ex){
			System.Console.WriteLine("Message data exception:");
			System.Console.WriteLine(ex);
		}

        AsyncRead();
    }

	/**
	 * Send ACK report to device
	 */
    private void SendAck()
    {
        usb_fs.Write(ack, 0, ack.Length);
    }

	/**
	 * Send NACK report to device
	 */
    private void SendNack()
    {
        usb_fs.Write(nack, 0, nack.Length);
    }

	/**
	 * Compute XOR for byte array
	 * If byte0 is 0x2 that gets skipped in
	 * accordance with RBA protocol
	 */
	private byte LrcXor(byte[] data)
    {
		byte lrc = 0;
		for (int i=0; i < data.Length; i++) {
            if (i == 0 && data[i] == 0x2) {
                continue; // STX not included in LRC
            }
			lrc = (byte)(lrc ^ data[i]);
        }
		return lrc;
	}

	private void ConfirmedWrite(byte[] b)
    {
		/**
		 * Widen message by one byte to add LRC
		 */
        byte[] msg = new byte[b.Length+1];
        for (int i=0; i < b.Length; i++) {
            msg[i] = b[i];
        }
        msg[b.Length] = LrcXor(b);

		/**
		 * Wait briefly to give the device time to
		 * send an ACK for previous write
		 */
		int count=0;
		while (last_message != null && count++ < 5) {
			Thread.Sleep(10);
        }
		last_message = b;

        int written = 0;
        while (written < msg.Length) {
			/**
			 * calculate how many bytes can be
			 * sent in this round's report
			 */
            int next = msg.Length - written;
            if (next > usb_report_size-2) {
                next = usb_report_size-2;
            }
			/**
			 * Remaining data will fit so this
			 * is the last report and needs to be
			 * zero padded
			 */
            if (next < usb_report_size-2) {
                byte[] pad_report = new byte[usb_report_size];
                pad_report[0] = 2;
                pad_report[1] = (byte)next;
                for (int i=2; i<usb_report_size; i++) {
                    if (written+i-2 < msg.Length) {
                        pad_report[i] = msg[written+i-2];
                    } else {
                        pad_report[i] = 0;
                    }
                }
				foreach (byte j in pad_report) {
					System.Console.Write("{0:x} ", j);
				}
				System.Console.WriteLine("");
                usb_fs.Write(pad_report, 0, pad_report.Length);
			/**
			 * Data will remain. Copy the next set of data
			 * bytes into report format and send them
			 */
            } else {
				byte[] report = new byte[usb_report_size];
				report[0] = 2;
				report[1] = (byte)next;
				for (int i=2; i<usb_report_size; i++) {
					report[i] = msg[written+i-2];
				}
                usb_fs.Write(report, 0, report.Length);
            }
            written += next;
        }
    }

}

}

