<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Database;

use Closure;

abstract class MySqlDbService extends DbService {

    private static $strippedCols = [];
    private static $strippedTbl = [];

    protected function initialize() {}
    protected function getRules($opr, $data, $id) {return [];}

    protected static function tableX() {
        if (!isset(self::$strippedTbl[static::ENTITY_NAME])) {
            $dotpos = \strpos(static::TABLE_NAME, ' ');
            self::$strippedTbl[static::ENTITY_NAME] = (false !== $dotpos ? \substr(static::TABLE_NAME, 0, $dotpos) : static::TABLE_NAME);
        }
        return self::$strippedTbl[static::ENTITY_NAME];
    }

    protected static function primaryKeyDef() {
        return self::defCol(static::PRIMARY_KEY);
    }

    protected static function primaryKeyDefX() {
        return self::defColX(static::PRIMARY_KEY);
    }

    protected function getLastIdTbl($db) {
        if (!$db->inTransaction()) {
            throw new \Exception(__METHOD__ . " require active transaction");
        }
        $primaryKey = self::primaryKeyDefX();
        $table = static::tableX();
        $sql = "select $primaryKey pk01 from $table order by created_at desc limit 0,1";
        $stmt = $db->query($sql);
        $row = $stmt->fetch();
        if (\is_object($row)) {
            return $row->pk01;
        }
        return null;
    }

    protected function getLastIdSeq($db) {
        if (!$db->inTransaction()) {
            throw new \Exception(__METHOD__ . " require active transaction");
        }
        $primaryKey = self::primaryKeyDefX();
        $sql = 'select last_val from sys_pk_sequences where pk_name=:pk_name';
        $stmt = $db->prepare($sql);
        $stmt->execute([':pk_name' => $primaryKey]);
        $row = $stmt->fetch();
        if (\is_object($row)) {
            return $row->last_val;
        }
        return null;
    }

    protected static function defColX($attrib) {
        if (self::$strippedCols[static::ENTITY_NAME][$attrib] ?? null == null) {
            $col = static::COLUMN_DEFS[$attrib]['db'];
            $dotpos = \strpos($col, '.');
            self::$strippedCols[static::ENTITY_NAME][$attrib] = (false !== $dotpos ? \substr($col, $dotpos + 1) : $col);
        }
        return self::$strippedCols[static::ENTITY_NAME][$attrib];
    }

    public function getById($id, $attribs = []) {
        $row = $this->findById($id, $attribs);
        if (!$row) {
            $this->throwRecordNotFound();
        }
        return $row;
    }

    public function findById($id, $attribs = [], $extrawhere = '') {
        $db = $this->db();
        $columns = self::selects($attribs);
        $idcol = static::PRIMARY_KEY;
        $primaryKey = self::primaryKeyDef();
        $table = static::TABLE_NAME;
        $extrawhere = $this->whereSoftDelete($extrawhere);
        $sql = "SELECT $columns FROM $table WHERE $primaryKey = :$idcol" . ($extrawhere ? " AND $extrawhere" : '');
        $stmt = $db->prepare($sql);

        //=============================
        //$stmt->debugDumpParams();
        //print_r([':' . $idcol => $id]);
        //=============================

        $stmt->execute([':' . $idcol => $id]);
        $result = $stmt->fetch();
        return $this->alterResult($result, self::RESULT_ROW);
    }

