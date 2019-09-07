<?php
namespace TestLSYSDB;
use PHPUnit\Framework\TestCase;
use LSYS\Database\DI;
use LSYS\Database;
final class DBTest extends TestCase
{
    public function testinit(){
        $db =DI::get()->db("database.mysqli");
        $this->assertTrue($db instanceof Database);
        $db = Database::factory(\LSYS\Config\DI::get()->config("database.mysqli"));
        $this->assertTrue($db instanceof Database);
    }
}