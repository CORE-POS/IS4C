using System.IO;

namespace USBLayer {

public class USBWrapper {

    /**
     * Get a handle for USB device file
     * @param filename the name of the file OR vendor and device ids formatted as "vid&pid"
     * @param report_size [optional] report size in bytes
     * @return open read/write FileStream
     */
    public virtual Stream GetUSBHandle(string filename, int report_size){ 
        return null;    
    }

    public virtual void CloseUSBHandle(){ }

}

}
