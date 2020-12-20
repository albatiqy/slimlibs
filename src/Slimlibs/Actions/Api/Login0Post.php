<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions\Api;

use Albatiqy\Slimlibs\Actions\ResultAction;
use Albatiqy\Slimlibs\Providers\Auth\AuthException;
use Albatiqy\Slimlibs\Providers\Auth\AuthInterface;
use Albatiqy\Slimlibs\Result\Results\Data;
use Albatiqy\Slimlibs\Providers\Libs\TelegramBot;

final class Login0Post extends ResultAction {

    protected function getResult(array $data, array $args) { //encrypt???

        $auth = $this->container->get(AuthInterface::class);

        $user = null;

        try {
            $user = $auth->login($data['email'], $data['password']);
        } catch (AuthException $ae) {
            $this->sendNotAuthorized($ae->getMessage());
        } catch (\Exception $e) {
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

        try {
            $settings = $this->container->get('settings');
            $telegram = $this->container->get(TelegramBot::class);
            if (!empty($settings['service_accounts'])) {
                if (isset($settings['service_accounts']['default'])) {
                    $telegram->messageUserText('albatiqy', 'ℹ <b>info</b>: <i>'.$user->name.'</i> <b>login</b> '.$settings['service_accounts']['default']['redirect_uri']);
                    //$telegram->messageChannelText($user->name.' login ke website');
                }
            } else {
                $telegram->messageUserText('albatiqy', 'ℹ <b>info</b>: <i>'.$user->name.'</i> <b>login</b>');
            }
        } catch (\Exception $e) {
        }

        return new Data([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires' => $expires,
            'refresh_token' => $refreshToken
        ]);
    }
}
