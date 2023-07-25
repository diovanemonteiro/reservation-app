<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_owner_can_view_activities_page()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get(route('companies.activities.index', $company->id));

        $response->assertOk();
    }

    public function test_company_owner_can_see_only_his_companies_activities()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $actitivy = Activity::factory()->create(['company_id' => $company->id]);
        $actitivy2 = Activity::factory()->create();

        $response = $this->actingAs($user)->get(route('companies.activities.index', $company->id));

        $response->assertSeeText($actitivy->name)
            ->assertDontSeeText($actitivy2->name);
    }

    public function test_company_owner_can_create_activity()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create();

        $response = $this->actingAs($user)->post(route('companies.activities.store', $company->id), [
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2023-07-25 10:00',
            'price' => 999,
            'guide_id' => $guide->id,
        ]);

        $response->assertRedirect(route('companies.activities.index', $company->id));

        $this->assertDatabaseHas('activities', [
            'company_id' => $company->id,
            'guide_id' => $guide->id,
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2023-07-25 10:00',
            'price' => 99900,
        ]);
    }

    public function test_can_upload_image()
    {
        Storage::fake('public');

        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create();

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($user)->post(route('companies.activities.store', $company), [
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2023-07-25 10:00',
            'price' => 999,
            'guide_id' => $guide->id,
            'image' => $file,
        ]);

        Storage::disk('public')->assertExists('activities/' . $file->hashName());
        // Storage::disk('public')->assertExists('thumbs/' . $file->hashName());
    }
}
