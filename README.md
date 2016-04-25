# mssqlDB.class.php
ms sql wrapper

# Methods
```php
<?php
connect(); \\ create database connection

close(); \\ close database connection

query($query_string); \\ send query request 

fetch_array($query_id); \\ get query result row

item($query_string, $key = null); \\ return first row of query result or return property value of first row, if $key argument is not null

table($query_string, $key_index_name = null, $value_index_name = null); \\ return array of query result rows. If key_index_name is not null, then each index of each array item will assigned to value by $key_index_name from current row, if $value_index_name is not null, then each item will contain property value from current_row

escape($string); \\ escape string value 

affected_rows(); \\ return number of affected rows in last query

last_insert_id(); \\ return identity value of last insert query

tree($query_string); \\ return nested array(tree), built from the query (per one request). $result[0] - will contain rooted element, each tree node has 'childs' property (contain Array of childs), each tree branch is available by address $result[$node_id].
?>
```

# mssqlDB.class.php
ms sql wrapper

# Methods
```php
<?php
connect(); \\ create database connection

close(); \\ close database connection

query($query_string); \\ send query request 

fetch_array($query_id); \\ get query result row

item($query_string, $key = null); \\ return first row of query result or return property value of first row, if $key argument is not null

table($query_string, $key_index_name = null, $value_index_name = null); \\ return array of query result rows. If key_index_name is not null, then each index of each array item will assigned to value by $key_index_name from current row, if $value_index_name is not null, then each item will contain property value from current_row

escape($string); \\ escape string value 

affected_rows(); \\ return number of affected rows in last query

last_insert_id(); \\ return identity value of last insert query

tree($query_string); \\ return nested array(tree), built from the query (per one request). $result[0] - will contain rooted element, each tree node has 'childs' property (contain Array of childs), each tree branch is available by address $result[$node_id].
?>
```

# Example
```php
<?php
$db_conf = array(
  'host' => '127.0.0.1',
  'user' => 'root',
  'password' => 'root',
  'port' => '3456',
  'db_name' => 'test'
);
$db = new MssqlDB($db_conf);
$i = $db -> item('SELECT * FROM table1');
?>
```
