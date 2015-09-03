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

public class SPH_SignAndPay_Auto : SPH_SignAndPay_USB 
{

    public SPH_SignAndPay_Auto(string p) : base(p)
    {
        System.Console.WriteLine("Loading Sign and Pay module");
        System.Console.WriteLine("  Screen Control: Auto");
        System.Console.WriteLine("  Paycards Communication: Direct");

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
        PushOutput("TERMAUTOENABLE");

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

    /**
      Auto override default behavior and automatically
      switches device state (screen) when receiving
      certain messages from the terminal
    */
    protected override void HandleDeviceMessage(byte[] msg)
    {
        if (this.verbose_mode > 0)
            System.Console.Write("DMSG: {0}: ",current_state);

        if (msg == null) msg = new byte[0];

        if (this.verbose_mode > 0){
            foreach(byte b in msg)
                System.Console.Write("{0:x} ",b);
            System.Console.WriteLine();
        }
        switch(current_state){
        case STATE_SELECT_CARD_TYPE:
            if (msg.Length == 4 && msg[0] == 0x7a){
                SendReport(BuildCommand(DoBeep()));
                if (msg[1] == BUTTON_CREDIT){
                    PushOutput("TERM:Credit");
                    SetStateWaitForCashier();
                }
                else if (msg[1] == BUTTON_DEBIT){
                    PushOutput("TERM:Debit");
                    // automatic state change
                    SetStateCashBack();
                }
                else if (msg[1] == BUTTON_EBT){
                    // purposely autochanged. no message to pos
                    // until ebt sub-selection is made
                    SetStateEbtType();
                }
                else if (msg[1] == BUTTON_GIFT){
                    PushOutput("TERM:Gift");
                    // automatic state change
                    SetStateWaitForCashier();    
                }
                else if (msg[1] == BUTTON_HARDWARE_BUTTON && msg[3] == 0x43){
                    SetStateStart();
                    PushOutput("TERMCLEARALL");
                }
            }
            break;
        case STATE_SELECT_EBT_TYPE:
            if (msg.Length == 4 && msg[0] == 0x7a){
                SendReport(BuildCommand(DoBeep()));
                if (msg[1] == BUTTON_EBT_FOOD){
                    PushOutput("TERM:EbtFood");
                    // automatic state change
                    SetStateGetPin();
                }
                else if (msg[1] == BUTTON_EBT_CASH){
                    PushOutput("TERM:EbtCash");
                    // automatic state change
                    SetStateCashBack();
                }
                else if (msg[1] == BUTTON_HARDWARE_BUTTON && msg[3] == 0x43){
                    SetStateStart();
                    PushOutput("TERMCLEARALL");
                }
            }
            break;
        case STATE_SELECT_CASHBACK:
            if (msg.Length == 4 && msg[0] == 0x7a){
                SendReport(BuildCommand(DoBeep()));
                if (msg[1] == BUTTON_HARDWARE_BUTTON && msg[3] == 0x43){
                    SetStateStart();
                    PushOutput("TERMCLEARALL");
                }
                else if(msg[1] != 0x6){
                    // 0x6 might be a serial protocol ACK
                    // timing issue means we got here too soon
                    // and should wait for next input
    
                    // Pressed green or yellow button
                    // Proceed to PIN entry but don't
                    // request 0xFF as cash back
                    if (msg[1] != BUTTON_HARDWARE_BUTTON)
                        PushOutput("TERMCB:"+msg[1]);
                    // automatic state change
                    SetStateGetPin();
                }
            }
            break;
        case STATE_ENTER_PIN:
            if (msg.Length == 3 && msg[0] == 0x15){
                SetStateStart();
                PushOutput("TERMCLEARALL");
            }
            else if (msg.Length == 36){
                string pinhex = "";
                foreach(byte b in msg)
                    pinhex += ((char)b);
                PushOutput("PINCACHE:"+pinhex);
                // automatic state change
                SetStateWaitForCashier();
            }
            break;
        case STATE_GET_SIGNATURE:
            if (msg.Length == 4 && msg[0] == 0x7a){
                //SendReport(BuildCommand(DoBeep()));
                if (msg[1] == BUTTON_SIG_RESET){
                    SendReport(BuildCommand(LcdClearSignature()));
                }
                else if (msg[1] == BUTTON_SIG_ACCEPT){
                    RemoveSignatureButtons();
                    SendReport(BuildCommand(LcdGetBitmapSig()));
                }
            }
            else if (msg.Length > 2 && msg[0] == 0x42 && msg[1] == 0x4d){
                BitmapOutput(msg);
                sig_message = "";
            }
            break;
        case STATE_MANUAL_PAN:
            if (msg.Length == 1 && msg[0] == 0x6){
                ack_counter++;
                if (this.verbose_mode > 0)
                    System.Console.WriteLine(ack_counter);
                if (ack_counter == 1)
                    SetStateGetManualExp();
            }
            else if (msg.Length == 3 && msg[0] == 0x15){
                SetStateStart();
            }
            break;
        case STATE_MANUAL_EXP:
            if (msg.Length == 1 && msg[0] == 0x6){
                ack_counter++;
                if (this.verbose_mode > 0)
                    System.Console.WriteLine(ack_counter);
                if (ack_counter == 2)
                    SetStateGetManualCVV();
            }
            else if (msg.Length == 3 && msg[0] == 0x15){
                SetStateStart();
            }
            break;
        case STATE_MANUAL_CVV:
            if (msg.Length > 63 && msg[0] == 0x80){
                string block = FixupCardBlock(msg);
                PushOutput("PANCACHE:"+block);
                SetStateCardType();
            }

            else if (msg.Length == 3 && msg[0] == 0x15){
                SetStateStart();
            }
            break;
        case STATE_START_TRANSACTION:
            if (msg.Length > 63 && msg[0] == 0x80 ){
                SendReport(BuildCommand(DoBeep()));
                string block = FixupCardBlock(msg);
                if (block.Length == 0){
                    SetStateReStart();
                }
                else {
                    PushOutput("PANCACHE:"+block);
                    // automatic state change
                    SetStateCardType();
                }
            }
            else if (msg.Length > 1){
                if (this.verbose_mode > 0)
                    System.Console.WriteLine(msg.Length+" "+msg[0]+" "+msg[1]);
            }
            break;
        }
    }

    /**
      Overridden to ignore screen-draw messages
      from POS that are handled automatically.
      Ignored messages are:
      * termGetType
      * termGetTypeWithFS
      * termCashBack
      * termGetPin
      * termWait
    */
    public override void HandleMsg(string msg)
    { 
        // optional predicate for "termSig" message
        // predicate string is displayed on sig capture screen
        if (msg.Length > 7 && msg.Substring(0, 7) == "termSig") {
            sig_message = msg.Substring(7);
            msg = "termSig";
        }

        // 7May13 use locks
        last_message = msg;
        switch(msg){
        case "termReset":
            lock(usb_lock){
                SetStateStart();
            }
            break;
        case "termReboot":
            lock(usb_lock){
                RebootTerminal();
            }
            break;
        case "termManual":
            lock(usb_lock){
                SetStateGetManualPan();
            }
            break;
        case "termSig":
            lock(usb_lock){
                SetStateGetSignature();
            }
            break;
        }
    }
}

}
