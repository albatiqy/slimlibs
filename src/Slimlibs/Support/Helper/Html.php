<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Helper;

use Albatiqy\Slimlibs\Support\Helper\Image;

final class Html {

    public static function getImagesSrc($html, $first = false) {
        if (!$html) {
            return [];
        }
        $matches = [];
        \preg_match_all('/\<img[^\>]*[src] *= *[\"\']{0,1}([^\"\']*)/i', $html, $matches); //  /<img(.*?)src=("\'|)(.*?)("|\'| )(.*?)>/s
        $images = $matches[1];
        if ($first) {
            if (\count($images) > 0) {
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
                if (\strpos($query['file'], $prefix) == 0) {
                    $fileImg = $query['file'] . '.png';
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
                $imageSrc = (isset($parse_url['scheme']) ? $parse_url['scheme'] . ':' : '') . '//' . $parse_url['host'] . $dir . '/hqdefault.jpg';
            }
            $key = Image::cache($imageSrc);
            $imageSrc = \BASE_PATH . '/resources/imgcache/' . $key;
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

    public static function getYoutubes($html) {
        if (!$html) {
            return [];
        }
        $results = [];
        $dom = new \DOMDocument();
        \libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        \libxml_use_internal_errors(false);
        $xpath = new \DOMXPath($dom);
        $figures = $xpath->query("//iframe[@class='xapp-youtube-media']");
        foreach ($figures as $figure) {
            $src = $figure->getAttribute('data-src');
            $results[] = $src;
        }
        return $results;
    }

/*
            $dom = new \DOMDocument();
            \libxml_use_internal_errors(true);
            $dom->loadHTML(\file_get_contents($fileout.'.html'));
            \libxml_use_internal_errors(false);
            $mock = new \DOMDocument();
            $body = $dom->getElementsByTagName('body')->item(0);
            foreach ($body->childNodes as $child){
                $mock->appendChild($mock->importNode($child, true));
            }
            $html = $mock->saveHTML();
*/

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
            $url = $figure->getAttribute('src');
            switch ($class) {
            case 'xapp-youtube-media':
                $parse_url = \parse_url($imageSrc);
                $dir = \dirname($parse_url['path']);
                $imageSrc = (isset($parse_url['scheme']) ? $parse_url['scheme'] . ':' : '') . '//' . $parse_url['host'] . $dir . '/hqdefault.jpg';
            }
            $key = Image::cache($imageSrc);
            $imageSrc = \BASE_PATH . '/resources/imgcache/' . $key;
            return (object) ['url' => $url, 'src' => $src, 'class' => $class, 'thumbnail' => $imageSrc];
        }
        return null;
    }

    public static function entities($string, $preserve_encoded_entities = false) {
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

    public static function linkify($text) {
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

        return \preg_replace_callback($section_html_pattern,[__CLASS__, 'linkifyCallback'], $text);
    }

    protected static function linkifyCallback($matches) {
        if (isset($matches[2])) {
            return $matches[2];
        }

        return self::linkifyRegex($matches[1]);
    }

    protected static function linkifyRegex($text) {
        $url_pattern = '/# Rev:20100913_0900 github.com\/jmrware\/LinkifyURL
            # Match http & ftp URL that is not already linkified.
            # Alternative 1: URL delimited by (parentheses).
            (\() # $1 "(" start delimiter.
            ((?:ht|f)tps?:\/\/[a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]+) # $2: URL.
            (\)) # $3: ")" end delimiter.
            | # Alternative 2: URL delimited by [square brackets].
            (\[) # $4: "[" start delimiter.
            ((?:ht|f)tps?:\/\/[a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]+) # $5: URL.
            (\]) # $6: "]" end delimiter.
            | # Alternative 3: URL delimited by {curly braces}.
            (\{) # $7: "{" start delimiter.
            ((?:ht|f)tps?:\/\/[a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]+) # $8: URL.
            (\}) # $9: "}" end delimiter.
            | # Alternative 4: URL delimited by <angle brackets>.
            (<|&(?:lt|\#60|\#x3c);) # $10: "<" start delimiter (or HTML entity).
            ((?:ht|f)tps?:\/\/[a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]+) # $11: URL.
            (>|&(?:gt|\#62|\#x3e);) # $12: ">" end delimiter (or HTML entity).
            | # Alternative 5: URL not delimited by (), [], {} or <>.
            (# $13: Prefix proving URL not already linked.
            (?: ^ # Can be a beginning of line or string, or
            | [^=\s\'"\]] # a non-"=", non-quote, non-"]", followed by
           ) \s*[\'"]? # optional whitespace and optional quote;
            | [^=\s]\s+ # or... a non-equals sign followed by whitespace.
           ) # End $13. Non-prelinkified-proof prefix.
            (\b # $14: Other non-delimited URL.
            (?:ht|f)tps?:\/\/ # Required literal http, https, ftp or ftps prefix.
            [a-z0-9\-._~!$\'()*+,;=:\/?#[\]@%]+ # All URI chars except "&" (normal*).
            (?: # Either on a "&" or at the end of URI.
            (?! # Allow a "&" char only if not start of an...
            &(?:gt|\#0*62|\#x0*3e); # HTML ">" entity, or
            | &(?:amp|apos|quot|\#0*3[49]|\#x0*2[27]); # a [&\'"] entity if
            [.!&\',:?;]? # followed by optional punctuation then
            (?:[^a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]|$) # a non-URI char or EOS.
           ) & # If neg-assertion true, match "&" (special).
            [a-z0-9\-._~!$\'()*+,;=:\/?#[\]@%]* # More non-& URI chars (normal*).
           )* # Unroll-the-loop (special normal*)*.
            [a-z0-9\-_~$()*+=\/#[\]@%] # Last char can\'t be [.!&\',;:?]
           ) # End $14. Other non-delimited URL.
            /imx';

        $url_replace = '$1$4$7$10$13<a href="$2$5$8$11$14" target="_blank">$2$5$8$11$14</a>$3$6$9$12';

        return \preg_replace($url_pattern, $url_replace, $text);
    }

    protected static function mbInternalEncoding($encoding = null) {
        if (\function_exists('mb_internal_encoding')) {
            return $encoding ? \mb_internal_encoding($encoding) : \mb_internal_encoding();
        }

        // @codeCoverageIgnoreStart
        return 'UTF-8';
        // @codeCoverageIgnoreEnd
    }
}