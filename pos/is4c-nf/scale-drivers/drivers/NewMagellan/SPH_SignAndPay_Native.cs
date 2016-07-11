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

using System;
using System.IO;
using System.IO.Ports;
using System.Threading;
using CustomForms;

using USBLayer;

namespace SPH {

public class SPH_SignAndPay_Native : SPH_SignAndPay_USB 
{

    public SPH_SignAndPay_Native(string p) : base(p)
    {
        System.Console.WriteLine("Loading Sign and Pay module");
        System.Console.WriteLine("  Screen Control: POS");
        System.Console.WriteLine("  Paycards Communication: Messages");

        #if MONO
        usb_devicefile = p;
        #endif
    }

    protected override void GetHandle()
    {
        usb_fs = null;
        #if MONO
        usb_port = new USBWrapper_Posix();
        usb_report_size = 64;
        System.Console.WriteLine("  USB Layer: Posix Device File");
        #else
        usb_port = new USBWrapper_Win32();
        usb_report_size = 65;
        System.Console.WriteLine("  USB Layer: Win32");
        #endif
        while(usb_fs == null){
            usb_fs = usb_port.GetUSBHandle(usb_devicefile,usb_report_size);
            if (usb_fs == null){
                if (this.verbose_mode > 0)
                    System.Console.WriteLine("No device");
                System.Threading.Thread.Sleep(5000);
            }
            else { 
                if (this.verbose_mode > 0)
                    System.Console.WriteLine("USB device found");
            }
        }
    }

    public override void Read()
    { 
        PushOutput("TERMAUTODISABLE");

        GetHandle();
        SendReport(BuildCommand(LcdSetBacklightTimeout(0)));
        SendReport(BuildCommand(EnableAudio()));
        /**
          Loading the logo is somewhat time consuming, so you may
          want to change logo_available to true and recompile once
          it's on the device. Otherwise it's loaded onto the device
          each time the driver starts up

          Logo is assumed to be 180x200. Max file size is 32KB.
        */
        if (!this.logo_available && File.Exists("logo.jpg")) {
            SendReport(BuildCommand(LcdStoreImage(1, "logo.jpg")));
            this.logo_available = true;
        }
        SetStateStart();
        #if MONO
        MonoRead();
        #else
        ReRead();
        #endif
    }

    protected override void RebootTerminal()
    {
        try {
            SendReport(BuildCommand(ResetDevice()));
        }
        catch (Exception ex){
            if (this.verbose_mode > 0){
                System.Console.WriteLine("Reboot error:");
                System.Console.WriteLine(ex);
            }
        }
        try {
            usb_fs.Dispose();
        }
        catch (Exception ex){
            if (this.verbose_mode > 0){
                System.Console.WriteLine("Dispose error:");
                System.Console.WriteLine(ex);
            }
        }
        try {
            usb_port.CloseUSBHandle();
        }
        catch (Exception ex){
            if (this.verbose_mode > 0){
                System.Console.WriteLine("Dispose error:");
                System.Console.WriteLine(ex);
            }
        }
        System.Threading.Thread.Sleep(DEFAULT_WAIT_TIMEOUT);
        GetHandle();
        SetStateStart();
        #if MONO
        //MonoRead();
        #else
        ReRead();
        #endif
    }
}

}
