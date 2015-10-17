using System;
using System.Linq;
using System.Reflection;
using System.Collections.Generic;

namespace Discover {

public class Discover
{
    private List<Type> cache;

    public Discover()
    {
        cache = new List<Type>();
        BuildCache();
    }

    private int BuildCache()
    {
        var query = AppDomain.CurrentDomain.GetAssemblies()
                    .SelectMany(t => t.GetTypes())
                    .Where(t => t.IsClass && t.Namespace != null && !t.Namespace.StartsWith("System"));

        int ret = 0;
        foreach (var t in query.ToList()) {
            if (!cache.Any(c => c.FullName == t.FullName)) {
                cache.Add(t);
                ret++;
            }
        }

        return ret;
    }

    public List<Type> GetSubClasses(Type parent)
    {
        return cache.Where(t => t.IsSubclassOf(parent)).ToList();
    }

    public List<Type> GetSubClasses(string parent)
    {
        return GetSubClasses(GetType(parent));
    }

    public Type GetType(string name)
    {
        return cache.Where(c => c.FullName == name).First();
    }

    static public void Main(string[] args)
    {
        new Discover();
    }
}

}
