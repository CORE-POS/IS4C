using System;
using System.Text;
using System.IO;
using System.Runtime.InteropServices;

namespace USBLayer {

public class USBWrapper_Win32 : USBWrapper {
    [StructLayout(LayoutKind.Sequential, Pack = 1)]
    protected struct Overlapped {
        public uint Internal;
        public uint InternalHigh;
        public uint Offset;
        public uint OffsetHigh;
        public IntPtr Event;
    }

    [StructLayout(LayoutKind.Sequential, Pack = 1)]
    protected struct DeviceInterfaceData {
        public int Size;
        public Guid InterfaceClassGuid;
        public int Flags;
        public IntPtr Reserved;
    }

    [StructLayout(LayoutKind.Sequential, Pack = 1)]
    protected struct HidCaps {
        public short Usage;
        public short UsagePage;
        public short InputReportByteLength;
        public short OutputReportByteLength;
        public short FeatureReportByteLength;
        [MarshalAs(UnmanagedType.ByValArray, SizeConst = 17)]
        public short[] Reserved;
        public short NumberLinkCollectionNodes;
        public short NumberInputButtonCaps;
        public short NumberInputValueCaps;
        public short NumberInputDataIndices;
        public short NumberOutputButtonCaps;
        public short NumberOutputValueCaps;
        public short NumberOutputDataIndices;
        public short NumberFeatureButtonCaps;
        public short NumberFeatureValueCaps;
        public short NumberFeatureDataIndices;
    }

