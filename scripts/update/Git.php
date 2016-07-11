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
    
    public function pull($remote='', $ref='', $rebase=true)
    {
        $revs = $this->getRevisions();
        $last = array_pop($revs);

        $ret = true;
        try {
            if ($rebase) {
                $this->execute("pull --rebase {$remote} {$ref}");
            } else {
                $this->execute("pull {$remote} {$ref}");
            }
        } catch (Exception $ex) {
            $this->execute("reset --hard {$last['sha1']}");
            $ret = false;
        }

        return true;
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

