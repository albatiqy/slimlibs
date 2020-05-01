<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Cache;

interface UseObjectCacheInterface {

    public function cacheGetId();
    public function cacheRetrieve($key);
    public function cacheGetValues();
}