    [StructLayout(LayoutKind.Sequential, Pack = 1)]
    public struct DeviceInterfaceDetailData {
        public int Size;
        [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 256)]
        public string DevicePath;
    }

    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode, Pack = 1)]
    public class DeviceBroadcastInterface {
        public int Size;
        public int DeviceType;
        public int Reserved;
        public Guid ClassGuid;
        [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 256)]
        public string Name;
    }        

    public const int WM_DEVICECHANGE = 0x0219;
    public const int DEVICE_ARRIVAL = 0x8000;
    public const int DEVICE_REMOVECOMPLETE = 0x8004;
    protected const int DIGCF_PRESENT = 0x02;
    protected const int DIGCF_DEVICEINTERFACE = 0x10;
    protected const int DEVTYP_DEVICEINTERFACE = 0x05;
    protected const int DEVICE_NOTIFY_WINDOW_HANDLE = 0;
    protected const uint PURGE_TXABORT = 0x01;
    protected const uint PURGE_RXABORT = 0x02;
    protected const uint PURGE_TXCLEAR = 0x04;
    protected const uint PURGE_RXCLEAR = 0x08;
    protected const uint GENERIC_READ = 0x80000000;
    protected const uint GENERIC_WRITE = 0x40000000;
    protected const uint FILE_FLAG_OVERLAPPED = 0x40000000;
    protected const uint OPEN_EXISTING = 3;
    protected const uint ERROR_IO_PENDING = 997;
    protected const uint INFINITE = 0xFFFFFFFF;
    public static IntPtr NullHandle = IntPtr.Zero;
    protected static IntPtr InvalidHandleValue = new IntPtr(-1);

    private IntPtr native_handle;

    [DllImport("hid.dll",      SetLastError = true)] protected static extern void HidD_GetHidGuid(out Guid gHid);
    [DllImport("setupapi.dll", SetLastError = true)] protected static extern IntPtr SetupDiGetClassDevs(ref Guid gClass, [MarshalAs(UnmanagedType.LPStr)] string strEnumerator, IntPtr hParent, uint nFlags);
    [DllImport("setupapi.dll", SetLastError = true)] protected static extern int SetupDiDestroyDeviceInfoList(IntPtr lpInfoSet);
    [DllImport("setupapi.dll", SetLastError = true)] protected static extern bool SetupDiEnumDeviceInterfaces(IntPtr lpDeviceInfoSet, uint nDeviceInfoData, ref Guid gClass, uint nIndex, ref DeviceInterfaceData oInterfaceData);
    [DllImport("setupapi.dll", SetLastError = true)] protected static extern bool SetupDiGetDeviceInterfaceDetail(IntPtr lpDeviceInfoSet, ref DeviceInterfaceData oInterfaceData, IntPtr lpDeviceInterfaceDetailData, uint nDeviceInterfaceDetailDataSize, ref uint nRequiredSize, IntPtr lpDeviceInfoData);
    [DllImport("setupapi.dll", SetLastError = true)] protected static extern bool SetupDiGetDeviceInterfaceDetail(IntPtr lpDeviceInfoSet, ref DeviceInterfaceData oInterfaceData, ref DeviceInterfaceDetailData oDetailData, uint nDeviceInterfaceDetailDataSize, ref uint nRequiredSize, IntPtr lpDeviceInfoData);
    [DllImport("user32.dll",   SetLastError = true)] protected static extern IntPtr RegisterDeviceNotification(IntPtr hwnd, DeviceBroadcastInterface oInterface, uint nFlags);
    [DllImport("user32.dll",   SetLastError = true)] protected static extern bool UnregisterDeviceNotification(IntPtr hHandle);
    [DllImport("hid.dll",      SetLastError = true)] protected static extern bool HidD_GetPreparsedData(IntPtr hFile, out IntPtr lpData);
    [DllImport("hid.dll",      SetLastError = true)] protected static extern bool HidD_FreePreparsedData(ref IntPtr pData);
    [DllImport("hid.dll",      SetLastError = true)] protected static extern int HidP_GetCaps(IntPtr lpData, out HidCaps oCaps);
    [DllImport("kernel32.dll", SetLastError = true)] protected static extern IntPtr CreateFile([MarshalAs(UnmanagedType.LPStr)] string strName, uint nAccess, uint nShareMode, IntPtr lpSecurity, uint nCreationFlags, uint nAttributes, IntPtr lpTemplate);
    [DllImport("kernel32.dll", SetLastError = true)] protected static extern int CloseHandle(IntPtr hFile);

    /**
     * Get a handle for USB device file
     * @param filename the name of the file OR vendor and device ids formatted as "vid&pid"
     * @param report_size [optional] report size in bytes
     * @return open read/write FileStream
     */
    public override FileStream GetUSBHandle(string filename, int report_size){
        if (filename.IndexOf("&") > 0){
            String[] parts = filename.Split(new Char[]{'&'});
            if (parts.Length != 2){
                System.Console.WriteLine(filename);
                return null;
            }
            try {
                filename = GetDeviceFilename(Convert.ToInt32(parts[0]),Convert.ToInt32(parts[1]));
            }
            catch(Exception ex){
                System.Console.WriteLine(ex.ToString());
                return null;
            }
        }
        native_handle = CreateFile(filename, GENERIC_READ | GENERIC_WRITE, 0, IntPtr.Zero, OPEN_EXISTING, FILE_FLAG_OVERLAPPED, IntPtr.Zero);
        if (native_handle != InvalidHandleValue){
            IntPtr lpData;
            if (HidD_GetPreparsedData(native_handle, out lpData)){
                HidCaps oCaps;
                HidP_GetCaps(lpData, out oCaps);    // extract the device capabilities from the internal buffer
                int inp = oCaps.InputReportByteLength;    // get the input...
                int outp = oCaps.OutputReportByteLength;    // ... and output report length
                HidD_FreePreparsedData(ref lpData);
                System.Console.WriteLine("Input: {0}, Output: {1}",inp, outp);
            }
            return new FileStream(native_handle, FileAccess.Read | FileAccess.Write, true, report_size, true);    
        }
        return null;
    }

    public override void CloseUSBHandle(){
        try {
            CloseHandle(native_handle);
        }
        catch(Exception){}
    }

    private String GetDeviceFilename(int vid, int pid){
        string devname = string.Format("vid_{0:x4}&pid_{1:x4}", vid, pid); 
        string filename = null;

        Guid gHid;
        HidD_GetHidGuid(out gHid);

        IntPtr hInfoSet = SetupDiGetClassDevs(ref gHid, null, IntPtr.Zero, DIGCF_DEVICEINTERFACE | DIGCF_PRESENT);

                DeviceInterfaceData oInterface = new DeviceInterfaceData();
                oInterface.Size = Marshal.SizeOf(oInterface);
        int nIndex = 0;
        while (SetupDiEnumDeviceInterfaces(hInfoSet, 0, ref gHid, (uint)nIndex, ref oInterface)) {
            string strDevicePath = GetDevicePath(hInfoSet, ref oInterface);    
            System.Console.WriteLine(strDevicePath);
            if (strDevicePath.IndexOf(devname) >= 0){
                filename = strDevicePath;
                break;
            }
            nIndex++;
        }

                SetupDiDestroyDeviceInfoList(hInfoSet);

        return filename;
    }

    private static string GetDevicePath(IntPtr hInfoSet, ref DeviceInterfaceData oInterface){
        uint nRequiredSize = 0;
        if (!SetupDiGetDeviceInterfaceDetail(hInfoSet, ref oInterface, IntPtr.Zero, 0, ref nRequiredSize, IntPtr.Zero)) {
            DeviceInterfaceDetailData oDetail = new DeviceInterfaceDetailData();
            oDetail.Size = (IntPtr.Size==8) ? 8 : 5;
            if (SetupDiGetDeviceInterfaceDetail(hInfoSet, ref oInterface, ref oDetail, nRequiredSize, ref nRequiredSize, IntPtr.Zero)){
                return oDetail.DevicePath;
            }
        }
        return null;
    }

} // end class

} // end namespace
