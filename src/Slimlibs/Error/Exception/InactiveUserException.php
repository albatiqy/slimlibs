<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Error\Exception;

class InactiveUserException extends HttpException {
    const CODE = 401;
    const MESSAGE = 'Akun anda tidak aktif';
    const TITLE = 'Inactive User';
    const DESCRIPTION = 'Silakan hubungi administrator.';

    private $user_id;

    public function __construct($request, $uid) {
        $this->user_id = $uid;
        parent::__construct($request);
    }

    public function getUserId() {
        return $this->user_id;
    }
}