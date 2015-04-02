<?php
/**
 * Created by PhpStorm.
 * @author albert
 * Date: 4/2/15
 * Time: 8:05 PM
 */
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