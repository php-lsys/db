<?php
namespace LSYS;
use PHPUnit\Framework\TestCase;
use LSYS\Database\DI;
use LSYS\Config\File;
use LSYS\Database\Result;
final class MysqlTest extends TestCase
{
    /**
     * @var Database
     */
    protected $_db;
    public function setUp(){
        $this->_db=DI::get()->db("database.mysqli");
    }
    public function testinsert()
    {
        $db=$this->_db;
        $table_name=$this->_db->quote_table("order");
       
        $sql="insert into {$table_name} (`sn`, `title`, `add_time`) values (:sn, :title, :add_time)";
        //发送SQL 请求
        $prepare=$db->prepare(Database::DML, $sql);
        $prepare->bindValue(array(
            'sn'=>'SN001',
            'title'=>'title'.uniqid(),
            'add_time'=>time(),
        ));
        $prepare->execute();
        $this->assertTrue($prepare->insert_id()>0);
        $this->assertTrue($prepare->affected_rows()>0);
    }
    public function testupdate()
    {
        $db=$this->_db;
        $table_name=$this->_db->quote_table("order");
        $sql="update {$table_name} set title=:title where id=:id";
        $prepare=$db->prepare(Database::DML, $sql);
        $prepare->bindValue(array(
            'id'=>1,
            'title'=>'title'.uniqid(),
        ));
        $this->assertTrue(!!$prepare->execute());
    }
    public function testselect()
    {
        $db=$this->_db;
        $table_name=$this->_db->quote_table("order");
        $value=$this->_db->quote("SN001");
        $prepare=$db->prepare(Database::DQL, "select * from {$table_name} where sn=:sn");
        $prepare->bindValue("sn",'SN001');
        $result=$prepare->execute();
        $record=$result->current();//第一个结果
        $this->assertInstanceOf(Result::class, $result);
    }
    public function testdel()
    {
        $db=$this->_db;
        $table_name=$this->_db->quote_table("order");
        $value=$this->_db->quote("SN001");
        $sql="delete from {$table_name} ";
        $prepare=$db->prepare(Database::DML, $sql);
        if ($prepare->execute()){
            $this->assertTrue($prepare->affected_rows()>0);
        }
    }
}