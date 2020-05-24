<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Helper;

use Albatiqy\Slimlibs\Support\Helper\Image;

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
        $dom = new \DOMDocument();
        \libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        \libxml_use_internal_errors(false);
        $xpath = new \DOMXPath($dom);
        $figures = $xpath->query("//iframe[starts-with(@class, 'xapp-') and contains(@class, '-media') and not(@class='xapp-pdf-media')]");
        foreach ($figures as $figure) {
            $class = $figure->getAttribute('class');
            $imageSrc = $figure->getAttribute('data-thumbnail');
            switch ($class) {
                case 'xapp-youtube-media':
                    $parse_url = \parse_url($imageSrc);
                    $dir = \dirname($parse_url['path']);
                    $imageSrc = (isset($parse_url['scheme'])?$parse_url['scheme'].':':'').'//'.$parse_url['host'].$dir.'/maxresdefault.jpg';
            }
            $key = Image::cache($imageSrc);
            $imageSrc = \BASE_PATH.'/resources/imgcache/'.$key;
            if ($first) {
                return $imageSrc;
            }
            $images[] = $imageSrc;
        }
        if ($first) {
            return false;
        }
        return $images;
    }

    public static function findFirstMedia($html) {
        if (!$html) {
            return null;
        }
        $dom = new \DOMDocument();
        \libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        \libxml_use_internal_errors(false);
        $xpath = new \DOMXPath($dom);
        $figures = $xpath->query("//iframe[starts-with(@class, 'xapp-') and contains(@class, '-media') and not(@class='xapp-pdf-media')]");
        foreach ($figures as $figure) {
            $class = $figure->getAttribute('class');
            $imageSrc = $figure->getAttribute('data-thumbnail');
            $src = $figure->getAttribute('data-src');
            switch ($class) {
                case 'xapp-youtube-media':
                    $parse_url = \parse_url($imageSrc);
                    $dir = \dirname($parse_url['path']);
                    $imageSrc = (isset($parse_url['scheme'])?$parse_url['scheme'].':':'').'//'.$parse_url['host'].$dir.'/maxresdefault.jpg';
            }
            $key = Image::cache($imageSrc);
            $imageSrc = \BASE_PATH.'/resources/imgcache/'.$key;
            return (object)['src'=>$src, 'class'=>$class, 'thumbnail'=>$imageSrc];
        }
        return null;
    }

    public static function entities($string, $preserve_encoded_entities = false)
    {
        if ($preserve_encoded_entities) {
            // @codeCoverageIgnoreStart
            if (\defined('HHVM_VERSION')) {
                $translation_table = \get_html_translation_table(\HTML_ENTITIES, \ENT_QUOTES);
            } else {
                $translation_table = \get_html_translation_table(\HTML_ENTITIES, \ENT_QUOTES, self::mbInternalEncoding());
            }
            // @codeCoverageIgnoreEnd

            $translation_table[\chr(38)] = '&';
            return \preg_replace('/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/', '&amp;', \strtr($string, $translation_table));
        }

        return \htmlentities($string, \ENT_QUOTES, self::mbInternalEncoding());
    }

    public static function linkify($text)
    {
        $text = \preg_replace('/&apos;/', '&#39;', $text); // IE does not handle &apos; entity!
        $section_html_pattern = '%# Rev:20100913_0900 github.com/jmrware/LinkifyURL
            # Section text into HTML <A> tags  and everything else.
              (                             # $1: Everything not HTML <A> tag.
                [^<]+(?:(?!<a\b)<[^<]*)*     # non A tag stuff starting with non-"<".
              |      (?:(?!<a\b)<[^<]*)+     # non A tag stuff starting with "<".
             )                              # End $1.
            | (                             # $2: HTML <A...>...</A> tag.
                <a\b[^>]*>                   # <A...> opening tag.
                [^<]*(?:(?!</a\b)<[^<]*)*    # A tag contents.
                </a\s*>                      # </A> closing tag.
             )                              # End $2:
            %ix';

        return \preg_replace_callback($section_html_pattern, array(__CLASS__, 'linkifyCallback'), $text);
    }

    protected static function mbInternalEncoding($encoding = null)
    {
        if (\function_exists('mb_internal_encoding')) {
            return $encoding ? \mb_internal_encoding($encoding) : \mb_internal_encoding();
        }

        // @codeCoverageIgnoreStart
        return 'UTF-8';
        // @codeCoverageIgnoreEnd
    }
}