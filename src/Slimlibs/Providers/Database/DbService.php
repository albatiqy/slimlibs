<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Database;

use Albatiqy\Slimlibs\Container\Container;
use Albatiqy\Slimlibs\Providers\Validation\ValidationException;
use Albatiqy\Slimlibs\Support\Util\Labels;

abstract class DbService {

    const TYPE_STRING = 1;
    const TYPE_NUMERIC = 2;

    protected const RULE_CREATE = 1;
    protected const RULE_UPDATE = 2;
    protected const RULE_DELETE = 3;

    protected const RESULT_ROW = 1;
    protected const RESULT_PAGE = 2;

    protected const TABLE_NAME = null;
    protected const PRIMARY_KEY = null;
    protected const ENTITY_NAME = null;
    protected const COLUMN_DEFS = [];
    protected const AUTO_ID = true;

    protected const DB = 'default';

    private static $instances = [];
    private static $dbs = [];

    private $labels = null;

    protected $container = null;
    protected $ignoreAttribs = [];

    abstract protected function initialize();

    private function __construct($container) {
        $this->container = $container;
        $this->initialize();
    }

    public static function getInstance() {
        $class = \get_called_class();
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static(Container::getInstance());
        }
        return self::$instances[$class];
    }

    public function getLabels() {
        if ($this->labels == null) {
            $this->labels = Labels::getInstance(static::ENTITY_NAME);
        }
        return $this->labels;
    }

    protected function db($key = 'default') {
        if (!isset(self::$dbs[$key])) {
            self::$dbs[$key] = $this->container->get('db')($key);
        }
        return self::$dbs[$key];
    }

    protected static function defCol($attrib) {
        return static::COLUMN_DEFS[$attrib]['db'];
    }

    protected function processData($data, $opr) {
        foreach ($this->ignoreAttribs as $field) {
            unset($data[$field]);
        }
        $attribs = \array_keys($data);
        foreach ($attribs as $attrib) {
            if (static::COLUMN_DEFS[$attrib]['null']??false===true) {
                if (\trim($data[$attrib])=='') {
                    $data[$attrib] = null;
                }
            }
        }
        /*
        foreach (static::COLUMN_DEFS as $attrib => $col) {
            if ($col['null']??false===true) {
                if (\trim($data[$attrib])=='') {
                    $data[$attrib] = null;
                }
            }
        }
        */
        return $data;
    }

    protected function onTransaction($db, $id, $opr) {
    }

    protected function alterResult($result, $rstype) {
        return $result;
    }

    protected function throwValidationException(array $errors = []) {
        throw new ValidationException($errors);
    }

    protected function throwRecordNotFound() {
        throw new DbServiceException("record not found", DbServiceException::E_NO_RESULT);
    }

    protected function throwPDOException($exception) {
        throw new DbServiceException($exception->getMessage(), DbServiceException::E_PDO, $exception);
    }
}