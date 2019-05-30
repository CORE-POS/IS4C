
using System;
using System.IO;
using System.Diagnostics;
using System.Runtime.InteropServices;
using System.Reflection;

[assembly: AssemblyVersion("1.0.*")]

class Watcher
{

    [StructLayout(LayoutKind.Sequential)]
    struct STARTUPINFO
    {
        public Int32 cb;
        public string lpReserved;
        public string lpDesktop;
        public string lpTitle;
        public Int32 dwX;
        public Int32 dwY;
        public Int32 dwXSize;
        public Int32 dwYSize;
        public Int32 dwXCountChars;
        public Int32 dwYCountChars;
        public Int32 dwFillAttribute;
        public Int32 dwFlags;
        public Int16 wShowWindow;
        public Int16 cbReserved2;
        public IntPtr lpReserved2;
        public IntPtr hStdInput;
        public IntPtr hStdOutput;
        public IntPtr hStdError;
    }

    [StructLayout(LayoutKind.Sequential)]
    internal struct PROCESS_INFORMATION
    {
        public IntPtr hProcess;
        public IntPtr hThread;
        public int dwProcessId;
        public int dwThreadId;
    }

    [DllImport("kernel32.dll")]
    static extern bool CreateProcess(
        string lpApplicationName,
        string lpCommandLine,
        IntPtr lpProcessAttributes,
        IntPtr lpThreadAttributes,
        bool bInheritHandles,
        uint dwCreationFlags,
        IntPtr lpEnvironment,
        string lpCurrentDirectory,
        [In] ref STARTUPINFO lpStartupInfo,
        out PROCESS_INFORMATION lpProcessInformation
    );

    [DllImport("kernel32.dll", SetLastError = true)]
    [return: MarshalAs(UnmanagedType.Bool)]
    static extern bool CloseHandle(IntPtr hObject);

    const int STARTF_USESHOWWINDOW = 1;
    const int SW_SHOWMINNOACTIVE = 7;
    const int CREATE_NEW_CONSOLE = 0x00000010;

    [DllImport("user32.dll")]
    static extern bool SetForegroundWindow(IntPtr hWnd);

    private static Process current;
    private static bool serviceMode = false;

    public static void ThreadMe()
    {
        Watcher.serviceMode = true;
        Watcher.Main(new string[]{});
    }

    public static int Main(string[] args)
    {
        Watcher.SafetyCheck();

        // let alternate browser be specified via CLI
        string browserName = "firefox";
        if (args.Length > 0 && args[0].Length > 0) {
            browserName = args[0];
        }

        // find self
        var my_location = AppDomain.CurrentDomain.BaseDirectory;
        var sep = Path.DirectorySeparatorChar;

        // try to close down cleanly
        Console.CancelKeyPress += new ConsoleCancelEventHandler(ctrlC);
        AppDomain.CurrentDomain.ProcessExit += new EventHandler(eventWrapper);

        // restart pos.exe minimized whenever it exits
        Console.WriteLine(DateTime.Now.ToString() + ": starting driver");
        while (true) {
            // START FIRST IMPLEMENTATION
            // Previous implementation
            // Uses strictly .NET to launch pos.exe process
            /*
            var p = new Process();
            p.StartInfo.WindowStyle = ProcessWindowStyle.Minimized;
            p.StartInfo.FileName = my_location + sep + "pos.exe";
            if (Watcher.serviceMode) {
                p.StartInfo.RedirectStandardOutput = true;
                p.StartInfo.UseShellExecute = false;
                p.StartInfo.CreateNoWindow = true;
            }
            p.Start();
            Watcher.current = p;
            while (p.MainWindowHandle == IntPtr.Zero);
            Watcher.maintainFocus(browserName);
            p.WaitForExit();
            Console.WriteLine(DateTime.Now.ToString() + ": re-starting driver (C# build)");
            */
            // END FIRST IMPLEMENTATION

            // START SECOND IMPLEMENTATION
            // C++ heavier approach
            // Using CreateProcess from the kernel gives additional option
            // SHOWMINNOACTIVE (show minimized no activate)
            // "activate" seems to means give the new window input focus
            STARTUPINFO si = new STARTUPINFO();
            si.cb = Marshal.SizeOf(si);
            si.dwFlags = STARTF_USESHOWWINDOW;
            si.wShowWindow = SW_SHOWMINNOACTIVE;
            PROCESS_INFORMATION pi = new PROCESS_INFORMATION();
            CreateProcess(null, my_location + sep + "pos.exe", IntPtr.Zero, IntPtr.Zero, true, CREATE_NEW_CONSOLE, IntPtr.Zero, null, ref si, out pi);
            Watcher.current = Process.GetProcessById(pi.dwProcessId);
            Watcher.maintainFocus(browserName);
            Watcher.current.WaitForExit();
            CloseHandle(pi.hProcess);
            CloseHandle(pi.hThread);
            Console.WriteLine(DateTime.Now.ToString() + ": re-starting driver (C++ build)");
            // END SECOND IMPLEMENTATION
        }
    }

    /**
      Avoid duplicate processes.
      If pos-watcher is already running just bail out
      If there are existing pos.exe processes attempt to close
      them before opening our own
    */
    private static void SafetyCheck()
    {
        var watchers = Process.GetProcessesByName("pos-watcher");
        var myID = Process.GetCurrentProcess().Id;
        foreach (var w in watchers) {
            if (w.Id != myID) {
                Environment.Exit(0);
            }
        }
        var attempts = 0;
        while (attempts < 5) {
            var drivers = Process.GetProcessesByName("pos");
            if (drivers.Length == 0) {
                return;
            }
            foreach (var d in drivers) {
                d.Kill();
                d.WaitForExit(500);
            }
            attempts++;
        }

        Environment.Exit(0);
    }

    /**
      Focus on the first matching process that has
      a window (most modern browsers will have background
      processes too).
    */
    private static void maintainFocus(string pName)
    {
        try {
            var processes = Process.GetProcessesByName(pName);
            foreach (var p in processes) {
                if (p.MainWindowHandle != IntPtr.Zero) {
                    SetForegroundWindow(p.MainWindowHandle);
                    Console.WriteLine("Foregrounding " + pName);
                    break;
                }
            }
        } catch (Exception) {
        }
    }

    private static void eventWrapper(object sender, EventArgs args)
    {
        Watcher.ctrlC(null, null);
    }

    private static void ctrlC(object sender, ConsoleCancelEventArgs args)
    {
        if (Watcher.current != null) {
            Watcher.current.Kill();
            Watcher.current = null;
        }
    }
}

