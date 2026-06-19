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

    #[DataProvider('staticPreviewRoutes')]
    public function test_static_preview_routes_are_available(string $path): void
    {
        $response = $this->get($path);

        $response->assertOk();
        $this->assertNotEmpty($response->getContent());
    }

    public static function staticPreviewRoutes(): array
    {
        return [
            ['/login'],
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
            ['/preview'],
        ];
    }
}
