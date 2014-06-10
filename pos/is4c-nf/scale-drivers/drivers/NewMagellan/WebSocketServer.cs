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
using System.Net;
using System.Net.Sockets;
using System.Collections.Generic;
using System.Threading;

namespace WebSockets 
{
    /**
      Simple server for handling WebSocket connections
      Currently push-to-clients only. Ignores any
      data sent by clients.
    */
    public class WebSocketServer
    {
        private TcpListener _tcp;
        private List<TcpClient> _clients;
        private Queue<string> _msg_queue;
        public static ManualResetEvent _connect_event;
        private int _verbose;

        /**
          constructor
        */
        public WebSocketServer(IPEndPoint ip)
        {
            _tcp = new TcpListener(ip);
            _clients = new List<TcpClient>();
            _msg_queue = new Queue<string>();
            _connect_event = new ManualResetEvent(false);
            _verbose = 0;
        }

        public void SetVerbose(int v)
        {
            _verbose = v;
        }

        /**
          Main loop
        */
        public void Run()
        {
            _tcp.Start();

            while (true) {
                _connect_event.Reset();
                _tcp.BeginAcceptTcpClient(new AsyncCallback(ConnectClient), _tcp);
                _connect_event.WaitOne(1000, false);
                /* test sending
                if (_clients.Count > 0) {
                    string ticks = Environment.TickCount.ToString();
                    Push("{\"ticks\":\"" + ticks + "\", \"server\":\"CORE\"}");
                }
                */
            }
        }

        /**
          Convert message to WebSocket data framing
          format
        */
        private byte[] MessageToFrames(string msg)
        {
            byte[] payload = System.Text.Encoding.UTF8.GetBytes(msg);
            byte[] resp;
            if (payload.Length <= 125) {
                /* option 1
                   single byte length
                */
                resp = new byte[payload.Length + 2];
                resp[0] = 0x81;
                resp[1] = (byte)payload.Length;
                for (int i=0; i<payload.Length; i++) {
                    resp[i+2] = payload[i];
                }
            } else if (payload.Length > 125 && payload.Length <= (1 << 16)) {
                /* option 2
                   byte1 126 => 2 length bytes follow
                */
                resp = new byte[payload.Length + 4];
                resp[0] = 0x81;
                resp[1] = 126;
                resp[2] = (byte)((payload.Length >> 8) & 0xff);
                resp[3] = (byte)(payload.Length & 0xff);
                for (int i=0; i<payload.Length; i++) {
                    resp[i+4] = payload[i];
                }
            } else if (payload.Length > (1 << 16) && payload.Length <= (1 << 64)) {
                /* option 3
                   byte1 128 => 8 length bytes follow
                */
                resp = new byte[payload.Length + 10];
                resp[0] = 0x81;
                resp[1] = 127;
                resp[2] = (byte)((payload.Length >> 56) & 0xff);
                resp[3] = (byte)((payload.Length >> 48) & 0xff);
                resp[4] = (byte)((payload.Length >> 40) & 0xff);
                resp[5] = (byte)((payload.Length >> 32) & 0xff);
                resp[6] = (byte)((payload.Length >> 24) & 0xff);
                resp[7] = (byte)((payload.Length >> 16) & 0xff);
                resp[8] = (byte)((payload.Length >> 8) & 0xff);
                resp[9] = (byte)(payload.Length & 0xff);;
                for (int i=0; i<payload.Length; i++) {
                    resp[i+10] = payload[i];
                }
            } else {
                /* message is too long for one frame
                   create max size frame, recurse to get
                   remainder
                */
                byte[] frame = new byte[payload.Length + 10];
                frame[0] = 0x1;
                frame[1] = 127;
                frame[2] = (byte)((payload.Length >> 56) & 0xff);
                frame[3] = (byte)((payload.Length >> 48) & 0xff);
                frame[4] = (byte)((payload.Length >> 40) & 0xff);
                frame[5] = (byte)((payload.Length >> 32) & 0xff);
                frame[6] = (byte)((payload.Length >> 24) & 0xff);
                frame[7] = (byte)((payload.Length >> 16) & 0xff);
                frame[8] = (byte)((payload.Length >> 8) & 0xff);
                frame[9] = (byte)(payload.Length & 0xff);;
                for (int i=0; i<(1<<64); i++) {
                    frame[i+10] = payload[i];
                }

                byte[] next = new byte[(1<<64) - payload.Length];
                for (int i=0; i<next.Length; i++) {
                    next[i] = payload[(1<<64) + i];
                }

                byte[] other_frames = MessageToFrames(System.Text.Encoding.UTF8.GetString(next));
                resp = new byte[frame.Length + other_frames.Length];
                for (int i=0; i<frame.Length; i++) {
                    resp[i] = frame[i];
                }
                for (int i=0; i<other_frames.Length; i++) {
                    resp[i + frame.Length] = other_frames[i];
                }
            }

            return resp;
        }

