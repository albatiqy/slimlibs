<?php declare (strict_types = 1);

return [
    'getBaseUrl' => function($container, $https=false){
        $request = $container->get('request');
        $uri = $request->getUri();
        return  ($https?'https':$uri->getScheme()) . '://' . $uri->getHost().\BASE_PATH;
    },
    'getCurrentUserId' => function($container){
        $payload = $container->get('payload');
        return $payload['uid'] ?? null;
    }
];