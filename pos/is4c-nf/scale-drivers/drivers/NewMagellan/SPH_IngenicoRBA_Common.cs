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
        int width=512;
        int height=128;
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
                try {
                    g.DrawLines(p, line.ToArray());
                    line.Clear();
                } catch (Exception) {
                    System.Console.Write("BAD LINE: ");
                    foreach (Point pt in line) {
                        System.Console.Write(pt);
                        System.Console.Write(" ");
                    }
                    System.Console.WriteLine("");
                }
            }
            else{
                line.Add(point);
            }
        }

        // silly rigamarole to get a unique file name
        System.Security.Cryptography.MD5 hasher = System.Security.Cryptography.MD5.Create();
        System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
        byte[] hash = hasher.ComputeHash(enc.GetBytes(DateTime.Now.ToString()));
        System.Text.StringBuilder sBuilder = new System.Text.StringBuilder();
        for (int i = 0; i < hash.Length; i++)
            sBuilder.Append(hash[i].ToString("x2"));
        string base_fn = path + "\\" + sBuilder.ToString()+".bmp";

        bmp.Save(base_fn, System.Drawing.Imaging.ImageFormat.Bmp);

        // pass through 1bpp conversion
        byte[] fixbpp = BitmapBPP.BitmapConverter.To1bpp(base_fn);
        System.IO.File.WriteAllBytes(base_fn,fixbpp);

        return base_fn;
    }

}

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
public class SPH_IngenicoRBA_Common : SerialPortHandler 
{
    protected byte[] last_message;
    /** not used with on-demand implementation
    private string terminal_serial_number;
    private string pos_trans_no;
    */
    private bool getting_signature;
    private Signature sig_object;
    protected bool auto_state_change = true;
    private string masked_pan = "";

    private static String MAGELLAN_OUTPUT_DIR = "ss-output/";

    // spacing matters on these
    protected const string EBT_CA = "1 0 4  0  14 10000 1 1 1 0      0 132 0 1 0 D 0 0 406";
    protected const string EBT_FS = "1 0 4  0  14     0 1 1 1 0      0 133 0 1 0 D 0 0 406";

    // to allow RBA_Stub
    public SPH_IngenicoRBA_Common() { }

    public SPH_IngenicoRBA_Common(string p) : base(p)
    {
        last_message = null;
        getting_signature = false;
        if (auto_state_change) {
            System.Console.WriteLine("SPH_Ingenico starting in AUTO mode");
        } else {
            System.Console.WriteLine("SPH_Ingenico starting in COORDINATED mode");
        }
    }

    // see if array has a valid check character
    private bool CheckLRC(byte[] b)
    {
        byte lrc = 0;
        for (int i=1; i < b.Length-1; i++) {
            lrc ^= b[i];
        }
        return (lrc == b[b.Length-1]) ? true : false;
    }


    // main read loop
    override public void Read()
    {
        // child class must override again
    }

