<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Libs;

use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerInterface;

final class SendMail {

    private $container;
    private $mailer;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $settings = $container->get('settings')['send_mail'];

        try {
            $this->mailer = new PHPMailer;
            $this->mailer->isSMTP();
            $this->mailer->SMTPDebug = $settings['smtp_debug'];
            $this->mailer->Host = $settings['host'];
            $this->mailer->Port = $settings['port'];
            $this->mailer->SMTPSecure = $settings['smtp_secure'];
            $this->mailer->SMTPAuth = $settings['smtp_auth'];
            $this->mailer->Username = $settings['username'];
            $this->mailer->Password = $settings['password'];
            //$this->mailer->addReplyTo('ppk.tendik@gmail.com', 'PPK Tendik');
            //$this->mailer->addAttachment('images/phpmailer_mini.png');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function from($address, $name) {
        $this->mailer->setFrom($address, $name);
        return $this;
    }

    public function to($address, $name) {
        $this->mailer->addAddress($address, $name);
        return $this;
    }

    public function subject($subject) {
        $this->mailer->Subject = $subject;
        return $this;
    }

    public function htmlBody($html) {
        $this->mailer->msgHTML($html, \APP_DIR . '/public');
        return $this;
    }

    public function textBodyAlt($text) {
        $this->mailer->AltBody = $text;
        return $this;
    }

    public function send() {
        try {
            if (!$this->mailer->send()) {
                throw new \Exception($this->mailer->ErrorInfo);
            } else {
                return true;
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}