        private WsDataFrame FramesToMessage(byte[] frames)
        {
            if (frames.Length < 2) {
                throw new WsProtocolException("Invalid frame: too short (header)");
            }

            int opcode = frames[0] & 0xf;
            int last_fragment = frames[0] & 0x80;
            int masked = frames[1] & 0x80;
            long length = frames[1] & 0x7f;
            int data_starts = 6;

            if (masked == 0) {
                throw new WsFatalClientException("Client data not masked");
            }

            byte[] mask_key = new byte[4];
            if (length <= 125) {
                if (frames.Length < 6) {
                    throw new WsProtocolException("Invalid frame: too short (mask)");
                }
                Array.Copy(frames, 2, mask_key, 0, 4);
            } else if (length == 126) {
                if (frames.Length < 8) {
                    throw new WsProtocolException("Invalid frame: too short (mask)");
                }
                length = ((frames[2] << 8) & 0xff00) + (frames[3] & 0xff);
                Array.Copy(frames, 4, mask_key, 0, 4);
                data_starts = 8;
            } else if (length == 127) {
                if (frames.Length < 14) {
                    throw new WsProtocolException("Invalid frame: too short (mask)");
                }
                length = ((frames[2] & 0xff) << 56)
                    + ((frames[3] & 0xff) << 48)
                    + ((frames[4] & 0xff) << 40)
                    + ((frames[5] & 0xff) << 32)
                    + ((frames[6] & 0xff) << 24)
                    + ((frames[7] & 0xff) << 16)
                    + ((frames[8] & 0xff) <<  8)
                    + (frames[9] &0xff);
                Array.Copy(frames, 10, mask_key, 0, 4);
                data_starts = 14;
            }

            if (frames.Length < (data_starts + length)) {
                throw new WsProtocolException("Invalid frame: too short (payload)");
            }

            byte[] payload = new byte[length];
            for (int i=0; i < length; i++) {
                payload[i] = (byte)(frames[i+data_starts] ^ mask_key[i % 4]);
            }

            if (last_fragment == 0) {
                byte[] remainder = new byte[frames.Length - (data_starts + length)];
                Array.Copy(frames, data_starts+length, remainder, 0, remainder.Length);
                WsDataFrame others = FramesToMessage(remainder);
                byte[] full_payload = new byte[payload.Length + others.payload.Length];
                Array.Copy(payload, 0, full_payload, 0, payload.Length);
                Array.Copy(others.payload, 0, full_payload, payload.Length, others.payload.Length);

                return new WsDataFrame(opcode, full_payload);
            } else {
                return new WsDataFrame(opcode, payload);
            }
        }

        /**
          Queue message msg and send
          queued messages to all
          connected clients. Message
          remains queued until a successful
          send.
        */
        public void Push(string msg)
        {
            _msg_queue.Enqueue(msg);

            NetworkStream s = null;
            while (_msg_queue.Count > 0 && _clients.Count > 0) {
                string next = _msg_queue.Peek();
                byte[] encoded = MessageToFrames(next);
                List<int> disconnected = new List<int>();
                for (int i = 0; i < _clients.Count; i++) {
                    try {
                        s = _clients[i].GetStream();
                        s.Write(encoded, 0, encoded.Length);
                    } catch (Exception ex) {
                        if (_verbose > 0) {
                            System.Console.WriteLine(ex);
                        }
                        disconnected.Add(i);
                        // disconnected?
                    }
                }
                foreach (int i in disconnected) {
                    try {
                        _clients[i].Close();
                    } catch (Exception) { }
                    _clients.RemoveAt(i);
                }
                
                if (_clients.Count > 0) {
                    _msg_queue.Dequeue();
                }

            }
        }

