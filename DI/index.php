<?php
/**
 * Created by PhpStorm.
 * User: hurace
 * Date: 2018/5/10 0010
 * Time: 15:10
 */

interface Mail {
    public function send();
}

class Email implements Mail {
    public function send() {
        echo 'send email success...';
    }
}

class SmsMail implements Mail {
    public function send() {
        echo 'send sms success...';
    }
}

class Register {
    private $_mailObj;

    public function __construct(Mail $mail) {
        $this->_mailObj = $mail;
    }

    public function send() {
        $this->_mailObj->send();
    }
}

$email = new Email();
$reg = new Register($email);
$reg->send();
echo PHP_EOL;
$sms = new SmsMail();
$reg = new Register($sms);
$reg->send();
