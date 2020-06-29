<?php
namespace TestLSYSDB;
use PHPUnit\Framework\TestCase;
use LSYS\Database\DI;
use LSYS\Database;
use LSYS\Database\AsyncQuery;
use LSYS\Database\Result;
use LSYS\EventManager\EventCallback;
use LSYS\Database\EventManager\DBEvent;
use LSYS\Database\EventManager\ProfilerObserver;
use LSYS\Database\AsyncMaster;
use LSYS\Database\PrepareSlave;
use LSYS\Database\ConnectSchema;
use LSYS\Database\ConnectCharset;
class MYSQLITest extends TestCase
{
    public function testinit(){
        $this->runInit("database.pdo_mysql");
        $this->runInit("database.mysqli");
    }
    protected function runInit($config){
        $db =DI::get()->db($config);
        $this->assertTrue($db instanceof Database);
        $db = Database::factory(\LSYS\Config\DI::get()->config($config));
        $this->assertTrue($db instanceof Database);
        $this->assertFalse($db->getConnect()->isConnected());
        $db->getConnect()->connect();
        $this->assertTrue($db->getConnect()->isConnected());
    }
    public function testQuote() {
        $this->runQuote(DI::get()->db("database.mysqli"));
        $this->runQuote(DI::get()->db("database.pdo_mysql"));
    }
    protected function runQuote(Database $db){
        $this->assertEquals($db ->getConnect()->quote(NULL),"NULL");
        $this->assertEquals($db ->getConnect()->quote("aaa"),"'aaa'");
        $this->assertEquals($db ->getConnect()->quote(true),"'1'");
        $this->assertEquals($db ->getConnect()->quote(false),"'0'");
        $this->assertEquals($db ->getConnect()->quote(Database::expr("aaa")),"aaa");
        $this->assertEquals(str_replace(" ", '', $db ->getConnect()->quote([1,2,3])),"(1,2,3)");
        $this->assertEquals($db ->getConnect()->quote(1),"1");
        $this->assertEquals($db ->getConnect()->quote(1.2),"1.200000");
        $this->assertEquals($db ->getConnect()->quoteTable(["a","b"]),"`l_a` AS `l_b`");
        $this->assertEquals($db ->getConnect()->quoteTable(["a.a","b"]),"`a`.`l_a` AS `l_b`");
        $this->assertEquals($db ->getConnect()->quoteTable(Database::expr("aaa as b")),"aaa as b");
        $this->assertEquals($db ->getConnect()->quoteColumn(["a","b"]),"`a` AS `b`");
        $this->assertEquals($db ->getConnect()->quoteColumn(["a.a","b"]),"`l_a`.`a` AS `b`");
        $expr=Database::expr("if(id>:a,1,2) as t",[":a"=>1]);
        $this->assertEquals($expr->value(), strval($expr));
        $expr->bindParam([":a"=> 2]);
        $eq="if(id>2,1,2) as t";
        $this->assertEquals($expr->compile(),$eq);
        $this->assertEquals($db ->getConnect()->quoteColumn($expr),$eq);
        $this->assertEquals($db ->getConnect()->quoteColumn("*"),"*");
        
        $this->assertEquals($db->getConnect()->escape("aaa\\a"), "aaa\\\\a");
        
    }
    public function testCURD() {
        $this->runCURD(DI::get()->db("database.mysqli"));
        $this->runCURD(DI::get()->db("database.pdo_sqlite"));
        $this->runCURD(DI::get()->db("database.pdo_mysql"));
    }
    protected function runCURD(Database $db) {
        \LSYS\EventManager\DI::get()->eventManager()->attach(new EventCallback([
            DBEvent::SQL_START,
            DBEvent::SQL_END,
            DBEvent::SQL_OK,
            DBEvent::SQL_END,
        ], function(\LSYS\EventManager\Event $e){
            $this->assertTrue($e->data("prepare") instanceof PrepareSlave);
        }));
        \LSYS\EventManager\DI::get()->eventManager()->attach(new EventCallback([
            DBEvent::TRANSACTION_BEGIN,
            DBEvent::TRANSACTION_COMMIT,
            DBEvent::TRANSACTION_ROLLBACK,
        ], function(\LSYS\EventManager\Event $e){
            $this->assertTrue(boolval($e->data("connent")));
        }));
        $db->setEventManager(\LSYS\EventManager\DI::get()->eventManager());
        
        $table_name=$db->getConnect()->quoteTable("order");
        $column=$db->getConnect() ->quoteColumn('sn');
        $titlec=$db->getConnect() ->quoteColumn('title');
        $add_time=$db->getConnect() ->quoteColumn('add_time');
        $val=$db->getConnect()->quote('SN001');
        $title=$db->getConnect()->quote(uniqid("title"));
        $time=$db->getConnect()->quote(time());
        $sql="insert into {$table_name} ({$column},{$titlec},{$add_time}) values ({$val},{$title},{$time});";
        $result=$db->getMasterConnect()->exec($sql);
        $this->assertTrue($result);
        $this->assertEquals($db->getMasterConnect()->lastQuery(), $sql);
        $id=$db->getMasterConnect()->insertId();
        $this->assertTrue(is_numeric($id));
        $row=$db->getMasterConnect()->affectedRows();
        $this->assertTrue(is_numeric($row));
        $_id=$db->getConnect()->quote($id);
        $res=$db->getConnect()->query("select * from {$table_name} where id={$_id}");
        $this->assertEquals($res->count(), "1");
        //预编译
        $usql="UPDATE {$table_name} SET sn=:sn WHERE id=:id ";
        $db->getMasterConnect()->exec($usql,array(":sn"=>"SN002",":id"=>$id));
        $pre=$db->getConnect()->prepare($usql);
        $this->assertTrue($pre->connect()->db() ===$db);
        $pre->bindParam(array(
            ":sn"=>"SN003",":id"=>$id
        ))->exec();
        $pre->bindParam(array(
            ":sn"=>"SN004",":id"=>$id
        ))->exec();
        
        $res=$db->getConnect()->query("select * from {$table_name} where id in :id",array(":id"=>[$id,$id]));
        $this->assertEquals($res->get("sn"), "SN004");
        $this->assertEquals($res->asArray()[0]['sn'],'SN004');
        
        
        //事务确认
        $db->getMasterConnect()->beginTransaction();
        $db->getMasterConnect()->exec($sql);
        $idd=$db->getMasterConnect()->insertId();
        $db->getMasterConnect()->commit();
        $bid=$db->getMasterConnect()->quote($idd);
        $res=$db->getMasterConnect()->query("select * from {$table_name} where id={$bid}");
        $res->setFetchMode(Result::FETCH_OBJ);
        $this->assertTrue($res->current() instanceof \stdClass);
        $this->assertEquals($res->count(), "1");
        
       
        
        //事务回滚
        $db->getMasterConnect()->beginTransaction();
        $db->getMasterConnect()->exec($sql);
        $this->assertTrue($db->getMasterConnect()->inTransaction());
        $rid=$db->getMasterConnect()->insertId();
        $db->getMasterConnect()->rollback();
        $rid=$db->getMasterConnect()->quote($rid);
        $tsql="select * from {$table_name} where id={$rid}";
        $res=$db->getSlaveConnect()->query($tsql);
        $this->assertEquals($res->count(), "0");
        
        $conn=$db->getMasterConnect();
        if ($conn instanceof ConnectSchema) {
            $conn->useSchema('test');
            $this->assertEquals('test', $conn->schema());
        }
        $conn=$db->getMasterConnect();
        if ($conn instanceof ConnectCharset) {
            $conn->setCharset('utf8');
            $this->assertEquals('utf8', $conn->charset());
        }
        
        //异步
        if ($db instanceof AsyncMaster) {
            $i1=$db->asyncQuery($db->getMasterConnect(),"select * from {$table_name} where id={$bid}");
            $i2=$db->asyncQuery($db->getMasterConnect(),"select * from {$table_name} where id={$_id}");
            $data=$db->asyncExecute()->result([$i1,$i2]);
            $this->assertTrue(is_array($data));
            $this->assertEquals($data[0]->get("id"),$bid);
            $this->assertEquals($data[1]->get("id"),$_id);
            $i1=$db->asyncExec($db->getMasterConnect(),$sql);
            $i2=$db->asyncQuery($db->getConnect(),"select * from {$table_name} where id=:id",[":id"=>$id]);
            $res=$db->asyncExecute();
            $data=$res->result([$i1,$i2]);
            $this->assertTrue($res->insertId($i1)>0);
            $this->assertTrue($res->affectedRows($i1)>0);
            $this->assertTrue($data[0]);
            $this->assertEquals($data[1]->get("id"),$_id);
        }
        
        $dsql="delete from {$table_name} where id=:id";
        $result=$db->getMasterConnect()->exec($dsql,array(":id"=>$idd));
        $this->assertTrue($db->getMasterConnect()->affectedRows()>0);
        //exception
        
        try{
            $sql="wrong sql";
            $db->getMasterConnect()->query($sql);
        }catch (\LSYS\Database\Exception $e){
            $this->assertEquals($e->getErrorSql(),$sql);
        }
        
    }
    public function testReconn() {
        $this->runCURD(DI::get()->db("database.mysqli"));
        $this->runCURD(DI::get()->db("database.pdo_mysql"));
    }
    public function runReconn(Database $db) {
        $table_name=$db->getConnect()->quoteTable("order");
        $sql="select * from {$table_name} where id>=:id";
        $db->getConnect()->query($sql,[":id"=>"764"]);
        `sudo service mysql restart`;
        $result= $db->getConnect()->query($sql,[":id"=>"764"]);
        $this->assertTrue($result instanceof Result);
    }
    public function testExpr() {
        $this->runExpr(DI::get()->db("database.mysqli"));
        $this->runExpr(DI::get()->db("database.pdo_mysql"));
    }
    public function runExpr(Database $db) {
        $table_name=$db->getConnect()->quoteTable("order");
        $sql="select * from {$table_name} where id in :id";
        $result= $db->getConnect()->query($sql,[":id"=>Database::expr("(1,2)")]);
        $this->assertTrue($result instanceof Result);
        $sql="UPDATE {$table_name} SET sn=:sn WHERE id>0";
        $result= $db->getMasterConnect()->exec($sql,[":sn"=>Database::expr("CONCAT(sn,'hi')")]);
        $this->assertTrue($result);
    }
    public function testProfiler() {
        $this->runProfiler(DI::get()->db("database.mysqli"));
        $this->runProfiler(DI::get()->db("database.pdo_mysql"));
    }
    public function runProfiler(Database $db) {
        $eventm=\LSYS\EventManager\DI::get()->eventManager();
        $eventm->attach(new ProfilerObserver());
        $db->setEventManager($eventm);
        $table_name=$db->getConnect()->quoteTable("order");
        $sql="select * from {$table_name} where id = :id";
        $db->getConnect()->query($sql,[":id"=>1]);
        $sql="select sleep(1) as t";
        $db->getConnect()->query($sql);
        $this->assertTrue(\LSYS\Profiler\DI::get()->profiler()->appTotal()[0]>1000);//总耗时肯定大于1秒
    }
}