<?php
/**
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\EventManager;
class ProfilerObserver implements \SplObserver
{
    protected $profiler;
    protected $token;
    public function __construct(\LSYS\Profiler $profiler){
        $this->profiler=$profiler;
    }
    public function update(\SplSubject $subject)
    {
        $event=$subject->event();
        switch ($event->name()) {
            case DBEvent::QUERY_START:
            case DBEvent::EXEC_START:
                $this->token = $this->profiler->start("Database",$event->eventargs()[0]);
            break;
            case DBEvent::QUERY_OK:
            case DBEvent::EXEC_OK:
                if($this->token){
                    $this->profiler->stop($this->token);
                    $this->token=null;
                }
            break;
            case DBEvent::QUERY_ERROR:
            case DBEvent::EXEC_ERROR:
                $this->token=null;
            break;
        }
    }
}
