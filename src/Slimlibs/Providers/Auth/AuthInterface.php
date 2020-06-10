<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Auth;

interface AuthInterface { // add payload??
    public function login($email, $password);
    public function createRefreshToken($uid);
    public function validateRefreshToken($token);
    public function isUserActive($uid);
    public function isSuperUser($uid);
    public function hasAccess($uid, $method, $callable);
    public function getLabels();
    public function flushRoles($route_maps);
    public function addPayload($payload);
}