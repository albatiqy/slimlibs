<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions;

use Albatiqy\Slimlibs\Providers\Libs\Configs;
use Psr\Container\ContainerInterface;

abstract class ViewAction {

    protected $container;
    protected $request = null;
    protected $response = null;
    protected $args = [];
    protected $data = [];
    protected $configs = [];
    protected $settings = [];

    protected const CACHE = false;
    protected const CACHE_EXPIRES = (60*60*24);

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->settings = $container->get('settings');
        $cfgfcache = $this->settings['cache']['base_dir'].'/object-configs.php';
        if (\file_exists($cfgfcache)) {
            $cfgcache = require $cfgfcache;
            if ((\time()-$cfgcache['expires']) < $cfgcache['generated']) {
                $this->configs = $cfgcache['objects'];
            }
        }
        if (\count($this->configs)==0) {
            $da = $container->get(Configs::class);
            $this->configs = $da->cacheGetValues();
        }
    }

    public function __invoke($request, $response, $args) {
        $isMaintenance = $this->configs['app.maintenance_mode']??false;
        if ($isMaintenance) {
            //$payload = $request->getAttribute('payload'); // by pass if logged in using cookie jwt
            $path = $request->getAttribute('__route__')->getPattern();
            $settings = $this->settings;
            $pass = function() use ($settings, $path) {
                if (\strpos($path, $settings['backend_path'])===0) {
                    return true;
                }
                if ($path==$settings['login_path']) {
                    return true;
                }
                if (\basename($path)=='globals.js') {
                    return true;
                }
                return false;
            };
            if (!$pass()) {
                throw new \Albatiqy\Slimlibs\Error\Exception\MaintenanceModeException($request);
            }
        }
        $this->setHits($args);
        if ($this->settings['cache']['pages']) {
            if (static::CACHE) {
                $pathuri = \substr($request->getUri()->getPath(), \strlen(\BASE_PATH));
                $cacheFile = $this->settings['cache']['base_dir'] . '/pages' . ($pathuri != '/' ? $pathuri : '/index') . '.php';
                if (\file_exists($cacheFile)) {
                    if (\time()-static::CACHE_EXPIRES < \filemtime($cacheFile)) {
                        $response->getBody()->write(\file_get_contents($cacheFile));
                        return $response;
                    }
                }
            }
        }
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;
        return $this->getResponse($args);
    }

    protected function getResponse(array $args) {
        return $this->render();
    }

    protected function render($template, $profile=null) { // [BUAT SEMUA TIPE URL]
        $output = '';
        $this->data['args'] = $this->args;
        $this->data['configs'] = $this->configs;
        $renderer = $this->renderer;
        $renderer->registerFunction('getBaseUrl', [$this->container, 'getBaseUrl']);
        $renderer->registerFunction('throwNotFound', function(){
            $this->throwNotFound();
        });
        if ($this->settings['cache']['pages'] && static::CACHE) {
            $pathuri = \substr($this->request->getUri()->getPath(), \strlen(\BASE_PATH));
            $cache = ($pathuri != '/' ? $pathuri : '/index');
            $dirtpl = \dirname($cache);
            $cache_basedir = $this->settings['cache']['base_dir'].($profile?'/'.$profile.'-pages':'/pages');
            if ($dirtpl != '/') {
                $mkdir =  $cache_basedir . $dirtpl;
                if (!\is_dir($mkdir)) {
                    \umask(2);
                    \mkdir($mkdir, 0777, true);
                }
            }
            $output = $renderer->make($template)->render($this->data);
            \file_put_contents($cache_basedir . $cache . '.php', $output);
        } else { //if $template null?? apa yg mau dirender??
            $output = $renderer->make($template)->render($this->data);
        }
        $this->response->getBody()->write($output);
        return $this->response;
    }

    protected function renderProfile($template, $cacheprofile = null) {
        $profile = self::setRendererProfile($this->renderer);
        return $this->render($profile . '/' . $template, $cacheprofile);
    }

    public static function setRendererProfile($renderer) {
        $viewdata = $renderer->getData();
        $da = $viewdata['container']->get(Configs::class);
        $profile = $da->get('app.view.profile');
        $setting = require \APP_DIR . '/view/profiles/' . $profile . '.php';
        \define('VIEW_TEMPLATE', $profile);
        \define('VIEW_ASSET_PATH', $setting['assets_path']);
        $profile_dir = \APP_DIR . '/var/resources/view/templates/'.$profile;
        if (\is_dir($profile_dir)) {
            $renderer->addFolder('profile', $profile_dir);
        }
        return $profile;
    }

    public static function getCacheExpires() {
        return static::CACHE_EXPIRES;
    }

    public static function isCacheApplied() {
        return static::CACHE;
    }

    protected function setHits($args) {}

    protected function cacheIdSave($module, $key) {
        $fdir = \APP_DIR.'/var/resources/routecache/'.$module;
        if (!\is_dir($fdir)) {
            \umask(2);
            \mkdir($fdir, 0777, true);
        }
        $pathuri = \substr($this->request->getUri()->getPath(), \strlen(\BASE_PATH));
        \file_put_contents($fdir.'/'.$key, $pathuri, \FILE_APPEND);
    }

    public static function cachePageRemove($module, $key) {
        $fnremove = function($fcache) {
            if (\file_exists($fcache)) {
                $caches = \file($fcache);
                foreach ($caches as $cachepage) {
                    $pagecache = \APP_DIR.'/var/cache/pages'.$cachepage.'.php';
                    if (\file_exists($pagecache)) {
                        \unlink($pagecache);
                    }
                }
                \unlink($fcache);
            }
        };
        $fdir = \APP_DIR.'/var/resources/routecache/'.$module;
        $glob = \glob($fdir.'/'.$key);
        foreach ($glob as $file) {
            $fnremove($fdir.'/'.$file);
        }
    }

    protected function redirect($path = null) {
        if ($path == null) {
            $path = \BASE_PATH . '/';
        } else {
            $path = \BASE_PATH . $path;
        }
        return $this->response
            ->withStatus(302)
            ->withHeader('Location', $path);
    }

    protected function redirectAway($url) {
        return $this->response
            ->withStatus(302)
            ->withHeader('Location', $url);
    }

    protected function throwNotFound() {
        throw new \Slim\Exception\HttpNotFoundException($this->request);
    }

    public function __get($key) {
        return $this->container->get($key);
    }
}