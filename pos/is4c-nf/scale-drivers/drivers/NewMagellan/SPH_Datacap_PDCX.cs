/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
using System.IO;
using System.Threading;
using System.Net;
using System.Net.Sockets;
using System.Xml;
using System.Drawing;
using System.Linq;
using System.Collections;
using System.Collections.Generic;
using CustomForms;
using BitmapBPP;
using DSIPDCXLib;
using AxDSIPDCXLib;

namespace SPH {

public class SPH_Datacap_PDCX : SerialPortHandler 
{
    private DsiPDCX ax_control = null;
    private string device_identifier = null;
    private string com_port = "0";
    protected string server_list = "x1.mercurypay.com;x2.backuppay.com";
    protected int LISTEN_PORT = 8999; // acting as a Datacap stand-in
    protected short CONNECT_TIMEOUT = 60;
    private bool log_xml = true;
    private RBA_Stub rba = null;
    private bool pdc_active;
    private Object pdcLock = new Object();

    public SPH_Datacap_PDCX(string p) : base(p)
    { 
        device_identifier=p;
        if (p.Contains(":")) {
            string[] parts = p.Split(new char[]{':'}, 2);
            device_identifier = parts[0];
            com_port = parts[1];
        }
        if (device_identifier == "INGENICOISC250_MERCURY_E2E") {
            rba = new RBA_Stub("COM"+com_port);
        }
        pdc_active = false;
    }

    public override void SetConfig(string k, string v)
    {
        if (k == "disableRBA" && v == "true") {
            try {
                if (this.rba != null) {
                    rba.stubStop();
                }
            } catch (Exception) {}
            this.rba = null;
        } else if (k == "disableButtons" && v == "true") {
            this.rba.SetEMV(RbaButtons.None);
        }
    }

    /**
      Initialize PDCX control with servers
      and response timeout
    */
    protected bool initDevice()
    {
        if (ax_control == null) {
            ax_control = new DsiPDCX();
            ax_control.ServerIPConfig(server_list, 0);
            ax_control.SetResponseTimeout(CONNECT_TIMEOUT);
            InitPDCX();
        }
        lock (pdcLock) {
            if (pdc_active) {
                ax_control.CancelRequest();
            }
        }
        if (rba != null) {
            rba.SetParent(this.parent);
            rba.SetVerbose(this.verbose_mode);
            rba.stubStart();
        }

        return true;
    }

    /**
      Driver listens over TCP for incoming HTTP data. Driver
      is providing a web-service style endpoint so POS behavior
      does not have to change. Rather than POSTing information to
      a remote processor it POSTs information to the driver.

      Driver strips off headers, feeds XML into the dsiPDCX control,
      then sends the response back to the client.
    */
    public override void Read()
    { 
        initDevice();
        TcpListener http = new TcpListener(IPAddress.Loopback, LISTEN_PORT);
        http.Start();
        byte[] buffer = new byte[10];
        while (SPH_Running) {
            try {
                using (TcpClient client = http.AcceptTcpClient()) {
                    client.ReceiveTimeout = 100;
                    using (NetworkStream stream = client.GetStream()) {
                        string message = "";
                        int bytes_read = 0;
                        do {
                            bytes_read = stream.Read(buffer, 0, buffer.Length);
                            message += System.Text.Encoding.ASCII.GetString(buffer, 0, bytes_read);
                        } while (stream.DataAvailable);

                        if (rba != null) {
                            rba.stubStop();
                        }

                        message = GetHttpBody(message);
                        message = message.Replace("{{SecureDevice}}", this.device_identifier);
                        message = message.Replace("{{ComPort}}", com_port);
                        message = message.Trim(new char[]{'"'});
                        if (this.verbose_mode > 0) {
                            Console.WriteLine(message);
                        }
                        lock (pdcLock) {
                            pdc_active = true;
                        }
                        string result = ax_control.ProcessTransaction(message, 1, null, null);
                        lock (pdcLock) {
                            pdc_active = false;
                        }
                        result = WrapHttpResponse(result);
                        if (this.verbose_mode > 0) {
                            Console.WriteLine(result);
                        }

                        byte[] response = System.Text.Encoding.ASCII.GetBytes(result);
                        stream.Write(response, 0, response.Length);
                        if (log_xml) {
                            using (StreamWriter file = new StreamWriter("log.xml", true)) {
                                file.WriteLine(message);
                                file.WriteLine(result);
                            }
			            }
                    }
                    client.Close();
                }
            } catch (Exception ex) {
                if (verbose_mode > 0) {
                    Console.WriteLine(ex);
                }
            }
        }
    }

    /**
      Pull HTTP body out of string. Simply looking
      for blank line between headers and body
    */
    protected string GetHttpBody(string http_request)
    {
        StringReader sr = new StringReader(http_request);
        string line;
        string ret = "";
        bool headers_over = false;
        while ((line = sr.ReadLine()) != null) {
            if (!headers_over && line == "") {
                headers_over = true;
            } else if (headers_over) {
                ret += line;
            }
        }

        return ret;
    }

