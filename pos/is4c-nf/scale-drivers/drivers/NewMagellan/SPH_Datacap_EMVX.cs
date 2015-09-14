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
using CustomForms;
using DSIEMVXLib;
using AxDSIEMVXLib;
using DSIPDCXLib;
using AxDSIPDCXLib;

namespace SPH {

public class SPH_Datacap_EMVX : SerialPortHandler 
{
    private DsiEMVX emv_ax_control = null;
    private DsiPDCX pdc_ax_control = null; // can I include both?
    private string device_identifier = null;
    private string com_port = "0";
    protected string server_list = "x1.mercurydev.net;x2.mercurydev.net";
    protected int LISTEN_PORT = 8999; // acting as a Datacap stand-in
    protected string sequence_no = null;
    private bool log_xml = true;

    public SPH_Datacap_EMVX(string p) : base(p)
    { 
        verbose_mode = 1;
        device_identifier=p;
        if (p.Contains(":")) {
            string[] parts = p.Split(new char[]{':'}, 2);
            device_identifier = parts[0];
            com_port = parts[1];
        }
    }

    /**
      Initialize EMVX control with servers
      and response timeout
    */
    protected bool initDevice()
    {
        if (pdc_ax_control == null) {
            pdc_ax_control = new DsiPDCX();
            pdc_ax_control.ServerIPConfig(server_list, 0);
            InitPDCX();
        }
        pdc_ax_control.CancelRequest();

        if (emv_ax_control == null) {
            emv_ax_control = new DsiEMVX();
        }
        PadReset();

        return true;
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

                        message = GetHttpBody(message);

                        // Send EMV messages to EMVX, others
                        // to PDCX
                        string result = "";
                        if (message.Contains("EMV")) {
                            result = ProcessEMV(message);
                        } else {
                            result = ProcessPDC(message);
                        }
                        result = WrapHttpResponse(result);
                        if (this.verbose_mode > 0) {
                            Console.WriteLine(result);
                        }

                        byte[] response = System.Text.Encoding.ASCII.GetBytes(result);
                        stream.Write(response, 0, response.Length);
                        if (log_xml) {
                            using (StreamWriter file = new StreamWriter("log.xml", true)) {
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
                initDevice();
                break;
            case "termManual":
                break;
            case "termApproved":
                break;
            case "termSig":
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
      Process XML transaction using dsiPDCX
    */
    protected string ProcessEMV(string xml)
    {
        /* 
           Substitute values into the XML request
           This is so the driver can handle any change
           in which hardware device is connected as well
           as so tracking SequenceNo values is not POS'
           problem.
        */
        xml = xml.Trim(new char[]{'"'});
        xml = xml.Replace("{{SequenceNo}}", SequenceNo());
        xml = xml.Replace("{{SecureDevice}}", SecureDeviceToEmvType(this.device_identifier));
        xml = xml.Replace("{{ComPort}}", com_port);
        if (this.verbose_mode > 0) {
            Console.WriteLine(xml);
        }
        if (log_xml) {
            using (StreamWriter file = new StreamWriter("log.xml", true)) {
                file.WriteLine(xml);
            }
        }

        string result = emv_ax_control.ProcessTransaction(xml);
        // track SequenceNo values in responses
        XmlDocument doc = new XmlDocument();
        try {
            doc.LoadXml(result);
            XmlNode sequence = doc.SelectSingleNode("RStream/CmdResponse/SequenceNo");
            sequence_no = sequence.Value;
        } catch (Exception ex) {
            if (this.verbose_mode > 0) {
                Console.WriteLine(ex);
            }
        }

        return result;
    }

    /**
      Process XML transaction using dsiPDCX
    */
    protected string ProcessPDC(string xml)
    {
        xml = xml.Trim(new char[]{'"'});
        xml = xml.Replace("{{SequenceNo}}", SequenceNo());
        xml = xml.Replace("{{SecureDevice}}", this.device_identifier);
        xml = xml.Replace("{{ComPort}}", com_port);
        if (this.verbose_mode > 0) {
            Console.WriteLine(xml);
        }
        if (log_xml) {
            using (StreamWriter file = new StreamWriter("log.xml", true)) {
                file.WriteLine(xml);
            }
        }

        return pdc_ax_control.ProcessTransaction(xml, 1, null, null);
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
      EMVX reset device for next transaction
    */
    protected string PadReset()
    {
        string xml="<?xml version=\"1.0\"?>"
            + "<TStream>"
            + "<Transaction>"
            + "<HostOrIP>127.0.0.1</HostOrIP>"
            + "<MerchantID>MerchantID</MerchantID>"
            + "<TranCode>EMVPadReset</TranCode>"
            + "<SecureDevice>" + SecureDeviceToEmvType(this.device_identifier) + "</SecureDevice>"
            + "<ComPort>" + this.com_port + "</ComPort>"
            + "<SequenceNo>" + SequenceNo() + "</SequenceNo>"
            + "</Transaction>"
            + "</TStream>";
    
        return ProcessEMV(xml);
    }

    /**
      PDCX method to get signature from device
    */
    protected string GetSignature()
    {
        string xml="<?xml version=\"1.0\"?>"
            + "<TStream>"
            + "<Transaction>"
            + "<MerchantID>MerchantID</MerchantID>"
            + "<TranCode>GetSignature</TranCode>"
            + "<SecureDevice>"+ this.device_identifier + "</SecureDevice>"
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
            if (status.Value != "Success") {
                return null;
            }
            string sigdata = doc.SelectSingleNode("RStream/Signature").Value;
        } catch (Exception) {
            return null;
        }
        
        return null;
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
            case "VX805XPI_MERCURY_E2E":
                return "EMV_VX805_MERCURY";
            default:
                return "EMV_" + device;
        }

    }
}

}

