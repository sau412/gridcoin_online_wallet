<?php
// Various DB functions

$db_queries_count = 0;

// Connect to DB
function db_connect() {
        global $db_host,$db_login,$db_password,$db_base;
        $res=mysql_pconnect($db_host,$db_login,$db_password);
        if(!$res) {
                log_write("Cannot connect to database", 3);
                throw new Exception("Cannot connect to database");
        }
        mysql_select_db($db_base);
//      db_query("SET NAMES 'utf8'");
}

// Query
function db_query($query) {
        global $project_log_name;
        global $db_queries_count;
        $db_queries_count++;
        $result=mysql_query($query);
        if($result===FALSE) {
		$message["mysql_error"] = mysql_error();
                $message["query"] = $query;
                $message["debug_backtrace"] = debug_backtrace();
                log_write($message, 3);
		die("Query error");
	}
        return $result;
}

// Query and return results array
function db_query_to_array($query) {
        $result_array=array();
        $result=db_query($query);
        if(mysql_num_rows($result)) {
                while($row=mysql_fetch_assoc($result)) {
                        $result_array[]=$row;
                }
        }
        return $result_array;
}

// Escape string
function db_escape($string) {
        return mysql_real_escape_string($string);
}

// Escape string
function db_escape_ascii($string) {
        $result="";
        for($i=0;$i!=strlen($string);$i++) {
                if(ord($string[$i])>=32 && ord($string[$i])<=127) $result.=$string[$i];
        }
        return mysql_real_escape_string($result);
}

// Query and return value from first row first column
function db_query_to_variable($query) {
        $result=db_query($query);
        if(mysql_num_rows($result)) {
                $row=mysql_fetch_array($result);
                $res=$row[0];
        } else {
                $res="";
        }
        return $res;
}

// For php7
if(!function_exists("mysql_pconnect")) {
        function mysql_pconnect($host,$login,$password) {
                global $mysqli_res;
                $mysqli_res=mysqli_connect($host,$login,$password);
                return $mysqli_res;
        }
        function mysql_select_db($db) {
                global $mysqli_res;
                return mysqli_select_db($mysqli_res,$db);
        }
        function mysql_query($query) {
                global $mysqli_res;
                return mysqli_query($mysqli_res,$query);
        }
        function mysql_fetch_assoc($resource) {
                global $mysqli_res;
                return mysqli_fetch_assoc($resource);
        }
        function mysql_fetch_array($resource) {
                global $mysqli_res;
                return mysqli_fetch_array($resource);
        }
        function mysql_num_rows($resource) {
                global $mysqli_res;
                return mysqli_num_rows($resource);
        }
        function mysql_real_escape_string($str) {
                global $mysqli_res;
                return mysqli_real_escape_string($mysqli_res,$str);
        }
        function mysql_insert_id() {
                global $mysqli_res;
                return mysqli_insert_id($mysqli_res);
        }
        function mysql_error() {
                global $mysqli_res;
                return mysqli_error($mysqli_res);
        }
}
