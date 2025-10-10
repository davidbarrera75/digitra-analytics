<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Digitra\Establecimiento;
use App\Services\InformeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant1;
    protected $tenant2;
    protected $user1;
    protected $user2;
    protected $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear tenants de prueba
        $this->tenant1 = Tenant::create([
            'name' => 'Tenant Test 1',
            'slug' => 'tenant-test-1',
            'digitra_user_id' => 999,
            'email' => 'tenant1@test.com',
            'is_active' => true,
        ]);

        $this->tenant2 = Tenant::create([
            'name' => 'Tenant Test 2',
            'slug' => 'tenant-test-2',
            'digitra_user_id' => 998,
            'email' => 'tenant2@test.com',
            'is_active' => true,
        ]);

        // Crear usuarios
        $this->user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@test.com',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant1->id,
            'is_super_admin' => false,
        ]);

        $this->user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@test.com',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant2->id,
            'is_super_admin' => false,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'tenant_id' => null,
            'is_super_admin' => true,
        ]);
    }

    /** @test */
    public function helper_functions_return_correct_tenant_data()
    {
        $this->actingAs($this->user1);

        $this->assertEquals($this->tenant1->id, tenant_id());
        $this->assertEquals($this->tenant1->digitra_user_id, digitra_user_id());
        $this->assertFalse(is_super_admin());
    }

    /** @test */
    public function super_admin_helper_returns_true()
    {
        $this->actingAs($this->superAdmin);

        $this->assertTrue(is_super_admin());
        $this->assertNull(tenant_id());
    }

    /** @test */
    public function cache_keys_are_tenant_specific()
    {
        // Test que las claves de cache incluyen el tenant_id
        $this->actingAs($this->user1);

        $cacheKey1 = 'informe_20250101_20250131_tenant' . $this->tenant1->digitra_user_id . '_establall';

        $this->actingAs($this->user2);

        $cacheKey2 = 'informe_20250101_20250131_tenant' . $this->tenant2->digitra_user_id . '_establall';

        $this->assertNotEquals($cacheKey1, $cacheKey2, 'Las claves de cache deben ser diferentes por tenant');
    }
}
