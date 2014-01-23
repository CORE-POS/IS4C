<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/**
  @class NewMemberEmailPipe

  This is a PHP script designed to receive emails.
  Postfix (and perhaps other MTAs) can deliver a message
  to a program rathan than a mailbox. This script is
  meant to receive those kind of piped messages.

  The main idea is to provide a pathway for a host on
  the general internet to send a message to Fannie
  without allowing access to HTTP/HTTPS/MySQL. There
  are validation/authentication concerns no matter 
  what the mechanism, but I think this approach 
  has some advantages in terms of keeping responses
  to external inputs tailored really, really narrowly.
  It does add "mail server admin" to the list of hats
  the IT staff has to wear though.

  The initial usage I have in mind is signing up
  members via the co-op's website. When someone signs
  up on the site, the website would send an email to
  the store with their information encoded somehow
  (JSON, probably) and then this script could use
  that information to update the database. Using a
  mysql driver that supports prepared statements 
  properly would be highly recommended since this
  increases the danger of malicious input a fair amount.

  For validation, I think I'll prepopuldate a set
  of member accounts on the POS side with some kind
  of random hexadecimal key on each account (likely
  via custdata.blueLine) and push them out to the 
  website. The incoming member signup messages could
  then be required to provide the correct hex key
  for the account they are creating. Headers could
  be validated, too (From, originating server, etc)
  but since it's all spoofable I don't know if it's
  even worth bothering.

  Prepopulating accounts on the website does open
  the possibility that new memberships could 
  become "out of stock" which is rather silly, but
  that should be fairly simple to avoid with a 
  generous initial allocation and then fine-tuning
  based on results. It would eliminate most of the
  risk of accidentally giving out the same number
  twice if one customer is signing up in the store
  while another is signing up online.
*/
class NewMemberEmailPipe
{
    public function processMail($msg)
    {
        $info = $this->parseEmail($msg);
        
        if ($this->validateHeaders($info['headers'])) {
            $data = json_decode($info['body']);
            var_dump($data);
        }
    }

    private function validateHeaders($headers)
    {
        // maybe impose sender or originating SMTP server restrictions
        // maybe require key-value pairs to help authenticate
        // the message
        return true;
    }

    /**
    Parse email message
    @param $content [string] raw email
    @return keyed array
      - header => key:value [array] of headers
      - body => [string] raw body
      - invalid => [array] of malformed lines

    I highly doubt this is 100% RFC2822 compliant.
    Shouldn't matter for use cases I'm considering,
    but take "invalid" with a grain of salt. It just
    means my validation says it isn't a correctly 
    formatted header line.
    */
    private function parseEmail($content)
    {
        $headers = array();
        $last_header = '';
        $body = '';
        $reached_body = false;
        $garbage = array();
        $lines = preg_split('/\r?\n|\n/', $content);
        foreach($lines as $line) {

            $test_end = str_replace("\r", "", $line);
            $test_end = str_replace("\n", "", $test_end);
            if ($test_end === '') {
                $reached_body = true;
                continue;
            }

            if ($reached_body) {
                $body .= $line;
            } else {
                if ($this->rfcCompliant($line[0]) && preg_match('/^([^:]+):(.+)$/', $line, $matches) === 1) {
                    $field_name = rtrim($matches[1]);
                    $field_body = ltrim($matches[2]);
                    if ($this->rfcCompliant($field_name)) {
                        $headers[$field_name] = $field_body;
                        $last_header = $field_name;
                    } else {
                        $garbage[] = $line;
                        $last_header = '';
                    }
                } else if ($last_header !== '') {
                    $headers[$last_header] .= $line;
                } else {
                    $garbage[] = $line;
                }
            }
        }

        return array('headers'=>$headers, 'body'=>$body, 'invalid'=>$garbage);
    }

    /**
      Check if string is an RFC2822 field name
      Field names should contain characters between
      ASCII 33 and 126 except for ":" (ASCII 58)
      @param $str [string] to check
      @return [boolean]
    */
    private function rfcCompliant($str)
    {
        for($i=0; $i<strlen($str);$i++) {
            if (ord($str[$i]) < 33 || ord($str[$i]) > 126 || ord($str[$i]) == 58) {
                return false;
            }
        }

        return true;
    }
}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $obj = new NewMemberEmailPipe();
    $message = file_get_contents("php://stdin");
    if (!empty($message)) {
        $obj->processMail($message);
    }
} 
