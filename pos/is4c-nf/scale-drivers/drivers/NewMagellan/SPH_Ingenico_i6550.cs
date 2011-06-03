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
 * 	SerialPortHandler implementation for the Ingenico 
 * 	signature capture devices using Retail Base
 * 	Application (RBA). Tested with i6550, should work
 * 	with i6580, i6770, and i6780 as well. Two other devices,
 *	i3070 and i6510, speak the same language but minor
 *	tweaking would need to be done to account for those
 *	devices not doing signature capture.
 *
 * Sets up a serial connection in the constructor
 *
 * Polls for data in Read(), writing responses back to the
 * device as needed and pushing data into the correct
 * WebBrowser frame
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

public class Signature {
	private int sig_length;
	private byte[][] sig_blocks;

	public Signature(int l){
		sig_length = l;
		sig_blocks = new byte[sig_length][];
	}

	public bool WriteBlock(int num, byte[] data){
		if (num >= sig_length) return false;
		sig_blocks[num] = data;
		return true;
	}

	public bool SigFull(){
		for(int i=0; i<sig_length;i++){
			if(sig_blocks[i] == null)
				return false;
		}
		return true;
	}

	// helpful example provided @ 
	// http://combustibleknowledge.com/2009/08/29/3-byte-ascii-format-deciphered/
	public string BuildImage(string path){
		List<Point> points = new List<Point>();
		List<byte> byteList = new List<byte>();

		for(int i=0;i<sig_length;i++){
			for(int j=0; j<sig_blocks[i].Length;j++){
				//Mask out most significant bit
				byte value = (byte)(sig_blocks[i][j] & 0x7f);
				byteList.Add(value);
			}
		}

		byte[] image = byteList.ToArray();
		Point lastPoint = new Point();
		int hiX, hiY;

		for (int i = 0; i < image.Length; i++){
			if (image[i] >= 96 && image[i] <= 111){
				hiX = (image[i] & 0x0C) * 128;
				hiY = (image[i] & 0x03) * 512;

				byte n1 = (byte)(image[i + 1] - 32);
				byte n2 = (byte)(image[i + 2] - 32);
				byte n3 = (byte)(image[i + 3] - 32);

				lastPoint.X = hiX + (n1 * 8) +
				    (n3 >> 3);
				lastPoint.Y = hiY + (n2 * 8) +
				    (n3 & 0x07);

				points.Add(lastPoint);
				i = i + 3;
			}
			else if (image[i] == 112){
				points.Add(new Point());
			}
			else {
				if (image[i] != 10 && image[i] != 13){
					int a, b;
					Point p = new Point();

					byte m1 = (byte)(image[i] - 32);
					byte m2 = (byte)(image[i + 1] - 32);
					byte m3 = (byte)(image[i + 2] - 32);

					a = (m1 * 8) + (m3 >> 3);
					b = (m2 * 8) + (m3 & 0x07);

					if (a > 256)
						p.X = lastPoint.X + (a - 512);
					else
						p.X = lastPoint.X + a;

					if (b > 256)
						p.Y = lastPoint.Y + (b - 512);
					else
						p.Y = lastPoint.Y + b;

					lastPoint.X = p.X;
					lastPoint.Y = p.Y;

					points.Add(p);
					i = i + 2;
				}
			}
		}

		return DrawSignatureImage(points,path);
	}