    /**
      Handle messages from the device
      @param buffer - the message
    */
    public void HandleMessageFromDevice(byte[] buffer)
    {
        System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
        if (this.verbose_mode > 0) {
            System.Console.WriteLine("Received:");
            foreach (byte b in buffer) {
                if (b < 10) {
                    System.Console.Write("0{0} ",b);
                } else {
                    System.Console.Write("{0} ",b);
                }
            }
            System.Console.WriteLine();

            System.Console.WriteLine(enc.GetString(buffer));

            System.Console.WriteLine("LRC "+(CheckLRC(buffer)?"Valid":"Invalid"));
            System.Console.WriteLine();
        }

        int code = ((buffer[1]-0x30)*10) + (buffer[2]-0x30);
        switch (code) {
            case 1: break;     // online response from device
            case 4: break;     // set payment response from device

            case 11:    
                // status response from device
                int status = ((buffer[4]-0x30)*10) + (buffer[5]-0x30);
                if (status == 11) { // signature ready
                    WriteMessageToDevice(GetVariableMessage("000712"));
                } else if (status == 6) {
                    WriteMessageToDevice(GetVariableMessage("000712"));
                } else if (status == 10) {
                    Thread.Sleep(500);
                    WriteMessageToDevice(StatusRequestMessage());
                }
                break;

            case 23:
            case 87:
                // get card info repsponse
                if (buffer[4] != 0x30) { // invalid status
                    HandleMsg("termReset");
                    break;
                }
                string card_msg = enc.GetString(buffer);
                card_msg = card_msg.Substring(1, card_msg.Length - 3); // trim STX, ETX, LRC 
                if (card_msg.Length > 5) {
                    card_msg = card_msg.Replace(new String((char)0x1c, 1), "@@");
                    PushOutput("PANCACHE:" + card_msg);
                    if (this.verbose_mode > 0) {
                        System.Console.WriteLine(card_msg);
                    }
                    if (card_msg.Contains("%") && card_msg.Contains("^")) {
                        string[] parts = card_msg.Split(new char[]{'%'}, 2);
                        parts = parts[1].Split(new char[]{'^'}, 2);
                        masked_pan = parts[0].Substring(1);
                    } else if (card_msg.Contains(";") && card_msg.Contains("=")) {
                        string[] parts = card_msg.Split(new char[]{';'}, 2);
                        parts = parts[1].Split(new char[]{'='}, 2);
                        masked_pan = parts[0];
                    }
                    if (auto_state_change) {
                        WriteMessageToDevice(GetCardType());
                        Thread.Sleep(2000);
                        char fs = (char)0x1c;
                        string buttons = "Bbtna,S"+fs+"Bbtnb,S"+fs+"Bbtnc,S"+fs+"Bbtnd,S";
                        WriteMessageToDevice(UpdateScreenMessage(buttons));
                    }
                } else {
                    WriteMessageToDevice(SwipeCardScreen());
                }
                break;

                case 24:
                    // response from a form message
                    // should normally mean "card type selected"
                    // if the buffer is undersized or the status byte
                    // is not ASCII zero, go back the beginning
                    // otherwise see which type was selected
                    if (buffer.Length < 6 || buffer[4] != 0x30 || buffer[5] == 0x1b) {
                        WriteMessageToDevice(SwipeCardScreen());
                        PushOutput("TERMCLEARALL");
                    } else if (buffer[5] == 0x41) {
                        PushOutput("TERM:Debit");
                        if (auto_state_change) {
                            WriteMessageToDevice(PinEntryScreen());
                        }
                    } else if (buffer[5] == 0x42) {
                        PushOutput("TERM:Credit");
                        if (auto_state_change) {
                            WriteMessageToDevice(TermWaitScreen());
                        }
                    } else if (buffer[5] == 0x43) {
                        PushOutput("TERM:EbtCash");
                        if (auto_state_change) {
                            WriteMessageToDevice(PinEntryScreen());
                        }
                    } else if (buffer[5] == 0x44) {
                        PushOutput("TERM:EbtFood");
                        if (auto_state_change) {
                            WriteMessageToDevice(PinEntryScreen());
                        }
                    }
                    break;

                case 29:    
                    // get variable response from device
                    status = buffer[4] - 0x30;
                    int var_code = 0;
                    for (int i=0;i<6;i++) {
                        var_code = var_code*10 + (buffer[i+6]-0x30);
                    }
                    if (var_code == 712) {
                        ParseSigLengthMessage(status,buffer);
                    } else if (var_code >= 700 && var_code <= 709) {
                        ParseSigBlockMessage(status,buffer);
                    }
                    break;

                case 31:
                    // PIN entry response
                    if (buffer.Length < 5 || buffer[4] != 0x30) {
                        // problem; start over
                        WriteMessageToDevice(SwipeCardScreen());
                        PushOutput("TERMCLEARALL");
                    } else {
                        string pin_msg = enc.GetString(buffer);
                        // trim STX, command prefix, status byte, ETX, and LRC
                        pin_msg = pin_msg.Substring(5, pin_msg.Length - 7);
                        PushOutput("PINCACHE:" + pin_msg);
                        if (auto_state_change) {
                            WriteMessageToDevice(TermWaitScreen());
                        }
                    }
                    break;

                case 50:    
                    // auth request from device
                    ParseAuthMessage(buffer);
                    break;
            }
    }

