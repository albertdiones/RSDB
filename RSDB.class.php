<?php
/**
 * Created by PhpStorm.
 * @author albert
 * Date: 4/2/15
 * Time: 8:02 PM
 */

CLASS RSDB {
   static $version="1.0";
   static $author="Albert D. Diones";
   static $dateOfCreation="March 01, 2009";
   protected $ERRORS;
   protected $log;
   const root = RSDB_root;
   public $root = RSDB_root;
   const replica_root = RSDB_replica_root;
   public $replica_root = RSDB_replica_root;
   /*DO NOT CHANGE THE ENDER IF DB BEGAN ALREADY!!!!!!!!!!!!!!!
   you can only change this when you are just starting the DB
   */
   public $ender=");//<\"KATAPUSAN\">\n?>";
   public function __set($name,$value) {
      if ($name=='errorMessage')
         $this->ERRORS.="RSDB Error:$value<br />";
      if ($name=='log')
         $this->log.="RSDB Log: $value<br />";
   }
   public function __get($name) {
      if ($name=='errorMessage')
         return $this->ERRORS;
      if ($name=='log')
         return $this->log;
   }
   public function __construct($properties) {
      if (!file_exists($this->root))
         $this->errorMessage=" $this->root doesn't exist!";
      if (!is_writable($this->root))
         $this->errorMessage=" $this->root isn't writable";
   }
   public function escape_string($str) {
      $str=str_replace("\\","\\\\",$str);
      $str=str_replace("'","\'",$str);
      return $str;
   }
   public function arrayFileString($arr) {
      foreach ($arr as $key=>$value) {
         if (is_array($value))
            $r.=$this->escape_string($key).'=>'.$this->arrayFileString($value).",\n";
         else
            $r.=$this->arrayItemFormat($key,$value);
      }
      return "array(\n$r)";
   }
   public function arrayItemFormat($field,$value) {
      $field=$this->escape_string($field);
      if (is_object($value))
         $value=(array)$value;

      if (is_array($value)) {
         $value=$this->arrayFileString($value);
         $ret=<<<EOT
'$field'=>$value,\n
EOT;
      }
      else {
         $type=gettype($value);
         if ($type!="string" && $type!="NULL")
            $type_cast="($type)";
         else
            $type_cast="";
         $value=$this->escape_string($value);
         $ret=<<<EOT
'$field'=>$type_cast'$value',\n
EOT;
      }
      return $ret;
   }
   static public function delete_dir($dir,$spare_dir='') {
      if ($dir==self::root) {
         return false;
      }

      //if (strpos($dir,self::root)===false && strpos($dir,self::replica_root)===false) {
      //	die("can't delete");
      //	return false;
      //}
      $files=scandir($dir);
      foreach ($files as $file) {
         if ($file=="." || $file=="..")
            continue;
         $path=$dir.$file;
         if (is_dir($path))
            self::delete_dir($path."/");
         else
            unlink($path);
      }
      if (!$spare_dir) {
         rmdir($dir);
      }
   }
   static function mkdir($dir) {
      mkdir($dir,0777);
   }
}