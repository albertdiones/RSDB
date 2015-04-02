<?php
/**
 * Created by PhpStorm.
 * @author albert
 * Date: 4/2/15
 * Time: 8:04 PM
 */
CLASS RSDBconnection EXTENDS RSDB {
   public $database;
   public $table;
   public $dir;
   public function select_db($name) {
      if ($this->table)
         $this->table="";
      $this->database=$name;
      $this->dir="$this->root$name/";
      if ($this->check())
         return true;
      else
         return false;
   }
   public function select_table($name) {
      if (!$name)
         die("Null table name");
      $this->table=$name;
      $this->dir="$this->root$this->database/$name/";
      if ($this->check())
         return true;
      else
         return false;
   }
   public function refresh() {
      $db=$this->database;
      $table=$this->table;
      $this->select_db($db);
      $this->select_table($table);
   }
   public function __construct($properties) {
      parent::__construct($properties);
      if ($properties['db'])
         $this->select_db($properties['db']);
      if ($properties['table'])
         $this->select_table($properties['table']);
   }
   private function check() {
      $dir=$this->dir;
      if (!file_exists($dir))
      {
         $E=1;
         $this->errorMessage="$dir was not found";
      }
      if (!is_writable($dir) && file_exists($dir))
      {
         $E=1;
         $this->errorMessage="$dir is not writable";
      }
      if ($E)
         return false;
      else
         return true;

   }
   public function write($file,$content)
   {
      if (file_exists($file))
      {
         if (!is_writable($file)) @chmod($file,0777);
         if (is_writable($file))
         {
            $file=fopen($file,w);
            fwrite($file,$content);
            fclose($file);
            return true;
         }
         else
            $this->errorMessage="$file isn't writable";
      }
      else
      {
         $dir=dirname($file);
         if (!file_exists($dir))
            RSDB::mkdir($dir);
         $path=$file;
         $file=fopen($file,'x');
         if (fwrite($file,$content));
         else
            $this->errorMessage="$file isn't writable";
         fclose($file);
         chmod($path,0666);
         return true;
      }
   }
   public function getNextVacant() {
      $chkpts=array(1000,100,10,1);
      $dir=$this->dir;
      $x=0;
      foreach ($chkpts as $j)
      {
         while (file_exists($dir.($x+$j).".php"))
         {
            $x+=$j;
         }
      }
      return $x+1;
   }
//Writing Format
   public function arrayFormat($field,$content="",$is_meta=null) {
      if (!$this->table)
         die("No table selected");
      $ret=<<<EOT
<?php
\$RSDB["$this->database"]["$this->table"]
EOT;
      if (is_array($content))
         $content=$this->arrayItemFormats($content);
      if ($field)
         $ret="{$ret}[\"$field\"]";
      if ($is_meta=='META')
         $ret.="[\"__META__\"]";
      $ret=$ret."=array(\n$content".$this->ender;
      return $ret;
   }
   public function arrayItemFormats($arr) {
      foreach ($arr as $k=>$v) {
         $ret.=$this->arrayItemFormat($k,$v);
      }
      return $ret;
   }
   public function insertToCol($field,$index,$value) {
      $file=$this->getFileLocCol($field);
      $cols=$this->readCol($field);
      $cols[$index]=$value;
      $C=$this->arrayFormat($field,$cols);
      $this->write($file,$C);
   }
//File Locations
   public function getFileLoc($index) {
      return "{$this->dir}$index.php";
   }
   public function getFileLocCol($field="") {
      if ($field || $field===0)
         $fx=".php";
      return "{$this->dir}COLS/$field$fx";
   }
   public function getFileLocMeta($field="") {
      return "{$this->dir}METAS/$field.php";
   }
//Reading and Writing
   public function replaceFileItem($search,$replace,$file) {
      $content=file_get_contents($file);
      $this->write($file,str_replace($search,$replace,$content));
   }
   public function read($index) {
      if (@include($this->getFileLoc($index)))
         $return=$RSDB[$this->database][$this->table][$index];
      else
         return false;
      return $return;
   }
   public function readCol($field) {
      if (@include($this->getFileLocCol($field)))
         return $RSDB[$this->database][$this->table][$field];
      else
         return false;
   }
   //Getting the ctime of the row
   public function ctime($index) {
      return filectime($this->getFileLoc($index));
   }
   public function replaceColItem($key,$value,$field) {
      $col=$this->readCol($field);
      $col[$key]=$value;
      $file=$this->arrayFormat($field);
   }
   public function deleteDir($dir,$spare_dir="") {
      self::delete_dir($dir,$spare_dir);
   }
//Field Entry validation
   public function validate_field($value,$field) {
      if ($metas=$this->read_meta($field)) {
         foreach ($metas as $property=>$meta_value) {
            if (!($this->validate($value,$property,$meta_value,$field)))
               return false;
         }
      }
      return true;
   }
   public function validate($value,$property,$meta_value,$field) {
      if (is_string($property)) {
         $rule="valid_for_".$property;
         if ($rule($value,$meta_value))
            return true;
         $this->errorMessage="Not valid for $rule($value,$meta_value)";
         return false;
      }
      else {
         $rule="valid_for_".$meta_value;
         if ($rule($value,$field))
            return true;
         $this->errorMessage="Not valid for $rule($value,$field)";
         return false;
      }
   }
   public function read_meta($field) {
      if (@include($this->getFileLocMeta($field)))
         return $RSDB[$this->database][$this->table][$field]['__META__'];
      else
         return false;
   }
   public function write_meta($field,$meta_arr) {
      $contents=$this->arrayFormat($field,$meta_arr,'META');
      $this->write($this->getFileLocMeta($field),$contents);
   }

}