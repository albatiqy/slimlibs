<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Auth;

interface AuthInterface { // add payload??
    public function login($email, $password);
    public function createRefreshToken($uid);
    public function isUserActive($uid);
    public function validateRefreshToken($token);
    public function getLabels();
    public function addPayload($payload);
}