	private string DrawSignatureImage(List<Point> Points,string path){
		int width=2048;
		int height=512;
		Bitmap bmp = new Bitmap(width, height);

		Graphics g = Graphics.FromImage(bmp);
		Brush whiteBrush = new SolidBrush(Color.White);
		Brush blackBrush = new SolidBrush(Color.Black);
		Pen p = new Pen(blackBrush);

		bmp.SetResolution(height/2, width/2);
		g.TranslateTransform(0f, height);
		g.ScaleTransform(1f, -1f);
		g.FillRegion(whiteBrush,
			new Region(new Rectangle(0, 0, width, height)));

		p.Width = 10;
		p.StartCap = System.Drawing.Drawing2D.LineCap.Round;
		p.EndCap = System.Drawing.Drawing2D.LineCap.Round;
		p.LineJoin = System.Drawing.Drawing2D.LineJoin.Round;

		List<Point> line = new List<Point>();
		foreach (Point point in Points){
			if (point.IsEmpty){
				g.DrawLines(p, line.ToArray());
				line.Clear();
			}
			else{
				line.Add(point);
			}
		}

		Image newImage = bmp.GetThumbnailImage(width/5, height/5, null, IntPtr.Zero);

		// silly rigamarole to get a unique file name
		System.Security.Cryptography.MD5 hasher = System.Security.Cryptography.MD5.Create();
		System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
		byte[] hash = hasher.ComputeHash(enc.GetBytes(DateTime.Now.ToString()));
		System.Text.StringBuilder sBuilder = new System.Text.StringBuilder();
		for (int i = 0; i < hash.Length; i++)
		    sBuilder.Append(hash[i].ToString("x2"));
		string base_fn = path + "\\" + sBuilder.ToString()+".bmp";

		newImage.Save(base_fn, System.Drawing.Imaging.ImageFormat.Bmp);

		// pass through 1bpp conversion
		byte[] fixbpp = BitmapBPP.BitmapConverter.To1bpp(base_fn);
		System.IO.File.WriteAllBytes(base_fn,fixbpp);

		return base_fn;
	}

}

public class SPH_Ingenico_i6550 : SerialPortHandler {
	private const int application_id = 1;
	private const int parameter_id = 1;
	private const bool VERBOSE = true;
	private byte[] last_message;
	private string terminal_serial_number;
	private string pos_trans_no;
	private bool getting_signature;
	private Signature sig_object;

	private static String INGENICO_OUTPUT_DIR = "C:\\is4c\\scale-drivers\\drivers\\NewMagellan\\cc-output";

	public SPH_Ingenico_i6550(string p) : base(p){
		last_message = null;
		getting_signature = false;	

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
		ConfirmedWrite(GetLRC(OnlineMessage()));
	}

	private void ByteWrite(byte[] b){
		sp.Write(b,0,b.Length);
	}

	// Waits for acknowledgement from device & resends
	// if necessary. Should probably be used instead
	// of ByteWrite for most messages.
	private void ConfirmedWrite(byte[] b){
		if (VERBOSE)
			System.Console.WriteLine("Tried to write");

		while(last_message != null)
			Thread.Sleep(10);
		last_message = b;
		ByteWrite(b);

		if (VERBOSE)
			System.Console.WriteLine("wrote");
	}

	// computes check character and appends it
	// to the array
	private byte[] GetLRC(byte[] b){
		byte[] ret = new byte[b.Length+1];
		ret[0] = b[0]; // STX
		byte lrc = 0;
		for(int i=1; i < b.Length; i++){
			lrc ^= b[i];
			ret[i] = b[i];
		}
		ret[b.Length] = lrc;

		return ret;
	}

	// see if array has a valid check character
	private bool CheckLRC(byte[] b){
		byte lrc = 0;
		for(int i=1; i < b.Length-1; i++)
			lrc ^= b[i];
		return (lrc == b[b.Length-1])?true:false;
	}


	// main read loop
	override public void Read(){
		ArrayList bytes = new ArrayList();
		while(SPH_Running){
			try {
				int b = sp.ReadByte();
				if (b == 0x06){
					// ACK
					if (VERBOSE)
						System.Console.WriteLine("ACK!");
					last_message = null;
				}
				else if (b == 0x15){
					// NAK
					// re-send
					if (VERBOSE)
						System.Console.WriteLine("NAK!");
					ByteWrite(last_message);
				}
				else {
					// part of a message
					// force to be byte-sized
					bytes.Add(b & 0xff); 
				}
				if (bytes.Count > 2 && (int)bytes[bytes.Count-2] == 0x3){
					// end of message, send ACK
					ByteWrite(new byte[1]{0x6}); 
					// convoluted casting required to get
					// values out of ArrayList and into
					// a byte array
					byte[] buffer = new byte[bytes.Count];
					for(int i=0; i<bytes.Count; i++){
						buffer[i] = (byte)((int)bytes[i] & 0xff);
					}
					// deal with message, clear arraylist for the
					// next one
					HandleMessage(buffer);
					bytes.Clear();
				}
			}
			catch{}
		}
		
		if (VERBOSE)
			System.Console.WriteLine("Crashed?");
	}

