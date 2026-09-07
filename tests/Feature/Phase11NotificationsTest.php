<?php

namespace Tests\Feature;

use App\Contracts\SmsGateway;
use App\Livewire\Blood\RequestForm;
use App\Livewire\Notifications\NotificationList;
use App\Models\BloodDonor;
use App\Models\BloodRequest;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\FieldVisitReport;
use App\Models\User;
use App\Notifications\CampaignForwardedNotification;
use App\Notifications\CriticalBloodRequestNotification;
use App\Notifications\DonationReceivedNotification;
use App\Services\CampaignVerificationService;
use App\Services\Payments\DonationCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class Phase11NotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_a_donation_notifies_the_campaign_seeker(): void
    {
        Notification::fake();

        $seeker = User::factory()->create();
        $seeker->assignRole('user');
        $campaign = Campaign::factory()->published()->create(['seeker_id' => $seeker->id]);
        $donation = Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'status' => Donation::STATUS_PENDING,
        ]);

        app(DonationCompletionService::class)->complete($donation);

        Notification::assertSentTo($seeker, DonationReceivedNotification::class);
    }

    public function test_forwarding_a_campaign_notifies_the_seeker(): void
    {
        Notification::fake();

        $seeker = User::factory()->create();
        $seeker->assignRole('user');
        $volunteer = User::factory()->create();
        $volunteer->assignRole('volunteer');
        $admin = User::factory()->create();
        $admin->assignRole('verification_admin');

        $campaign = Campaign::factory()->create([
            'status' => Campaign::STATUS_FIELD_VISIT,
            'seeker_id' => $seeker->id,
            'assigned_volunteer_id' => $volunteer->id,
        ]);
        FieldVisitReport::factory()->create(['campaign_id' => $campaign->id, 'volunteer_id' => $volunteer->id]);

        app(CampaignVerificationService::class)->forwardToExecutive($campaign, $admin);

        Notification::assertSentTo($seeker, CampaignForwardedNotification::class);
    }

    public function test_a_critical_blood_request_alerts_matching_available_donors(): void
    {
        Notification::fake();

        $matchingDonorUser = User::factory()->create();
        $matchingDonorUser->assignRole('user');
        BloodDonor::factory()->create(['user_id' => $matchingDonorUser->id, 'blood_group' => 'O-', 'is_available' => true]);

        $wrongGroupUser = User::factory()->create();
        $wrongGroupUser->assignRole('user');
        BloodDonor::factory()->create(['user_id' => $wrongGroupUser->id, 'blood_group' => 'A+', 'is_available' => true]);

        $unavailableUser = User::factory()->create();
        $unavailableUser->assignRole('user');
        BloodDonor::factory()->create(['user_id' => $unavailableUser->id, 'blood_group' => 'O-', 'is_available' => false]);

        Livewire::test(RequestForm::class)
            ->set('requesterName', 'Emergency Requester')
            ->set('requesterPhone', '01700000000')
            ->set('bloodGroup', 'O-')
            ->set('urgency', BloodRequest::URGENCY_CRITICAL)
            ->call('submit');

        Notification::assertSentTo($matchingDonorUser, CriticalBloodRequestNotification::class);
        Notification::assertNotSentTo($wrongGroupUser, CriticalBloodRequestNotification::class);
        Notification::assertNotSentTo($unavailableUser, CriticalBloodRequestNotification::class);
    }

    public function test_a_normal_urgency_blood_request_does_not_alert_donors(): void
    {
        Notification::fake();

        $donorUser = User::factory()->create();
        $donorUser->assignRole('user');
        BloodDonor::factory()->create(['user_id' => $donorUser->id, 'blood_group' => 'B+', 'is_available' => true]);

        Livewire::test(RequestForm::class)
            ->set('requesterName', 'Normal Requester')
            ->set('requesterPhone', '01700000001')
            ->set('bloodGroup', 'B+')
            ->set('urgency', BloodRequest::URGENCY_NORMAL)
            ->call('submit');

        Notification::assertNotSentTo($donorUser, CriticalBloodRequestNotification::class);
    }

    public function test_the_sms_channel_sends_through_the_bound_gateway_using_the_donors_phone(): void
    {
        $sent = [];
        $this->app->instance(SmsGateway::class, new class($sent) implements SmsGateway
        {
            public function __construct(public array &$log) {}

            public function send(string $toPhoneNumber, string $message): void
            {
                $this->log[] = [$toPhoneNumber, $message];
            }
        });

        $donorUser = User::factory()->create(['phone' => '01712345678']);
        $donorUser->assignRole('user');
        BloodDonor::factory()->create(['user_id' => $donorUser->id, 'blood_group' => 'AB+', 'is_available' => true]);

        Livewire::test(RequestForm::class)
            ->set('requesterName', 'Critical Requester')
            ->set('requesterPhone', '01799999999')
            ->set('bloodGroup', 'AB+')
            ->set('urgency', BloodRequest::URGENCY_CRITICAL)
            ->call('submit');

        $sent = $this->app->make(SmsGateway::class)->log;

        $this->assertCount(1, $sent);
        $this->assertSame('01712345678', $sent[0][0]);
        $this->assertStringContainsString('AB+', $sent[0][1]);
    }

    public function test_the_notifications_page_lists_and_marks_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $campaign = Campaign::factory()->published()->create(['seeker_id' => $user->id]);
        $donation = Donation::factory()->create(['campaign_id' => $campaign->id, 'status' => Donation::STATUS_PENDING]);
        app(DonationCompletionService::class)->complete($donation);

        $this->assertSame(1, $user->fresh()->unreadNotifications()->count());

        Livewire::actingAs($user)->test(NotificationList::class)
            ->assertSee('New donation received on your campaign.')
            ->call('markAllAsRead');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_the_notifications_route_renders_for_an_authenticated_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
    }
}
