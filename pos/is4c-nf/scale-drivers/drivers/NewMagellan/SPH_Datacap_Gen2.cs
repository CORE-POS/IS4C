/*******************************************************************************

    Copyright 2019 Whole Foods Co-op

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

/*
  This is functionally pretty similar to SPH_Datacap_EMVX. The main read loop
  listens for HTTP requests, runs the request through the appropriate ActiveX
  control, and relays back a HTTP response. There are two significant differences:

  1) Rather than implementing its own HTTP server it uses CHttp which in turn
     uses System.Net.HttpListener and friends. There's nothing necessarily wrong
     with the implementation in SPH_Datacap_EMVX but the standard library one
     is probably more robust.

  2) All ActiveX calls are kept in the same thread (DeviceLoop). Using the same
     control from multiple threads may lead to some instability. Other threads
     that need to interact with the ActiveX controls need to push messages onto
     the work_queue and then monitor http_message for a response.

*/

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
using System.Collections.Concurrent;
using CustomForms;
using BitmapBPP;
using DSIEMVXLib;
using AxDSIEMVXLib;
using DSIPDCXLib;
using AxDSIPDCXLib;
using ComPort;
using CHttp;

namespace SPH {

public interface IMessage
{
    string type();
    string body();
    string from();
}

public class SimpleMessage : IMessage
{
    private string msg = "";
    public SimpleMessage(string s) {
        this.msg = s;
    }

    public string type() {
        return "simple";
    }

    public string body() {
        return this.msg;
    }

    public string from() {
        return "udp";
    }
}

public class EMVMessage : IMessage
{
    private string msg = "";
    private string f = "";
    public EMVMessage(string s, string f) {
        this.msg = s;
        this.f = f;
    }

    public string type() {
        return "emv";
    }

    public string body() {
        return this.msg;
    }

    public string from() {
        return this.f;
    }
}

public class PDCMessage : IMessage
{
    private string msg = "";
    private string f = "";
    public PDCMessage(string s, string f) {
        this.msg = s;
        this.f = f;
    }

    public string type() {
        return "pdc";
    }

    public string body() {
        return this.msg;
    }

    public string from() {
        return this.f;
    }
}

public class SPH_Datacap_Gen2 : SerialPortHandler 
{
    private DsiEMVX emv_ax_control = null;
    private DsiPDCX pdc_ax_control = null;
    private string device_identifier = null;
    private string com_port = "0";
    protected string server_list = "x1.mercurypay.com;x2.backuppay.com";
    protected int LISTEN_PORT = 8999; // acting as a Datacap stand-in
    protected short CONNECT_TIMEOUT = 60;
    protected string sequence_no = null;
    private RBA_Stub rba = null;
    private string xml_log = null;
    private bool enable_xml_log = false;
    private bool pdc_active;
    private Object pdcLock = new Object();
    private bool emv_reset;
    private bool always_reset = false;
    private bool emv_active;
    private Object emvLock = new Object();
    private string terminalID = "";
    private BlockingCollection<IMessage> work_queue = null;
    private BlockingCollection<string> http_mailbox = null;
    private int device_thread_id;

    public SPH_Datacap_Gen2(string p) : base(p)
    { 
        device_identifier=p;
        if (p.Contains(":")) {
            string[] parts = p.Split(new char[]{':'}, 2);
            device_identifier = parts[0];
            com_port = parts[1];
        }

        string my_location = AppDomain.CurrentDomain.BaseDirectory;
        char sep = Path.DirectorySeparatorChar;
        xml_log = my_location + sep + "log.xml";
        pdc_active = false;
        emv_active = false;
        emv_reset = true;

        if (device_identifier == "INGENICOISC250_MERCURY_E2E") {
            rba = new RBA_Stub("COM"+com_port);
            rba.SetEMV(RbaButtons.EMV);
        }

        this.work_queue = new BlockingCollection<IMessage>();
        this.http_mailbox = new BlockingCollection<string>();
    }