	// deal to messages from the device
	void HandleMessage(byte[] buffer){
		if (VERBOSE){
			System.Console.WriteLine("Received:");
			foreach(byte b in buffer){
				if (b < 10)
					System.Console.Write("0{0} ",b);
				else
					System.Console.Write("{0} ",b);
			}
			System.Console.WriteLine();

			System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
			System.Console.WriteLine(enc.GetString(buffer));

			System.Console.WriteLine("LRC "+(CheckLRC(buffer)?"Valid":"Invalid"));
			System.Console.WriteLine();
		}

		int code = ((buffer[1]-0x30)*10) + (buffer[2]-0x30);
		switch(code){
		case 1: break; 	// online response from device
		case 4: break; 	// set payment response from device
		case 11:	// status response from device
			int status = ((buffer[4]-0x30)*10) + (buffer[5]-0x30);
			if (status == 11){ // signature ready
				ConfirmedWrite(GetLRC(GetVariableMessage("000712")));
			}
			else if (status == 6){
				ConfirmedWrite(GetLRC(GetVariableMessage("000712")));
			}
			else {
				Thread.Sleep(500);
				ConfirmedWrite(GetLRC(StatusRequestMessage()));
			}
			break;
		case 29:	// get variable response from device
			status = buffer[4] - 0x30;
			int var_code = 0;
			for(int i=0;i<6;i++)
				var_code = var_code*10 + (buffer[i+6]-0x30);
			if (var_code == 712)
				ParseSigLengthMessage(status,buffer);
			else if (var_code >= 700 && var_code <= 709)
				ParseSigBlockMessage(status,buffer);
			break;
		case 50:	// auth request from device
			ParseAuthMessage(buffer);
			break;
		}
	}

	// web page changed, check field for requests from POS
	// FIELDS CHECKED:
	// * ccTermOut
	// 	VALUES:
	// 	* total:xxx => new transaction amount
	// 	* reset => start the transaction over
	// 	* resettotal:xxx => start over but immediately re-total
	// 		(so the cashier doesn't have to do it again)
	// 	* sig => request a signature
	// 	* approval:xxx => pass back approval code
	override public void HandleMsg(String msg){
		if (!getting_signature && msg.Length > 6 && msg.Substring(0,6) == "total:"){
			string amount = msg.Substring(6);
			for(int i=0; i<amount.Length; i++){
				if ( (int)amount[i] < 0x30 || (int)amount[i] > 0x39 )
					return; // not a number
			}
			ConfirmedWrite(GetLRC(AmountMessage(amount)));

			if (VERBOSE)
				System.Console.WriteLine("Sent amount: "+amount);
		}
		else if (msg == "reset"){
			last_message = null;
			getting_signature = false;
			ByteWrite(GetLRC(HardResetMessage())); // force, no matter what
			ConfirmedWrite(GetLRC(SetPaymentTypeMessage("2")));

			if (VERBOSE)
				System.Console.WriteLine("Sent reset");
		}
		else if (!getting_signature && msg.Length > 11 && msg.Substring(0,11) == "resettotal:"){
			ConfirmedWrite(GetLRC(HardResetMessage()));
			ConfirmedWrite(GetLRC(SetPaymentTypeMessage("2")));

			if (VERBOSE)
				System.Console.WriteLine("Sent reset");

			string amount = msg.Substring(11);
			for(int i=0; i<amount.Length; i++){
				if ( (int)amount[i] < 0x30 || (int)amount[i] > 0x39 )
					return; // not a number
			}
			ConfirmedWrite(GetLRC(AmountMessage(amount)));

			if (VERBOSE)
				System.Console.WriteLine("Sent amount: "+amount);
		}
		else if (!getting_signature && msg.Length > 9 && msg.Substring(0,9) == "approval:"){
			string approval_code = msg.Substring(9);
			ConfirmedWrite(GetLRC(AuthMessage(approval_code)));
			getting_signature = true;	
			last_message = null;
			ConfirmedWrite(GetLRC(StatusRequestMessage()));
		}
		else if (!getting_signature && msg == "sig"){
			ConfirmedWrite(GetLRC(SigRequestMessage()));
			getting_signature = true;	
			last_message = null;
			ConfirmedWrite(GetLRC(StatusRequestMessage()));
		}
		else if (msg == "poke"){
			UdpClient u = new UdpClient("127.0.0.1",9451);
			Byte[] sendb = Encoding.ASCII.GetBytes("hi there");
			u.Send(sendb, sendb.Length);
		}

		if (VERBOSE)
			System.Console.WriteLine(msg);
	}