    public function findAll($params, $attribs = [], $whereAll = [], $table = null, $whereResult = [], $whereAllBinds = [], $attribsBinds = [], $whereResultBinds = []) {
        $db = $this->db();
        if ($table==null) {
            $table = static::TABLE_NAME;
        }
        $primaryKey = self::primaryKeyDef();
        $bindings = [];
        //$localWhereResult = [];
        //$localWhereAll = [];
        if (static::SOFT_DELETE) {
            $sdcol = self::defCol('deleted_at');
            //$whereResult[] = "$sdcol IS NULL";
            $whereAll[] = "$sdcol IS NULL";
        }
        $whereAllSql = '';
        $where = self::filter($params, $bindings, $attribs);
        $whereResult = self::flatten($whereResult);
        $whereAll = self::flatten($whereAll);
        $resTotalLengthBinds = [];
        $resFilterLengthBinds = $rowsBinds = $bindings;
        if ($whereResult) {
            $where = $where ? "$where AND $whereResult" : "WHERE $whereResult";
            if (\count($whereResultBinds) > 0) {
                $resFilterLengthBinds += $whereResultBinds;
                $rowsBinds += $whereResultBinds;
            }
        }
        if ($whereAll) {
            $where = $where ? "$where AND $whereAll" : "WHERE $whereAll";
            $whereAllSql = "WHERE $whereAll";
            if (\count($whereAllBinds) > 0) {
                $resFilterLengthBinds += $whereAllBinds;
                $rowsBinds += $whereAllBinds;
                $resTotalLengthBinds += $whereAllBinds;
            }
        }
        if (\count($attribsBinds) > 0) {
            $rowsBinds += $attribsBinds;
        }
        $resFilterLength = self::sql_exec($db, $resFilterLengthBinds, "SELECT COUNT($primaryKey) AS aa1 FROM $table $where");
        $recordsFiltered = (int) $resFilterLength[0]->aa1;
        $lastPage = 1;
        $limit = self::limit($params, $recordsFiltered, $lastPage);
        $order = self::order($params);
        $rows = self::sql_exec($db, $rowsBinds, 'SELECT ' . self::selects($attribs) . " FROM $table $where $order $limit");
        $resTotalLength = self::sql_exec($db, $resTotalLengthBinds, "SELECT COUNT($primaryKey) AS aa1 FROM $table $whereAllSql");
        $recordsTotal = (int) $resTotalLength[0]->aa1;
        $result = ['pageCount' => $lastPage, 'recordsFiltered' => $recordsFiltered, 'recordsTotal' => $recordsTotal, 'data' => $rows];
        return (object) $this->alterResult($result, self::RESULT_PAGE);
    }

    protected function transaction($db, Closure $fn) {
        try {
            $db->beginTransaction();
            //Closure::bind($fn, $this);
            $fn($db);
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            $this->container->logError($e->getMessage());
            return false;
        }
    }

    public function create($data) {
        $db = $this->db();
        $validator = $this->container->get('validator')($this->getRules(self::RULE_CREATE, $data, null), $this->getLabels());
        if ($validator->validate($data)) {
            $data = $validator->getValues();
            $data = $this->processData($data, self::RULE_CREATE);
            $new_id = null;
            try {
                $db->beginTransaction();
                //$table = self::tableX();
                //$db->exec("LOCK TABLES $table WRITE");
                $stmt = $this->dbInsert($data, $db);
                if (static::AUTO_ID) { // auto id =========================
                    $new_id = $db->lastInsertId();
                } else {
                    $primaryKey = self::primaryKeyDefX();
                    if (isset($data[$primaryKey])) {
                        $new_id = $data[$primaryKey];
                    } else {
                        $new_id = $this->getLastIdTbl($db);
                    }
                }
                $this->onTransaction($db, $new_id, self::RULE_CREATE);
                //$db->exec("UNLOCK TABLES"); // is raise exception??
                $db->commit();
            } catch (\Exception $e) {
                $db->rollBack();
                if ($e instanceof \PDOException) {
                    $this->throwPDOException($e);
                }
                throw new DbServiceException($e->getMessage(), DbServiceException::E_ANY);
            }
            return $this->findById($new_id);
        } else {
            $errors = $validator->getErrors();
            $this->throwValidationException($errors);
        }
    }

