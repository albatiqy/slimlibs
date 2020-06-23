<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Database;

final class DbProxy { // factory class

    private static $self = null;
    private $instances = [];
    private $settings;

    private function __construct($settings) {
        $this->settings = $settings;
    }

    public static function getInstance($setting) {
        if (self::$self == null) {
            self::$self = new self($setting);
        }
        return self::$self;
    }

    public function __invoke($key = 'default') {
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }
        $setting = $this->settings[$key];
        $dsn = '';
        $conn = null;
        switch ($setting['driver']) {
        case 'mysql':
            $dsn = 'mysql:host=' . $setting['hostname'] . ';dbname=' . $setting['database'];
            break;
        case 'mssql':
            $dsn = 'sqlsrv:Server=' . $setting['hostname'] . ';Database=' . $setting['database'];
            break;
        }
        try {
            $conn = new \PDO($dsn, $setting['username'], $setting['password']);
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        }
        $this->instances[$key] = $conn;
        return $conn;
    }

}