    /**
        Write a message to the device
    */
    public virtual void WriteMessageToDevice(byte[] msg)
    {
    }

    override public void HandleMsg(String msg)
    {
        // optional predicate for "termSig" message
        // predicate string is displayed on sig capture screen
        if (msg.Length > 7 && msg.Substring(0, 7) == "termSig") {
            //string sig_message = msg.Substring(7);
            msg = "termSig";
        }

        if (msg == "termReset" || msg == "termReboot") {
            last_message = null;
            getting_signature = false;
            WriteMessageToDevice(HardResetMessage());
            WriteMessageToDevice(SwipeCardScreen());
            WriteMessageToDevice(SaveStateMessage());

            if (this.verbose_mode > 0) {
                System.Console.WriteLine("Sent reset");
            }

        } else if (!getting_signature && msg == "termSig") {
            WriteMessageToDevice(SigRequestMessage());
            getting_signature = true;    
            last_message = null;
            WriteMessageToDevice(StatusRequestMessage());
        } else if (!auto_state_change && !getting_signature && (msg == "termGetType" || msg == "termGetTypeWithFS")) {
            WriteMessageToDevice(GetCardType());
            Thread.Sleep(2000);
            char fs = (char)0x1c;
            string buttons = "Bbtna,S"+fs+"Bbtnb,S"+fs+"Bbtnc,S"+fs+"Bbtnd,S";
            WriteMessageToDevice(UpdateScreenMessage(buttons));
        } else if (!auto_state_change && !getting_signature && msg == "termWait") {
            WriteMessageToDevice(TermWaitScreen());
        } else if (!auto_state_change && !getting_signature && msg == "termApproved") {
            WriteMessageToDevice(TermApprovedScreen());
        } else if (!auto_state_change && !getting_signature && msg == "termGetPin") {
            WriteMessageToDevice(PinEntryScreen());
        } else if (msg == "termReConfig") {
            WriteMessageToDevice(OfflineMessage());
            // enable ebt cash
            WriteMessageToDevice(WriteConfigMessage("11", "3", EBT_CA));
            // enable ebt food
            WriteMessageToDevice(WriteConfigMessage("11", "4", EBT_FS));
            // mute beep volume
            WriteMessageToDevice(WriteConfigMessage("7", "14", "5"));
            // new style save/restore state
            WriteMessageToDevice(WriteConfigMessage("7", "15", "1"));
            // do not show messages between screens
            WriteMessageToDevice(WriteConfigMessage("7", "1", "0"));
            // send reset reply
            WriteMessageToDevice(WriteConfigMessage("7", "9", "1"));
            WriteMessageToDevice(OnlineMessage());
        }

        if (this.verbose_mode > 0) {
            System.Console.WriteLine(msg);
        }
    }

    /***********************************************
     * Messages
    ***********************************************/

    protected byte[] OnlineMessage()
    {
        byte[] msg = new byte[13];
        msg[0] = 0x2; // STX

        msg[1] = 0x30; // Online Code
        msg[2] = 0x31;
        msg[3] = 0x2e;

        /*
        msg[4] = (application_id >> 24) & 0xff;
        msg[5] = (application_id >> 16) & 0xff;
        msg[6] = (application_id >> 8) & 0xff;
        msg[7] = application_id & 0xff;

        msg[8] = (parameter_id >> 24) & 0xff;
        msg[9] = (parameter_id >> 16) & 0xff;
        msg[10] = (parameter_id >> 8) & 0xff;
        msg[11] = parameter_id & 0xff;
        */
        msg[4] = 0x30;
        msg[5] = 0x30;
        msg[6] = 0x30;
        msg[7] = 0x30;
        msg[8] = 0x30;
        msg[9] = 0x30;
        msg[10] = 0x30;
        msg[11] = 0x30;

        msg[12] = 0x3; // ETX

        return msg;
    }

    protected byte[] OfflineMessage()
    {
        byte[] msg = new byte[9];
        msg[0] = 0x2; // STX

        msg[1] = 0x30; // Offline Code
        msg[2] = 0x30;
        msg[3] = 0x2e;

        msg[4] = 0x30;
        msg[5] = 0x30;
        msg[6] = 0x30;
        msg[7] = 0x30;

        msg[8] = 0x3; // ETX

        return msg;
    }

