<?php
namespace Albatiqy\Slimlibs\Providers\Renderer\Extension;

use Albatiqy\Slimlibs\Providers\Renderer\Engine;

/**
 * A common interface for extensions.
 */
interface ExtensionInterface
{
    public function register(Engine $engine);
}
