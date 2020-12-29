<?php

namespace Tests\Feature;

use App\Services\GitlabApi;
use App\Services\HelmApi;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $install = false;
        try {
            $install = true;
        } catch(\Throwable $e) {

        } finally {
            dd($install);
        }
    }
}
