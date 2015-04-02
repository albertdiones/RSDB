<?php
if (!defined('RSDB')) {

   DEFINED('RSDB_root')
      || DEFINE('RSDB_root',dirname('.'));
   DEFINED('RSDB_replica_root')
      || DEFINE('RSDB_replica_root',dirname(RSDB_root.'/../RSDB_replica'));

   require 'RSDB.class.php';
   require 'RSDBconnection.class.php';
   require 'RSDB_result.class.php';
   require 'functions.php';
   RSDB_var('connect', array());
   RSDB_var('select', array());
   RSDB_var('insert', array());
}
/*

# Debug / Example:

$link=RSDB_connect("open-source","macro");
RSDB_drop("macro",$link);
RSDB_mk_table("macro",$link,$cols);
pingOS($link);

if ($arr=$link->readCol("c"))
echo "<h1>$x</h1><xmp>".print_r($arr,true)."</xmp>";
else
	echo "<h1>read column failed";
*/