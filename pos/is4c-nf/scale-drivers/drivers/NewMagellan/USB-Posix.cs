using System;
using System.IO;

namespace USBLayer {

public class USBWrapper_Posix : USBWrapper {

	/**
	 * Get a handle for USB device file
	 * @param filename the name of the file OR vendor and device ids formatted as "vid&pid"
	 * @param report_size [optional] report size in bytes
	 * @return open read/write FileStream
	 */
	public override FileStream GetUSBHandle(string filename, int report_size){ 
		return new FileStream(filename, FileMode.OpenOrCreate, FileAccess.ReadWrite, FileShare.None, report_size, true);
	}

	public override void CloseUSBHandle(){ }

}

}
