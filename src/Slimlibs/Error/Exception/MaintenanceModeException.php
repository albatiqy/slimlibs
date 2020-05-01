<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Error\Exception;

class MaintenanceModeException extends HttpException {
    const CODE = 500;
    const MESSAGE = 'Kami offline untuk sementara';
    const TITLE = 'Maintenance Mode';
    const DESCRIPTION = 'Silakan kembali lagi nanti.';
}