<?php
/**
MSSQL database connection wrapper

EXAMPLE:
 ** init **
$_DB = array(
    'default' => array(
        'host' => '127.0.0.1',
        'port' => '5432',
        'user' => 'u',
        'password' => '1',
        'codepage' => 'UTF8',
        'database' => 'b',
        'link' => null
    )
);

$DB = new DB($_DB['default']);
$DB -> connect();

 ** get single row result **
 $DB -> item('SELECT * FROM my_table');

 ** get all result **
 $DB -> table('SELECT * FROM my_table');
*/
class MssqlDB{
    var $db_conn;
    var $user;
    var $db_name;
    var $host;
    var $password;
    var $port;
    var $codepage = 'UTF8';
    var $last_query;
    var $log = null;
    var $errorLog = null;
    var $root_fieldname = 'root';
    var $parentId_fieldname = 'parent_id';
    var $id_fieldname = 'id';
    function __construct(&$DB){
        $this -> host = $DB['host'];
        $this -> user = $DB['user'];
        $this -> password = $DB['password'];
        $this -> port = $DB['port'];
        $this -> db_name = $DB['database'];
        //$this -> codepage = $DB['codepage'];
        // create log file instance if possible
        if(class_exists('logWriter')){
            global $_SYSTEM_CONF;
            $log_name = isset($_SYSTEM_CONF['sql_log_name'])?$_SYSTEM_CONF['sql_log_name']:'sql'.date('YmdH').'.log';
            $log_dir = isset($_SYSTEM_CONF['log_dir'])?$_SYSTEM_CONF['log_dir']:'logs/';
            $this -> log = new logWriter($log_name, $log_dir);
        }
        global $errorLog;
        if($errorLog instanceof logWriter){
            $this -> errorLog = $errorLog;
        }
    }
    function connect(){
        $conn = mssql_connect($this -> host.':'.$this -> port, $this -> user, $this -> password);
        if($conn === false){
            throw new Exception('Can not connect');
        }
        $q = mssql_select_db($this -> db_name, $conn);
        if($q === false){
            throw new Exception('Can not select database');
        }
        $this -> db_conn = $conn;
        //$this -> query('SET NAMES \''.$this -> codepage.'\'');
    }
    function close(){
        mssql_close($this -> db_conn);
    }
    function query($str){
        $result = mssql_query($str, $this -> db_conn);
        $this -> last_query = $result;
        if($result === false){
            $error = $str.' '.mssql_get_last_message();
            if($this -> log !== null){
                $this -> log -> put(array('query' => $str, 'error' => $error));
            }
            if($this -> errorLog !== null){
                $this -> errorLog -> put($error);
            }
            throw new Exception('Database error: '. $error);
        }
        return $this -> last_query;
    }
    function fetch_array($query_id){
        $result = mssql_fetch_assoc($query_id);
        /*try{
            $result = mssql_fetch_assoc($query_id);
        }catch(Exception $e){
            $error = $e -> getMessage();
            $error .= ', '.mssql_get_last_message();
            throw new Exception('Database error: '.$error);
        }*/
        return $result;
    }
    function item($str, $key = null){
        $qid = $this -> query($str);
        if($qid === true){
            return null;
        }
        $result = $this -> fetch_array($qid);
        if($key !== null){
            $result = $result[$key];
        }
        return $result;
    }
    function table($str, $key_index_name = null, $value_index_name = null){
        $qid = $this -> query($str);
        $i = 0;
        $result = array();
        if($qid === true){
            return array();
        }
        while($t = $this -> fetch_array($qid)){
            $key = $key_index_name === null?$i:$t[$key_index_name];
            $value = $value_index_name === null?$t:$t[$value_index_name];
            $result[$key] = $value;
            $i++;
        }
        return $result;
    }
    function escape($str){
        if ( !isset($str) or empty($str) ) return '';
        if ( is_numeric($str) ) return $str;

        $non_displayables = array(
            '/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15
            '/%1[0-9a-f]/',             // url encoded 16-31
            '/[\x00-\x08]/',            // 00-08
            '/\x0b/',                   // 11
            '/\x0c/',                   // 12
            '/[\x0e-\x1f]/'             // 14-31
        );
        foreach ( $non_displayables as $regex )
            $str = preg_replace( $regex, '', $str );
        $str = str_replace("'", "''", $str );
        return $str;

    }
    function affected_rows(){
        return mssql_rows_affected($this -> db_conn);
    }
    function last_insert_id(){
        $qid = $this -> query('SELECT SCOPE_IDENTITY()');
        $result = mssql_fetch_array($qid, MSSQL_NUM);
        return $result[0];
    }
    function tree($s){
        $result = array();
        $qid = $this -> query($s);
        $root_fieldname = $this -> root_fieldname;
        $parentId_fieldname = $this -> parentId_fieldname;
        $id_fieldname = $this -> id_fieldname;
        while($t = $this -> fetch_array($qid)){

            //$link_key = (isset($t[$root_fieldname]) && $t[$root_fieldname] == 1)?0:$t[$id_fieldname];
            $link_key = $t[$id_fieldname];
            if(!isset($result[$link_key])){
                // create node
                $result[$link_key] = $t;
            }else{
                // copy properties to existed node
                foreach($t as $key => $value){
                    $result[$link_key][$key] = $value;
                }
            }
            if(isset($t[$root_fieldname]) && $t[$root_fieldname] == 1){
                if(!isset($result[$t[$id_fieldname]])){
                    $result[$t[$id_fieldname]] = $t;
                }else{
                    foreach($t as $key => $value){
                        $result[$t[$id_fieldname]][$key] = $value;
                    }
                }
            }
            if(isset($t[$root_fieldname]) && $t[$root_fieldname] == 1){
                $result[0] = &$result[$t[$id_fieldname]];
            }
            if($t[$parentId_fieldname] === null){
                continue;
            }
            if(isset($t[$root_fieldname]) && $t[$root_fieldname] == 1){
                continue;
            }
            if(!isset($result[$t[$parentId_fieldname]])){
                $result[$t[$parentId_fieldname]] = array('childs' => array($t[$id_fieldname] => &$result[$t[$id_fieldname]]));
            }else{
                $result[$t[$parentId_fieldname]]['childs'][$t[$id_fieldname]] = &$result[$t[$id_fieldname]];
            }
        }
        return $result;
    }
}
