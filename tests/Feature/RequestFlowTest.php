<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_selecting_process_is_required_to_create_ticket(): void
    {
        if (!Schema::hasTable('tbl_categoria') || !Schema::hasTable('tbl_tickets')) {
            $this->markTestSkipped('Service desk tables are not present.');
        }

        $response = $this->postJson('/api/tickets', [
            'description' => 'Prueba sin categoría',
        ]);

        $response->assertStatus(422);
    }
}