	/***********************************************
	 * Messages
	***********************************************/

	private byte[] OnlineMessage(){
		byte[] msg = new byte[13];
		msg[0] = 0x2; // STX

		msg[1] = 0x30; // Online Code
		msg[2] = 0x31;
		msg[3] = 0x2e;

		msg[4] = (application_id >> 24) & 0xff;
		msg[5] = (application_id >> 16) & 0xff;
		msg[6] = (application_id >> 8) & 0xff;
		msg[7] = application_id & 0xff;

		msg[8] = (parameter_id >> 24) & 0xff;
		msg[9] = (parameter_id >> 16) & 0xff;
		msg[10] = (parameter_id >> 8) & 0xff;
		msg[11] = parameter_id & 0xff;

		msg[12] = 0x3; // ETX

		return msg;
	}

	private byte[] HardResetMessage(){
		byte[] msg = new byte[5];
		msg[0] = 0x2; // STX

		msg[1] = 0x31; // Reset Code
		msg[2] = 0x30;
		msg[3] = 0x2e;

		msg[4] = 0x3; // ETX
		
		return msg;
	}

	private byte[] StatusRequestMessage(){
		byte[] msg = new byte[5];
		msg[0] = 0x2; // STX

		msg[1] = 0x31; // Reset Code
		msg[2] = 0x31;
		msg[3] = 0x2e;

		msg[4] = 0x3; // ETX
		
		return msg;
	}

	// valid ptypes: 1 through 5 -OR- A through P
	private byte[] SetPaymentTypeMessage(string ptype){
		byte[] p = new System.Text.ASCIIEncoding().GetBytes(ptype);
		byte[] msg = new byte[7];
		msg[0] = 0x2; // STX

		msg[1] = 0x30; // Set Payment Code
		msg[2] = 0x34;
		msg[3] = 0x2e;

		msg[4] = 0x30; // unconditional force
		msg[5] = p[0];
		
		msg[6] = 0x3; // ETX
		
		return msg;
	}

	// amount format: 5.99 = 599 (just like POS input)
	private byte[] AmountMessage(string amt){
		byte[] a = new System.Text.ASCIIEncoding().GetBytes(amt);
		byte[] msg = new byte[4 + a.Length + 1];
		msg[0] = 0x2; // STX

		msg[1] = 0x31; // Amount Code
		msg[2] = 0x33;
		msg[3] = 0x2e;

		for(int i=0; i < a.Length; i++)
			msg[i+4] = a[i];

		msg[msg.Length-1] = 0x3; // ETX

		return msg;
	}

	private byte[] SigRequestMessage(){
		string display = "Please sign";
		byte[] m = new System.Text.ASCIIEncoding().GetBytes(display);
		byte[] msg = new byte[4 + m.Length + 1]; 

		msg[0] = 0x2; // STX

		msg[1] = 0x32; // Sig Request Code
		msg[2] = 0x30;
		msg[3] = 0x2e;

		for(int i=0; i < m.Length; i++)
			msg[i+4] = m[i];

		msg[msg.Length-1] = 0x3; // ETX

		return msg;
	}

	// var_code should be length 6
	private byte[] GetVariableMessage(string var_code){
		System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();

		byte[] msg = new byte[13];

		msg[0] = 0x2; // STX
		msg[1] = 0x32; // get var code
		msg[2] = 0x39;
		msg[3] = 0x2e;

		msg[4] = 0x31; // documentation says this should be ASII 00,
		msg[5] = 0x30; // but example says it should be ASII 10

		byte[] tmp = enc.GetBytes(var_code);
		for(int i=0; i<tmp.Length;i++)
			msg[i+6] = tmp[i];

		msg[12] = 0x3; // ETX

		if (VERBOSE)
			System.Console.WriteLine("Sent: "+enc.GetString(msg));

		return msg;
	}

