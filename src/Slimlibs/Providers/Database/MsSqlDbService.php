<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Database;

use Closure;

abstract class MsSqlDbService extends DbService {

    protected const DB = 'default';

    private function limit($data, $columns, $recordsFiltered, &$lastPage) {
        $limit = '';
        $lastPage = 0;
        if (isset($data['size'])) {
            $size = 10;
            if (!empty($data['size'])) {
                $size = \intval($data['size']);
            }
            if ($size < 1) {
                $size = 10;
            }

            $lastPage = ceil($recordsFiltered / $size);

            $page = 1;
            if ($lastPage > 0) {
                if (!empty($data['page'])) {
                    $page = \intval($data['page']);
                }
                if ($page < 1) {
                    $page = 1;
                }
                if ($page > $lastPage) {
                    $page = $lastPage;
                }
            }

            $page -= 1;
            $from = $page * $size;
            $from += 1;
            $to = $from + $size - 1;

            $limit = "WHERE ROW_NUM BETWEEN $from AND $to";
        } else {
            if ($recordsFiltered > 0) {
                $lastPage = 1;
            }
        }

        return $limit;
    }

    private function order($data, $columns) {
        $order = '';
        if (!empty($data['sorters'])) {
            $orderBy = [];
            foreach ($data['sorters'] as $k => $sort) {
                $dir = ('asc' === $sort['dir'] ? 'ASC' : 'DESC');
                if (empty($columns[$sort['field']]['fn'])) {
                    $orderBy[] = $columns[$sort['field']]['db'].' '.$dir;
                }
            }
            if (count($orderBy)) {
                $order = 'ORDER BY '.implode(', ', $orderBy);
            }
        }
        return $order;
    }

    private function filter($data, $columns, &$bindings) {
        $globalSearch = [];
        $columnSearch = [];
        if (!empty($data['search'])) {
            $str = $data['search'];
            foreach ($columns as $field => $col) {
                if (empty($col['fn']) && ($col['search'] ?? true)) {
                    if (self::TYPE_STRING == $col['type']) {
                        $binding = $this->bind($bindings, '%'.$str.'%');
                        $globalSearch[] = $col['db'].' LIKE '.$binding;
                    }
                }
            }
        }
        if (!empty($data['filters'])) {
            foreach ($data['filters'] as $filter) {
                if (!empty($columns[$filter['field']])) {
                    $col = $columns[$filter['field']];
                    if (empty($col['fn']) && ($col['search'] ?? true)) {
                        switch ($filter['type']) {
                            case 'like':
                                if (self::TYPE_STRING == $col['type']) {
                                    $binding = $this->bind($bindings, '%'.$filter['value'].'%');
                                    $columnSearch[] = $col['db'].' LIKE '.$binding;
                                }
                                break;
                            case '=':
                            case '!=':
                                $binding = $this->bind($bindings, $filter['value']);
                                $columnSearch[] = $col['db'].' = '.$binding;
                                break;
                            case '<':
                            case '<=':
                            case '>':
                            case '>=':
                                if (self::TYPE_NUMERIC == $col['type'] && is_numeric($filter['value'])) {
                                    $binding = $this->bind($bindings, '%'.$filter['value'].'%');
                                    $columnSearch[] = $col['db'].' LIKE '.$binding;
                                }
                                break;
                                // in type??
                        }
                    }
                }
            }
        }
        $where = '';
        if (count($globalSearch)) {
            $where = '('.implode(' OR ', $globalSearch).')';
        }
        if (count($columnSearch)) {
            $where = '' === $where ? implode(' AND ', $columnSearch) : $where.' AND '.implode(' AND ', $columnSearch);
        }
        if ('' !== $where) {
            $where = 'WHERE '.$where;
        }
        return $where;
    }

    /**
     * Perform the SQL queries needed for an server-side processing requested,
     * utilising the helper functions of this class, limit(), order() and
     * filter() among others. The returned array is ready to be encoded as JSON
     * in response to an SSP request, or can be modified if needed before
     * sending back to the client.
     *
     *  @param  array $data Data sent to server by DataTables
     *  @param  array|PDO $conn PDO connection resource or connection parameters array
     *  @param  string $table SQL table to query
     *  @param  string $primaryKey Primary key of the table
     *  @param  array $columns Column information array
     *  @return array          Server-side processing response array
     */
    public function simple($db, $data, $table, $primaryKey, $columns) {
        $bindings = [];

        $where = $this->filter($data, $columns, $bindings);

        $resFilterLength = $this->sql_exec($db, $bindings, "SELECT COUNT({$primaryKey}) AS a FROM $table $where");
        $recordsFiltered = (int) $resFilterLength[0]->a;
        $lastPage = 1;

        $limit = $this->limit($data, $columns, $recordsFiltered, $lastPage);
        $order = $this->order($data, $columns);

        $sql = 'SELECT * FROM (SELECT ROW_NUMBER() OVER ('.($order ? $order : "ORDER BY {$primaryKey}").') AS ROW_NUM,'.$this->selects($columns)." FROM $table $where) x $limit";

        $rows = $this->sql_exec($db, $bindings, $sql);

        $resTotalLength = $this->sql_exec($db, "SELECT COUNT({$primaryKey}) AS a FROM $table");
        $recordsTotal = (int) $resTotalLength[0]->a;

        return ['last_page' => $lastPage, 'x_records_filtered' => $recordsFiltered, 'x_records_total' => $recordsTotal, 'data' => $rows]; //current_page
    }

