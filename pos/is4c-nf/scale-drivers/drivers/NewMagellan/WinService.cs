
using System;
using System.ServiceProcess;
using System.Threading;
using System.Configuration.Install;
using System.ComponentModel;

class WinService : ServiceBase
{

    private Thread watchThread;

    public WinService()
    {
        this.watchThread = new Thread(new ThreadStart(Watcher.ThreadMe));
        this.ServiceName = "CORE-POS Hardware Manager";
    }

    protected override void OnStart(string[] args)
    {
        this.watchThread.Start();
    }

    protected override void OnStop()
    {
        try {
            this.watchThread.Abort();
            this.watchThread.Join();
        } catch {}
    }
}

[RunInstallerAttribute(true)]
public class MyInstaller : ServiceProcessInstaller 
{
    private ServiceInstaller s;

    public MyInstaller()
    {
        this.s = new ServiceInstaller();    
        this.s.ServiceName = "CORE-POS Hardware Manager";
        this.Installers.AddRange(new Installer[] { this.s });
    }    
}

