<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Validation;

final class ValidationException extends \Exception {

    private $errors = [];

    public function __construct(array $errors = []) {
        parent::__construct("Validation Error");
        $this->errors = $errors;
    }

    public function getErrors() {
        return $this->errors;
    }
}