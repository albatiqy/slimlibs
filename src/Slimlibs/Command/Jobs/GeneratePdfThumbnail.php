<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command\Jobs;

use Albatiqy\Slimlibs\Command\AbstractJob;

/**
 * Create Pdf Thumbnail image
 *
 */
final class GeneratePdfThumbnail extends AbstractJob {

    protected const MAP = 'generatepdfthumb';

    /**
     * source pdf file
     *
     * @alias [pdfsrc]
     */
    public $src_pdf = '';

    /**
     * destination png file
     *
     * @alias [destpng]
     */
    public $dest_png = '';

    protected function handle() {
        $tmpname = \bin2hex(\random_bytes(8));
        $fileout = \APP_DIR . '/var/tmp/'.$tmpname;
        \exec("pdftoppm -png -f 1 -scale-to 500 -singlefile ".$this->src_pdf.' '.$fileout, $output, $retval);
        \rename($fileout.'.png', $this->dest_png);
    }
}