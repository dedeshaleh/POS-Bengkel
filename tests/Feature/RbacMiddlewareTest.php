<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test menu and role
        $this->menu = Menu::create([
            'name' => 'Test Menu',
            'url' => '/test-rbac',
            'sort_order' => 1,
        ]);

        $this->adminRole = Role::create(['name' => 'Admin Test']);
        $this->cashierRole = Role::create(['name' => 'Cashier Test']);

        // Admin: full access
        RolePermission::create([
            'role_id' => $this->adminRole->id,
            'menu_id' => $this->menu->id,
            'can_read' => true,
            'can_create' => true,
            'can_update' => true,
            'can_delete' => true,
        ]);

        // Cashier: read only
        RolePermission::create([
            'role_id' => $this->cashierRole->id,
            'menu_id' => $this->menu->id,
            'can_read' => true,
            'can_create' => false,
            'can_update' => false,
            'can_delete' => false,
        ]);
    }

    public function test_admin_can_read_menu(): void
    {
        $user = User::factory()->create(['role_id' => $this->adminRole->id]);
        $user->roles()->sync([$this->adminRole->id]);

        // We test the middleware directly via a temporary route
        $this->actingAs($user)
            ->get('/test-rbac')
            ->assertOk();
    }

    public function test_cashier_can_read_but_not_create(): void
    {
        $user = User::factory()->create(['role_id' => $this->cashierRole->id]);
        $user->roles()->sync([$this->cashierRole->id]);

        // GET should be allowed (can_read = true)
        $this->actingAs($user)
            ->get('/test-rbac')
            ->assertOk();

        // POST should be denied (can_create = false)
        $this->actingAs($user)
            ->post('/test-rbac')
            ->assertForbidden();
    }

    public function test_user_without_permission_is_denied(): void
    {
        $noAccessRole = Role::create(['name' => 'No Access']);
        $user = User::factory()->create(['role_id' => $noAccessRole->id]);
        $user->roles()->sync([$noAccessRole->id]);

        $this->actingAs($user)
            ->get('/test-rbac')
            ->assertForbidden();
    }
}
