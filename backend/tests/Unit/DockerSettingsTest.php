<?php

namespace Tests\Unit;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Console\ServeCommand;
use Tests\TestCase;

class DockerSettingsTest extends TestCase
{
    public function test_docker_settings_reach_the_website_through_the_development_server(): void
    {
        foreach (AppServiceProvider::DOCKER_SETTINGS as $name) {
            $this->assertContains($name, ServeCommand::$passthroughVariables);
        }
    }
}
