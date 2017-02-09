/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
using System.Threading;
using System.Net;
using System.Net.Sockets;
using CustomForms;

namespace CustomUDP {

public class UDPMsgBox {
    public Thread My_Thread;
    protected bool running;
    protected DelegateForm parent;
    protected int port;
    protected UdpClient u;
    private bool runAsync;

    public UDPMsgBox(int p, bool a){ 
        this.My_Thread = new Thread(new ThreadStart(this.Read));
        this.running = true;
        this.port = p;
        this.u = new UdpClient(this.port);
        this.runAsync = a;
    }
    
    public void SetParent(DelegateForm p){ parent = p; }

    public void Read()
    {
        if (this.runAsync) {
            Console.WriteLine("UDP is async");
            this.ReadAsync();
        } else {
            Console.WriteLine("UDP is synchronous");
            IPEndPoint e = new IPEndPoint(IPAddress.Any, 0);
            while(running){
                try {
                    Byte[] b = this.u.Receive(ref e);
                    this.SendBytes(b);
                }
                catch (Exception ex){
                    System.Console.WriteLine(ex.ToString());
                }
            }
        }
    }

    public void ReadAsync()
    {
       u.BeginReceive(new AsyncCallback(HandleAsync), null);
    }

    public void HandleAsync(IAsyncResult res)
    {
        IPEndPoint e = new IPEndPoint(IPAddress.Any, 0);
        Byte[] b = this.u.EndReceive(res, ref e);
        this.ReadAsync();
        this.SendBytes(b);
    }

    private void SendBytes(Byte[] receiveBytes){
        string receiveString = System.Text.Encoding.ASCII.GetString(receiveBytes);

        Console.WriteLine("Received: "+ receiveString);
        parent.MsgRecv(receiveString);
    }

    public void Stop(){
        running = false;
        this.u.Close();
        My_Thread.Join();
    }

}

}