    /**
     * The difference between this method and the `simple` one, is that you can
     * apply additional `where` conditions to the SQL queries. These can be in
     * one of two forms:
     *
     * * 'Result condition' - This is applied to the result set, but not the
     *   overall paging information query - i.e. it will not effect the number
     *   of records that a user sees they can have access to. This should be
     *   used when you want apply a filtering condition that the user has sent.
     * * 'All condition' - This is applied to all queries that are made and
     *   reduces the number of records that the user can access. This should be
     *   used in conditions where you don't want the user to ever have access to
     *   particular records (for example, restricting by a login id).
     *
     *  @param  array $data Data sent to server by DataTables
     *  @param  array|PDO $conn PDO connection resource or connection parameters array
     *  @param  string $table SQL table to query
     *  @param  string $primaryKey Primary key of the table
     *  @param  array $columns Column information array
     *  @param  string $whereResult WHERE condition to apply to the result set
     *  @param  string $whereAll WHERE condition to apply to all queries
     *  @return array          Server-side processing response array
     */
    public function complex($db, $data, $table, $primaryKey, $columns, $whereResult = null, $whereAll = null) {
        $bindings = [];
        $localWhereResult = [];
        $localWhereAll = [];
        $whereAllSql = '';

        $where = $this->filter($data, $columns, $bindings);

        $whereResult = $this->_flatten($whereResult);
        $whereAll = $this->_flatten($whereAll);
        if ($whereResult) {
            $where = $where ? $where.' AND '.$whereResult : 'WHERE '.$whereResult;
        }
        if ($whereAll) {
            $where = $where ? $where.' AND '.$whereAll : 'WHERE '.$whereAll;
            $whereAllSql = 'WHERE '.$whereAll;
        }

        $resFilterLength = $this->sql_exec($db, $bindings, "SELECT COUNT({$primaryKey}) AS a FROM $table $where");

        $recordsFiltered = (int) $resFilterLength[0]->a;
        $lastPage = 1;

        $limit = $this->limit($data, $columns, $recordsFiltered, $lastPage);
        $order = $this->order($data, $columns);

        $sql = 'SELECT * FROM (SELECT ROW_NUMBER() OVER ('.($order ? $order : "ORDER BY {$primaryKey}").') AS ROW_NUM,'.$this->selects($columns)." FROM $table $where) x $limit";
        //xC('logger')->info($sql);
        $rows = $this->sql_exec($db, $bindings, $sql);
        $resTotalLength = $this->sql_exec($db, $bindings, "SELECT COUNT({$primaryKey}) AS a FROM $table ".$whereAllSql);
        $recordsTotal = (int) $resTotalLength[0]->a;

        return ['last_page' => $lastPage, 'x_records_filtered' => $recordsFiltered, 'x_records_total' => $recordsTotal, 'data' => $rows];
    }

    private function sql_exec($db, $bindings, $sql = null) {
        if (null === $sql) {
            $sql = $bindings;
        }
        $stmt = $db->prepare($sql, [\PDO::ATTR_EMULATE_PREPARES => true]);
        if (is_array($bindings)) {
            for ($i = 0, $ien = count($bindings); $i < $ien; $i++) {
                $binding = $bindings[$i];
                $stmt->bindParam($binding['key'], $binding['val']);
            }
        }
        try {
            //self::log($stmt);
            $stmt->execute();
        } catch (\PDOException $e) {
            $this->fatal('An SQL error occurred: '.$e->getMessage());
        }
        return $stmt->fetchAll();
    }

    private function fatal($msg) {
        throw new \Exception($msg);
    }

    private function bind(&$a, $val) {
        $key = ':binding_'.count($a);
        $a[] = ['key' => $key, 'val' => $val];
        return $key;
    }

    private function selects($columns) {
        $out = [];
        foreach ($columns as $field => $col) {
            if (!($col['hidden'] ?? false)) {
                if (!empty($col['fn'])) {
                    if (is_array($col['fn'])) {
                        $sfn = $col['db'];
                        foreach ($col['fn'] as $fn) {
                            $sfn = "$fn($sfn)";
                        }
                        $out[] = $sfn.' AS '.$field;
                    } else {
                        $out[] = sprintf($col['fn'], $col['db']).' AS '.$field;
                    }
                } else {
                    $out[] = $col['db'].' AS '.$field;
                }
            }
        }
        return implode(', ', $out);
    }

    private function _flatten($a, $join = ' AND ') {
        if (!$a) {
            return '';
        } else if ($a && is_array($a)) {
            return implode($join, $a);
        }
        return $a;
    }
}