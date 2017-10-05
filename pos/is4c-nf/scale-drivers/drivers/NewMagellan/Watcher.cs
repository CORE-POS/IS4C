
using System;
using System.IO;
using System.Diagnostics;
using System.Runtime.InteropServices;
using System.Reflection;

[assembly: AssemblyVersion("1.0.*")]

class Watcher
{
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
            Console.WriteLine(DateTime.Now.ToString() + ": re-starting driver");
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

