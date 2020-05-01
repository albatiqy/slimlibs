<?php declare (strict_types = 1);

namespace Albatiqy\Slimlibs\Error;

use Albatiqy\Slimlibs\Result\ResultException;
use Psr\Container\ContainerInterface;
use Slim\Error\Renderers\JsonErrorRenderer;
use Throwable;

class JsonResultErrorRenderer extends JsonErrorRenderer {

    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function __invoke(Throwable $exception, bool $displayErrorDetails): string {
        if (\is_subclass_of($exception, ResultException::class)) {
            $error = ['message' => $exception->getMessage()]; //$this->getErrorTitle($exception)
            $error['errType'] = $exception->getErrType();
            $error['error'] = $exception->getData();

            if ($displayErrorDetails) {
                $error['exception'] = [];
                do {
                    $error['exception'][] = $this->formatExceptionFragment($exception);
                } while ($exception = $exception->getPrevious());
            }

            return (string) \json_encode($error, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);

        } // else???
        return parent::__invoke($exception, $displayErrorDetails);
    }

    private function formatExceptionFragment(Throwable $exception): array//format
    {
        return [
            'type' => \get_class($exception),
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];
    }
}