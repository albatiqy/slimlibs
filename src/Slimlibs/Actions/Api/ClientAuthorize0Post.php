<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions\Api;

use Albatiqy\Slimlibs\Actions\ResultAction;
use Albatiqy\Slimlibs\Providers\Auth\AuthException;
use Albatiqy\Slimlibs\Providers\Auth\AuthInterface;
use Albatiqy\Slimlibs\Result\Results\Data;
use Albatiqy\Slimlibs\Result\Exception\ValidationException;

final class ClientAuthorize0Post extends ResultAction {

    protected function getResult(array $data, array $args) {
        $auth = $this->container->get(AuthInterface::class);
        $user = null;
        try {
            $validator = $this->container->get('validator')([
                'client_id' => ['required'],
                'key' => ['required']
            ]);
            if (!$validator->validate($data)) {
                $errors = $validator->getErrors();
                $this->sendValidationError($errors);
            }
            $user = $auth->clientAuthorize($data['client_id'], $data['key']); //behalf user?
            if ($user==null) {
                throw new \Exception();
            }
        } catch (AuthException $ae) {
            $this->sendNotAuthorized($ae->getMessage());
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }
            $this->sendBadRequestError($e->getMessage());
        }
        $jwt = $this->jwt;
        $jwt_settings = ($this->settings)['jwt'];
        $time = \time();
        $expires = $time + $jwt_settings['max_age'];
        $jwt->setTestTimestamp($time);
        $payload = [
            'uid' => $user->user_id,
            'aud' => $jwt_settings['aud'],
            'scopes' => ['user'],
            'iss' => $jwt_settings['iss']
        ];
        $token = $jwt->encode($auth->jwtAppendPayload($payload));
        $refreshToken = $auth->createRefreshToken($user->user_id);

        return new Data([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires' => $expires,
            'refresh_token' => $refreshToken
        ]);
    }
}
