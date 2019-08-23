<?php
namespace LSYS\Database\EventManager;
use LSYS\Database\Result;
class EventQueryAfter extends EventDB{
    public $sql;
    public $result;
    public function __construct($sql,Result $result){
        $this->sql=$sql;
        $this->result=$result;
    }
}