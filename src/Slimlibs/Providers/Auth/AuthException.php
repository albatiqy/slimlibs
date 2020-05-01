<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Auth;

final class AuthException extends \Exception {

    const E_ANY = -1;
    const E_INACTIVE = 0;
    const E_REFRESH_TOKEN = 1;

    public function __construct($message = "", $code = 0, \Exception $previous = null) {
        if (!$code) {
            $code = self::E_ANY;
        }
        parent::__construct($message, $code, $previous);
    }
}