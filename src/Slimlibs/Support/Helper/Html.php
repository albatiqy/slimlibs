<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Helper;

final class Html {

    public static function getImagesSrc($html, $first=false) {
        if (!$html) {
            return [];
        }
        $matches = [];
        \preg_match_all('/\<img[^\>]*[src] *= *[\"\']{0,1}([^\"\']*)/i', $html, $matches); //  /<img(.*?)src=("\'|)(.*?)("|\'| )(.*?)>/s
        $images = $matches[1];
        if ($first) {
            if (\count($images)>0) {
                return $images[0];
            }
        }
        $matches = [];
        \preg_match_all('/<iframe\s+(?=[^>]*?(?<=\s)class\s*=\s*"\s*xapp-pdf-media\s*")[^>]*?(?<=\s)src\s*=\s*"\s*([^"]*)/i', $html, $matches);
        foreach ($matches[1] as $pdf) {
            $url = \parse_url($pdf);
            \parse_str($url['query'], $query);
            if (isset($query['file'])) {
                $prefix = '/resources/media';
                if (\strpos($query['file'], $prefix)==0) {
                    $fileImg = $query['file'].'.png';
                    if ($first) {
                        return $fileImg;
                    }
                    $images[] = $fileImg;
                }
            }
        }
        $MediaEmbed = new \MediaEmbed\MediaEmbed();
        $dom = new \DOMDocument();
        \libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        \libxml_use_internal_errors(false);
        $xpath = new \DOMXPath($dom);
        $figures = $xpath->query('//figure[@class="media"]'); // no ckeditor perbaiki!!!!!!!!!!!!!!!!!!!
        foreach ($figures as $figure) {
            $oembeds = $figure->getElementsByTagName('oembed'); // firstChild
            $url = $oembeds->item(0)->getAttribute('url');
            $MediaObject = $MediaEmbed->parseUrl($url);
            if ($MediaObject) {
                $imageSrc = $MediaObject->getImageSrc();
                if ($first) {
                    return $imageSrc;
                }
                $images[] = $imageSrc;
            }
        }
        if ($first) {
            return false;
        }
        return $images;
    }
}