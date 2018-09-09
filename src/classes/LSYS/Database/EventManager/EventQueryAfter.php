<?php
namespace LSYS\Database\EventManager;
use LSYS\Database\Result;
class EventQueryAfter extends EventDB{
    public $type;
    public $sql;
    public $result;
    public function __construct($type,$sql,Result $result){
        $this->type=$type;
        $this->sql=$sql;
        $this->result=$result;
    }
}