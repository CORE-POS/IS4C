using System;
using System.IO;
using System.IO.Ports;
using System.Threading;
using System.Diagnostics;
using System.Collections.Generic;

namespace SPH {

public class SPH_SPA_Scale : SerialPortHandler 
{

    private const char STX = ((char)2);
    private const char ETX = '\r';

    public SPH_SPA_Scale(string p) : base(p)
    {
        sp = new SerialPort();
        sp.PortName = this.port;
        sp.BaudRate = 9600;
        sp.DataBits = 7;
        sp.StopBits = StopBits.One;
        sp.Parity = Parity.Even;
        sp.RtsEnable = true;
        sp.Handshake = Handshake.None;
        sp.ReadTimeout = 500;
    }

    override public void HandleMsg(string msg)
    {
        if (msg == "wakeup") {
            this.GetWeight();
        }
    }

    override public void Read()
    {
        string buffer = "";
        this.GetWeight();
        char[] trims = new char[]{ STX, ETX };
        while (SPH_Running) {
            try {
                char c = (char)sp.ReadByte();
                if (c == ETX) {
                    buffer = buffer.Trim(trims);
                    Console.WriteLine("RECV FROM SCALE: " + buffer);
                    buffer = this.TranslateResponse(buffer);
                    if (buffer.Length > 0) {
                        Console.WriteLine("PASS TO POS: " + buffer);
                        this.parent.MsgSend(buffer);
                    }
                    buffer = "";
                    Thread.Sleep(250);
                    this.GetWeight();
                }
                buffer += c;
            } catch (Exception) {
            }
        }
    }

    private void GetWeight()
    {
        try {
            this.sp.Write("W");
        } catch (Exception) {
        }
    }

    private string TranslateResponse(string resp)
    {
        if (resp.Length == 5) {
            return "S11" + resp.Replace(".", "");
        } else if (resp.Length == 2) {
            int status = (int)resp[1];
            if ((status & 1) != 0) {
                return "S141";
            } else if ((status & 2) != 0) {
                return "S142";
            } else if ((status & 4) != 0) {
                return "S145";
            }
        }

        Console.WriteLine("Unknown command: " + resp);
        var encoder = new System.Text.ASCIIEncoding();
        var bytes = encoder.GetBytes(resp);
        var hex = BitConverter.ToString(bytes);
        Console.WriteLine("As hex: " + hex);

        return "";
    }
}

}


