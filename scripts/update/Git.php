<?php

class Git extends SebastianBergmann\Git\Git
{
    public function fetchURL($remote)
    {
        $info = $this->remote($remote);
        $head = array_filter($info, function($line) { return strstr($line, 'Fetch URL'); });
        $head = array_pop($head);
        list($junk, $url) = explode(': ', $head);

        return $url;
    }

    public function remote($name)
    {
        return $this->execute("remote show {$name}");
    }

    public function fetch($remote='')
    {
        $this->execute("fetch {$remote}");
    }

    public function tags($remote='')
    {
        $vals = $this->execute("ls-remote --tags {$remote}");
        $vals = array_map(function($i) {
            list($before, $tag) = explode("refs/tags/", $i, 2);
            return $tag;
        }, $vals);
        $vals = array_filter($vals, function($i) { return substr($i, -3) !== '^{}'; });
        return $vals;
    }
    
    public function pull($remote='', $ref='', $rebase=true, $force=false)
    {
        $revs = $this->getRevisions();
        $last = array_pop($revs);

        $cmd = "pull";
        if ($rebase) {
            $cmd .= " --rebase";
        }
        if ($force) {
            $cmd .= " -s recursive " . ($rebase ? "-Xours" : "-Xtheirs");
        }

        $ret = true;
        try {
            $this->execute("{$cmd} {$remote} {$ref}");
        } catch (Exception $ex) {
            $this->execute("reset --hard {$last['sha1']}");
            $ret = $ex->getMessage();
        }

        return $ret;
    }

    public function branch($name, $source_rev='')
    {
        $this->execute("branch {$name} {$source_rev}");
    }

    public function merge($merge_rev)
    {
        $revs = $this->getRevisions();
        $last = array_pop($revs);

        $ret = true;
        try {
            $this->execute("merge {$merge_rev}");
        } catch (Exception $ex) {
            $this->execute("reset --hard {$last['sha1']}");
            $ret = false;
        }

        return $ret;
    }

    public function addRemote($name, $url)
    {
        $this->execute("remote add {$name} {$url}");
    }
}

