<?php
namespace LSYS\Database\EventManager;
class EventQueryBefore extends EventDB{
    public $sql;
    public function __construct($sql){
        $this->sql=$sql;
    }
}