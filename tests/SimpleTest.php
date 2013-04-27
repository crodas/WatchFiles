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

    public function testDir()
    {
        $watch = new Watch(TMP . '/test-dir.php');
        $watch->watchDir(__DIR__ . '/features');
        $watch->watch();
        $this->assertFalse($watch->hasChanged());
        touch(__DIR__ . '/features/' . uniqid(true) . '.txt', time());
        clearstatcache();
        $this->assertNotEquals($watch->hasChanged(), false);
    }
}
