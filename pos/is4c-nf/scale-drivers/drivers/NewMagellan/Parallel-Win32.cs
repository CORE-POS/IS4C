using System;
using System.Text;
using System.IO;
using System.Runtime.InteropServices;
using Microsoft.Win32.SafeHandles;

namespace ParallelLayer {

public class ParallelWrapper_Win32 : ParallelWrapper 
{

	protected const uint GENERIC_READ = 0x80000000;
	protected const uint GENERIC_WRITE = 0x40000000;
	protected const uint OPEN_EXISTING = 3;
	public static IntPtr NullHandle = IntPtr.Zero;
	protected static IntPtr InvalidHandleValue = new IntPtr(-1);

	private IntPtr native_handle;
    private SafeHandle safe_handle;

	[DllImport("kernel32.dll", SetLastError = true)] protected static extern IntPtr CreateFile([MarshalAs(UnmanagedType.LPStr)] string strName, uint nAccess, uint nShareMode, IntPtr lpSecurity, uint nCreationFlags, uint nAttributes, IntPtr lpTemplate);
    /** alt; use safehandles?
    [DllImport("kernel32.dll", SetLastError = true, CharSet = CharSet.Auto)] 
        static extern SafeFileHandle CreateFile(
            string fileName,
            [MarshalAs(UnmanagedType.U4)] FileAccess fileAccess,
            [MarshalAs(UnmanagedType.U4)] FileShare fileShare,
            IntPtr securityAttributes,
            [MarshalAs(UnmanagedType.U4)] FileMode creationDisposition,
            [MarshalAs(UnmanagedType.U4)] FileAttributes flags,
            IntPtr template
    ); */
	[DllImport("kernel32.dll", SetLastError = true)] protected static extern int CloseHandle(IntPtr hFile);

	public override FileStream GetLpHandle(string filename)
    {
		native_handle = CreateFile(filename, GENERIC_WRITE, 0, IntPtr.Zero, OPEN_EXISTING, 0, IntPtr.Zero);
		if (native_handle != InvalidHandleValue) {
			return new FileStream(native_handle, FileAccess.Write);
		}

        /** alt; use safehandles?
		safe_handle = CreateFile(filename, GENERIC_WRITE, 0, IntPtr.Zero, OPEN_EXISTING, 0, IntPtr.Zero);
		if (native_handle != InvalidHandleValue) {
			return new FileStream(native_handle, FileAccess.Write);
		} */

		return null;
	}

	public override void CloseLpHandle(){
		try {
			CloseHandle(native_handle);
		}
		catch(Exception ex){}
	}

} // end class

} // end namespace
