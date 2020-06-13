<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Error\Exception;

class NoAccessException extends HttpException {

    const E_INACTIVE = 0;
    const E_ROLE_REQUIRED = 1;

    const CODE = 403;
    const MESSAGE = 'Akses Ditolak';
    const TITLE = 'Forbidden';
    const DESCRIPTION = 'Silakan hubungi administrator';

    private $user_id;

    public function __construct($request, $uid, $message='', $err_code = self::E_ANY) {
        $this->user_id = $uid;
        parent::__construct($request, $message, [], $err_code);
    }

    public function getUserId() {
        return $this->user_id;
    }
}