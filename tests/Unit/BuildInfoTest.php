<?php

namespace Tests\Unit;

use App\Support\BuildInfo;
use Tests\TestCase;

class BuildInfoTest extends TestCase
{
    public function test_build_info_includes_version_from_file(): void
    {
        $info = BuildInfo::toArray();

        $this->assertArrayHasKey('version', $info);
        $this->assertNotSame('', $info['version']);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $info['version']);
    }
}
