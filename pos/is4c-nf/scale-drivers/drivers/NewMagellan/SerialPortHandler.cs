/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

/*************************************************************
 * SerialPortHandler
 *     Abstract class to manage a serial port in a separate
 * thread. Allows top-level app to interact with multiple, 
 * different serial devices through one class interface.
 * 
 * Provides Stop() and SetParent(DelegateForm) functions.
 *
 * Subclasses must implement Read() and PageLoaded(Uri).
 * Read() is the main polling loop, if reading serial data is
 * required. PageLoaded(Uri) is called on every WebBrowser
 * load event and provides the Url that was just loaded.
 *
*************************************************************/
using System;
using System.IO.Ports;
using System.Threading;
using CustomForms;

namespace SPH {

public class SerialPortHandler {
    public Thread SPH_Thread;
    protected bool SPH_Running;
    protected SerialPort sp;
    protected CustomForms.DelegateForm parent;
    protected string port;
    protected int verbose_mode;

    // to allow RBA_Stub
    public SerialPortHandler() {}

    public SerialPortHandler(string p){ 
        this.SPH_Thread = new Thread(new ThreadStart(this.Read));    
        this.SPH_Running = true;
        this.port = p;
        this.verbose_mode = 0;
    }

    public string Status()
    {
        return this.GetType().Name + ": " + this.port;
    }

    public virtual void SetConfig(string k, string v) {}
    
    public void SetParent(DelegateForm p){ parent = p; }
    public void SetVerbose(int v){ verbose_mode = v; }

    public virtual void Read(){ }
    public virtual void HandleMsg(string msg){ }

    public void Stop(){
        SPH_Running = false;
        SPH_Thread.Join();
        System.Console.WriteLine("SPH Stopped");
    }

}

}
