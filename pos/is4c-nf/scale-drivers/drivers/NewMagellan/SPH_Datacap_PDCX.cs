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
using ComPort;

namespace SPH {

public class SPH_Datacap_PDCX : SerialPortHandler 
{
    private DsiPDCX ax_control = null;
    private string device_identifier = null;
    private string com_port = "0";
    protected string server_list = "x1.mercurypay.com;x2.backuppay.com";
    protected int LISTEN_PORT = 8999; // acting as a Datacap stand-in
    protected short CONNECT_TIMEOUT = 60;
    private bool log_xml = false;
    private RBA_Stub rba = null;
    private bool pdc_active;
    private Object pdcLock = new Object();
    private short hideDialogs = 1;
    private string lastResponse = "";

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
        } else if (k == "logXML" && v == "true") {
            this.log_xml = true;
        }
    }

    /**
      Supported options:
        -- Global Options --
        * logErrors [boolean] default false
            Write error information to the same debug_lane.log file as PHP.
            Errors are logged regardless of whether the verbose switch (-v) 
            is used but not all verbose output is treated as an error & logged
        * logXML [boolean] default false
            Log XML requests & responses to "xml.log" in the current directory.

        -- Ingencio Specific Options --
        * disableRBA [boolean] default false
            Stops all direct communication with Ingenico terminal.
            Driver will solely utilize Datacap functionality
        * disableButtons [boolean] default false
            Does not display payment type or cashback selection buttons.
            RBA commands can still be used to display static text
            Irrelevant if disableRBA is true
        * buttons [string] default Credit
            Change labeling of the buttons. Valid options are "credit"
            and "cashback" currently.
            Irrelevant if disableRBA or disableButtons is true
        * defaultMessage [string] default "Welcome"
            Message displayed onscreen at the start of a transaction
            Irrelevant if disableRBA is true
        * cashback [boolean] default true
            Show cashback selections if payment type debit or ebt cash
            is selected.
            Irrelevant if disableRBA or disableButtons is true
        * servers [string] default "x1.mercurypay.com;x2.backuppay.com"
            Set PDCX server list
    */
    public override void SetConfig(Dictionary<string,string> d)
    {
        if (d.ContainsKey("disableRBA") && d["disableRBA"].ToLower() == "true") {
            try {
                if (this.rba != null) {
                    rba.stubStop();
                }
            } catch (Exception) {}
            this.rba = null;
        }

        if (this.rba != null && d.ContainsKey("disableButtons") && d["disableButtons"].ToLower() == "true") {
            this.rba.SetEMV(RbaButtons.None);
        }

        if (this.rba != null && d.ContainsKey("buttons")) {
            if (d["buttons"].ToLower() == "cashback") {
                this.rba.SetEMV(RbaButtons.Cashback);
            }
        }

        if (this.rba != null && d.ContainsKey("defaultMessage")) {
            this.rba.SetDefaultMessage(d["defaultMessage"]);
        }

        if (d.ContainsKey("logXML") && d["logXML"].ToLower() == "true") {
            this.log_xml = true;
        }

        if (d.ContainsKey("logErrors") && d["logErrors"].ToLower() == "true") {
            this.enableUnifiedLog();
        }

        if (d.ContainsKey("showDialogs") && d["showDialogs"].ToLower() == "true") {
            this.hideDialogs = 0;
        }

        if (this.rba != null && d.ContainsKey("cashback") && (d["cashback"].ToLower() == "true" || d["cashback"].ToLower() == "false")) {
            this.rba.SetCashBack(d["cashback"].ToLower() == "true" ? true : false);
        }

        if (d.ContainsKey("servers")) {
            this.server_list = d["servers"];
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
                pdc_active = false;
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

                        /**
                          Re-send the last successful response
                          If any kind of communication error occurs between this
                          HTTP server and the POS client then the client does
                          not know the status of their request. This "termGetLast"
                          signal lets the client re-establish the connection and
                          see if any information is available.
                        */
                        if (message.Contains("termGetLast")) {
                            SendResponse(stream, this.lastResponse);
                            continue;
                        }

                        this.lastResponse = "";
                        message = message.Replace("{{SecureDevice}}", this.device_identifier);
                        message = message.Replace("{{ComPort}}", com_port);
                        message = message.Trim(new char[]{'"'});
                        LogXml(message);

                        PdcActive(true);
                        string result = "Error";
                        if (message.Contains("termSig")) {
                            result = GetSignature(true);
                        } else {
                            result = ax_control.ProcessTransaction(message, this.hideDialogs, string.Empty, string.Empty);
                            this.lastResponse = result;
                        }
                        PdcActive(false);

                        LogXml(result);
                        SendResponse(stream, result);
                    }
                    client.Close();
                }
            } catch (Exception ex) {
                this.LogMessage(ex.ToString());
            } finally {
                PdcActive(false);
            }
        }
    }

    private void SendResponse(NetworkStream stream, string msg)
    {
        msg = WrapHttpResponse(msg);
        byte[] response = System.Text.Encoding.ASCII.GetBytes(msg);
        stream.Write(response, 0, response.Length);
    }

    private void PdcActive(bool isActive)
    {
        lock (pdcLock) {
            pdc_active = isActive;
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
            case "termReboot":
                lock (pdcLock) {
                    if (!pdc_active) {
                        if (rba != null) {
                            rba.hardReset();
                        }
                        ax_control = null;
                        initDevice();
                    }
                }
                break;
            case "termReset":
                if (rba != null) {
                    rba.stubStop();
                }
                initDevice();
                break;
            case "termManual":
                break;
            case "termApproved":
                if (rba != null) {
                    rba.showMessage("Approved");
                }
                break;
            case "termDeclined":
                if (rba != null) {
                    rba.showMessage("Declined");
                }
                break;
            case "termError":
                if (rba != null) {
                    rba.showMessage("Error");
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
            case "termFindPort":
                var new_port = this.PortSearch(this.device_identifier);
                if (new_port != "" && new_port != this.com_port && new_port.All(char.IsNumber)) {
                    this.com_port = new_port;
                }
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
        
        PdcActive(true);
        string ret = ax_control.ProcessTransaction(xml, this.hideDialogs, string.Empty, string.Empty);
        PdcActive(false);

        return ret;
    }
    
    protected string GetSignature(bool udp=true)
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
        PdcActive(true);
        string result = ax_control.ProcessTransaction(xml, this.hideDialogs, string.Empty, string.Empty);
        PdcActive(false);
        XmlDocument doc = new XmlDocument();
        try {
            doc.LoadXml(result);
            XmlNode status = doc.SelectSingleNode("RStream/CmdResponse/CmdStatus");
            if (status.InnerText != "Success") {
                return null;
            }
            string sigdata = doc.SelectSingleNode("RStream/Signature").InnerText;
            List<Point> points = SigDataToPoints(sigdata);

            string my_location = AppDomain.CurrentDomain.BaseDirectory;
            char sep = Path.DirectorySeparatorChar;
            string ticks = System.Guid.NewGuid().ToString();
            string filename = my_location + sep + "ss-output"+ sep + "tmp" + sep + ticks + ".bmp";
            BitmapBPP.Signature sig = new BitmapBPP.Signature(filename, points);
            if (udp) {
                parent.MsgSend("TERMBMP" + ticks + ".bmp");
            } else {
                return "<img>" + ticks + ".bmp</img>";
            }
            if (rba != null) {
                rba.showApproved();
            }
        } catch (Exception ex) {
            this.LogMessage(ex.ToString());
        }
        
        return "<err>Error collecting signature</err>";
    }

    protected string PortSearch(string device)
    {
        switch (device) {
            case "VX805XPI":
            case "VX805XPI_MERCURY_E2E":
                return ComPortUtility.FindComPort("Verifone");
            case "INGENICOISC250":
            case "INGENICOISC250_MERCURY_E2E":
                return ComPortUtility.FindComPort("Ingenico");
            default:
                return "";
        }
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

    private void VerboseOutput(string msg)
    {
        if (this.verbose_mode > 0) {
            Console.WriteLine(msg);
        }
    }

    private void LogXml(string xml)
    {
        if (log_xml) {
            using (StreamWriter file = new StreamWriter("log.xml", true)) {
                file.WriteLine(DateTime.Now.ToString() + ": " + xml);
            }
        }
    }
}

}