    /**
      Initialize EMVX control with servers
      and response timeout
    */
    protected bool ReInitDevice()
    {
        if (pdc_ax_control == null) {
            pdc_ax_control = new DsiPDCX();
            pdc_ax_control.ServerIPConfig(server_list, 0);
            pdc_ax_control.SetResponseTimeout(CONNECT_TIMEOUT);
            InitPDCX();
        }
        lock (pdcLock) {
            if (pdc_active) {
                Console.WriteLine("Reset PDC");
                pdc_ax_control.CancelRequest();
                pdc_active = false;
            }
        }
        /*
        lock (emvLock) {
            if (emv_active) {
                try {
                    Console.WriteLine("Reset EMV");
                    emv_ax_control.CancelRequest();
                    emv_active = false;
                } catch (Exception) {
                    // I assume this will through if either the ActiveX DLL
                    // was generated against an older OCX that doesn't have
                    // this method or if the DLL has the method but the
                    // OCX does not. OCX v1.22+ should have the method.
                }
            }
        }
        */

        if (emv_ax_control == null) {
            emv_ax_control = new DsiEMVX();
        }
        FlaggedReset();

        if (rba != null) {
            rba.SetParent(this.parent);
            rba.SetVerbose(this.verbose_mode);
            try {
                rba.stubStart();
            } catch (Exception) {}
        }

        return true;
    }

    /**
      Confine all interactions with ActiveX controls to this thread
      Other threads access this thread by adding IMessages to the work_queue
      and should monitor the http_mailbox for responses
    */
    public void DeviceLoop()
    {
        Console.WriteLine("Starting device thread: " + Thread.CurrentThread.ManagedThreadId);
        this.ReInitDevice();
        while (true) {
            var msg = this.work_queue.Take();
            if (msg.type() == "simple") {
                this.ProcessUdpMessage(msg.body());
            } else if (msg.type() == "emv") {
                string result = emv_ax_control.ProcessTransaction(msg.body());
                if (msg.from() == "http") {
                    this.http_mailbox.Add(result);
                }
            } else if (msg.type() == "pdc") {
                string result = pdc_ax_control.ProcessTransaction(msg.body(), 1, null, null);
                if (msg.from() == "http") {
                    this.http_mailbox.Add(result);
                }
            }
        }
    }

