using System;
using System.IO;
using System.IO.Ports;
using System.Threading;
using System.Diagnostics;
using System.Collections.Generic;

namespace SPH {

public class SPH_SPA_Scale : SerialPortHandler 
{

    private const char STX = '\n';
    private const char ETX = '\r';

    public SPH_SPA_Scale(string p) : base(p)
    {
        sp = new SerialPort();
        sp.PortName = this.port;
        sp.BaudRate = 9600;
        sp.DataBits = 8;
        sp.StopBits = StopBits.One;
        sp.Parity = Parity.None;
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
            this.sp.Write(STX + "P" + ETX);
        } catch (Exception) {
        }
    }

    private string TranslateResponse(string resp)
    {
        if (resp.Length < 15) {
            return "";
        }

        var status = resp.Substring(0, 1);
        var motion = resp.Substring(3, 1);
        var weight = resp.Substring(5, 10).Trim();

        if (motion == "M") {
            return "S141";
        } else if (status == "O") {
            return "S142";
        } else if (status == "U") {
            return "S145";
        } else if (status == "E" || status == "I" || status == "T") {
            return "S140";
        } else if (status == "Z") {
            return "S11000";
        } else if (weight.Length > 0) {
            // format is xxxx.xxx with three decimal places
            // take out the decimal point and convert to just two
            // decimal places since that's what the POS side understands
            var fraction = weight.Substring(weight.Length - 3).Substring(0, 2);
            var whole = weight.Substring(0, weight.Length - 4);
            return "S11" + whole + fraction;
        }

        return "";
    }
}

}


