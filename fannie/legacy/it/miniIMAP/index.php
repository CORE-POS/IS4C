<?php

include('lib.php');

$domain = "wholefoods.coop";
$server = "{localhost:993/ssl/novalidate-cert}";
$trash_folder = "mail-trash";
$iv = "a934kals83mksdf0a::ASLDjO@ak1:!20984";
$mb = 0;

if (isset($_REQUEST['logout'])){
    setcookie("minimap-user","",time()-3600);
    setcookie("minimap-session","",time()-3600);
    header("Location: index.php");
    return;
}
if (!isset($_COOKIE["minimap-user"])){
    if (!isset($_REQUEST['login'])){
        echo "<form action=index.php method=post>";
        echo "<table cellspacing=0 cellpadding=4>";
        echo "<tr><th>User</th>";
        echo "<td><input type=text name=username /></td></tr>";
        echo "<tr><th>Password</th>";
        echo "<td><input type=password name=pass /></td></tr>";
        echo "</table>";
        echo "<input type=submit name=login value=Login />";
        echo "</form>";
    }
    else {
        $user = $_REQUEST['username'];
        $pass = $_REQUEST['pass'];
        $mb = imap_open($server,$user,$pass);
        if (!$mb){
            echo "Login failed. ";
            echo "<a href=index.php>Retry</a>";
        }
        else {
            $block = enc($user,$pass,$iv);
            setcookie("minimap-user",$user);
            setcookie("minimap-session",$block);
            header("Location: index.php");
        }
    }
    return;
}

$user = $_COOKIE["minimap-user"];
$pass = dec($user,$_COOKIE['minimap-session'],$iv);
$mb = imap_open($server,$user,$pass);

if (isset($_REQUEST['attach'])){
    $mb = imap_open($server.$_REQUEST['mbox'],$user,$pass);
    stream_attachment($mb,$_REQUEST['num'],$_REQUEST['sub'],base64_decode($_REQUEST['fname']));
    return;
}

$output = "<html><head>
<style type=\"text/css\">
.unread td {
    font-weight: bold;
}
</style>
</head>";

if (isset($_REQUEST['send'])){
    $to = $_REQUEST['to_line'];
    $cc = $_REQUEST['cc_line'];
    $from = $user."@".$domain;
    $subject = $_REQUEST['subject'];
    $body = $_REQUEST['body'];

    $headers = "From: ".$from."\r\n";
    if (!empty($cc))
        $headers .= "Cc: ".$cc."\r\n";
    if (strstr(strtolower($body),"<html>") !== False){
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    }

    mail($to,$subject,$body,$headers);

    $output .= "<div align=center><b>Message sent</b></div>";
    $_REQUEST['folder'] = $_REQUEST['mbox'];
}

