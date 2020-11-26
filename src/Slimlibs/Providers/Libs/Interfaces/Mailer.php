<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Libs\Interfaces;

interface Mailer {
    public function sendMessage($to, $subject, $messageText, $fattachment = null);
}