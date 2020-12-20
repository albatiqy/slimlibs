<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions\Resource;

use Albatiqy\Slimlibs\Actions\ResourceAction;
use Albatiqy\Slimlibs\Support\Util\Stringy;

final class MediaGet extends ResourceAction {

    protected function getResponse(array $data, array $args) {
        $path = (string) Stringy::create($args['path'])->tidy();
        $filein = \APP_DIR.'/var/resources/media/'.$path;
        if (\file_exists($filein)) {
            $extension = \pathinfo($filein, \PATHINFO_EXTENSION);
            switch (\strtoupper($extension)) {
                case 'JPG':
                case 'JFIF':
                case 'JPEG':
                    $extension = 'JPG';
                case 'PNG':
                    $method = 'render'.\ucfirst($extension);
                    return $this->$method('/media/'.$path);
                case 'PDF':
                    $this->response->getBody()->write(\file_get_contents($filein));
                    return $this->response
                        ->withHeader('Content-Type', 'application/pdf')
                        //->withHeader('Content-Disposition', 'attachment;filename="'.$args['zipname'].'"')
                        ->withHeader('Content-Length', \filesize($filein))
                        //->withHeader('Content-Transfer-Encoding', 'binary')
                        ->withHeader('Cache-Control', 'public, max-age=86400')
                        ->withHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT')
                        ->withHeader('Last-Modified', \gmdate('D, d M Y H:i:s') . ' GMT')
                        ->withAddedHeader('Cache-Control', 'cache, must-revalidate')
                        ->withHeader('Pragma', 'public')
                        ->withStatus(200);
            }
        } else {
            $accepts = \explode(',', $this->request->getHeader('Accept')[0]);
            $accept = $accepts[0];
            $type = \explode('/', $accept);
            switch (\strtoupper($type[1])) {
                case 'JPG':
                case 'JFIF':
                case 'JPEG':
                    $response = $this->renderImg($accept, \LIBS_DIR.'/web/resources/blank.jpg');
                    return $response->withStatus(404);
                case 'PNG':
                    $response = $this->renderImg($accept, \LIBS_DIR.'/web/resources/blank.png');
                    return $response->withStatus(404);
                case 'PDF':
                    $fblank = \LIBS_DIR.'/web/resources/blank.pdf';
                    $this->response->getBody()->write(\file_get_contents($fblank));
                    return $this->response
                        ->withHeader('Content-Type', 'application/pdf')
                        //->withHeader('Content-Disposition', 'attachment;filename="'.$args['zipname'].'"')
                        ->withHeader('Content-Length', \filesize($fblank))
                        //->withHeader('Content-Transfer-Encoding', 'binary')
                        ->withHeader('Cache-Control', 'public, max-age=86400')
                        ->withHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT')
                        ->withHeader('Last-Modified', \gmdate('D, d M Y H:i:s') . ' GMT')
                        ->withAddedHeader('Cache-Control', 'cache, must-revalidate')
                        ->withHeader('Pragma', 'public')
                        ->withStatus(404);
            }
            switch (\strtoupper($type[0])) {
                case 'IMAGE':
                    $response = $this->renderImg('image/png', \LIBS_DIR.'/web/resources/blank.png');
                    return $response->withStatus(404);
                default:
                    $this->response->getBody()->write("resource not found!");
                    return $this->response
                        ->withHeader('Content-Type', $accept)
                        //->withHeader('Content-Disposition', 'attachment;filename="'.$args['zipname'].'"')
                        //->withHeader('Content-Transfer-Encoding', 'binary')
                        ->withHeader('Cache-Control', 'public, max-age=86400')
                        ->withHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT')
                        ->withHeader('Last-Modified', \gmdate('D, d M Y H:i:s') . ' GMT')
                        ->withAddedHeader('Cache-Control', 'cache, must-revalidate')
                        ->withHeader('Pragma', 'public')
                        ->withStatus(404);
            }
        }
    }
}