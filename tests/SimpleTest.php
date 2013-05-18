<?php

use WAtchFiles\Watch;

class SimpleTest extends \phpunit_framework_testcase
{
    public function testFile()
    {
        $watch = new Watch(TMP . '/test-file.php');
        $watch->watchFile(__DIR__ . '/features/somefolder/foobar.txt');
        $watch->watch();
        $this->assertFalse($watch->hasChanged());
        touch(__DIR__ . '/features/somefolder/foobar.txt', time());
        clearstatcache();
        $this->assertNotEquals($watch->hasChanged(), false);
    }

    public function testFileRubbishInpt()
    {
        $watch = new Watch(TMP . '/test-file.php');
        $watch->watchFile(__DIR__ . '/features/somefolder/xxx.txt');
        $watch->watch();
        $this->assertTrue($watch->hasChanged());
    }

    public function testDir()
    {
        $watch = new Watch(TMP . '/test-dir.php');
        $watch->watchGlob(__DIR__ . '/featur*');
        $watch->watch();
        $this->assertFalse($watch->hasChanged());
        touch(__DIR__ . '/features/' . uniqid(true) . '.txt', time());
        sleep(1);
        clearstatcache();
        $this->assertNotEquals($watch->hasChanged(), false);
    }
}
