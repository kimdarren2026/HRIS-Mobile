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

    public function test_login_and_preview_routes_are_public(): void
    {
        $this->get('/login')->assertOk()->assertSee('HRIS Mobile App');
        $this->get('/preview')->assertOk()->assertSee('HRIS Mobile App');
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
