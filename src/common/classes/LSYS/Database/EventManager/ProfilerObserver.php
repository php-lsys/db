<?php
/**
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\EventManager;
use LSYS\EventManager\Event;
use LSYS\EventManager\EventObserver;
class ProfilerObserver implements EventObserver
{
    protected $profiler;
    protected $token;
    public function __construct(\LSYS\Profiler $profiler=null){
        $this->profiler=$profiler?$profiler:\LSYS\Profiler\DI::get()->profiler();
    }
    public function eventNotify(Event $event)
    {
        switch ($event->getName()) {
            case DBEvent::SQL_START:
                list($prepare)=$event->getData();
                $this->token = $this->profiler->start("Database",$prepare->querySQL());
                break;
            case DBEvent::SQL_OK:
                if($this->token){
                    $this->profiler->stop($this->token);
                    $this->token=null;
                }
                break;
            case DBEvent::SQL_BAD:
                $this->token=null;
                break;
        }
    }
    public function eventName()
    {
        return [
            DBEvent::SQL_START,
            DBEvent::SQL_OK,
            DBEvent::SQL_BAD,
        ];
    }
}
