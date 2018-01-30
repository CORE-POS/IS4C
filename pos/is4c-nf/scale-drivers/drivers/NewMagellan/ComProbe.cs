
using System;
using System.Reflection;
using ComPort;

[assembly: AssemblyVersion("1.0.*")]

class ComProbe
{
    public static int Main(string[] args)
    {
        string portID = "Ingenico";
        if (args.Length > 0 && args[0].Length > 0) {
            portID = args[0];
        }

        string found = ComPortUtility.FindComPort(portID);
        if (found == "") {
            found = "No matching device found";
        }

        Console.WriteLine(found);

        return 0;
    }
}

