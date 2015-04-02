<?php
if (!defined('RSDB')) {
   define('RSDB','My Database');
   CLASS RSDB {
      static $version="1.0";
      static $author="Albert D. Diones";
      static $dateOfCreation="March 01, 2009";
      protected $ERRORS;
      protected $log;
      const root = Document::RSDB_dir;
      public $root = Document::RSDB_dir;
      const replica_root = Document::RSDB_dir;
      public $replica_root = Document::RSDB_replica_dir;
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
   CLASS RSDB_result EXTENDS RSDBconnection {
      public $total_indexes;
      public $index;
      public $indexes;
      private $array;
      private $pointer=0;
      private $operations=array(
         write=>array(insert,update)
      ,read=>array(select,show)
      );
      private $shuffled=false;
      public $operation_done;
      public function __construct($i,$t=1,$l,$operation="select") {
         if (!$i) {
            return false;
         }
         switch ($operation) {
            case 'select':
               sort($i);
               $this->indexes=$i;
               $this->total_indexes=$t;
               break;
            case 'insert':
               if (!is_object($i) && !is_array($i))
                  $this->index=$i;
               $this->indexes=array($i);
               break;
         }
         $this->operation_done=$operation;
         $this->select_db($l->database);
         $this->select_table($l->table);
         $this->dir=$l->dir;
      }
      public function fetch_array($t="") {
         if ($this->indexes[$this->pointer])
            $row=$this->read($this->indexes[$this->pointer]);
         if ($row) {
            if (!$row['__ctime__'])
               $row['__ctime__']=$this->ctime($this->indexes[$this->pointer]);
            $row['__index__']=$this->indexes[$this->pointer];
            $this->pointer=($this->pointer)+1;
            return $row;
         }
         else {
            $this->pointer=($this->pointer)+1;
            return false;
         }
      }
      public function shuffle() {
         if (is_array($this->indexes))
            shuffle($this->indexes);
      }
      public function fetch_all_array() {
         foreach ($this->indexes as $i) {
            $row=$this->read($i);
            $row['__index__']=$i;
            $ret[]=$row;
         }
         return $ret;
      }
      public function affected_rows() {
         if (in_array($this->operation_done,$this->operations['write']))
            return count($this->indexes);
      }
      public function num_rows() {
         if (in_array($this->operation_done,$this->operations['read']))
            return count($this->indexes);
      }
      public function total_rows() {
         return count($this->indexes);
      }
      public function sort_reverse() {
         sort($this->indexes);
         $this->indexes=array_reverse($this->indexes);
      }
      public function reset_pointer() {
         $this->pointer=0;
      }
   }
   /*
      ****************************************
            all about CONNECTIONS!!!
      ****************************************
   */
   function RSDB_connect($db="",$table="") {

//A function to initiate the database and the table

      $p=array();
      $p['db']=$db;
      $p['table']=$table;
      /*This object will contain the
      ->Directory
      ->Errors
      ->Database Name
      ->Table name
      ->Database Operations(Methods)
      */
      return RSDB_var('connect',new RSDBconnection($p));

   }
   function RSDB_valid_global(&$var,$name) {

//A function to easily and uniformly know existing globals
      if (!$var)
         $var=RSDB_var($name);
      if ($var)
         return true;
      if ($name == "connect") {
         if ( $var = RSDB_connect() ) {
            return true;
         }
      }
      return false;
   }
   function RSDB_var($name,$value="") {
//A function to conveniently read/write globals_with_long_names
      $real_name=$name;
      $name="GLOBAL_RSDB_VAR_$name";
      if (is_array($GLOBALS[$name])) {
         $key=count($GLOBALS[$name]);
         if ($value) {
            $GLOBALS[$name][$key]=$value;
            $ret=$GLOBALS[$name][$key];
         }
         else
            $ret=$GLOBALS[$name][$key-1];
         return $ret;
      }
      if ($value)
         $GLOBALS[$name]=$value;
      return $GLOBALS[$name];
   }
   /*
      ****************************************
            all about DATABASES!!!
      ****************************************
   */
   function RSDB_show_dbs() {
      $files=scandir(RSDB::root);
      foreach ($files as $f) {
         if ($f=="." || $f==".." || strpos($f,"."))
            continue;
         $db[]=$f;
      }
      return $db;
   }
   function RSDB_mk_db($db,&$link="") {

//Makes a database, $db = name of database, $link is the connection (optional)

//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      $d="{$link->root}$db/";
      if (!file_exists($d))
         if (RSDB::mkdir($d)) {

            $link->log="Database $d created";
            return true;
         }
         else {
            $link->errorMessage="Database cannot be created";
            return false;
         }
      else {
         $link->errorMessage="Database already exists";
         return false;
      }
   }
   function RSDB_drop_db($db,&$link="") {

//Drops a database, (recursively deletes the directory, subdirectories and files)

      if (!RSDB_valid_global($link,'connect'))
         return false;
      $link->select_db($db);
      $link->deleteDir($link->dir);
   }
   /*
      ****************************************
            all about TABLES!!!
      ****************************************
   */
   function RSDB_show_tables(&$link='') {
      if (!RSDB_valid_global($link,'connect'))
         return false;
      if (isset($link->database))
         $link->select_db($link->database);
      else {
         $link->errorMessage="No Database Selected";
         return false;
      }
      $files=scandir($link->dir);
      foreach ($files as $f) {
         if ($f=="." || $f==".." || strpos($f,"."))
            continue;
         $table[]=$f;
      }
      return $table;
   }
   function RSDB_mk_table($table,$cols,&$link="") {

//Creates a table $table with columns $cols array values

//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      if (isset($link->database))
         $link->select_db($link->database);
      else {
         $link->errorMessage="No Database Selected";
         return false;
      }
      $d="{$link->dir}$table/";
      if (!file_exists($d)) {
         RSDB::mkdir($d);
         @chmod($d,0777);
      }
      if (file_exists($d))
         $link->log="Successfuly Created $d";
      else
         $link->errorMessage="<b>Failed to create</b> $d";
      $link->select_table($table);
      if (!file_exists($link->getFileLocCol()))
         RSDB::mkdir($link->getFileLocCol());
      foreach ($cols as $k=>$col)
      {
         if (is_array($col)) {
            $meta=$col;
            $col=$k;
         }
         $file=$link->getFileLocCol($col);
         $col_contents=$link->arrayFormat($col);
         $link->write($file,$col_contents);
         if (file_exists($file))
            $link->log="Successfuly Created $file";
         else
            $link->errorMessage="<b>Failed to create</b> $file";
         if ($meta) {
            if (RSDB_meta($col,$meta,$link))
               $link->log="Succesfully added meta";
            unset($meta);
         }

      }
   }
   function RSDB_drop_table($table,&$link='') {

//Drops (delete) a table by deleting it recursively

//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      if (!$table)
         $table=$link->table;
      if (!$table) {
         $link->errorMessage="No table found";
         return false;
      }
      RSDB_select_table($table,$link);
      $col_dir=$link->getFileLocCol();
      $link->deleteDir($col_dir);
      $link->deleteDir($link->dir);
      $link->log="Table $table dropped";
   }
   function RSDB_reset_table($table="",&$link='') {

//Clear out all the rows on the table(but doesn't delete the table)

//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      if (!$link)
         return false;
      if (!$table && $link->table) {
         $table=$link->table;
      }
      RSDB_select_table($table,$link);
      $indexes=RSDB_show_rows($table,$link);
      foreach ($indexes as $index) {
         RSDB_delete($index,$link);
      }
      $cols=RSDB_show_columns($table,$link);
      foreach ($cols as $col)
      {
         $file=$link->getFileLocCol($col);
         $contents=$link->arrayFormat(array());
         $link->write($file,$contents);
         if (file_exists($file))
            $link->log="Successfuly Created $file";
         else
            $link->errorMessage="<b>Failed to create</b> $file";

      }
   }
   function RSDB_refresh_table($table="",&$link='') {

//Reads and rewrites the rows one-by-one to apply new file formats or pinging the columns

//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      if (!$table)
         $table=$link->table;
      if (!$table)
         return false;
      $link->select_table($table);
      $rows=RSDB_show_rows($table,$link);
      foreach ($rows as $rown) {
         $row=$link->read($rown);
         RSDB_insert($row,$link,$rown);
      }
   }
   /*
      ****************************************
            all about WRITING!!!
      ****************************************
   */
   function RSDB_insert($row,&$link='',$ind=null) {

//Inserts a row to the table, if $ind is given, the row $ind is updated

//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect')) {
         die("NO Link");
         return false;
      }
      if (!$link->table)
         return "Failed to insert: table is not yet set!";
      if ($ind===null) {
         $ind=$link->getNextVacant();
         $row['__ptime__']=$row['__ctime__']=time();
         $link->operation_done=insert;
      }
      else {
         $db_row=$link->read($ind);
         $link->refresh();
         $db_row['__ctime__']=$db_row['__ctime__'] ? $db_row['__ctime__'] : filectime($link->getFileLoc($ind));
         $row['__ptime__']=$db_row['__ptime__'] ? $db_row['__ptime__'] : filectime($link->getFileLoc($ind));
         $row['__ctime__']=time();
         $link->operation_done='update';
      }
#	foreach ($row as $field=>$value) {
#		$rowArrayItems.=$link->arrayItemFormat($field,$value);
#	}
      $cols=RSDB_show_columns($link->table,$link);
      foreach ($cols as $field) {
         if (!($link->validate_field($row[$field],$field))) {
            $link->errorMessage="Failed to insert: value is not valid";
            return false;
         }
      }
      //Check if it is not same as the recorded
      if ($db_row) {
         $cmp_row=$row;
         $cmp_db_row=$db_row;
         $magics=array('__index__','__ptime__','__ctime__');
         foreach ($magics as $magic) {
            unset($cmp_row[$magic]);
            unset($cmp_db_row[$magic]);
         }
         if ($cmp_row==$cmp_db_row) {
            $row['__ctime__']=$db_row['__ctime__'];
         }
      }
      //Fills $rowfile with the php file string
      $rowfile=$link->arrayFormat($ind,$row);
      if ($link->write("$link->dir$ind.php",$rowfile)) {
         //Updates each columns
         foreach ($row as $field=>$value){
            $link->insertToCol($field,$ind,$value);
         }
      }
      return RSDB_var('insert',new RSDB_result($ind,1,$link,'insert'));
   }
   function RSDB_update($row,& $link='') {
      if (!RSDB_valid_global($link,'connect')) {
         die("NO Link");
         return false;
      }
      return RSDB_insert($row,$link,$row['__index__']);
   }
   function RSDB_delete($ind,&$link='') {

//Deletes a row by $ind ($index)

//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      $row=$link->read($ind);
      foreach ($row as $field=>$value) {
         $colFile=$link->getFileLocCol($field);
         $link->replaceFileItem($link->arrayItemFormat($ind,$value),"",$colFile);
      }
      @unlink($link->getFileLoc($ind));
      return "Successfully deleted all row $ind components";
   }
   /*
      ****************************************
            all about READING!!!
      ****************************************
   */
   function RSDB_row($row,&$link='') {
      if (!RSDB_valid_global($link,'connect'))
         return false;
      if (!$link)
         return false;
      if (!$row)
         return false;
      $result=RSDB_var('select',new RSDB_result(array($row),1,$link));
      return $result->fetch_array();
   }
   function RSDB_select($field=NULL,$needle=NULL,&$link=NULL) {
//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      if ($field===NULL) {
         $result=RSDB_show_rows($link);
         $total=count($result);
         $result=RSDB_var('select',new RSDB_result($result,$total,$link));
         return $result;
      }
      if (is_string($field))
         $field=array($field);
      $result=array();
      $total=0;
      foreach ($field as $f) {
         $field_array=$link->readCol($f);
         if ($needle!==NULL || $needle === 'BLANK')
         {
            if ($needle === 'BLANK')
               $needle = '';
            #$link->log=gettype($needle);
            #type strict
            if (is_string($needle))
               $type_strict=false;
            else
               $type_strict=true;
            $link->log="using 2 parameters for (".gettype($needle).") $needle on $link->table on $link->database";
            if (is_array($field_array) && $keys=array_keys($field_array,$needle,$type_strict))
            {
               if ($total<count(array_keys($field_array)))
                  $total+=count(array_keys($field_array));
               $result=array_merge($result,$keys);
            }
         }
         else
         {
            //If no needle is given,record all array index
            if (!is_array($field_array)) {
               $link->errorMessage="No records on the column";
               return false;
            }
            if ($keys=array_keys($field_array))
            {
               if ($total<count(array_keys($field_array)))
                  $total+=count(array_keys($field_array));
               $result=array_merge($result,$keys);
            }
         }
      }
      $result=RSDB_var('select',new RSDB_result($result,$total,$link));
      if ($result->indexes)
         return $result;
      else
         return false;
   }

   function RSDB_select_like($field,$needle,&$link='') {
//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      if (!$needle) {
         return RSDB_select($field,$needle);
      }
      $result=array();
      $col = $link->readCol($field);
      $total = count($col);
      foreach ($col as $index=>$value) {
         if (is_array($value)) {
            $needle_position = in_array($needle,$value);
         }
         else
            $needle_position = stripos($value,$needle);
         if ($needle_position===false)
            continue;
         else if ($needle_position) {
            if ($index!=0)
               $result[]=$index;
         }
      }
      if ($result) {
         $result=RSDB_var('select',new RSDB_result($result,$total,$link));
         if ($result->indexes) {
            return $result;
         }
         else {
            unset($result);
            return false;
         }
      }
      else
         return false;
   }

   function RSDB_fetch_first_array($field,$needle,&$link='') {
//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      $result=RSDB_select($field,$needle,$link);
      if ($result)
         return $result->fetch_array();
      else
         return false;
   }
   function RSDB_fetch_array(&$result="") {
      if (!$result)
         $result=RSDB_var('select');
      if (!$result)
         return false;
      return $result->fetch_array();
   }
   function RSDB_shuffle(&$result="") {
      if (!$result)
         $result=RSDB_var('select');
      if (!$result)
         return false;
      $result->shuffle();
   }
   function RSDB_sort(&$result="",$type="") {
      if (!$result)
         $result=RSDB_var('select');
      if (!$result)
         return false;
      $func = "sort_$type";
      $result->$func();
   }
   function RSDB_num_rows(&$result="") {
      if (!RSDB_valid_global($link,'select'))
         return false;
      if (!$result)
         return false;
      return $result->num_rows();
   }
   function RSDB_show_columns($table="",&$link='') {
//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      if (!$table)
         $table=$link->table;
      if (!$table)
         return false;
      RSDB_select_table($table);
      $dir=$link->getFileLocCol();
      $files=scandir($dir);
      $cols=array();
      foreach ($files as $f) {
         if (strpos($f,".php"))
            $cols[count($cols)]=str_replace(".php","",$f);
      }
      return $cols;
   }
   function RSDB_show_rows($table='',&$link='') {
//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      $files=scandir($link->dir);
      foreach ($files as $f) {
         if ($f=="." || $f==".." || strpos($f,".")===false)
            continue;
         $f=str_replace('.php','',$f);
         $indexes[]=$f;
      }
      return $indexes;
   }
   /*
      ****************************************
            all about ADMINISTRATION!!!
      ****************************************
   */
   function RSDB_purge_backup(&$link='') {
//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      $link->deleteDir($link->replica_root,true);
   }
   function RSDB_execute($cmd,&$link='') {
//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      $temp_file=$link->root."temp.php";

      $cmd=strpos($cmd,"<?php")!==0 ? "<?php\n".$cmd : $cmd;
      $link->write($temp_file,$cmd);
      include($temp_file);
      echo RSDB_log();
      echo RSDB_error_log();

   }
   function RSDB_array_to_HTML_table($array) {
      foreach ($array as $key=>$value) {
         $top_row.="<td> $key </td>";
         if (is_array($value))
            $value=implode(",",$value);
         $bottom_row.="<td> $value </td>";
      }
      return "<table border=3><tr>$top_row</tr><tr>$bottom_row</tr></table>";
   }
   function RSDB_HTML_row($row,$cols=null,$attr=null) {
      if ($cols) {
         foreach ($cols as $order=>$col) {
            if (is_array($row[$col]))
               $row[$col]= RSDB_array_to_HTML_table($row[$col]);
            $ret.="<td $attr><xmp>{$row[$col]}</xmp></td>";
         }
      }
      else {
         foreach ($row as $field=>$value) {
            if (is_array($value))
               $value= RSDB_array_to_HTML_table($value);
            $ret.="<td $attr><xmp>$value</xmp></td>";
         }
      }
      return "<tr $attr>$ret</tr>";
   }
   function RSDB_HTML_selection_table(& $result='') {
      if (!RSDB_valid_global($result,'select'))
         return false;
      $result->reset_pointer();
      $cols=RSDB_show_columns($result->table,$result);
      if (!in_array('__index___',$cols))
         $cols[]='__index__';
      if (!in_array('__ctime__',$cols))
         $cols[]='__ctime__';
      $html=RSDB_HTML_row($cols);
      while ($row=RSDB_fetch_array()) {
         $html.=RSDB_HTML_row($row,$cols);
      }
      $html="<table border=3 style='width:100%'>$html</table>";
      $num_rows=RSDB_num_rows($result);
      $html="number of rows: $num_rows <br /> $html";
      return $html;
   }
   function RSDB_HTML_insertion_table(& $result='') {
      if (!RSDB_valid_global($result,'insert'))
         return false;
      if (!$result->index)
         die("Can't Get The Latest/Given Result ".$result);
      $insert_id=$result->index;
      $add=-10;
      $lim=0;
      $affected_rows=$result->index;//implode(',',$result->indexes);
      if ($result->operation_done=='update') {
         $add=-5;
         $lim=5;
      }
      $cols=RSDB_show_columns();
      $html=RSDB_HTML_row($cols);
      while ($add<=$lim) {
         $row=RSDB_row($insert_id+$add);
         if ($add===0)
            $attr='color="red"';
         if ($row)
            $html.=RSDB_HTML_row($row,$cols,$attr);
         $add++;
      }
      $html="<table>$html</table>";
      $num_affected_rows=$result->affected_rows();
      $html="Affected rows ($num_affected_rows) # $affected_rows<br />$html";
      return $html;
   }
   /*
      ****************************************
            Backup Copying Functions
      ****************************************
   */
   function replica_parent_dir() {
      return "/home/albertd/public_html/wow300.net/";
   }
   function replica_dir() {
      return "/home/albertd/public_html/wow300.net/RSDB/";
   }
   function replicate($directory) {
      return;
      static $recurse=1;
      $dir=str_replace(replica_parent_dir(),"",$directory);
      $dir=ltrim($dir,"/");
      $dirs=explode('/',$dir);
      $DIR=replica_dir();
      if (recurse==1)
         RSDB::delete_dir($DIR,true);
      foreach ($dirs as $dir) {
         $DIR.="$dir/";
         if (!file_exists($DIR)) {
            RSDB::mkdir($DIR);
            @chmod($DIR,0777);

         }
      }
      $files=scandir($directory);
      foreach ($files as $file) {
         if ($file=="." || $file=="..")
            continue;
         $path=$directory.$file;
         if (is_dir($path)) {
            $path.="/";
            replicate($path);
         }
         else {
            $contents=file_get_contents($path);
            $rep_path=$DIR.$file;
            var_dump($rep_path);
            echo "<br />";
            replica_write($rep_path,$contents);
         }
      }
      $recurse++;
   }
   function replica_write($f,$c) {
      if (file_exists($f))
      {
         if (!is_writable($f))
            @chmod($f,0777);
         if (is_writable($f))
         {
            $f=fopen($f,w);
            fwrite($f,$c);
            fclose($f);
            return true;
         }
      }
      else
      {
         $f=fopen($f,x);
         fwrite($f,$c);
         fclose($f);
         return true;
      }
   }
   function RSDB_select_db($db,&$link='') {
//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      if ($link->select_db($db)) {
         $link->log="$db database selected";
         return RSDB_var('connect',$link);
      }
      return false;
   }
   function RSDB_select_table($table,&$link='') {
//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      if (strpos($table,".")) {
         $path=explode(".",$table);
         RSDB_select_db($path[0]);
         $table=$path[1];
      }
      if ($link->select_table($table)) {
         $link->log="$table table selected";
         return RSDB_var('connect',$link);
      }
      return false;
   }
   function col_exists($field,& $link='') {
//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      if (!$link)
         return false;
      if (in_array($field,RSDB_show_columns($link->table,$link)))
         return true;
      return false;
   }
   function RSDB_meta($field,$arr='',& $link='') {
//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      if (!$link)
         return false;
      if ($arr) {
         if (col_exists($field,$link)) {
            $link->write_meta($field,$arr);
            return true;
         }
         else {
            $link->errorMessage="Column does not exist!";
            return false;
         }
      }
      return $link->read_meta($field);
   }
   function RSDB_error_log(& $link='') {
//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      if (!$link)
         return false;
      return $link->errorMessage;
   }
   function RSDB_log(& $link='') {
//If no $link is given, get the latest object generated by RSDB_connect
      if (!RSDB_valid_global($link,'connect'))
         return false;
      if (!$link)
         return false;
      return $link->log;
   }