	private byte[] AuthMessage(string approval_code){
		System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
		string display_message = "Thank You!";

		byte[] msg = new byte[31 + display_message.Length + 2];

		msg[0] = 0x2; // STX
		msg[1] = 0x35; // Auth Code
		msg[2] = 0x30;
		msg[3] = 0x2e;

		byte[] tmp = enc.GetBytes(terminal_serial_number);
		for(int i=0; i<8; i++)
			msg[i+4] = tmp[i];

		msg[12] = 0x0;

		tmp = enc.GetBytes(pos_trans_no);
		for(int i=0; i<4; i++)
			msg[i+13] = tmp[i];

		if(approval_code == "denied"){
			msg[17] = 0x45;
			msg[18] = 0x3f;
		}
		else{
			msg[17] = 0x41;
			msg[18] = 0x3f;
		}

		tmp = enc.GetBytes(approval_code);
		for(int i=0;i<6;i++)
			msg[i+19] = tmp[i];

		string today = String.Format("(0:yyMMdd)",DateTime.Today);
		tmp = enc.GetBytes(today);
		for(int i=0;i<4;i++)
			msg[i+25] = tmp[i];

		tmp = enc.GetBytes(display_message);
		for(int i=0;i<tmp.Length;i++)
			msg[i+31] = tmp[i];

		msg[msg.Length-2] = 0x1c; // ASCII FS delimiter

		msg[msg.Length-1] = 0x3; // ETX

		return msg;
	}

	private void ParseSigLengthMessage(int status, byte[] msg){
		if (status == 2){
			int num_blocks = 0;
			int pos = 12;
			while(msg[pos] != 0x3){
				num_blocks = (num_blocks*10) + (msg[pos]-0x30);
				pos++;
			}
			if (num_blocks == 0){
				// should never happen, but just in case...
				ConfirmedWrite(GetLRC(StatusRequestMessage()));
			}
			else {
				sig_object = new Signature(num_blocks);
				ConfirmedWrite(GetLRC(GetVariableMessage("000700")));
			}
		}
		else {
			// didn't get data; re-request
			ConfirmedWrite(GetLRC(GetVariableMessage("000712")));
		}
	}

	private void ParseSigBlockMessage(int status, byte[] msg){
		System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
		
		if (status == 2){
			byte[] var_data = new byte[msg.Length-14];
			for(int i=0;i<var_data.Length;i++){
				var_data[i] = msg[i+12];
			}
			sig_object.WriteBlock(msg[11]-0x30,var_data);
			msg[11]++; // move to next sig block
		}

		if(sig_object.SigFull()){
			// signature capture complete
			string sigfile="";
			try{
			sigfile = sig_object.BuildImage(INGENICO_OUTPUT_DIR+"\\sig");
			}catch(Exception e){
				System.Console.WriteLine(e);
			}
			getting_signature = false;
			PushOutput(sigfile);
			ConfirmedWrite(GetLRC(HardResetMessage()));
		}
		else {
			// get the next sig block or re-request the
			// current one
			string var_num = enc.GetString(new byte[6]
					{msg[6],msg[7],msg[8],msg[9],msg[10],msg[11]});
			ConfirmedWrite(GetLRC(GetVariableMessage(var_num)));
		}
	}