    /**
      Add simple HTTP headers to content string
    */
    protected string WrapHttpResponse(string http_response)
    {
        string headers = "HTTP/1.0 200 OK\r\n"
            + "Connection: close\r\n"
            + "Content-Type: text/xml\r\n"
            + "Content-Length: " + http_response.Length + "\r\n" 
            + "Access-Control-Allow-Origin: http://localhost\r\n"
            + "\r\n"; 
        
        return headers + http_response;
    }

    public override void HandleMsg(string msg)
    { 
        // optional predicate for "termSig" message
        // predicate string is displayed on sig capture screen
        if (msg.Length > 7 && msg.Substring(0, 7) == "termSig") {
            //sig_message = msg.Substring(7);
            msg = "termSig";
        }
        switch(msg) {
            case "termReset":
            case "termReboot":
                if (rba != null) {
                    rba.stubStop();
                }
                initDevice();
                break;
            case "termManual":
                break;
            case "termApproved":
                if (rba != null) {
                    rba.showApproved();
                }
                break;
            case "termSig":
                if (rba != null) {
                    rba.stubStop();
                }
                GetSignature();
                break;
            case "termGetType":
                break;
            case "termGetTypeWithFS":
                break;
            case "termGetPin":
                break;
            case "termWait":
                break;
        }
    }

    /**
      PDCX initialize device
    */
    protected string InitPDCX()
    {
        string xml="<?xml version=\"1.0\"?>"
            + "<TStream>"
            + "<Admin>"
            + "<MerchantID>MerchantID</MerchantID>"
            + "<TranCode>SecureDeviceInit</TranCode>"
            + "<TranType>Setup</TranType>"
            + "<SecureDevice>" + this.device_identifier + "</SecureDevice>"
            + "<ComPort>" + this.com_port + "</ComPort>"
            + "<PadType>" + SecureDeviceToPadType(device_identifier) + "</PadType>"
            + "</Admin>"
            + "</TStream>";
        
        return ax_control.ProcessTransaction(xml, 1, null, null);
    }
    
    protected string GetSignature()
    {
        string xml="<?xml version=\"1.0\"?>"
            + "<TStream>"
            + "<Transaction>"
            + "<MerchantID>MerchantID</MerchantID>"
            + "<TranCode>GetSignature</TranCode>"
            + "<SecureDevice>"+ this.device_identifier + "</SecureDevice>"
            + "<ComPort>" + this.com_port + "</ComPort>"
            + "<Account>"
            + "<AcctNo>SecureDevice</AcctNo>"
            + "</Account>"
            + "</Transaction>"
            + "</TStream>";
        lock (pdcLock) {
            pdc_active = true;
        }
        string result = ax_control.ProcessTransaction(xml, 1, null, null);
        lock (pdcLock) {
            pdc_active = false;
        }
        XmlDocument doc = new XmlDocument();
        try {
            doc.LoadXml(result);
            XmlNode status = doc.SelectSingleNode("RStream/CmdResponse/CmdStatus");
            if (status.InnerText != "Success") {
                return null;
            }
            string sigdata = doc.SelectSingleNode("RStream/Signature").InnerText;
            List<Point> points = SigDataToPoints(sigdata);

            int ticks = Environment.TickCount;
            string my_location = AppDomain.CurrentDomain.BaseDirectory;
            char sep = Path.DirectorySeparatorChar;
            while (File.Exists(my_location + sep + "ss-output/"  + sep + ticks)) {
                ticks++;
            }
            string filename = my_location + sep + "ss-output"+ sep + "tmp" + sep + ticks + ".bmp";
            BitmapBPP.Signature sig = new BitmapBPP.Signature(filename, points);
            parent.MsgSend("TERMBMP" + ticks + ".bmp");
            if (rba != null) {
                rba.showApproved();
            }
        } catch (Exception) {
            return null;
        }
        
        return null;
    }

    protected string SecureDeviceToPadType(string device)
    {
        switch (device) {
            case "VX805XPI":
            case "VX805XPI_MERCURY_E2E":
                return "VX805";
            case "INGENICOISC250":
            case "INGENICOISC250_MERCURY_E2E":
                return "ISC250";
            default:
                return device;
        }
    }

    protected List<Point> SigDataToPoints(string data)
    {
        char[] comma = new char[]{','};
        char[] colon = new char[]{':'};
        var pairs = from pair in data.Split(colon) 
            select pair.Split(comma);
        var points = from pair in pairs 
            where pair.Length == 2
            select new Point(CoordsToInt(pair[0]), CoordsToInt(pair[1]));

        return points.ToList();
    }

    protected int CoordsToInt(string coord)
    {
        if (coord == "#") {
            return 0;
        } else {
            return Int32.Parse(coord);
        }
    }
}

}
