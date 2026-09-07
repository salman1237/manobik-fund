<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4PublicCampaignsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_only_lists_public_campaigns(): void
    {
        $published = Campaign::factory()->published()->create(['title' => 'Visible Published Campaign']);
        Campaign::factory()->create(['status' => Campaign::STATUS_DRAFT, 'title' => 'Hidden Draft Campaign']);
        Campaign::factory()->pendingVerification()->create(['title' => 'Hidden Pending Campaign']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Visible Published Campaign');
        $response->assertDontSee('Hidden Draft Campaign');
        $response->assertDontSee('Hidden Pending Campaign');
    }

    public function test_campaigns_can_be_filtered_by_category(): void
    {
        Campaign::factory()->published()->create(['category' => Campaign::CATEGORY_TREATMENT, 'title' => 'A Treatment Case']);
        Campaign::factory()->published()->create(['category' => Campaign::CATEGORY_EDUCATION, 'title' => 'An Education Case']);

        $response = $this->get('/campaigns?category=treatment');

        $response->assertOk();
        $response->assertSee('A Treatment Case');
        $response->assertDontSee('An Education Case');
    }

    public function test_campaigns_can_be_searched_by_title(): void
    {
        Campaign::factory()->published()->create(['title' => 'Help Karim Beat Cancer']);
        Campaign::factory()->published()->create(['title' => 'Flood Relief for Sylhet']);

        $response = $this->get('/campaigns?search=Karim');

        $response->assertOk();
        $response->assertSee('Help Karim Beat Cancer');
        $response->assertDontSee('Flood Relief for Sylhet');
    }

    public function test_a_non_public_campaign_detail_page_returns_404(): void
    {
        $draft = Campaign::factory()->create(['status' => Campaign::STATUS_DRAFT]);

        $response = $this->get(route('campaigns.show', $draft));

        $response->assertNotFound();
    }

    public function test_a_published_campaign_detail_page_shows_progress_and_updates(): void
    {
        $campaign = Campaign::factory()->published()->create([
            'target_amount' => 100000,
            'raised_amount' => 25000,
        ]);
        CampaignUpdate::factory()->create([
            'campaign_id' => $campaign->id,
            'content' => 'Patient responded well to today\'s treatment.',
        ]);

        $response = $this->get(route('campaigns.show', $campaign));

        $response->assertOk();
        $response->assertSee($campaign->title);
        $response->assertSee('25%');
        $response->assertSee("Patient responded well to today's treatment.");
    }
}
