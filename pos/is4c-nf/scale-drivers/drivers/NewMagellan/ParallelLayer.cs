using System.IO;
using System.Reflection;

[assembly: AssemblyVersion("1.0.*")]

namespace ParallelLayer {

public class ParallelWrapper 
{

    /**
     * Get a handle for paralell device file
     * @param filename the name of the file 
     * @param report_size [optional] report size in bytes
     * @return open write FileStream
     */
    public virtual FileStream GetLpHandle(string filename)
    { 
        return null;    
    }

    public virtual void CloseLpHandle(){ }

}

}
