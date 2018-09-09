<?php
namespace LSYS\Database\EventManager;
class EventQueryFail extends EventDB{
    public $type;
    public $sql;
    public $error;
	public $errno;
    public function __construct($type,$sql,$errno,$error){
        $this->type=$type;
        $this->sql=$sql;
        $this->errno=$errno;
        $this->error=$error;
    }
}