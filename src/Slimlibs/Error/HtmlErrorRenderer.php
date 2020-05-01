<?php declare (strict_types = 1);

namespace Albatiqy\Slimlibs\Error;

use Psr\Container\ContainerInterface;
use Slim\Error\Renderers\HtmlErrorRenderer as ParentHtmlErrorRenderer;
use Slim\Exception\HttpException as SlimHttpException;
use Albatiqy\Slimlibs\Error\Exception\HttpException;
use Throwable;

class HtmlErrorRenderer extends ParentHtmlErrorRenderer {

    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function __invoke(Throwable $exception, bool $displayErrorDetails): string {
        if ($exception instanceof SlimHttpException) {
            $renderer = $this->container->get('renderer');
            $request = $exception->getRequest();
            $accepts = \explode(',', $request->getHeader('Accept')[0]);
            $data = [
                'mpa' => (\count($accepts) > 1),
                'exception' => $exception,
                'displayErrorDetails' => $displayErrorDetails
            ];
            //die('tambahin template');
            if ($exception instanceof HttpException) {
                if (\file_exists(\APP_DIR . '/view/templates/system/slimlibs-error.php')) {
                    return $renderer->make('system/slimlibs-error')->render($data);
                }
            } else {
                if (\file_exists(\APP_DIR . '/view/templates/system/http-error.php')) {
                    return $renderer->make('system/http-error')->render($data);
                }
            }
            return $this->renderExceptionFragment($exception);
        }
        return parent::__invoke($exception, $displayErrorDetails);
    }

    private function renderExceptionFragment(Throwable $exception): string {
        $html = \sprintf('<div><strong>Type:</strong> %s</div>', \get_class($exception));

        $code = $exception->getCode();
        if ($code !== null) {
            $html .= \sprintf('<div><strong>Code:</strong> %s</div>', $code);
        }

        $message = $exception->getMessage();
        if ($message !== null) {
            $html .= \sprintf('<div><strong>Message:</strong> %s</div>', \htmlentities($message));
        }

        $file = $exception->getFile();
        if ($file !== null) {
            $html .= \sprintf('<div><strong>File:</strong> %s</div>', $file);
        }

        $line = $exception->getLine();
        if ($line !== null) {
            $html .= \sprintf('<div><strong>Line:</strong> %s</div>', $line);
        }

        $trace = $exception->getTraceAsString();
        if ($trace !== null) {
            $html .= '<h2>Trace</h2>';
            $html .= \sprintf('<pre>%s</pre>', \htmlentities($trace));
        }

        return $html;
    }
}
