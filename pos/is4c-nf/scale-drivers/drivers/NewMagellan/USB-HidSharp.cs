using System;
using System.IO;
using System.Linq;

using HidSharp;

namespace USBLayer 
{

public class USBWrapper_HidSharp : USBWrapper 
{

    /**
     * Get a handle for USB device file
     * @param filename the name of the file OR vendor and device ids formatted as "vid&pid"
     * @param report_size [optional] report size in bytes
     * @return open read/write Stream
     */
    public override Stream GetUSBHandle(string filename, int report_size)
    { 
        HidDeviceLoader loader = new HidDeviceLoader();
        int vid = 0;
        int pid = 0;
        if (filename.IndexOf("&") > 0) {
            String[] parts = filename.Split(new Char[]{'&'});
            vid = Convert.ToInt32(parts[0]);
            pid = Convert.ToInt32(parts[1]);
        } else {
            System.Console.WriteLine("Invalid device specification: " + filename);
            return null;
        }

        HidDevice dev = loader.GetDeviceOrDefault(vid, pid, null, null);
        if (dev == null) {
            System.Console.WriteLine("Could not find requested device: " + filename);
            var devices = loader.GetDevices().ToArray();
            foreach (HidDevice d in devices) {
                System.Console.WriteLine(d);
            }
            return null;
        }

        HidStream stream;
        if (!dev.TryOpen(out stream)) {
            System.Console.WriteLine("Found requested device but cannot connect: " + filename);
            return null;
        }

        return stream;
    }

    public override void CloseUSBHandle()
    { 
    }
}

}
