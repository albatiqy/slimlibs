<?php declare (strict_types = 1);

return [
    'getBaseUrl' => function($container, $https=false){
        $request = $container->get('request');
        $uri = $request->getUri();
        return  ($https?'https':$uri->getScheme()) . '://' . $uri->getHost().\BASE_PATH;
    },
    'getCurrentUserId' => function($container){
        $payload = $container->get('jwt_payload');
        return $payload['uid'] ?? null;
    },
    'logError' => function($container, $message){
        $logger = $container->get('monolog');
        $logger->error($message);
    },
    'tmpCapture' => function($container, $var){
        ob_start();
        var_dump($var);
        $cap = ob_get_contents();
        ob_end_clean();
        file_put_contents(APP_DIR . '/var/tmp/'.sprintf('%s.%0.8s', bin2hex(random_bytes(8)), 'tmpCap'), $cap);
    }
];