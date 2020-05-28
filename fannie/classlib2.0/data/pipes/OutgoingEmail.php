<?php

namespace COREPOS\Fannie\API\data\pipes;

class OutgoingEmail
{
    public static function available()
    {
        if (class_exists('\\PHPMailer')) {
            return true;
        } elseif (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
            return true;
        }

        return false;
    }

    public static function get()
    {
        if (class_exists('\\PHPMailer')) {
            return new \PHPMailer();
        } elseif (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
            return new \PHPMailer\PHPMailer\PHPMailer();
        }

        throw new \Exception('Mailer unavailable');
    }
}