    public function dbInsert($data, $db = null) {
        if (static::FETCH_ONLY) {
            throw new \Exception();
        }
        if ($db==null) {
            $db = $this->db();
        }
        $cols1 = $cols2 = '';
        $binds = self::bindSql($data, $cols1, $cols2);
        $table = self::tableX();
        $sql = "INSERT INTO $table ($cols1) VALUES ($cols2)";
        $stmt = $db->prepare($sql);

        //=====================================
        //print_r($binds);
        //$stmt->debugDumpParams();
        //=====================================

        $stmt->execute($binds);
        return $stmt;
    }

    public function update($data, $id, $pkUpd = '') { // remove primary key from $data
        $db = $this->db();
        $validator = $this->container->get('validator')($this->getRules(self::RULE_UPDATE, $data, $id));
        if ($validator->validate($data)) {
            $data = $validator->getValues();

            $data[static::PRIMARY_KEY] = $id;

            $data = $this->processData($data, self::RULE_UPDATE);

            try {
                $db->beginTransaction();
                $this->onTransaction($db, $id, self::RULE_UPDATE);

                $stmt = $this->dbUpdate($data, $pkUpd, $db);

                $db->commit();
            } catch (\Exception $e) {
                $db->rollBack();
                if ($e instanceof \PDOException) {
                    $this->throwPDOException($e);
                }
                throw new DbServiceException($e->getMessage(), DbServiceException::E_ANY);
            }
            return $this->getById($id);
        } else {
            $errors = $validator->getErrors();
            $this->throwValidationException($errors);
        }
    }

    public function dbUpdate($data, $pkUpd = '', $db=null) {
        if (static::FETCH_ONLY) {
            throw new \Exception();
        }
        if ($db==null) {
            $db = $this->db();
        }
        $idcol = static::PRIMARY_KEY;
        $cols1 = $cols2 = null;
        $cols3 = '';
        $binds = self::bindSql($data, $cols1, $cols2, $cols3, ($pkUpd == '' ? null : $idcol));
        $pkPrms = null;
        if ('' !== $pkUpd) {
            $pkPrms = "old_$idcol";
            $binds[":$pkPrms"] = $pkUpd;
        } else {
            $pkPrms = $idcol.'1';
        }
        $table = self::tableX();
        $sql = "UPDATE $table SET $cols3 WHERE $idcol = :$pkPrms";
        $stmt = $db->prepare($sql);

        //===============================
        //$stmt->debugDumpParams();
        //===============================

        $stmt->execute($binds); // check result
        return $stmt;
    }

    public function delete($id) {
        $db = $this->db();
        $affected = false;
        try {
            $db->beginTransaction();
            $this->onTransaction($db, $id, self::RULE_DELETE);
            $stmt = $this->dbDelete($id, $db);
            $db->commit();
            $affected = ($stmt->rowCount() > 0);
            if ($affected) {
                return true;
            }
        } catch (\Exception $e) {
            $db->rollBack();
            if ($e instanceof \PDOException) {
                $this->throwPDOException($e);
            }
            throw new DbServiceException($e->getMessage(), DbServiceException::E_ANY);
        }
        if (!$affected) {
            $this->throwRecordNotFound();
        }
    }