if (isset($_REQUEST['folder'])){
    $output .= "<a href=\"index.php\">Back</a><br />";

    $mb = imap_open($server.$_REQUEST['folder'],$user,$pass);
    $n = imap_num_msg($mb);
    imap_headers($mb);
    $output .= "<table cellspacing=0 cellpadding=4 border=1>";
    for($i=$n; $i > 0; $i--){
        $h = imap_header($mb,$i);
        $output .= "<tr";
        if ($h->Unseen=='U' || $h->Recent=='N')
            $output .= " class=\"unread\"";
        $output .= ">";
        $output .= "<td>".date("n/d/y g:ia",$h->udate)."</td>";
        $output .= "<td>".$h->fromaddress."</td>";
        $output .= sprintf("<td><a href=\"index.php?mbox=%s&msg=%d\">%s</a></td>",
            $_REQUEST['folder'],$i,$h->subject);
        $output .= "</tr>";
    }
    $output .= "</table>";
}
else if (isset($_REQUEST['msg'])){
    $mb = imap_open($server.$_REQUEST['mbox'],$user,$pass);

    $msgBody = get_body($mb,$_REQUEST['msg']);    

    $h = imap_headerinfo($mb,$_REQUEST['msg']);
    
    $output .= "<a href=\"index.php?folder=".$_REQUEST['mbox']."\">Back</a>";
    $output .= "&nbsp;&nbsp;&nbsp;&nbsp;";
    $output .= "<a href=\"index.php?mbox=".$_REQUEST['mbox']."&num=".$_REQUEST['msg']."&mode=reply\">Reply</a>";
    $output .= "&nbsp;&nbsp;&nbsp;&nbsp;";
    $output .= "<a href=\"index.php?mbox=".$_REQUEST['mbox']."&num=".$_REQUEST['msg']."&mode=replyall\">ReplyAll</a>";
    $output .= "&nbsp;&nbsp;&nbsp;&nbsp;";
    $output .= "<a href=\"index.php?mbox=".$_REQUEST['mbox']."&num=".$_REQUEST['msg']."&mode=trash\">Trash</a>";
    $output .= "<hr />";

    $output .= "<b>Date</b>: ".date("n/d/y g:ia",$h->udate);
    $output .= "<hr />";
    $output .= "<b>From</b>: ".$h->fromaddress;
    $output .= "<hr />";
    $output .= "<b>To</b>: ".$h->toaddress;
    $output .= "<hr />";
    $output .= "<b>CC</b>: ".$h->ccaddress;
    $output .= "<hr />";
    $output .= $msgBody;

    $attachments = get_attachments($mb,$_REQUEST['msg']);
    foreach($attachments as $a){
        $output .= "<hr />";
        $output .= sprintf("<a href=\"index.php?attach=get&num=%d&sub=%d&mbox=%s&fname=%s\">%s</a><br />",
            $_REQUEST['msg'],$a['num'],$_REQUEST['mbox'],
            base64_encode(!empty($a['name'])?$a['name']:$a['filename']),
            (!empty($a['name'])?$a['name']:$a['filename']));
        $output .= $a['mime']." (".better_size($a['size']).")";
    }
}
else if (isset($_REQUEST['mode'])){
    $mb = imap_open($server.$_REQUEST['mbox'],$user,$pass);
    if ($_REQUEST['mode'] == 'trash'){
        imap_mail_move($mb,$_REQUEST['num'],$trash_folder);
        imap_expunge($mb);
        header("Location: index.php?folder=".$_REQUEST['mbox']);
        return;
    }

    $h = imap_headerinfo($mb,$_REQUEST['num']);

    $to = "";
    $cc = "";
    if (isset($h->reply_to)){
        foreach($h->reply_to as $r)
            $to .= $r->mailbox."@".$r->host.", ";
    }
    if ($_REQUEST['mode'] == "replyall"){
        foreach($h->to as $t){
            $addr = $t->mailbox."@".$t->host;
            // don't include yourself
            if (strtolower($addr)==strtolower($user."@".$domain)) continue;
            if(strstr($to,$addr) === False)
                $to .= $addr.", ";
        }
        foreach($h->cc as $c){
            $addr = $c->mailbox."@".$c->host;
            // don't include yourself
            if (strtolower($addr)==strtolower($user."@".$domain)) continue;
            if(strstr($to,$addr) === False && strstr($cc,$addr) === False)
                $cc .= $addr.", ";
        }
    }
    $to = substr($to,0,strlen($to)-2);    
    $cc = substr($cc,0,strlen($cc)-2);    

    $subject = $h->subject;
    if (substr(strtolower($subject),0,3) != "re:")
        $subject = "RE: ".$subject;

    $output .= "<form action=index.php method=post>";

    $output .= "<b>To</b>: ";
    $output .= sprintf("<input size=40 type=text name=to_line value=\"%s\" />",$to);
    $output .= "<hr />";

    $output .= "<b>CC</b>: ";
    $output .= sprintf("<input size=40 type=text name=cc_line value=\"%s\" />",$cc);
    $output .= "<hr />";

    $output .= "<b>Subject</b>: ";
    $output .= sprintf("<input size=40 type=text name=subject value=\"%s\" />",$subject);
    $output .= "<hr />";

    $msgBody = get_body($mb,$_REQUEST['num'],False);
    if (strstr(strtolower($msgBody),"<html>") !== False)
        $msgBody .= "<html>\n\n\n<hr />"; // ultra lazy, no parsing
    else {
        $msgBody = "\n\n\n>".str_replace("\n","\n>",$msgBody);
    }
    $output .= "<textarea name=body rows=10 cols=40>";
    $output .= $msgBody;
    $output .= "</textarea>";

    $output .= "<hr />";
    $output .= "<input type=submit name=send value=Send />";
    $output .= sprintf("<input type=hidden name=mbox value=\"%s\" />",$_REQUEST['mbox']);
    $output .= "</form>";
}
else {
    $folders = imap_getmailboxes($mb,$server,"*");

    $output .= "<a href=index.php?folder=INBOX>INBOX</a><br />";
    foreach($folders as $f){
        $name = substr($f->name,strlen($server));
        if ($name == "INBOX") continue;
        $output .= sprintf("<a href=\"index.php?folder=%s\">%s</a><br />",
            $name,$name);
    }
}

$output .= "<hr />";
$output .= "<a href=index.php?logout=yes>Logout</a>";

echo $output;

