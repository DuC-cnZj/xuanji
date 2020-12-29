<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $arr = [
            [
                'project' => 1,
                'file'    => '.env',
            ],
        ];
        dd(collect($arr)->keyBy->project->get('2')['file']);

        $this->assertTrue(true);
    }
}