    public function dbDelete($id, $db=null) {
        if (static::FETCH_ONLY) {
            throw new \Exception();
        }
        if ($db==null) {
            $db = $this->db();
        }
        $idcol = static::PRIMARY_KEY;
        $table = self::tableX();
        $primaryKey = self::primaryKeyDefX();
        $stmt = null;
        if (static::SOFT_DELETE) {
            $uid = $this->container->getCurrentUserId();
            $sql = "UPDATE $table SET deleted_by=:deleted_by, deleted_at=:deleted_at WHERE $primaryKey = :$idcol";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':deleted_by' => $uid,
                ':deleted_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                ":$idcol" => $id,
            ]);
        } else {
            $sql = "DELETE FROM $table WHERE $primaryKey = :$idcol";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ":$idcol" => $id,
            ]);
        }
        return $stmt;
    }

    protected static function selects($attribs = []) { // implements !!
        $out = [];
        if (\count($attribs) == 0) {
            $attribs = \array_keys(static::COLUMN_DEFS);
        }
        foreach ($attribs as $attrib) {
            if (!(static::COLUMN_DEFS[$attrib]['hidden'] ?? false)) {
                if (!empty(static::COLUMN_DEFS[$attrib]['fn'])) {
                    if (\is_array(static::COLUMN_DEFS[$attrib]['fn'])) {
                        $sfn = static::COLUMN_DEFS[$attrib]['db'];
                        foreach (static::COLUMN_DEFS[$attrib]['fn'] as $fn) {
                            $sfn = "$fn($sfn)";
                        }
                        $out[] = "$sfn AS $attrib";
                    } else {
                        $out[] = \sprintf(static::COLUMN_DEFS[$attrib]['fn'], static::COLUMN_DEFS[$attrib]['db']) . " AS $attrib";
                    }
                } else {
                    $out[] = static::COLUMN_DEFS[$attrib]['db'] . " AS $attrib";
                }
            }
        }
        return \implode(', ', $out);
    }

    protected static function filter($params, &$bindings, $attribs = []) {
        $globalSearch = [];
        $columnSearch = [];
        if (!empty($params['search'])) {
            $str = $params['search'];
            if (\count($attribs) == 0) {
                $attribs = \array_keys(static::COLUMN_DEFS);
            }
            foreach ($attribs as $attrib) {
                if (empty(static::COLUMN_DEFS[$attrib]['fn']) && (static::COLUMN_DEFS[$attrib]['search'] ?? true)) {
                    if (self::TYPE_STRING == static::COLUMN_DEFS[$attrib]['type']) {
                        $binding = self::bind($bindings, "%$str%");
                        $globalSearch[] = static::COLUMN_DEFS[$attrib]['db'] . " LIKE $binding";
                    }
                }
            }
        }
        if (!empty($params['filters'])) {
            foreach ($params['filters'] as $filter) {
                if (!empty(static::COLUMN_DEFS[$filter['column']])) {
                    $col = static::COLUMN_DEFS[$filter['column']];
                    if (empty($col['fn']) && ($col['search'] ?? true)) {
                        switch ($filter['type']) {
                        case 'like':
                            if (self::TYPE_STRING == $col['type']) {
                                $binding = self::bind($bindings, "%{$filter['value']}%");
                                $columnSearch[] = "{$col['db']} LIKE $binding";
                            }
                            break;
                        case '=':
                        case '!=':
                            $binding = self::bind($bindings, $filter['value']);
                            $columnSearch[] = "{$col['db']} = $binding";
                            break;
                        case '<':
                        case '<=':
                        case '>':
                        case '>=':
                            if (self::TYPE_NUMERIC == $col['type'] && \is_numeric($filter['value'])) {
                                $binding = self::bind($bindings, "%{$filter['value']}%");
                                $columnSearch[] = "{$col['db']} LIKE $binding";
                            }
                            break;
                            // in type??
                        }
                    }
                }
            }
        }
        $where = '';
        if (\count($globalSearch)) {
            $where = '(' . \implode(' OR ', $globalSearch) . ')';
        }
        if (\count($columnSearch)) {
            $where = '' === $where ? \implode(' AND ', $columnSearch) : "$where AND " . \implode(' AND ', $columnSearch);
        }
        if ('' !== $where) {
            $where = "WHERE $where";
        }
        return $where;
    }

    protected static function limit($params, $recordsFiltered, &$lastPage) {
        $limit = '';
        $lastPage = 0;
        if (isset($params['length'])) {
            $size = 10;
            if (!empty($params['length'])) {
                $size = \intval($params['length']);
            }
            if ($size < 1) {
                $size = 10;
            }
            $lastPage = \ceil($recordsFiltered / $size);
            $page = 1;
            if ($lastPage > 0) {
                if (!empty($params['page'])) {
                    $page = \intval($params['page']);
                }
                if ($page < 1) {
                    $page = 1;
                }
                if ($page > $lastPage) {
                    $page = $lastPage;
                }
            }
            $page -= 1;
            $offset = $page * $size;
            $limit = "LIMIT $offset, $size";
        } else {
            if ($recordsFiltered > 0) {
                $lastPage = 1;
            }
        }
        return $limit;
    }

    protected static function order($params) {
        $order = '';
        if (!empty($params['orders'])) {
            $orderBy = [];
            foreach ($params['orders'] as $k => $sort) {
                if (!empty(static::COLUMN_DEFS[$sort['column']])) {
                    $dir = ('asc' === $sort['dir'] ? 'ASC' : 'DESC');
                    if (empty(static::COLUMN_DEFS[$sort['column']]['fn'])) {
                        $orderBy[] = static::COLUMN_DEFS[$sort['column']]['db'] . ' ' . $dir;
                    }
                }
            }
            if (\count($orderBy)) {
                $order = 'ORDER BY ' . \implode(', ', $orderBy);
            }
        }
        return $order;
    }

    private static function sql_exec($db, $bindings, $sql = null) {
        if (null === $sql) {
            $sql = $bindings;
        }
        $stmt = $db->prepare($sql);
        if (\is_array($bindings)) {
            foreach ($bindings as $key => $binding) {
                $stmt->bindValue($key, $binding);
            }

        /*
        for ($i = 0, $ien = \count($bindings); $i < $ien; $i++) {
        $binding = $bindings[$i];
        $stmt->bindValue($binding['key'], $binding['val']);
        }
         */
        }

        //=====================================
        //print_r($bindings);
        //$stmt->debugDumpParams();
        //=====================================

        $stmt->execute();
        return $stmt->fetchAll();
    }

    protected static function bind(&$a, $val) {
        $key = ':binding_' . \count($a);
        /*
        $a[] = ['key' => $key, 'val' => $val];
         */
        $a[$key] = $val;
        return $key;
    }

    private static function flatten($a, $join = ' AND ') {
        if (!$a) {
            return '';
        } else if ($a && \is_array($a)) {
            return \implode($join, $a);
        }
        return $a;
    }

    protected static function bindSql($data, &$cols1 = null, &$cols2 = null, &$cols3 = null, $col3strip=null) { //add strip some column
        $binds = [];
        if ($cols1 !== null || $cols3 !== null) {
            $keys = \array_keys($data);
            if (null !== $cols2) {
                $cols1 = \implode(', ', \array_map(function ($val) {return self::defColX($val);}, $keys));
                $cols2 = \implode(', ', \array_map(function ($val) {return ":$val";}, $keys));
                foreach ($keys as $key) {
                    $binds[":$key"] = $data[$key];
                }
            }
            if (null !== $cols3) {
                if ($col3strip!=null) {
                    $kremove = \array_search($col3strip, $keys);
                    if ($kremove!==false) {
                        unset($keys[$kremove]);
                    }
                }
                foreach ($keys as $key) {
                    $binds[":{$key}1"] = $data[$key];
                }
                $cols3 = \implode(', ', \array_map(function ($val) {return "$val = :{$val}1";}, $keys));
            }
        }
        return $binds;
    }

    protected function telegramBroadcast($db, $text) {
        try {
            $sql = "INSERT INTO sys_telegram_chqueue (`type`,`text`,`state`) VALUES (:type,:text,0)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':type' => 0,
                ':text' => $text
            ]);
        } catch (\Exception $e) {
        }
    }

    protected function whereSoftDelete($where = '') {
        if (static::SOFT_DELETE) {
            $sdcol = self::defCol('deleted_at');
            if ($where) {
                $where .= " AND ($sdcol IS NULL)";
            } else {
                $where = "$sdcol IS NULL";
            }
        }
        return $where;
    }
}