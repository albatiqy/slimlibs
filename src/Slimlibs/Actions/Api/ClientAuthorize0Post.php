<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions\Api;

use Albatiqy\Slimlibs\Actions\ResultAction;
use Albatiqy\Slimlibs\Providers\Auth\AuthException;
use Albatiqy\Slimlibs\Providers\Auth\AuthInterface;
use Albatiqy\Slimlibs\Result\Results\Data;
use App\Services\Sys\Users;

final class ClientAuthorize0Post extends ResultAction {

    protected function getResult(array $data, array $args) {
        $authorization = \explode(' ', (string) $this->request->getHeaderLine('Authorization'));
        $bearer = $authorization[1] ?? '';

        $da = Users::getInstance();

        $user = $da->authorizeClient($data['client_id'], $bearer);
        if (\is_object($user)) {
            $auth = $this->container->get(AuthInterface::class);

            $jwt = $this->container->get('jwt');
            $jwt_settings = ($this->container->get('settings'))['jwt'];
            $time = \time();
            $expires = $time + $jwt_settings['max_age'];
            $jwt->setTestTimestamp($time);
            $payload = [
                'uid' => $user->user_id,
                'aud' => $jwt_settings['aud'],
                'scopes' => ['user'],
                'iss' => $jwt_settings['iss']
            ];
            $token = $jwt->encode($auth->addPayload($payload));
            $refreshToken = $auth->createRefreshToken($user->user_id);

            return new Data([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires' => $expires,
                'refresh_token' => $refreshToken
            ]);
        }
        $this->sendNotAuthorized('authorization failed');
    }
}
