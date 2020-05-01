<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Error\Exception;

use Slim\Exception\HttpException as SlimHttpException;
use Psr\Http\Message\ServerRequestInterface;

abstract class HttpException extends SlimHttpException {

    const CODE = 500;
    const MESSAGE = 'Internal server error.';
    const TITLE = '500 Internal Server Error';
    const DESCRIPTION = 'Unexpected condition encountered preventing server from fulfilling request.';

    public function __construct(ServerRequestInterface $request, $message = '')
    {
        if ($message=='') {
            $message = static::MESSAGE;
        }
        $this->description = static::DESCRIPTION;
        $this->title = static::TITLE;
        parent::__construct($request, $message, static::CODE);
    }
}