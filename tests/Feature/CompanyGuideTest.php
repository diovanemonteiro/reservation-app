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

class CompanyGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_owner_can_view_his_companies_guides()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $secondUser = User::factory()->guide()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get(route('companies.guides.index', $company->id));

        $response->assertOk()
            ->assertSeeText($secondUser->name);
    }

    public function test_company_owner_cannot_view_other_companies_guides()
    {
        $company = Company::factory()->create();
        $company2 = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get(route('companies.guides.index', $company2->id));

        $response->assertForbidden();
    }

//    public function test_company_owner_can_create_guide_for_his_company()
//    {
//        $company = Company::factory()->create();
//        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
//
//        $response = $this->actingAs($user)->post(route('companies.guides.store', $company->id), [
//            'name' => 'test guide',
//            'email' => 'guide@test.com',
//            'password' => 'password',
//        ]);
//
//        $response->assertRedirect(route('companies.guides.index', $company->id));
//
//        $this->assertDatabaseHas('users', [
//            'name' => 'test guide',
//            'email' => 'guide@test.com',
//            'company_id' => $company->id,
//        ]);
//    }

    public function test_company_owner_cannot_create_guide_to_other_company()
    {
        $company = Company::factory()->create();
        $company2 = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->post(route('companies.guides.store', $company2->id), [
            'name' => 'test guide',
            'email' => 'guide@test.com',
            'password' => 'password',
        ]);

        $response->assertForbidden();
    }

    public function test_company_owner_can_edit_guide_for_his_company()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->put(route('companies.guides.update', [$company->id, $guide->id]), [
            'name' => 'updated guide',
            'email' => 'guide@updated.com',
        ]);

        $response->assertRedirect(route('companies.guides.index', $company->id));

        $this->assertDatabaseHas('users', [
            'name' => 'updated guide',
            'email' => 'guide@updated.com',
            'company_id' => $company->id,
        ]);
    }

    public function test_company_owner_cannot_edit_guide_for_other_company()
    {
        $company = Company::factory()->create();
        $company2 = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->put(route('companies.guides.update', [$company2->id, $guide->id]), [
            'name' => 'updated guide',
            'email' => 'guide@updated.com',
        ]);

        $response->assertForbidden();
    }

    public function test_company_owner_can_delete_guide_for_his_company()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->delete(route('companies.guides.destroy', [$company->id, $guide->id]));

        $response->assertRedirect(route('companies.guides.index', $company->id));

        $this->assertSoftDeleted($guide);
    }

    public function test_company_owner_cannot_delete_guide_for_other_company()
    {
        $company = Company::factory()->create();
        $company2 = Company::factory()->create();

        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create(['company_id' => $company2->id]);

        $response = $this->actingAs($user)->delete(route('companies.guides.destroy', [$company2->id, $guide->id]));

        $response->assertForbidden();
    }

    public function test_company_owner_can_send_invite_to_guide_to_his_company()
    {
        Mail::fake();

        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->post(route('companies.guides.store', $company), [
            'email' => 'guide@test.com',
        ]);

        Mail::assertSent(UserRegistrationInvite::class);

        $response->assertRedirect(route('companies.guides.index', $company));

        $this->assertDatabaseHas('user_invitations', [
            'email' => 'guide@test.com',
            'registered_at' => null,
            'company_id' => $company->id,
            'role_id' => Role::GUIDE->value,
        ]);
    }

    public function test_invitation_can_be_sent_only_once_for_user()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $this->actingAs($user)->post(route('companies.guides.store', $company), [
            'email' => 'guide@test.com',
        ]);

        $response = $this->actingAs($user)->post(route('companies.guides.store', $company), [
            'email' => 'guide@test.com',
        ]);

        $response->assertInvalid(['email' => 'Invitation with this email address already requested.']);
    }
}
