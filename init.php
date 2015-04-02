<?php
if (!defined('RSDB')) {
   require 'RSDB.class.php';
   require 'RSDBconnection.class.php';
   require 'RSDB_result.class.php';
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