        /**
          Callback when client connects
          Validates initial HTTP header from client
          and sends appropriate response.
          Adds client to list of connected clients.
        */
        private void ConnectClient(IAsyncResult state)
        {
            TcpClient client;
            try {
                TcpListener server = (TcpListener)state.AsyncState;
                client = server.EndAcceptTcpClient(state);
            } finally {
                _connect_event.Set();
            }

            try {
                NetworkStream stream = client.GetStream();
                byte[] buffer = new byte[256];
                string headers = "";
                int bytes_read;
                stream.ReadTimeout = 1000;
                // loop structure matters.
                // stream.DataAvailable is not reliable
                // until read has been initiated
                do {
                    bytes_read = stream.Read(buffer, 0, buffer.Length);
                    if (bytes_read == 0) {
                        break;
                    }
                    headers += System.Text.Encoding.UTF8.GetString(buffer, 0, bytes_read);
                } while(stream.DataAvailable);

                if (_verbose > 0) {
                    System.Console.WriteLine("Handshake: " + headers);
                }

                string[] lines = headers.Split('\n');
                string[] pair;
                string protocol = null;
                string key = null;
                string upgrade = null;
                string connection = null;
                string version = null;
                foreach (string line in lines) {
                    pair = line.Split(new char[]{':'}, 2);
                    if (pair.Length != 2) {
                        continue;
                    }
                    switch (pair[0].Trim()) {
                        case "Upgrade":
                            upgrade = pair[1].Trim();
                            break;
                        case "Connection":
                            connection = pair[1].Trim();
                            break;
                        case "Sec-WebSocket-Version":
                            version = pair[1].Trim();
                            break;
                        case "Sec-WebSocket-Protocol":
                            protocol = pair[1].Trim();
                            break;
                        case "Sec-WebSocket-Key":
                            key = pair[1].Trim();
                            break;
                    }
                }

                if (upgrade != "websocket") {
                    throw new WsFatalClientException("Invalid header \"Upgrade\"");
                } else if (!connection.Contains("Upgrade")) {
                    throw new WsFatalClientException("Invalid header \"Connection\"");
                } else if (key == null) {
                    throw new WsFatalClientException("Invalid header \"Sec-WebSocket-Key\"");
                }

                key += "258EAFA5-E914-47DA-95CA-C5AB0DC85B11"; // magic value
                byte[] hashed = System.Security.Cryptography.SHA1.Create().ComputeHash(
                    System.Text.Encoding.UTF8.GetBytes(key)
                );

                string resp = "HTTP/1.1 101 Switching Protocols\r\n"
                    + "Connection: Upgrade\r\n"
                    + "Upgrade: websocket\r\n"
                    + "Sec-WebSocket-Accept: " + Convert.ToBase64String(hashed) + "\r\n";
                if (version != null && int.Parse(version) >= 13) {
                    resp += "Sec-WebSocket-Version: 13\r\n";
                }
                if (protocol != null) {
                    resp += "Sec-WebSocket-Protocol: " + protocol + "\r\n";
                }
                resp += "\r\n";

                if (_verbose > 0) {
                    System.Console.WriteLine(resp);
                }

                byte[] r = System.Text.Encoding.UTF8.GetBytes(resp);
                stream.Write(r, 0, r.Length);

                _clients.Add(client);
                MonitorClient(client);

            } catch (Exception ex) {
                // client initialization failed 
                if (_verbose > 0) {
                    System.Console.WriteLine(ex);
                }
                client.Close();
            }
        }

        /**
          Do async read on client
          Added for debugging purposes
          Doesn't do much yet
        */
        private void MonitorClient(TcpClient client)
        {
            try {
                byte[] buffer = new byte[512];
                WsCallbackState state = new WsCallbackState(client, buffer);
                client.GetStream().BeginRead(buffer, 0, buffer.Length, new AsyncCallback(ClientDataCallback), state);
            } catch (Exception) {

            }
        }

