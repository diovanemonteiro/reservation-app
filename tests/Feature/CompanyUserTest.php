<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Mail\UserRegistrationInvite;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CompanyUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_company_users_page()
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('companies.users.index', $company->id));

        $response->assertOk();
    }

//    public function test_admin_can_create_user_for_a_company()
//    {
//        $company = Company::factory()->create();
//        $user = User::factory()->admin()->create();
//
//        $response = $this->actingAs($user)->post(route('companies.users.store', $company->id), [
//            'name' => 'test user',
//            'email' => 'test@test.com',
//            'password' => 'password',
//        ]);
//
//        $response->assertRedirect(route('companies.users.index', $company->id));
//
//        $this->assertDatabaseHas('users', [
//            'name' => 'test user',
//            'email' => 'test@test.com',
//        ]);
//    }

    public function test_admin_can_edit_user_for_a_company()
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->put(route('companies.users.update', [$company->id, $user->id]), [
            'name' => 'update user',
            'email' => 'test@update.com',
        ]);

        $response->assertRedirect(route('companies.users.index', $company->id));

        $this->assertDatabaseHas('users', [
            'name' => 'update user',
            'email' => 'test@update.com',
        ]);
    }

    public function test_admin_can_delete_user_for_a_company()
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->delete(route('companies.users.destroy', [$company->id, $user->id]));

        $response->assertRedirect(route('companies.users.index', $company->id));

        // $this->assertDatabaseMissing('users', [
        //     'name' => $user->name,
        //     'company_id' => $company->id,
        // ]);

        $this->assertSoftDeleted($user);
    }

    public function test_company_owner_can_view_his_companies_users()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $secondUser = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get(route('companies.users.index', $company->id));

        $response->assertOk()
            ->assertSeeText($secondUser->name);
    }

    public function test_company_owner_cannot_view_other_companies_users()
    {
        $company = Company::factory()->create();
        $company2 = Company::factory()->create();

        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get(route('companies.users.index', $company2->id));

        $response->assertForbidden();
    }

//    public function test_company_owner_can_create_user_to_his_company()
//    {
//        $company = Company::factory()->create();
//        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
//
//        $response = $this->actingAs($user)->post(route('companies.users.store', $company->id), [
//            'name' => 'test user',
//            'email' => 'test@test.com',
//            'password' => 'password',
//        ]);
//
//        $response->assertRedirect(route('companies.users.index', $company->id));
//
//        $this->assertDatabaseHas('users', [
//            'name' => 'test user',
//            'email' => 'test@test.com',
//            'company_id' => $company->id,
//        ]);
//    }

    public function test_company_owner_cannot_create_user_to_other_company()
    {
        $company = Company::factory()->create();
        $company2 = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->post(route('companies.users.store', $company2->id), [
            'name' => 'test user',
            'email' => 'test@test.com',
            'password' => 'password',
        ]);

        $response->assertForbidden();
    }

    public function test_company_owner_can_edit_user_for_his_company()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->put(route('companies.users.update', [$company->id, $user->id]), [
            'name' => 'updated user',
            'email' => 'test@updated.com',
        ]);

        $response->assertRedirect(route('companies.users.index', $company));

        $this->assertDatabaseHas('users', [
            'name' => 'updated user',
            'email' => 'test@updated.com',
            'company_id' => $company->id,
        ]);
    }

    public function test_company_owner_cannot_edit_user_to_other_company()
    {
        $company = Company::factory()->create();
        $company2 = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->put(route('companies.users.update', [$company2->id, $user->id]), [
            'name' => 'update user',
            'email' => 'test@update.com',
        ]);

        $response->assertForbidden();
    }

    public function test_company_owner_can_delete_user_for_his_company()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->delete(route('companies.users.destroy', [$company->id, $user->id]));

        $response->assertRedirect(route('companies.users.index', $company->id));

        $this->assertSoftDeleted($user);
    }

    public function test_company_owner_can_delete_user_to_other_company()
    {
        $company = Company::factory()->create();
        $company2 = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->delete(route('companies.users.destroy', [$company2->id, $user->id]));

        $response->assertForbidden();
    }

    public function test_admin_can_send_invite_to_user_for_a_company()
    {
        Mail::fake();

        $company = Company::factory()->create();
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post(route('companies.users.store', $company), [
            'email' => 'test@test.com',
        ]);

        Mail::assertSent(UserRegistrationInvite::class);

        $response->assertRedirect(route('companies.users.index', $company));

        $this->assertDatabaseHas('user_invitations', [
            'email' => 'test@test.com',
            'registered_at' => null,
            'company_id' => $company->id,
            'role_id' => Role::COMPANY_OWNER->value,
        ]);
    }

    public function test_invitation_can_be_sent_only_once_for_user()
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->post(route('companies.users.store', $company), [
            'email' => 'test@test.com',
        ]);

        $response = $this->actingAs($user)->post(route('companies.users.store', $company), [
            'email' => 'test@test.com',
        ]);

        $response->assertInvalid(['email' => 'Invitation with this email address already requested.']);
    }

    public function test_company_owner_can_send_invite_to_user()
    {
        Mail::fake();

        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->post(route('companies.users.store', $company), [
            'email' => 'test@test.com',
        ]);

        Mail::assertSent(UserRegistrationInvite::class);

        $response->assertRedirect(route('companies.users.index', $company));

        $this->assertDatabaseHas('user_invitations', [
            'email' => 'test@test.com',
            'registered_at' => null,
            'company_id' => $company->id,
            'role_id' => Role::COMPANY_OWNER->value,
        ]);
    }
}
