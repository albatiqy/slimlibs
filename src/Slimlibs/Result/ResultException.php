<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Result;

use Slim\Exception\HttpException;
use Psr\Http\Message\ServerRequestInterface;

abstract class ResultException extends HttpException {

    const E_ANY = -1;

    const CODE = 500;
    const MESSAGE = 'Internal server error.';
    const TITLE = '500 Internal Server Error';
    const DESCRIPTION = 'Unexpected condition encountered preventing server from fulfilling request.';

    const T_UNAUTHORIZED = 'UNAUTHORIZED';
    const T_SERVICE = 'SERVICE';
    const T_VALIDATION = 'VALIDATION';
    const T_NOT_EXIST = 'NOT_EXIST';

    protected $data = [];
    protected $errType = self::T_UNAUTHORIZED;
    protected $errCode;

    const ERR_TYPES = [
        self::T_UNAUTHORIZED  => 1,
        self::T_SERVICE  => 2,
        self::T_VALIDATION  => 3,
        self::T_NOT_EXIST  => 4
    ];

    abstract protected function init();

    public function __construct(ServerRequestInterface $request, $data = [], $message = '', $err_code = self::E_ANY)
    {
        //$request = $request->withHeader('Accept', 'application/json'); // do in error handler
        $this->init();
        if (!$message) {
            $message = static::MESSAGE;
        }
        parent::__construct($request, $message, static::CODE);
        $this->data = $data;
        $this->errCode = $err_code;
    }

    public function getData() {
        return (object)$this->data;
    }

    public function getErrType() {
        return self::ERR_TYPES[$this->errType];
    }

    public function getErrCode() {
        return $this->errCode;
    }
}