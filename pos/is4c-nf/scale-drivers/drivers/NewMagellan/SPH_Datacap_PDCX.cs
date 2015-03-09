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
using DSIPDCXLib;
using AxDSIPDCXLib;

namespace SPH {

public class SPH_Datacap_PDCX : SerialPortHandler 
{
    private DsiPDCX ax_control = null;
    private string device_identifier = null;
    protected string server_list = "x1.mercurydev.net;x2.mercurydev.net";
    protected int LISTEN_PORT = 9000; // acting as a Datacap stand-in

    public SPH_Datacap_PDCX(string p) : base(p)
    { 
        verbose_mode = 1;
        device_identifier=p;
    }

    /**
      Initialize PDCX control with servers
      and response timeout
    */
    protected bool initDevice()
    {
        ax_control = new DsiPDCX();
        ax_control.ServerIPConfig(server_list, 0);
        ax_control.SetResponseTimeout(60);

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
                TcpClient client = http.AcceptTcpClient();
                client.ReceiveTimeout = 100;
                NetworkStream stream = client.GetStream();
                string message = "";
                int bytes_read = 0;
                do {
                    bytes_read = stream.Read(buffer, 0, buffer.Length);
                    message += System.Text.Encoding.ASCII.GetString(buffer, 0, bytes_read);
                } while (stream.DataAvailable);

                message = GetHttpBody(message);
                if (this.verbose_mode > 0) {
                    System.Console.WriteLine(message);
                }

                string result = ax_control.ProcessTransaction(message, 0, null, null);
                result = WrapHttpResponse(result);
                if (this.verbose_mode > 0) {
                    System.Console.WriteLine(result);
                }

                byte[] response = System.Text.Encoding.ASCII.GetBytes(result);
                stream.Write(response, 0, response.Length);

                client.Close();
            } catch (Exception ex) {
                if (verbose_mode > 0) {
                    System.Console.WriteLine(ex);
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
                ax_control.CancelRequest();
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
        string result = ax_control.ProcessTransaction(xml, 0, null, null);
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
}

}
