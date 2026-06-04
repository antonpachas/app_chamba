<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_root_redirects_to_spa(): void
    {
        $this->get('/')->assertRedirect('/app');
    }

    public function test_spa_shell_renders(): void
    {
        $this->get('/app')->assertStatus(200)->assertSee('chamba-root', false);
    }

    public function test_spa_catch_all_renders_for_inner_routes(): void
    {
        $this->get('/app/buscar')->assertStatus(200);
        $this->get('/app/acceder')->assertStatus(200);
    }
}