    protected byte[] HardResetMessage()
    {
        byte[] msg = new byte[5];
        msg[0] = 0x2; // STX

        msg[1] = 0x31; // Reset Code
        msg[2] = 0x30;
        msg[3] = 0x2e;

        msg[4] = 0x3; // ETX
        
        return msg;
    }

    protected byte[] ScreenLinesReset()
    {
        byte[] msg = new byte[6];
        msg[0] = 0x2; // STX

        msg[1] = 0x31; // Reset Code
        msg[2] = 0x35;
        msg[3] = 0x2e;

        msg[4] = 0x38;

        msg[5] = 0x3; // ETX
        
        return msg;
    }


    protected byte[] StatusRequestMessage()
    {
        byte[] msg = new byte[5];
        msg[0] = 0x2; // STX

        msg[1] = 0x31; // Status Code
        msg[2] = 0x31;
        msg[3] = 0x2e;

        msg[4] = 0x3; // ETX
        
        return msg;
    }

    // valid ptypes: 1 through 5 -OR- A through P
    /**
      Not used in current implementation. Commented to
      reduce compilation warnings.
      29Dec2014
    protected byte[] SetPaymentTypeMessage(string ptype)
    {
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
    */

    // amount format: 5.99 = 599 (just like POS input)
    /**
      Not used in current implementation. Commented to
      reduce compilation warnings.
      29Dec2014
    protected byte[] AmountMessage(string amt)
    {
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
    */

    protected byte[] SigRequestMessage()
    {
        string display = "Please sign";
        byte[] m = new System.Text.ASCIIEncoding().GetBytes(display);
        byte[] msg = new byte[4 + m.Length + 1]; 

        msg[0] = 0x2; // STX

        msg[1] = 0x32; // Sig Request Code
        msg[2] = 0x30;
        msg[3] = 0x2e;

        for (int i=0; i < m.Length; i++) {
            msg[i+4] = m[i];
        }

        msg[msg.Length-1] = 0x3; // ETX

        return msg;
    }

    // var_code should be length 6
    protected byte[] GetVariableMessage(string var_code)
    {
        System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();

        byte[] msg = new byte[13];

        msg[0] = 0x2; // STX
        msg[1] = 0x32; // get var code
        msg[2] = 0x39;
        msg[3] = 0x2e;

        msg[4] = 0x31; // documentation says this should be ASII 00,
        msg[5] = 0x30; // but example says it should be ASII 10

        byte[] tmp = enc.GetBytes(var_code);
        for (int i=0; i<tmp.Length;i++) {
            msg[i+6] = tmp[i];
        }

        msg[12] = 0x3; // ETX

        if (this.verbose_mode > 0) {
            System.Console.WriteLine("Sent: "+enc.GetString(msg));
        }

        return msg;
    }

    // again var_code should have length 6
    /**
      Not used in current implementation. Commented to
      reduce compilation warnings.
      29Dec2014
    */
    protected byte[] SetVariableMessage(string var_code, string var_value)
    {
        System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();

        byte[] valbytes = enc.GetBytes(var_value);
        byte[] msg = new byte[12 + valbytes.Length + 1]; 

        msg[0] = 0x2; // STX
        msg[1] = 0x32; // set var code
        msg[2] = 0x38;
        msg[3] = 0x2e;

        msg[4] = 0x39; // no response
        msg[5] = 0x31; // constant

        byte[] tmp = enc.GetBytes(var_code);
        for(int i=0; i<tmp.Length;i++)
            msg[i+6] = tmp[i];

        for(int i=0; i<valbytes.Length;i++)
            msg[i+12] = valbytes[i];

        msg[msg.Length-1] = 0x3; //ETX

        if (this.verbose_mode > 0)
            System.Console.WriteLine("Sent: "+enc.GetString(msg));

        return msg;
    }

    /**
      Not used in current implementation. Commented to
      reduce compilation warnings.
      29Dec2014
    protected byte[] AuthMessage(string approval_code)
    {
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
    */