	private void ParseAuthMessage(byte[] msg){
		System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
		
		// skipping 0 (stx), 1-3 (message #)
	
		string aquirer = enc.GetString(new byte[6]{msg[4],msg[5],msg[6],msg[7],msg[8],msg[9]});	

		string merch_id = enc.GetString(new byte[12]{msg[10],msg[11],msg[12],msg[13],
							msg[14],msg[15],msg[16],msg[17],
							msg[18],msg[19],msg[20],msg[21]});

		string store_id = enc.GetString(new byte[4]{msg[22],msg[23],msg[24],msg[25]});

		string pinpad_id = enc.GetString(new byte[4]{msg[26],msg[27],msg[28],msg[29]});

		string std_ind_class = enc.GetString(new byte[4]{msg[30],msg[31],msg[32],msg[33]});

		string country = enc.GetString(new byte[3]{msg[34],msg[35],msg[36]});

		string zipcode = enc.GetString(new byte[5]{msg[37],msg[38],msg[39],msg[40],msg[41]});

		string timezone = enc.GetString(new byte[3]{msg[42],msg[43],msg[44]});

		string trans_code = enc.GetString(new byte[2]{msg[45],msg[46]});

		terminal_serial_number = enc.GetString(new byte[8]{msg[47],msg[48],msg[49],msg[50],
							msg[51],msg[52],msg[53],msg[54]});

		// skipping 55 (constant 0)
		
		pos_trans_no = enc.GetString(new byte[4]{msg[56],msg[57],msg[58],msg[59]});

		// skipping 60 (constant @)

		int data_source = (int)msg[61];

		// variable length from here on, fields termed by 0x1c

		int pos = 62;
		while(msg[pos] != 0x1c) pos++;
		byte[] stripe_bytes = new byte[pos-62];
		Array.Copy(msg,62,stripe_bytes,0,pos-62);
		string stripe = enc.GetString(stripe_bytes);

		pos++; // sitting at the 0x1c;

		// read PIN info
		int pin_start = pos;
		while(msg[pos] != 0x1c) pos++;
		byte[] pin_bytes = new byte[pos-pin_start];
		Array.Copy(msg,pin_start,pin_bytes,0,pos-pin_start);
		string pin_enc_block = "";
		string pin_ksi = "";
		string pin_device_id = "";
		string pin_enc_counter = "";
		// M/S or DUKPT
		if (pin_bytes.Length == 23 || pin_bytes.Length == 43){
			byte[] block = new byte[16];
			Array.Copy(pin_bytes,7,block,0,16);
			pin_enc_block = enc.GetString(block);
		}
		// only DUKPT
		if (pin_bytes.Length == 43){
			byte[] ksi = new byte[6];
			Array.Copy(pin_bytes,27,ksi,0,6);
			pin_ksi = enc.GetString(ksi);

			byte[] did = new byte[5];
			Array.Copy(pin_bytes,33,did,0,5);
			pin_device_id = enc.GetString(did);

			byte[] ec = new byte[5];
			Array.Copy(pin_bytes,38,ec,0,5);
			pin_enc_counter = enc.GetString(ec);
		}
		
		pos++; // should be at next 0x1c;

		int amount = 0;
		while(msg[pos] != 0x1c){
			amount = (amount*10) + ((int)msg[pos] - 0x30);
			pos++;
		}

		if (data_source == 0x48 || data_source == 0x58){
			// track 1
			stripe = "%"+stripe+"?";
		}
		else if (data_source == 0x44 || data_source == 0x54){
			// track 2
			stripe = ";"+stripe+"?";	
		}
		stripe = "T"+amount+"?"+stripe;
		if (pin_bytes.Length == 23){
			stripe += "PM"+pin_enc_block+"?";
		}
		else if (pin_bytes.Length == 43){
			stripe += "PD"+pin_enc_block;
			stripe += ((char)0x1e)+pin_ksi;
			stripe += ((char)0x1e)+pin_device_id;
			stripe += ((char)0x1e)+pin_enc_counter;
			stripe += "?";
		}

		PushOutput(stripe);

		if (VERBOSE)
			System.Console.WriteLine(stripe);
		stripe = null;
	}

	private void PushOutput(string s){
		int ticks = Environment.TickCount;
		while(File.Exists(INGENICO_OUTPUT_DIR+"\\"+ticks))
			ticks++;

		TextWriter sw = new StreamWriter(INGENICO_OUTPUT_DIR+"\\tmp\\"+ticks);
		sw = TextWriter.Synchronized(sw);
		sw.WriteLine(s);
		sw.Close();
		File.Move(INGENICO_OUTPUT_DIR+"\\tmp\\"+ticks,
			  INGENICO_OUTPUT_DIR+"\\"+ticks);
	}
}

}