    /**
      Driver listens over TCP for incoming HTTP data. Driver
      is providing a web-service style endpoint so POS behavior
      does not have to change. Rather than POSTing information to
      a remote processor it POSTs information to the driver.

      Driver strips off headers, feeds XML into the dsiEMVX control,
      then sends the response back to the client.
    */
    public override void Read()
    { 
        var deviceThread = new Thread(new ThreadStart(this.DeviceLoop));
        deviceThread.Start();
        this.device_thread_id = deviceThread.ManagedThreadId;
        Console.WriteLine("Set device thread: " + this.device_thread_id);
        Server http = new Server();
        http.SetPort(LISTEN_PORT);
        while (SPH_Running) {
            string result = "";
            string keyVal = "key";
            bool saveResult = false;
            try {
                var pair = http.GetNext();

                if (rba != null) {
                    rba.stubStop();
                }

                string message = pair.body;
                message = message.Trim(new char[]{'"'});
                try {
                    XmlDocument request = new XmlDocument();
                    request.LoadXml(message);
                    keyVal = request.SelectSingleNode("TStream/Transaction/InvoiceNo").InnerXml;
                } catch (Exception) {
                    Console.WriteLine("Error parsing from\n" + message);
                }
                // Send EMV messages to EMVX, others
                // to PDCX
                if (message.Contains("EMV")) {
                    result = ProcessEMV(message, true);
                    saveResult = true;
                } else if (message.Contains("termSig")) {
                    FlaggedReset();
                    result = GetSignature(true);
                } else if (message.Length > 0) {
                    result = ProcessPDC(message);
                    saveResult = true;
                }
                http.Respond(pair.response, result);
            } catch (Exception ex) {
                this.LogMessage(ex.ToString());
                try {
                    if (saveResult && result.Length > 0) {
                        parent.SqlLog(keyVal, result);
                    }
                } catch (Exception) {
                    this.LogMessage(keyVal + ": " + result);
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
        this.work_queue.Add(new SimpleMessage(msg));
    }

    private void ProcessUdpMessage(string msg)
    { 
        // optional predicate for "termSig" message
        // predicate string is displayed on sig capture screen
        if (msg.Length > 7 && msg.Substring(0, 7) == "termSig") {
            //sig_message = msg.Substring(7);
            msg = "termSig";
        }
        if (msg.Length > 10 && msg.Substring(0, 10) == "screenLine") {
            string line = msg.Substring(10);
            msg = "IGNORE";
            if (rba != null) {
                rba.addScreenMessage(line);
            }
        }
        switch(msg) {
            case "termReset":
            case "termReboot":
                if (rba != null) {
                    rba.stubStop();
                }
                ReInitDevice();
                break;
            case "termManual":
                break;
            case "termApproved":
                FlaggedReset();
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
                FlaggedReset();
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
            this.enable_xml_log = true;
        }
    }

    /**
      Supported options:
        -- Global Options --
        * alwaysReset [boolean] default false
            Issue a PadReset command following a transaction. This will make
            the terminal beep until the customer removes their card. Control
            will not be returned to the cashier until the card is removed or
            the reset command times out.
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
        * buttons [string] default EMV
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
            if (d["buttons"].ToLower() == "credit") {
                this.rba.SetEMV(RbaButtons.Credit);
            } else if (d["buttons"].ToLower() == "cashback") {
                this.rba.SetEMV(RbaButtons.Cashback);
            }
        }

        if (this.rba != null && d.ContainsKey("defaultMessage")) {
            this.rba.SetDefaultMessage(d["defaultMessage"]);
        }

        if (d.ContainsKey("alwaysReset") && d["alwaysReset"].ToLower() == "true") {
            this.always_reset = true;
        }

        if (d.ContainsKey("logXML") && d["logXML"].ToLower() == "true") {
            this.enable_xml_log = true;
        }

        if (d.ContainsKey("logErrors") && d["logErrors"].ToLower() == "true") {
            this.enableUnifiedLog();
        }

        if (this.rba != null && d.ContainsKey("cashback") && (d["cashback"].ToLower() == "true" || d["cashback"].ToLower() == "false")) {
            this.rba.SetCashBack(d["cashback"].ToLower() == "true" ? true : false);
        }

        if (d.ContainsKey("servers")) {
            this.server_list = d["servers"];
        }

        if (d.ContainsKey("terminalID")) {
            this.terminalID = d["terminalID"];
        }
    }

    /**
      Process XML transaction using dsiPDCX
      @param xml the request body
      @param autoReset true if the request requires a reset, false if the request IS a reset
    */
    protected string ProcessEMV(string xml, bool autoReset)
    {
        /* 
           Substitute values into the XML request
           This is so the driver can handle any change
           in which hardware device is connected as well
           as so tracking SequenceNo values is not POS'
           problem.
        */
        xml = xml.Replace("{{SequenceNo}}", SequenceNo());
        if (IsCanadianDeviceType(this.device_identifier)) {
            // tag name is different in this case;
            // replace placeholder then the open/close tags
            xml = xml.Replace("{{SecureDevice}}", this.device_identifier);
            xml = xml.Replace("SecureDevice", "PadType");
        } else {
            xml = xml.Replace("{{SecureDevice}}", SecureDeviceToEmvType(this.device_identifier));
        }
        xml = xml.Replace("{{ComPort}}", com_port);
        if (this.terminalID.Length > 0) {
            xml = xml.Replace("{{TerminalID}}", this.terminalID);
        }

        try {
            /**
              Extract HostOrIP field and split it on commas
              to allow multiple IPs
            */
            XmlDocument request = new XmlDocument();
            request.LoadXml(xml);
            var IPs = request.SelectSingleNode("TStream/Transaction/HostOrIP").InnerXml.Split(new Char[]{','}, StringSplitOptions.RemoveEmptyEntries);
            string result = "";
            foreach (string IP in IPs) {
                // try request with an IP

                // If this is NOT a pad reset request, check the emv_reset
                // flag to see if a reset is needed. If so, execute one
                // and update the flag
                if (autoReset) {
                    FlaggedReset();
                }

                lock(emvLock) {
                    emv_active = true;
                }

                request.SelectSingleNode("TStream/Transaction/HostOrIP").InnerXml = IP;
                /*
                   To avoid a big refactor check thread IDs to see if we're already
                   in the DeviceLoop thread. Some messages processed in that thread
                   might trigger additional calls to ProcessEMV or ProcessPDC and 
                   recursive adding jobs to the work queue will deadlock
                */
                if (Thread.CurrentThread.ManagedThreadId != this.device_thread_id) {
                    this.work_queue.Add(new EMVMessage(request.OuterXml, "http"));
                    result = this.http_mailbox.Take();
                } else {
                    result = emv_ax_control.ProcessTransaction(request.OuterXml);
                }

                lock(emvLock) {
                    emv_active = false;
                    // if this is not a reset command, set the reset needed flag
                    if (autoReset) {
                        emv_reset = true;
                    }
                }

                if (enable_xml_log) {
                    using (StreamWriter sw = new StreamWriter(xml_log, true)) {
                        sw.WriteLine(DateTime.Now.ToString() + " (send emv): " + request.OuterXml);
                        sw.WriteLine(DateTime.Now.ToString() + " (recv emv): " + result);
                    }
                }
                XmlDocument doc = new XmlDocument();
                try {
                    doc.LoadXml(result);
                    // track SequenceNo values in responses
                    XmlNode sequence = doc.SelectSingleNode("RStream/CmdResponse/SequenceNo");
                    sequence_no = sequence.InnerXml;
                    XmlNode return_code = doc.SelectSingleNode("RStream/CmdResponse/DSIXReturnCode");
                    XmlNode origin = doc.SelectSingleNode("RStream/CmdResponse/ResponseOrigin");
                    /**
                      On anything that is not a local connectivity failure, exit the
                      loop and return the result without trying any further IPs.
                    */
                    if (origin.InnerXml != "Client" || return_code.InnerXml != "003006") {
                        break;
                    }
                } catch (Exception ex) {
                    // response was invalid xml
                    this.LogMessage(ex.ToString());
                    // status is unclear so do not attempt 
                    // another transaction
                    break;
                }
            }

            if (autoReset && this.always_reset) {
                FlaggedReset();
            }

            return result;

        } catch (Exception ex) {
            // request was invalid xml
            this.LogMessage(ex.ToString());
        }

        return "";
    }

    /**
      Process XML transaction using dsiPDCX
    */
    protected string ProcessPDC(string xml)
    {
        lock (pdcLock) {
            pdc_active = true;
        }
        string ret = "";
        try {
            xml = xml.Replace("{{SequenceNo}}", SequenceNo());
            xml = xml.Replace("{{SecureDevice}}", this.device_identifier);
            xml = xml.Replace("{{ComPort}}", com_port);
            if (this.terminalID.Length > 0) {
                xml = xml.Replace("{{TerminalID}}", this.terminalID);
            }

            /*
               To avoid a big refactor check thread IDs to see if we're already
               in the DeviceLoop thread. Some messages processed in that thread
               might trigger additional calls to ProcessEMV or ProcessPDC and 
               recursive adding jobs to the work queue will deadlock
            */
            if (Thread.CurrentThread.ManagedThreadId != this.device_thread_id) {
                this.work_queue.Add(new PDCMessage(xml, "http"));
                ret = this.http_mailbox.Take();
            } else {
                ret = pdc_ax_control.ProcessTransaction(xml, 1, null, null);
            }
            if (enable_xml_log) {
                using (StreamWriter sw = new StreamWriter(xml_log, true)) {
                    sw.WriteLine(DateTime.Now.ToString() + " (send pdc): " + xml);
                    sw.WriteLine(DateTime.Now.ToString() + " (recv pdc): " + ret);
                }
            }
        } catch (Exception ex) {
            this.LogMessage(ex.ToString());
        }
        lock (pdcLock) {
            pdc_active = false;
        }

        return ret;
    }

    /**
      Get the current sequence number OR the default
    */
    protected string SequenceNo()
    {
        return sequence_no != null ? sequence_no : "0010010010";
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
            + "<SecureDevice>"+ this.device_identifier + "</SecureDevice>"
            + "<ComPort>" + this.com_port + "</ComPort>"
            + "<PadType>" + SecureDeviceToPadType(device_identifier) + "</PadType>"
            + "</Admin>"
            + "</TStream>";
        
        return ProcessPDC(xml);
    }

    /**
      Set a lock, check if the reset flag is active.
      If so, issue a reset and clear the flag then
      release the lock
    */
    protected string FlaggedReset()
    {
        string ret = "";
        lock(emvLock) {
            if (emv_reset) {
                ret  = PadReset();
                emv_reset = false;
            }
        }

        return ret;
    }

    /**
      EMVX reset device for next transaction
    */
    protected string PadReset()
    {
        string xml="<?xml version=\"1.0\"?>"
            + "<TStream>"
            + "<Transaction>"
            + "<HostOrIP>127.0.0.1</HostOrIP>"
            + "<MerchantID>MerchantID</MerchantID>"
            + (this.terminalID.Length > 0 ? "<TerminalID>" + this.terminalID + "</TerminalID>" : "")
            + "<TranCode>EMVPadReset</TranCode>"
            + "<SecureDevice>" + SecureDeviceToEmvType(this.device_identifier) + "</SecureDevice>"
            + "<ComPort>" + this.com_port + "</ComPort>"
            + "<SequenceNo>" + SequenceNo() + "</SequenceNo>"
            + "</Transaction>"
            + "</TStream>";
    
        return ProcessEMV(xml, false);
    }

    /**
      PDCX method to get signature from device
    */
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
        string result = ProcessPDC(xml);
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
            case "INGENICOISC250_RAPIDCONNECT_E2E":
                return ComPortUtility.FindComPort("Ingenico");
            default:
                return "";
        }
    }

    /**
      Translate securedevice strings to padtype strings
    */
    protected string SecureDeviceToPadType(string device)
    {
        switch (device) {
            case "VX805XPI":
            case "VX805XPI_MERCURY_E2E":
                return "VX805";
            case "INGENICOISC250":
            case "INGENICOISC250_MERCURY_E2E":
            case "INGENICOISC250_RAPIDCONNECT_E2E":
                return "ISC250";
            default:
                return device;
        }
    }

    /**
      Translate pdc securedevice strings to emv securedevice strings
    */
    protected string SecureDeviceToEmvType(string device)
    {
        switch (device) {
            case "VX805XPI":
                return "EMV_VX805_RAPIDCONNECT";
            case "VX805XPI_MERCURY_E2E":
                return "EMV_VX805_MERCURY";
            case "INGENICOISC250_MERCURY_E2E":
                return "EMV_ISC250_MERCURY";
            case "INGENICOISC250_RAPIDCONNECT_E2E":
                return "EMV_ISC250_RAPIDCONNECT_E2E";
            default:
                return "EMV_" + device;
        }

    }

    protected bool IsCanadianDeviceType(string device)
    {
        switch (device) {
            case "Paymentech1":
            case "Global1":
            case "Moneris1":
                return true;
            default:
                return false;
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

