<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        // Halaman utama sengaja redirect ke Data Peminjaman (lihat
        // routes/web.php: Route::redirect('/', '/peminjaman-mess')),
        // bukan halaman 200 mandiri.
        $response->assertRedirect('/peminjaman-mess');
    }
}
