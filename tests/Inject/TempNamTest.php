<?php

declare(strict_types=1);

namespace Twifty\VirtualFileSystem\Test\Inject;

use PHPUnit\Framework\TestCase;
use Twifty\VirtualFileSystem\Inject\TempNam;
use Twifty\VirtualFileSystem\VirtualFileSystem;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class TempNamTest extends TestCase
{
    /**
     * @var VirtualFileSystem
     */
    protected $vfs;
    
    /**
     * @var TempNam
     */
    protected $tempNam;
    
    public function setUp(): void
    {
        $this->vfs = new VirtualFileSystem([
            'temp' => [],
        ]);
        $this->tempNam = new TempNam($this->vfs->path('/'));
    }
    
    public function testCreatesFile()
    {
        $this->assertSame('tempnam', $this->tempNam->getName());
        
        $tmpFile = ($this->tempNam)($this->vfs->path('/temp'), 'prefix');
        
        $this->assertTrue(is_file($tmpFile));
        $this->assertRegExp('{^vfs[\w\d]+://temp/prefix[\w\d]+$}', $tmpFile);
        
        $tmpFile = ($this->tempNam)($this->vfs->path('/temp'), '');
        
        $this->assertTrue(is_file($tmpFile));
        $this->assertRegExp('{^vfs[\w\d]+://temp/[\w\d]+$}', $tmpFile);
    }
    
    public function testFailsOnMissingDirectory()
    {
        try {
            $tmpFile = ($this->tempNam)($this->vfs->path('/missing'), 'prefix');
            $this->fail('Call should have redirected to global \tempnam() and issued a warning');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\PHPUnit\Framework\Error\Notice::class, $e);
            $this->assertSame('tempnam(): file created in the system\'s temporary directory', $e->getMessage());
        }
    }
}
