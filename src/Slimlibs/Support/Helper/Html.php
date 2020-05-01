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
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $figures = $xpath->query('//figure[@class="media"]');
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

    public static function processMediaEmbed($html) {
        if (!$html) {
            return '';
        }
        $MediaEmbed = new \MediaEmbed\MediaEmbed();
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $figures = $xpath->query('//figure[@class="media"]');
        foreach ($figures as $figure) {
            $oembeds = $figure->getElementsByTagName('oembed'); // firstChild
            $url = $oembeds->item(0)->getAttribute('url');
            $MediaObject = $MediaEmbed->parseUrl($url);
            if ($MediaObject) {
                $MediaObject->setAttribute([
                    'class' => 'xapp-'.$MediaObject->slug().'-media'
                ]);
                $htmlEmbed = $MediaObject->getEmbedCode();
                $mediaDoc = new \DOMDocument();
                $mediaDoc->loadHTML($htmlEmbed);
                $figure->parentNode->replaceChild($dom->importNode($mediaDoc->getElementsByTagName('body')->item(0)->childNodes[0],true), $figure);
            }
        }
        $mock = new \DOMDocument();
        $body = $dom->getElementsByTagName('body')->item(0);
        foreach ($body->childNodes as $child){
            $mock->appendChild($mock->importNode($child, true));
        }
        return $mock->saveHTML();
    }
}