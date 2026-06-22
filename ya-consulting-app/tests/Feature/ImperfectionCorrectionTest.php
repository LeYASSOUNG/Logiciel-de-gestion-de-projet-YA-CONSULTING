<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImperfectionCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_access_activity_logs()
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admintest@yaconsulting.sn',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('activity-logs.index'));
        $response->assertStatus(200);
    }

    public function test_collaborator_cannot_access_activity_logs()
    {
        $user = User::create([
            'name' => 'Collab Test',
            'email' => 'collabtest@yaconsulting.sn',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('collaborateur');

        $response = $this->actingAs($user)->get(route('activity-logs.index'));
        $response->assertStatus(403);
    }

    public function test_project_requires_actual_end_date_when_completed()
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admintest@yaconsulting.sn',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $client = Client::create([
            'name' => 'Test Client',
        ]);

        $project = Project::create([
            'name' => 'Test Project',
            'client_id' => $client->id,
            'start_date' => '2026-06-01',
            'planned_end_date' => '2026-06-30',
            'budget' => 100000,
            'budget_labor' => 25000,
            'budget_material' => 25000,
            'budget_transport' => 25000,
            'budget_other' => 25000,
            'status' => 'en_cours',
            'created_by' => $admin->id,
        ]);

        // Attempting to change status to termine without actual_end_date
        $response = $this->actingAs($admin)->put(route('projects.update', $project->id), [
            'name' => 'Test Project Updated',
            'client_id' => $client->id,
            'start_date' => '2026-06-01',
            'planned_end_date' => '2026-06-30',
            'budget_labor' => 25000,
            'budget_material' => 25000,
            'budget_transport' => 25000,
            'budget_other' => 25000,
            'status' => 'termine',
            'actual_end_date' => null,
        ]);

        $response->assertSessionHasErrors('actual_end_date');
    }

    public function test_expense_date_cannot_be_before_project_start_date()
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admintest@yaconsulting.sn',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $client = Client::create(['name' => 'Test Client']);
        $category = ExpenseCategory::first();

        $project = Project::create([
            'name' => 'Test Project',
            'client_id' => $client->id,
            'start_date' => '2026-06-10',
            'planned_end_date' => '2026-06-30',
            'budget' => 100000,
            'budget_labor' => 25000,
            'budget_material' => 25000,
            'budget_transport' => 25000,
            'budget_other' => 25000,
            'status' => 'en_cours',
            'created_by' => $admin->id,
        ]);

        // Attempt to create expense with date 2026-06-05 (before start_date 2026-06-10)
        $response = $this->actingAs($admin)->post(route('expenses.store'), [
            'project_id' => $project->id,
            'category_id' => $category->id,
            'date' => '2026-06-05',
            'amount' => 5000,
            'description' => 'Test Expense',
        ]);

        $response->assertSessionHasErrors('date');
    }

    public function test_expense_date_cannot_be_after_project_actual_end_date()
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admintest@yaconsulting.sn',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $client = Client::create(['name' => 'Test Client']);
        $category = ExpenseCategory::first();

        $project = Project::create([
            'name' => 'Test Project',
            'client_id' => $client->id,
            'start_date' => '2026-06-10',
            'planned_end_date' => '2026-06-30',
            'actual_end_date' => '2026-06-25',
            'budget' => 100000,
            'budget_labor' => 25000,
            'budget_material' => 25000,
            'budget_transport' => 25000,
            'budget_other' => 25000,
            'status' => 'termine',
            'created_by' => $admin->id,
        ]);

        // Attempt to create expense with date 2026-06-28 (after actual_end_date 2026-06-25)
        $response = $this->actingAs($admin)->post(route('expenses.store'), [
            'project_id' => $project->id,
            'category_id' => $category->id,
            'date' => '2026-06-28',
            'amount' => 5000,
            'description' => 'Test Expense',
        ]);

        $response->assertSessionHasErrors('date');
    }

    public function test_user_shares_correct_inertia_notifications()
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admintest@yaconsulting.sn',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $client = Client::create(['name' => 'Test Client']);
        $category = ExpenseCategory::first();

        // 1. Create a project with budget overrun
        $projectOverrun = Project::create([
            'name' => 'Overrun Project',
            'client_id' => $client->id,
            'start_date' => '2026-06-01',
            'planned_end_date' => '2026-06-30',
            'budget' => 1000, // Very low budget to trigger overrun
            'budget_labor' => 250,
            'budget_material' => 250,
            'budget_transport' => 250,
            'budget_other' => 250,
            'status' => 'en_cours',
            'created_by' => $admin->id,
        ]);

        Expense::create([
            'project_id' => $projectOverrun->id,
            'category_id' => $category->id,
            'amount' => 2000,
            'date' => '2026-06-15',
            'description' => 'Overrun Expense',
            'created_by' => $admin->id,
        ]);

        // 2. Create a project with exceeded deadline
        $projectLate = Project::create([
            'name' => 'Late Project',
            'client_id' => $client->id,
            'start_date' => '2026-06-01',
            'planned_end_date' => '2026-06-10', // Exceeded deadline (today is 2026-06-21)
            'budget' => 100000,
            'budget_labor' => 25000,
            'budget_material' => 25000,
            'budget_transport' => 25000,
            'budget_other' => 25000,
            'status' => 'en_cours',
            'created_by' => $admin->id,
        ]);

        // Access dashboard to see Inertia shared props
        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertStatus(200);

        // Assert shared Inertia notifications prop
        $inertiaProps = $response->original->getData()['page']['props'];
        $this->assertArrayHasKey('notifications', $inertiaProps);
        
        $notifications = $inertiaProps['notifications'];
        $this->assertNotEmpty($notifications);

        // Find overrun notification
        $overrunNotif = collect($notifications)->first(fn($n) => $n['id'] === 'budget-' . $projectOverrun->id);
        $this->assertNotNull($overrunNotif);
        $this->assertEquals('warning', $overrunNotif['type']);
        $this->assertStringContainsString('Dépassement de budget', $overrunNotif['title']);

        // Find late notification
        $lateNotif = collect($notifications)->first(fn($n) => $n['id'] === 'deadline-' . $projectLate->id);
        $this->assertNotNull($lateNotif);
        $this->assertEquals('danger', $lateNotif['type']);
        $this->assertStringContainsString('Échéance dépassée', $lateNotif['title']);
    }

    public function test_chef_projet_only_sees_their_own_inertia_notifications()
    {
        $chef1 = User::create([
            'name' => 'Chef One',
            'email' => 'chef1@yaconsulting.sn',
            'password' => bcrypt('password'),
        ]);
        $chef1->assignRole('chef_projet');

        $chef2 = User::create([
            'name' => 'Chef Two',
            'email' => 'chef2@yaconsulting.sn',
            'password' => bcrypt('password'),
        ]);
        $chef2->assignRole('chef_projet');

        $client = Client::create(['name' => 'Test Client']);

        // Chef 1 project - overrun
        $project1 = Project::create([
            'name' => 'Chef 1 Project',
            'client_id' => $client->id,
            'start_date' => '2026-06-01',
            'planned_end_date' => '2026-06-30',
            'budget' => 1000,
            'budget_labor' => 250,
            'budget_material' => 250,
            'budget_transport' => 250,
            'budget_other' => 250,
            'status' => 'en_cours',
            'created_by' => $chef1->id,
        ]);
        
        $category = ExpenseCategory::first();
        Expense::create([
            'project_id' => $project1->id,
            'category_id' => $category->id,
            'amount' => 2000,
            'date' => '2026-06-15',
            'description' => 'Chef 1 Expense',
            'created_by' => $chef1->id,
        ]);

        // Chef 2 project - overrun
        $project2 = Project::create([
            'name' => 'Chef 2 Project',
            'client_id' => $client->id,
            'start_date' => '2026-06-01',
            'planned_end_date' => '2026-06-30',
            'budget' => 1000,
            'budget_labor' => 250,
            'budget_material' => 250,
            'budget_transport' => 250,
            'budget_other' => 250,
            'status' => 'en_cours',
            'created_by' => $chef2->id,
        ]);

        Expense::create([
            'project_id' => $project2->id,
            'category_id' => $category->id,
            'amount' => 2000,
            'date' => '2026-06-15',
            'description' => 'Chef 2 Expense',
            'created_by' => $chef2->id,
        ]);

        // Access dashboard as Chef 1
        $response = $this->actingAs($chef1)->get(route('dashboard'));
        $response->assertStatus(200);

        $inertiaProps = $response->original->getData()['page']['props'];
        $notifications = $inertiaProps['notifications'];

        // Chef 1 should see project 1 notification, but NOT project 2
        $hasProject1Notif = collect($notifications)->contains(fn($n) => $n['id'] === 'budget-' . $project1->id);
        $hasProject2Notif = collect($notifications)->contains(fn($n) => $n['id'] === 'budget-' . $project2->id);

        $this->assertTrue($hasProject1Notif);
        $this->assertFalse($hasProject2Notif);
    }
}
