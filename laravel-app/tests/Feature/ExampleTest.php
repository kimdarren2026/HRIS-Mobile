<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_login_route_is_public_and_preview_redirects_guest(): void
    {
        $this->get('/login')->assertOk()->assertSee('Hadir');
        $this->get('/preview')->assertRedirect('/login');
    }

    #[DataProvider('protectedStaticRoutes')]
    public function test_protected_static_routes_redirect_guests_to_login(string $path): void
    {
        $response = $this->get($path);

        $response->assertRedirect('/login');
    }

    public static function protectedStaticRoutes(): array
    {
        return [
            ['/employee/dashboard'],
            ['/attendance/checkin'],
            ['/attendance/checkin-outside'],
            ['/attendance/history'],
            ['/leave/request'],
            ['/leave/history'],
            ['/payslip/detail'],
            ['/hr/approval-queue'],
            ['/hr/employees'],
            ['/payroll/periods'],
            ['/reports'],
            ['/profile'],
            ['/admin/dashboard'],
            ['/finance/dashboard'],
            ['/settings'],
        ];
    }
}
