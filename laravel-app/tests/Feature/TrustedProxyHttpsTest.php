<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 58B: bootstrap/app.php now calls $middleware->trustProxies(at: '*', ...)
 * so that Laravel honors X-Forwarded-Proto/Host/Port from the Coolify reverse
 * proxy in front of staging. Without this, url()/route() build links from the
 * plain HTTP request the proxy makes internally, producing insecure form
 * actions even though the browser is on HTTPS.
 */
class TrustedProxyHttpsTest extends TestCase
{
    use RefreshDatabase;

    private function forwardedHttpsHeaders(): array
    {
        return [
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host'  => 'staging.hrismobile.my.id',
            'X-Forwarded-Port'  => '443',
        ];
    }

    public function test_login_form_action_is_https_when_request_is_forwarded_from_https_proxy(): void
    {
        $response = $this->withHeaders($this->forwardedHttpsHeaders())->get('/login');

        $response->assertOk();
        $response->assertSee('https://staging.hrismobile.my.id/login', false);
    }

    public function test_login_form_action_does_not_leak_plain_http_when_behind_https_proxy(): void
    {
        $response = $this->withHeaders($this->forwardedHttpsHeaders())->get('/login');

        $response->assertOk();
        $response->assertDontSee('http://staging.hrismobile.my.id/login', false);
    }

    public function test_guest_redirect_to_login_uses_https_when_request_is_forwarded_from_https_proxy(): void
    {
        $response = $this->withHeaders($this->forwardedHttpsHeaders())->get('/employee/dashboard');

        $response->assertRedirect();
        $this->assertStringStartsWith(
            'https://staging.hrismobile.my.id/login',
            $response->headers->get('Location')
        );
    }

    public function test_local_request_without_forwarded_headers_is_not_forced_onto_staging_domain(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('staging.hrismobile.my.id', false);
    }
}
