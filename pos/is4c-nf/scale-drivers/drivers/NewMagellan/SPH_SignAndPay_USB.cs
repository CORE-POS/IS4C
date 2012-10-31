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

public class SPH_SignAndPay_USB : SerialPortHandler {

	private static String MAGELLAN_OUTPUT_DIR = "ss-output/";

	private USBWrapper usb_port;
	private bool read_continues;
	private byte[] long_buffer;
	private int long_length;
	private int long_pos;
	private FileStream usb_fs;
	private int usb_report_size;

	private string usb_devicefile;

	public SPH_SignAndPay_USB(string p) : base(p){ 
		read_continues = false;
		long_length = 0;
		long_pos = 0;
		
		#if MONO
		usb_devicefile = p;
		#else
		int vid = 0xacd;
		int pid = 0x2310;
		usb_devicefile = string.Format("{0}&{1}",vid,pid);
		#endif
	}

	public override void Read(){ 
		#if MONO
		usb_port = new USBWrapper_Posix();
		usb_report_size = 64;
		#else
		usb_port = new USBWrapper_Win32();
		usb_report_size = 65;
		#endif
		usb_fs = usb_port.GetUSBHandle(usb_devicefile,usb_report_size);
		if (usb_fs == null)
			System.Console.WriteLine("No device");
		else
			System.Console.WriteLine("USB device found");
		byte[] version = BuildCommand(new byte[]{0x78,0x46,0x01});
		SendReport(version);
		ReRead();
	}

	private void  ReadCallback(IAsyncResult iar){
		byte[] input = (byte[])iar.AsyncState;
		try {
			usb_fs.EndRead(iar);
		}
		catch (Exception ex){}

		// prepend extra byte if report size is 64
		// then parsing is identical for win32/linux
		if (usb_report_size == 64){
			byte[] temp_in = new byte[65];
			temp_in[0] = 0;
			for (int i=0; i < input.Length; i++)
				temp_in[i+1] = input[i];
			input = temp_in;
		}

		/* Data received, as bytes
		System.Console.WriteLine("");
		System.Console.WriteLine("");
		for(int i=0;i<input.Length;i++){
			if (i>0 && i %16==0) System.Console.WriteLine("");
			System.Console.Write(input[i]+" ");
		}
		System.Console.WriteLine("");
		System.Console.WriteLine("");
		*/


		int report_length = input[1] & (0x80-1);

		/*
		 * Bit 7 turned on means a multi-report message
		 */
		if ( (input[1] & 0x80) != 0)
			read_continues = true;

		if (report_length > 3){
			int data_length = input[3] + (input[4] << 8);

			int d_start = 5;
			if (input[d_start] == 0x6){ 
				d_start++; // ACK byte
				data_length--;
			}

			/*
			 * New multi-report message; init class members
			 */
			if (read_continues && long_length == 0){
				long_length = data_length;
				long_pos = 0;
				long_buffer = new byte[data_length];
				if (data_length > report_length)
					data_length = report_length-3;
			}
			else if (read_continues){
				// subsequent messages start immediately after
				// report ID & length fields
				d_start = 2;
			}

			if (data_length > report_length) data_length = report_length;

			byte[] data = new byte[data_length];
			for (int i=0; i<data_length && i+d_start<report_length+2;i++)
				data[i] = input[i+d_start];

			/**
			 * Append data from multi-report messages to the
			 * class member byte buffer
			 */
			if (read_continues){
				// last message will contain checksum bytes and
				// End Tx byte, so don't copy entire data array
				int d_len = ((input[1]&0x80)!=0) ? data.Length : data.Length-3;
				for(int i=0;i<d_len;i++){
					long_buffer[long_pos++] = data[i];
				}
			}

			System.Console.Write("Received: ");
			foreach(byte b in data)
				System.Console.Write((char)b);
			System.Console.WriteLine("");
		}

		if ( (input[1] & 0x80) == 0){
			if (long_buffer != null){
				System.Console.Write("Big Msg: ");
				foreach(byte b in long_buffer)
					System.Console.Write((char)b);
				System.Console.WriteLine("");
				string hex = BitConverter.ToString(long_buffer).Replace("-","");
				if (long_buffer[0] == 0x80){
					// magstripe data (encyrpted)
					// add serial control characters back
					// to the data. MPS gateway docs expect
					// them to be there
					hex = "02E600"+hex+"XXXX03";
					PushOutput(hex);
				}
				System.Console.WriteLine("HEX: "+hex);
				/*
				System.Console.Write("As Bytes: ");
				for (int i=0; i<long_buffer.Length;i++){
					if (i>0 && i %16==0) System.Console.WriteLine("");
					System.Console.Write(long_buffer[i]+" ");
				}
				*/
			}
			read_continues = false;
			long_length = 0;
			long_pos = 0;
			long_buffer = null;
		}

		
		ReRead();
	}

