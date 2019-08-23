<?php
namespace LSYS;
use PHPUnit\Framework\TestCase;
use LSYS\Database\DI;
use LSYS\Config\File;
use LSYS\Database\Result;
final class MysqlPTest extends TestCase
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
        $table_name=$this->_db->quoteTable("order");
        $value=$this->_db->quote("SN001");
        $sql="insert into {$table_name} (sn,title,add_time) values ('SN001','".uniqid()."','".time()."') ";
        $result=$db->query(Database::DML, $sql);
        $this->assertTrue($db->insertId()>0);
        $this->assertTrue($db->affectedRows()>0);
    }
    public function testupdate()
    {
        $db=$this->_db;
        $table_name=$this->_db->quoteTable("order");
        $value=$this->_db->quote("SN001");
        $id=$db->quote(1);
        $sql="update {$table_name} set title='update data' where id={$id}";
        $result=$db->query(Database::DML, $sql);
        $this->assertTrue(!!$result);
    }
    public function testselect()
    {
        $db=$this->_db;
        $table_name=$this->_db->quoteTable("order");
        $value=$this->_db->quote("SN001");
        $sql="select * from {$table_name} where sn={$value}";
        $result= $this->_db->query( $sql);//DQL返回结果对象,其他返回布尔
        $this->assertInstanceOf(Result::class, $result);
    }
    public function testdel()
    {
        $db=$this->_db;
        $table_name=$this->_db->quoteTable("order");
        $value=$this->_db->quote("SN001");
        $id=$db->quote(3);
        $sql="delete from {$table_name} ";
        $result=$db->query(Database::DML, $sql);
        $this->assertTrue($db->affectedRows()>0);
    }
}