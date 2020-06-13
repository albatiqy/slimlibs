<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Error\Exception;

use Slim\Exception\HttpException as SlimHttpException;
use Psr\Http\Message\ServerRequestInterface;

abstract class HttpException extends SlimHttpException {

    const E_ANY = -1;
    const CODE = 500;
    const MESSAGE = 'Internal server error';
    const TITLE = 'Internal Server Error';
    const DESCRIPTION = 'Unexpected condition encountered preventing server from fulfilling request';

    protected $data = [];
    protected $errCode;

    public function __construct(ServerRequestInterface $request, $message = '', $data = [], $err_code = self::E_ANY)
    {
        if (!$message) {
            $message = static::MESSAGE;
        }
        $this->description = static::DESCRIPTION;
        $this->title = static::TITLE;
        $this->data = $data;
        $this->errCode = $err_code;
        parent::__construct($request, $message, static::CODE);
    }

    public function getData() {
        return $this->data;
    }

    public function getErrCode() {
        return $this->errCode;
    }
}