    // write DFS configuration values
    /**
      Not used in current implementation. Commented to
      reduce compilation warnings.
      29Dec2014
    */
    protected byte[] WriteConfigMessage(string group_num, string index_num, string val)
    {
        System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
        byte[] gb = enc.GetBytes(group_num);
        byte[] ib = enc.GetBytes(index_num);
        byte[] vb = enc.GetBytes(val);

        byte[] msg = new byte[4 + gb.Length + ib.Length + vb.Length + 4];

        msg[0] = 0x2; // STX
        msg[1] = 0x36; // Write Code
        msg[2] = 0x30;
        msg[3] = 0x2e;

        int pos = 4;

        // write group
        for(int i=0; i<gb.Length; i++)
            msg[pos++] = gb[i];

        msg[pos++] = 0x1d; // ASII GS delimiter

        // write index
        for(int i=0; i<ib.Length; i++)
            msg[pos++] = ib[i];

        // write value
        msg[pos++] = 0x1d; // ASII GS delimiter

        for(int i=0; i<vb.Length; i++)
            msg[pos++] = vb[i];

        msg[msg.Length-2] = 0x1c; // ASCII FS delimiter

        msg[msg.Length-1] = 0x3; // ETX
        return msg;
    }

    protected byte[] ReadConfigMessage(string group_num, string index_num)
    {
        System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
        byte[] gb = enc.GetBytes(group_num);
        byte[] ib = enc.GetBytes(index_num);

        byte[] msg = new byte[4 + gb.Length + ib.Length + 3];

        msg[0] = 0x2; // STX
        msg[1] = 0x36; // Write Code
        msg[2] = 0x31;
        msg[3] = 0x2e;

        int pos = 4;
        // group number
        for (int i=0; i<gb.Length; i++) {
            msg[pos++] = gb[i];
        }

        msg[pos++] = 0x1d; // ASII GS delimiter

        // index number
        for (int i=0; i<ib.Length; i++) {
            msg[pos++] = ib[i];
        }

        msg[pos++] = 0x1d; // ASII GS delimiter
        msg[msg.Length-1] = 0x3; // ETX

        return msg;
    }

    protected byte[] SwipeCardScreen()
    {
        System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
        byte[] prompt = enc.GetBytes("Swipe Card");
        byte[] msg = new byte[5 + prompt.Length];

        msg[0] = 0x2;
        msg[1] = 0x38;
        msg[2] = 0x37;
        msg[3] = 0x2e;
        int pos = 4;
        foreach (byte b in prompt) {
            msg[pos] = b;
            pos++;
        }
        msg[pos] = 0x3;

        return msg;
    }

    /**
      Draw select-card-type screen on demand
    */
    protected byte[] GetCardType()
    {
        System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
        byte[] form_name = enc.GetBytes("pay1.K3Z");
        byte[] msg = new byte[6 + form_name.Length + 0];

        msg[0] = 0x2;
        msg[1] = 0x32;
        msg[2] = 0x34;
        msg[3] = 0x2e;

        int pos = 4;
        foreach (byte b in form_name) {
            msg[pos] = b;
            pos++;
        }

        msg[pos] = 0x1c; // FS

        /*
        msg[pos+1] = 0x42;
        msg[pos+2] = 0x62;
        msg[pos+3] = 0x74;
        msg[pos+4] = 0x6e;
        msg[pos+5] = 0x61;
        msg[pos+6] = 0x2c;
        msg[pos+7] = 0x53;
        */

        msg[pos+1] = 0x3;

        return msg;
    }

    protected byte[] SimpleMessageScreen(string the_message)
    {
        System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
        the_message = "Tpromptline1,"+the_message;
        byte[] text = enc.GetBytes(the_message);
        byte[] form = enc.GetBytes("msg.k3z");
        byte[] msg = new byte[6 + form.Length + text.Length];

        msg[0] = 0x2;
        msg[1] = 0x32;
        msg[2] = 0x34;
        msg[3] = 0x2e;

        int pos = 4;
        foreach (byte b in form) {
            msg[pos] = b;
            pos++;
        }

        msg[pos] = 0x1c;
        pos++;

        foreach (byte b in text) {
            msg[pos] = b;
            pos++;
        }

        msg[pos] = 0x3;

        return msg;
    }

