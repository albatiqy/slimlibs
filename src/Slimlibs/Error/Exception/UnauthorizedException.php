<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Error\Exception;

class UnauthorizedException extends HttpException {
    const CODE = 401;
    const MESSAGE = 'Akses Ditolak';
    const TITLE = 'Unauthorized';
    const DESCRIPTION = 'Silakan hubungi administrator.';
}