<?php
namespace LSYS;
use PHPUnit\Framework\TestCase;
use LSYS\Database\DI;
use LSYS\Database\Result;
final class ConfigTest extends TestCase
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
        $config = new \LSYS\Config\Database("aaa");
        var_dump(serialize($config));
        var_dump($config->get("bbb"));
        var_dump(unserialize(serialize($config))->asArray());
    }
}