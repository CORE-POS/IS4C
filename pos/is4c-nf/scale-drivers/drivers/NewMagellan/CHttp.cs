
using System;
using System.Net;
using System.IO;
using System.Reflection;

[assembly: AssemblyVersion("1.0.*")]

namespace CHttp {

/**
  POCO for handing off responses
*/
public class ResponsePair {
    public HttpListenerResponse response { get; set; }
    public string body { get; set; }
}

/**
  Simple HTTP server. Does not pay attention
  to URLs and just checks the body of the request
  for a message.
*/
public class Server {

    private HttpListener http;

    public Server() {
        this.http = new HttpListener();
    }

    public void SetPort(int p) {
        var prefix = "http://localhost:" + p.ToString() + "/";
        this.http.Prefixes.Add(prefix);
    }

    /**
      Get next request. Returns response object and
      message body, if any. The response object is
      required later if the caller wants to respond.
    */
    public ResponsePair GetNext() {
        if (!this.http.IsListening) {
            this.http.Start();
        }
        var cxt = http.GetContext();
        var req = cxt.Request;
        var ret = new ResponsePair();
        ret.response = cxt.Response;
        if (!req.HasEntityBody) {
            ret.body = "";
        } else {
            var reader = new StreamReader(req.InputStream, req.ContentEncoding);
            ret.body = reader.ReadToEnd();
        }

        return ret;
    }

    /**
      Send back a response using an object return from GetNext()
    */
    public void Respond(HttpListenerResponse resp, string msg) {
        resp.Headers.Add("Access-Control-Allow-Origin", "*");
        var buf = System.Text.Encoding.UTF8.GetBytes(msg);
        resp.ContentLength64 = buf.Length;
        resp.OutputStream.Write(buf, 0, buf.Length);
        resp.OutputStream.Close();
    }
}

}

