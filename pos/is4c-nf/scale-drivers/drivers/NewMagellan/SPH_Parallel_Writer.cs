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
using System.IO.Ports;
using System.Threading;
using CustomForms;
using System.Net.Sockets;

using ParallelLayer;

namespace SPH {

public class SPH_Parallel_Writer : SerialPortHandler {

    private ParallelWrapper lp_port;
    private FileStream lp_fs;

    private ManualResetEvent client_connect_signal;

	public SPH_Parallel_Writer(string p) : base(p)
    { 
        #if MONO
        lp_port = new ParallelWrapper_Posix();
        #else
        lp_port = new ParallelWrapper_Win32();
        #endif

        lp_fs = lp_port.GetLpHandle(p);
	}
	
	override public void Read()
    { 
        TcpListener server = new TcpListener(System.Net.IPAddress.Parse("127.0.0.1"), 9450);
        server.Start();
        client_connect_signal = new ManualResetEvent(false);
        if (verbose_mode > 0) {
            System.Console.WriteLine("Listening for print connections");
        }

        while(SPH_Running) {
            server.BeginAcceptTcpClient(new AsyncCallback(HandleClient), server);

            // Wait for client connection
            client_connect_signal.WaitOne();
        }

        server.Stop();
    }

    private void HandleClient(IAsyncResult ar)
    {
        TcpClient client = null;
        try {
            TcpListener listener = (TcpListener) ar.AsyncState;
            client = listener.EndAcceptTcpClient(ar);
            if (this.verbose_mode > 0) {
                System.Console.WriteLine("Print client conencted");
            }
        } catch (Exception ex) {
            if (verbose_mode > 0) {
                System.Console.WriteLine(ex);
            }
        } finally {
            // signal back client connection made
            client_connect_signal.Set();
        }

        try {
            NetworkStream stream = client.GetStream();

            int bytes_read;
            Byte[] bytes = new Byte[512];
            Byte[] ack = System.Text.Encoding.ASCII.GetBytes("ACK");
            bool etx = false;
            while((bytes_read = stream.Read(bytes, 0, bytes.Length))!=0) {

                if (bytes[bytes_read-1] == 0x3) {
                    // exclude ETX from message content
                    etx = true;
                    bytes_read--;
                }

                lp_fs.Write(bytes, 0, bytes_read);

                if (verbose_mode > 0) {
                    foreach(Byte b in bytes) {
                        System.Console.Write(b + " ");
                    }
                    System.Console.WriteLine();
                }

                if (etx) break;
            }
            
            lp_fs.Flush();
            stream.Write(ack, 0, ack.Length);

            client.Close();

        } catch (Exception ex) {
            if (verbose_mode > 0) {
                System.Console.WriteLine(ex);
            }
        }
    }

	override public void HandleMsg(string msg)
    { 
        // ignore messages
    }

}

}