	private void ReRead(){
		byte[] buf = new byte[usb_report_size];
		usb_fs.BeginRead(buf, 0, usb_report_size, new AsyncCallback(ReadCallback), buf);
	}

	public override void HandleMsg(string msg){ 
	}

	/**
	 * Wrap command in proper leading and ending bytes
	 */
	private byte[] BuildCommand(byte[] data){
		int size = data.Length + 6;
		if (data.Length > 0x8000) size++;

		byte[] cmd = new byte[size];

		int pos = 0;
		cmd[pos] = 0x2;
		pos++;

		if (data.Length > 0x8000){
			cmd[pos] = (byte)(data.Length & 0xff);
			pos++;
			cmd[pos] = (byte)((data.Length >> 8) & 0xff);
			pos++;
			cmd[pos] = (byte)((data.Length >> 16) & 0xff);
			pos++;
		}
		else {
			cmd[pos] = (byte)(data.Length & 0xff);
			pos++;
			cmd[pos] = (byte)((data.Length >> 8) & 0xff);
			pos++;
		}

		for (int i=0; i < data.Length; i++){
			cmd[pos+i] = data[i];
		}
		pos += data.Length;

		cmd[pos] = LrcXor(data);
		pos++;

		cmd[pos] = LrcSum(data);
		pos++;

		cmd[pos] = 0x3;

		return cmd;
	}

	/**
	 * LRC type 1: sum of data bytes
	 */
	private byte LrcSum(byte[] data){
		int lrc = 0;
		foreach(byte b in data)
			lrc = (lrc + b) & 0xff;
		return (byte)lrc;
	}

	/**
	 * LRC type 2: xor of data bytes
	 */
	private byte LrcXor(byte[] data){
		byte lrc = 0;
		foreach(byte b in data)
			lrc = (byte)(lrc ^ b);
		return lrc;
	}

	/**
	 * Write to device in formatted reports
	 */
	private void SendReport(byte[] data){
		byte[] report = new byte[usb_report_size];
		int size_field = (usb_report_size == 65) ? 1 : 0;

		for(int j=0;j<usb_report_size;j++) report[j] = 0;
		int size=0;

		for (int i=0;i<data.Length;i++){
			if (i > 0 && i % 63 == 0){
				report[size_field] = 63 | 0x80;
				usb_fs.Write(report,0,usb_report_size);
				for(int j=0;j<usb_report_size;j++) report[j] = 0;
				size=0;
			}
			report[i+size_field+1] = data[i];
			size++;
		}

		report[size_field] = (byte)size;
		for(int i=0;i<usb_report_size;i++){
			if (i % 16 == 0 && i > 0)
				System.Console.WriteLine("");
			System.Console.Write(report[i]+" ");
		}
		System.Console.WriteLine("");
		usb_fs.Write(report,0,usb_report_size);
	}

	private void PushOutput(string s){
		int ticks = Environment.TickCount;
		char sep = System.IO.Path.DirectorySeparatorChar;
		while(File.Exists(MAGELLAN_OUTPUT_DIR+sep+ticks))
			ticks++;

		TextWriter sw = new StreamWriter(MAGELLAN_OUTPUT_DIR+sep+"tmp"+sep+ticks);
		sw = TextWriter.Synchronized(sw);
		sw.WriteLine(s);
		sw.Close();
		File.Move(MAGELLAN_OUTPUT_DIR+sep+"tmp"+sep+ticks,
			  MAGELLAN_OUTPUT_DIR+sep+ticks);
	}

}

}
