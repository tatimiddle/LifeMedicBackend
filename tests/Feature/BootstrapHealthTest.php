<?php

namespace Tests\Feature;

use Tests\TestCase;

class BootstrapHealthTest extends TestCase
{
    public function test_application_boots_and_vite_manifest_exists(): void
    {
        $this->assertFileExists(public_path('build/manifest.json'));
        $this->assertSame('LifeMedic', config('app.name'));
        $this->assertSame('es', config('app.locale'));

        $envExample = file_get_contents(base_path('.env.example'));
        $this->assertIsString($envExample);
        $this->assertStringContainsString('APP_NAME=LifeMedic', $envExample);
        $this->assertStringContainsString('APP_LOCALE=es', $envExample);
    }
}