    protected byte[] TermApprovedScreen()
    {
        return SimpleMessageScreen("Approved - Thank You");
    }

    protected byte[] TermWaitScreen()
    {
        return SimpleMessageScreen("Waiting for total");
    }

    protected byte[] PinEntryScreen()
    {
        System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
        byte[] pan = enc.GetBytes(masked_pan);
        byte[] form = enc.GetBytes("pin.K3Z");
        byte[] msg = new byte[10 + pan.Length + form.Length];

        msg[0] = 0x2;
        msg[1] = 0x33;
        msg[2] = 0x31;
        msg[3] = 0x2e;

        msg[4] = 0x44; // DUKPT, default settings
        msg[5] = 0x2a;

        msg[6] = 0x31;
        msg[7] = 0x1c; 

        int pos = 8;
        foreach (byte b in pan) {
            msg[pos] = b;
            pos++;
        }

        msg[pos] = 0x1c; 
        pos++;
        
        foreach (byte b in form) {
            msg[pos] = b;
            pos++;
        }

        msg[pos] = 0x3;

        System.Console.WriteLine("get pin for:" + masked_pan);

        return msg;
    }

    protected byte[] SaveStateMessage()
    {
        return new byte[6]{ 0x2, 0x33, 0x34, 0x2e, 0x53, 0x3 };
    }

    protected byte[] UpdateScreenMessage(string update)
    {
        System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
        byte[] encode = enc.GetBytes(update);
        byte[] msg = new byte[5 + encode.Length];

        msg[0] = 0x2;
        msg[1] = 0x37;
        msg[2] = 0x30;
        msg[3] = 0x2e;

        int pos = 4;
        foreach (byte b in encode) {
            msg[pos] = b;
            pos++;
        }

        msg[pos] = 0x3;

        return msg;
    }

    protected void ParseSigLengthMessage(int status, byte[] msg)
    {
        if (status == 2) {
            int num_blocks = 0;
            int pos = 12;
            while (msg[pos] != 0x3) {
                num_blocks = (num_blocks*10) + (msg[pos]-0x30);
                pos++;
            }
            if (num_blocks == 0) {
                // should never happen, but just in case...
                WriteMessageToDevice(StatusRequestMessage());
            } else {
                sig_object = new Signature(num_blocks);
                WriteMessageToDevice(GetVariableMessage("000700"));
            }
        } else {
            // didn't get data; re-request
            WriteMessageToDevice(GetVariableMessage("000712"));
        }
    }

    protected void ParseSigBlockMessage(int status, byte[] msg)
    {
        System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
        
        if (status == 2) {
            byte[] var_data = new byte[msg.Length-14];
            for (int i=0;i<var_data.Length;i++) {
                var_data[i] = msg[i+12];
            }
            sig_object.WriteBlock(msg[11]-0x30,var_data);
            msg[11]++; // move to next sig block
        }

        if (sig_object.SigFull()) {
            // signature capture complete
            string sigfile="";
            try {
                char sep = System.IO.Path.DirectorySeparatorChar;
                sigfile = sig_object.BuildImage(MAGELLAN_OUTPUT_DIR+sep+"tmp");
            } catch (Exception e) {
                if (this.verbose_mode > 0) {
                    System.Console.WriteLine(e);
                }
            }
            getting_signature = false;
            FileInfo fi = new FileInfo(sigfile);
            PushOutput("TERMBMP" + fi.Name);
            HandleMsg("termReset");
        } else {
            // get the next sig block or re-request the
            // current one
            string var_num = enc.GetString(new byte[6]
                    {msg[6],msg[7],msg[8],msg[9],msg[10],msg[11]});
            WriteMessageToDevice(GetVariableMessage(var_num));
        }
    }

    protected void ParseAuthMessage(byte[] msg)
    {
        System.Text.ASCIIEncoding enc = new System.Text.ASCIIEncoding();
        
        // skipping 0 (stx), 1-3 (message #)
    
        /** don't need any of these values for anything
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
        */

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
        stripe = null;
    }

    private void PushOutput(string s)
    {
        parent.MsgSend(s);
    }
}

}
