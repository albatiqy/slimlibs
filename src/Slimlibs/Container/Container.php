<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Container;

use Closure;
use Exception;
use Psr\Container\ContainerInterface;
use ReflectionClass;

class Container implements ContainerInterface {

    private static $instance = null;
    private $registers = [];
    private $providers = [];
    private $extends = [];
    private $aliases = [];
    private $functions = [];

    private function __construct(array $default) {
        $this->registers += $default;
    }

    public function get($key) {
        if (isset($this->providers[$key])) {
            return $this->providers[$key];
        }
        $item = $this->resolve($key);
        if ($item instanceof ReflectionClass) {
            if ($item->isInstantiable()) {
                $this->providers[$key] = $this->instance($item);
                return $this->providers[$key];
            }
        } else {
            return $item;
        }
        throw new NotFoundException();
    }

    public function mapAlias($class, $key) {
        $this->aliases[$key] = $class;
        return $this;
    }

    public function defineExtends($parent, $callable) {
        $this->extends[$parent] = $callable;
        return $this;
    }

    public function setFunction($name, Closure $callable) {
        $this->functions[$name] = $callable;
    }

    public function registerFunctions($functions) {
        $this->functions = $functions;
    }

    public function __call($name, $arguments) {
        if (isset($this->functions[$name])) {
           return $this->functions[$name]($this, ...$arguments);
        }
        throw new NotFoundException();
    }

    public function __get($key) {
        return $this->get($key);
    }

    public function set(string $key, $value) {
        $this->providers[$key] = $value;
        return $this;
    }

    public function has($key) {
        if (isset($this->providers[$key])) {
            return true;
        }
        $item = null;
        try {
            $item = $this->resolve($key);
        } catch (NotFoundException $e) {
            return false;
        }
        if ($item instanceof ReflectionClass) {
            return $item->isInstantiable();
        }
        return false;
    }

    private function resolve($key) {
        try {
            if (isset($this->aliases[$key])) {
                if (isset($this->providers[$this->aliases[$key]])) {
                    $this->providers[$key] = $this->providers[$this->aliases[$key]];
                    return $this->providers[$key];
                } else {
                    $key = $this->aliases[$key];
                }
            }
            if (isset($this->registers[$key])) {
                if ($this->registers[$key] instanceof Closure) {
                    $name = $this->registers[$key];
                    $this->providers[$key] = $name($this);
                    unset($this->registers[$key]);
                    return $this->providers[$key];
                }
                if (\class_exists($this->registers[$key])) {
                    //check interface here
                    $refl = new ReflectionClass($this->registers[$key]);
                    unset($this->registers[$key]);
                    return $refl;
                }
            }
            if (\class_exists($key)) {
                $parent = \get_parent_class($key);
                if (isset($this->extends[$parent])) {
                    if ($this->extends[$parent] instanceof Closure) {
                        $this->providers[$key] = $this->extends[$parent]($key);
                        //check parent here
                        return $this->providers[$key];
                    }
                }
                return new ReflectionClass($key);
            }
            throw new Exception();
        } catch (Exception $e) {
            throw new NotFoundException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function instance(ReflectionClass $item) {
        $constructor = $item->getConstructor();
        if (\is_null($constructor) || $constructor->getNumberOfRequiredParameters() == 0) {
            return $item->newInstance();
        }
        $params = [];
        foreach ($constructor->getParameters() as $param) {
            if ($type = $param->getType()) {
                $params[] = $this->get($type->getName());
            }
        }
        return $item->newInstanceArgs($params);
    }

    public static function getInstance(array $default = []) {
        if (self::$instance == null) {
            self::$instance = new static($default);
            self::$instance->set(ContainerInterface::class, self::$instance);
        }
        return self::$instance;
    }
}