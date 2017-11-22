
using System;
using System.IO.Ports;
using System.Reflection;
using SPH;

[assembly: AssemblyVersion("1.0.*")]

class RbaVolume : SPH_IngenicoRBA_Common
{
    public RbaVolume(string p)
    {
        this.port = p;
    }

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

    public override void WriteMessageToDevice(byte[] msg)
    {
        var newMsg = GetLRC(msg);
        sp.Write(newMsg, 0, newMsg.Length);
    }

    private void initPort()
    {
        this.sp = new SerialPort();
        sp.PortName = this.port;
        sp.BaudRate = 19200;
        sp.DataBits = 8;
        sp.StopBits = StopBits.One;
        sp.Parity = Parity.None;
        sp.RtsEnable = true;
        sp.Handshake = Handshake.None;
        sp.ReadTimeout = 500;
    }

    public int SetVolume(int vol)
    {
        this.initPort();
        this.sp.Open();
        WriteMessageToDevice(WriteConfigMessage("7", "14", vol.ToString()));
        var ret =  this.sp.ReadByte();
        int moreBytes = 0;
        try {
            while (true) {
                sp.ReadByte();
                moreBytes++;
            }
        } catch (Exception) {
        }
        if (moreBytes > 0) {
            WriteMessageToDevice(new byte[1]{0x6});    
            Console.WriteLine("Acked " + moreBytes + " bytes");
        }
        this.sp.Close();

        return ret;
    }

    public static int Main(string[] args)
    {
        if (args.Length != 2) {
            Console.WriteLine("Usage: rbavolume.exe [com port number] [volume level]");
            return 1;
        }

        try {
            string port = "COM"+args[0];
            int vol = Int32.Parse(args[1]);
            var rba = new RbaVolume(port);
            var resp = rba.SetVolume(vol);
            Console.WriteLine("Response: " + resp);
        } catch (Exception ex) {
            Console.WriteLine("Error: " + ex.ToString());
            return 1;
        }

        return 0;
    }

    override public void Read()
    {
    }
}