        /**
          Async callback for data sent by client
        */
        private void ClientDataCallback(IAsyncResult state)
        {
            try {
                WsCallbackState cs = (WsCallbackState)state.AsyncState;
                NetworkStream stream = cs.client.GetStream();
                int bytes = stream.EndRead(state);
                if (bytes > 0) {
                    byte[] frames = new byte[bytes];
                    Array.Copy(cs.buffer, 0, frames, 0, bytes);
                    bool closed = false;
                    try {
                        // decode client message
                        WsDataFrame frame = FramesToMessage(frames);

                        if (frame.opcode == 0x8) { // close frame
                            int close_code = 0;
                            if (frame.payload.Length == 2) {
                                close_code = ((frame.payload[0] & 0xff) << 8) + (frame.payload[1] & 0xff);
                            }
                            byte[] close_msg = WsDataFrame.CloseFrame(close_code);
                            stream.Write(close_msg, 0, close_msg.Length);
                            cs.client.Close();
                            _clients.Remove(cs.client);
                            closed = true;
                        } else if (frame.opcode == 0x9) { // ping frame
                            byte[] pong_msg = WsDataFrame.PongFrame(frame.payload);
                            stream.Write(pong_msg, 0, pong_msg.Length);
                        }
                    } catch (WsFatalClientException ex) {
                        // client did something wrong. kill connection
                        if (_verbose > 0) {
                            System.Console.WriteLine(ex);
                        }
                        cs.client.Close();
                        _clients.Remove(cs.client);
                        closed = true;
                    } catch (Exception ex) {
                        if (_verbose > 0) {
                            System.Console.WriteLine(ex);
                        }
                    }
                    
                    if (!closed) {
                        MonitorClient(cs.client);
                    }
                } else { // zero-bytes read implies closed connection, I think
                    cs.client.Close();
                    _clients.Remove(cs.client);
                }
            } catch (Exception) {

            }
        }

        // testing stub
        public static void Main(string[] args)
        {
            WebSocketServer ws = new WebSocketServer(new IPEndPoint(IPAddress.Any, 8888));
            ws.Run();
        }
    }

    /**
      State object for async delegate
      Needs access to buffer that contains
      actual bytes read but also needs
      access to the client to manage disconnects
      or additional reads.
    */
    class WsCallbackState
    {
        public TcpClient client;
        public byte[] buffer;

        public WsCallbackState(TcpClient c, byte[] b)
        {
            client = c;
            buffer = b;
        }
    }

    class WsDataFrame
    {
        public int opcode;
        public byte[] payload;

        public WsDataFrame(int o, byte[] p) 
        {
            opcode = o;
            payload = p;
        }

        /**
          Factory: get bytes for a close frame
        */
        public static byte[] CloseFrame(int reason)
        {
            if (reason >= 1000 && reason <= 1003) {
                return new byte[4]{ 
                    0x88, 
                    0x2, 
                    (byte)((reason>>8)&0xff),
                    (byte)(reason&0xff) 
                };
            } else {
                return new byte[2]{ 0x88, 0x0 };
            }
        }

        /**
          Factory: get bytes for a pong frame
        */
        public static byte[] PongFrame(byte[] payload)
        {
            if (payload.Length > 125) {
                // technically wrong. stupid client
                // sending GIGANTIC pings can decide
                // how to deal with it
                return new byte[2]{ 0x8a, 0x0 };
            }
            byte[] frame = new byte[2 + payload.Length];
            frame[0] = 0x8a;
            frame[1] = (byte)payload.Length;
            Array.Copy(payload, 0, frame, 2, payload.Length);

            return frame;
        }
    }

    class WsException : Exception
    { 
        public WsException() { }
        public WsException(string message) : base(message) { }
        public WsException(string message, Exception inner) : base(message, inner) { }
    }
    class WsProtocolException : WsException
    {
        public WsProtocolException() { }
        public WsProtocolException(string message) : base(message) { }
        public WsProtocolException(string message, Exception inner) : base(message, inner) { }
    }
    class WsFatalClientException : WsException
    { 
        public WsFatalClientException() { }
        public WsFatalClientException(string message) : base(message) { }
        public WsFatalClientException(string message, Exception inner) : base(message, inner) { }
    }
}

