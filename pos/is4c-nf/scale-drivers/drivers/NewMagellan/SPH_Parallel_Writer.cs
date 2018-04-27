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
    private const int PRINT_PORT = 9100;

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
        TcpListener server = new TcpListener(System.Net.IPAddress.Parse("127.0.0.1"), PRINT_PORT);
        server.Start();
        if (verbose_mode > 0) {
            System.Console.WriteLine("Listening for print connections");
        }

        byte[] buffer = new byte[1024];
        while(SPH_Running) {
            try {
                using (TcpClient client = server.AcceptTcpClient()) {
                    client.ReceiveTimeout = 100;
                    using (NetworkStream stream = client.GetStream()) {
                        int bytes_read = 0;
                        do {
                            bytes_read = stream.Read(buffer, 0, buffer.Length);
                            lp_fs.Write(buffer, 0, bytes_read);
                        } while (stream.DataAvailable);
                    }
                    client.Close();
                }
            } catch (Exception ex) {
                this.LogMessage(ex.ToString());
            }
        }

        server.Stop();
    }

    override public void HandleMsg(string msg)
    { 
        // ignore messages
    }

}

}
