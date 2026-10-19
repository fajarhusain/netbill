<?php

namespace Tests\Feature;

use Tests\TestCase;

class AccessControlTest extends TestCase
{
    public function test_collector_cannot_access_admin_routes(): void
    {
        $this->actingAsCollector();

        $this->get('/customers')->assertForbidden();
        $this->get('/customers/create')->assertForbidden();
        $this->get('/invoices')->assertForbidden();
        $this->get('/invoices/create')->assertForbidden();
        $this->get('/reports')->assertForbidden();
        $this->get('/users')->assertForbidden();
        $this->get('/audit')->assertForbidden();
    }

    public function test_collector_can_access_shared_routes(): void
    {
        $this->actingAsCollector();

        $this->get('/dashboard')->assertOk();
        $this->get('/scan')->assertOk();
        $this->get('/payments')->assertOk();
        $this->get('/visits')->assertOk();
        $this->get('/visits/create')->assertOk();
        $this->get('/settlements')->assertOk();
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $this->actingAsAdmin();

        $this->get('/customers')->assertOk();
        $this->get('/invoices')->assertOk();
        $this->get('/reports')->assertOk();
        $this->get('/users')->assertOk();
        $this->get('/audit')->assertOk();
    }
}
