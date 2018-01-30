
using System;
using System.Management;
using System.Text.RegularExpressions;
using System.Reflection;

[assembly: AssemblyVersion("1.0.*")]

namespace ComPort
{

public class ComPortUtility
{
    /**
      Query connected serial ports looking for one with a descriptor
      that matches the search string. If one is found try to extract the
      COM port number from one of the descriptive fields.

      The idea here is that if the virtual COM port shifts at runtime the driver
      could programmatically relocate it and adjust the COM port setting on the
      fly.

      Need to explore the actual properties of virtual port drivers to see
      what the best approach is. A GUID that's consistent across driver versions
      would be more reliable than the string-based approach if such a thing
      exists.
    */
    public static string FindComPort(string search)
    {
        var searcher = new ManagementObjectSearcher(
            "root\\CIMV2",
            "SELECT * FROM Win32_PnPEntity WHERE ClassGuid=\"{4d36e978-e325-11ce-bfc1-08002be10318}\""
        );
        var comRegEx = new Regex(@"COM[0-9]+");

        foreach (var queryObj in searcher.Get()) {
            var thisOne = false;
            foreach (var p in queryObj.Properties) {
                if (p.Name == "Name" || p.Name == "Description" || p.Name == "Caption") {
                    if (!thisOne && p.Value.ToString().Contains(search)) {
                        thisOne = true;
                    }
                    if (thisOne) {
                        var match = comRegEx.Match(p.Value.ToString());
                        if (match.Success) {
                            return match.Value;
                        }
                    }
                }
            }
        }

        return "";
    }

}

}

