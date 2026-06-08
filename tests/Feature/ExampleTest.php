<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_login_page_renders_mercatoria_branding(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Mercatoria')
            ->assertDontSee('Kabus');
    }

    public function test_public_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_public_brand_documents_are_available(): void
    {
        $this->get('/pgp-key')->assertOk();
        $this->assertStringContainsString(
            'Mercatoria Monero Marketplace Script',
            file_get_contents(storage_path('app/public/pgp_key.txt'))
        );

        $this->get('/canary')->assertOk();
        $this->assertStringContainsString(
            'Mercatoria Monero Marketplace Script',
            file_get_contents(storage_path('app/public/canary.txt'))
        );
    }
}
