<?php
namespace LSYS\Database\EventManager;
class EventQueryBefore extends EventDB{
    public $type;
    public $sql;
    public function __construct($type,$sql){
        $this->type=$type;
        $this->sql=$sql;
    }
}