//Global Validators
   function valid_for_not_null($value,$field) {
      if (isset($value) && $value!=null)
         return true;
      return false;
   }
   function valid_for_unique($value,$field) {
      $result=RSDB_select($field,$value);
      if (RSDB_num_rows()<=1)
         return true;
      return false;
   }
   function valid_for_max_length($value,$requirement) {
      if (strlen($value)<=$requirement)
         return true;
      return false;
   }
   function valid_for_min_length($value,$requirement) {
      if (strlen($value)>=$requirement)
         return true;
      return false;
   }
   function valid_for_type($value,$requirement) {
      $function="is_$requirement";
      if ($function($value))
         return true;
      return false;
   }
   function valid_for_allowed_chars($value,$requirement) {
      $x=0;
      if (preg_match('/^\/.*\/$/',$requirement)) {
         if (preg_match($requirement,$value))
            return true;
         else
            return false;
      }
      while (isset($value[$x])) {
         if (strpos($requirement,$value[$x])===false)
            return false;
      }
      return true;
   }
   function valid_for_enum($value,$requirement) {
      if (in_array($value,$requirement))
         return true;
      return false;
   }
   function RSDB_list_random_rows($format='',$table='',$no=10,&$link='',$flags='') {
//If no $link is given, get the latest object generated by RSDB_connect
      if ($flags)
         $flags=explode(" ",$flags);
      if (!RSDB_valid_global($link,'connect'))
         return false;
      if (!$link)
         return false;
      if (!$table)
         RSDB_select_table($link->table);
      RSDB_select_table($table);
      $selection=RSDB_select();
      RSDB_shuffle();
      for ($x=1; $x<=$no; $x++) {
         $row=RSDB_fetch_array($selection);
         $LI=$format;
         foreach ($row as $field=>$value) {
            if ($flags) {
               foreach ($flags as $flag) {
                  $value=$flag($value);
               }
            }
            $LI=str_replace("{".$field."}",$value,$LI);
         }
         $UL.=$LI;
      }
      $UL="<ul>$UL</ul>";
      return $UL;
   }
   RSDB_var('connect',array());
   RSDB_var('select',array());
   RSDB_var('insert',array());
}
/*$link=RSDB_connect("open-source","macro");
RSDB_drop("macro",$link);
RSDB_mk_table("macro",$link,$cols);
pingOS($link);

if ($arr=$link->readCol("c"))
echo "<h1>$x</h1><xmp>".print_r($arr,true)."</xmp>";
else
	echo "<h1>read column failed";
*/
?>