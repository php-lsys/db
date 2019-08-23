<?php
namespace LSYS\Database\EventManager;
class EventQueryFail extends EventDB{
    public $sql;
    public $error;
	public $errno;
    public function __construct($sql,$errno,$error){
        $this->sql=$sql;
        $this->errno=$errno;
        $this->error=$error;
    }
}