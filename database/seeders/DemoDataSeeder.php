<?php

namespace Database\Seeders;

use App\Models\Ambulance;
use App\Models\BloodDonor;
use App\Models\BloodDriveEvent;
use App\Models\BloodRequest;
use App\Models\Campaign;
use App\Models\CampaignUpdate;
use App\Models\Disbursement;
use App\Models\Donation;
use App\Models\FieldVisitReport;
use App\Models\FundUtilization;
use App\Models\RefundRequest;
use App\Models\TreatmentParameter;
use App\Models\User;
use App\Services\Payments\DonationCompletionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Realistic, narratively-consistent demo data for client walkthroughs -
 * NOT part of the default DatabaseSeeder/RefreshDatabase test path (would
 * slow every test run and pollute record-count assertions). Run explicitly:
 *
 *   php artisan db:seed --class=DemoDataSeeder
 *
 * Every campaign status in the verification pipeline is represented at
 * least once, so this doubles as a guided tour of "how the process works"
 * rather than just filler rows.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $staff = $this->makeStaffUsers();
        $seekers = $this->makeSeekerUsers();
        $donors = $this->makeDonorUsers();

        $this->makeCampaigns($seekers, $staff, $donors);
        $this->makeBloodNetwork($staff);
        $this->makeAmbulances($staff);

        $this->command?->info('Demo data seeded: campaigns across every pipeline stage, donations, blood network, and ambulances.');
    }

    /**
     * @return array{verification: User, executive: User, volunteers: User[]}
     */
    private function makeStaffUsers(): array
    {
        $verification = $this->staffUser('Nusrat Jahan', 'nusrat.verification@manobikfund.demo', 'verification_admin');
        $executive = $this->staffUser('Shariful Islam', 'shariful.executive@manobikfund.demo', 'executive_admin');
        $volunteerOne = $this->staffUser('Kamrul Hasan', 'kamrul.volunteer@manobikfund.demo', 'volunteer');
        $volunteerTwo = $this->staffUser('Sabina Yasmin', 'sabina.volunteer@manobikfund.demo', 'volunteer');

        return [
            'verification' => $verification,
            'executive' => $executive,
            'volunteers' => [$volunteerOne, $volunteerTwo],
        ];
    }

    private function staffUser(string $name, string $email, string $role): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => bcrypt('password'), 'email_verified_at' => now()]
        );
        $user->syncRoles([$role]);

        return $user;
    }

    /** @return array<string, User> */
    private function makeSeekerUsers(): array
    {
        $names = [
            'rahim' => 'Rahim Uddin',
            'sylhet_committee' => 'Kamal Hossain',
            'jashim' => 'Jashim Uddin',
            'farhana' => 'Farhana Akter',
            'coastal_volunteers' => 'Nasrin Sultana',
            'rezaul' => 'Rezaul Karim',
            'abdul' => 'Abdul Kader',
        ];

        $seekers = [];

        foreach ($names as $key => $name) {
            $email = Str::slug($name).'@manobikfund.demo';
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => bcrypt('password'), 'email_verified_at' => now()]
            );
            $user->syncRoles(['user']);
            $seekers[$key] = $user;
        }

        return $seekers;
    }

    /** @return User[] */
    private function makeDonorUsers(): array
    {
        $names = ['Nusrat Jahan', 'Jahangir Alam', 'Rina Akter', 'Mahmuda Khatun', 'Shakil Ahmed', 'Sultana Kamal'];
        $donors = [];

        foreach ($names as $index => $name) {
            $email = Str::slug($name).$index.'@manobikfund.demo';
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => bcrypt('password'), 'email_verified_at' => now()]
            );
            $user->syncRoles(['user']);
            $donors[] = $user;
        }

        return $donors;
    }

    /**
     * @param  array<string, User>  $seekers
     * @param  array{verification: User, executive: User, volunteers: User[]}  $staff
     * @param  User[]  $donors
     */
    private function makeCampaigns(array $seekers, array $staff, array $donors): void
    {
        $completion = app(DonationCompletionService::class);

        // 1. Published, actively receiving donations, medical tracking.
        $rahim = Campaign::query()->create([
            'seeker_id' => $seekers['rahim']->id,
            'category' => Campaign::CATEGORY_TREATMENT,
            'title' => "Help Rahim Continue His Chemotherapy",
            'description' => "Rahim, 34, was diagnosed with stage-2 lymphoma in early 2026. He's two cycles into a six-cycle chemotherapy plan at Dhaka Medical College Hospital and responding well - but the remaining treatment, medication, and hospital stays are beyond what his family can cover alone.\n\nManobik Fund volunteers visited the hospital and confirmed Rahim's diagnosis and treatment plan directly with his care team before this campaign went live. Every taka raised is tracked against his actual medical costs.",
            'hospital_name' => 'Dhaka Medical College Hospital',
            'latitude' => 23.7264,
            'longitude' => 90.3971,
            'target_amount' => 500_000_00,
            'raised_amount' => 0,
            'status' => Campaign::STATUS_PUBLISHED,
            'published_at' => now()->subDays(18),
            'deadline' => now()->addMonths(2),
        ]);
        $this->donate($completion, $rahim, [
            [50000, $donors[0], false], [30000, $donors[1], false],
            [75000, null, true], [25000, $donors[2], false], [25000, null, true],
        ]);
        $this->addUpdates($rahim, $staff['volunteers'][0], [
            "Rahim tolerated today's session well - mild fatigue but appetite is holding up. Next bloodwork scheduled Thursday.",
            "Discharged after a 3-day observation stay. Resting at home, back for Cycle 3's second dose next week.",
            "Thank you all - we crossed 40% of the goal this week. Cycle 3 begins Monday.",
        ]);
        $this->addWbcTrend($rahim);

        // 2. Published, emergency category, no medical tracking.
        $flood = Campaign::query()->create([
            'seeker_id' => $seekers['sylhet_committee']->id,
            'category' => Campaign::CATEGORY_EMERGENCY,
            'title' => 'Flood Relief for Char Char Families, Sylhet',
            'description' => "Monsoon flooding has displaced 40+ families in the Char Char area of Sylhet Sadar. Homes are underwater, and the community needs emergency food, clean water, and temporary shelter materials.\n\nA Manobik Fund volunteer visited the affected area and confirmed the scale of displacement directly with the local union council before this campaign was published.",
            'hospital_name' => 'Sylhet Sadar Union Relief Center',
            'latitude' => 24.8949,
            'longitude' => 91.8687,
            'target_amount' => 600_000_00,
            'raised_amount' => 0,
            'status' => Campaign::STATUS_PUBLISHED,
            'published_at' => now()->subDays(9),
            'deadline' => now()->addMonth(),
        ]);
        $this->donate($completion, $flood, [
            [150000, null, true], [80000, $donors[3], false], [60000, $donors[4], false],
            [50000, null, true], [44000, $donors[5], false],
        ]);
        $this->addUpdates($flood, $seekers['sylhet_committee'], [
            'Distributed the first batch of dry food and drinking water to 18 families today. Photos and receipts on file with our volunteer.',
            'Water levels are slowly receding. Focus is shifting to temporary shelter materials for the families whose homes were damaged worst.',
        ]);

        // 3. Funded/completed, full transparency section (disbursement + fund utilization).
        $kidney = Campaign::query()->create([
            'seeker_id' => $seekers['jashim']->id,
            'category' => Campaign::CATEGORY_TREATMENT,
            'title' => 'Kidney Dialysis Support for Amina Begum',
            'description' => "Amina Begum, 58, requires ongoing dialysis twice a week at Chattogram Medical College Hospital following kidney failure diagnosed last year. Her son Jashim has been covering costs alone until now.\n\nThis campaign fully funded within 6 weeks thanks to 40+ donors. Funds have been disbursed directly to the hospital and pharmacy - see the breakdown below.",
            'hospital_name' => 'Chattogram Medical College Hospital',
            'latitude' => 22.3569,
            'longitude' => 91.7832,
            'target_amount' => 500_000_00,
            'raised_amount' => 0,
            'status' => Campaign::STATUS_PUBLISHED,
            'published_at' => now()->subDays(45),
            'deadline' => now()->addDays(20),
        ]);
        $this->donate($completion, $kidney, [
            [80000, $donors[0], false], [65000, null, true], [60000, $donors[1], false],
            [55000, $donors[2], false], [90000, null, true], [70000, $donors[3], false], [80000, $donors[4], false],
        ]);
        $kidney->update(['status' => Campaign::STATUS_COMPLETED]);

        $slip = 'deposit-slips/'.Str::uuid().'.txt';
        Storage::disk('public')->put($slip, "Manobik Fund - Deposit Slip (Demo)\nCampaign: {$kidney->title}\nAmount: BDT 2,05,000\nDate: ".now()->subDays(5)->format('Y-m-d'));
        Disbursement::query()->create([
            'campaign_id' => $kidney->id,
            'amount' => 205000,
            'deposit_slip_file' => $slip,
            'disbursed_by' => $staff['executive']->id,
            'disbursed_at' => now()->subDays(5),
        ]);
        foreach ([
            [FundUtilization::CATEGORY_MEDICATION, 112750, 'Dialysis medication, 6-week supply'],
            [FundUtilization::CATEGORY_ICU, 57400, 'Hospital stay, observation ward'],
            [FundUtilization::CATEGORY_POST_OP, 34850, 'Lab work and diagnostics'],
        ] as [$category, $amount, $description]) {
            FundUtilization::query()->create([
                'campaign_id' => $kidney->id,
                'category' => $category,
                'amount' => $amount,
                'description' => $description,
            ]);
        }

        // 4. Field visit in progress - volunteer assigned, report not yet filed.
        Campaign::query()->create([
            'seeker_id' => $seekers['farhana']->id,
            'category' => Campaign::CATEGORY_EDUCATION,
            'title' => 'Nursing Scholarships for Rural Youth',
            'description' => "Funding nursing school tuition and books for 8 students from Jashore who were accepted into their local nursing college but can't afford the fees.\n\nA volunteer has been assigned and is scheduling a site visit with the college administration.",
            'hospital_name' => 'Jashore Nursing College',
            'latitude' => 23.1667,
            'longitude' => 89.2167,
            'target_amount' => 200_000_00,
            'raised_amount' => 0,
            'status' => Campaign::STATUS_FIELD_VISIT,
            'assigned_volunteer_id' => $staff['volunteers'][0]->id,
            'volunteer_assigned_at' => now()->subDays(2),
            'deadline' => now()->addMonths(3),
        ]);

        // 5. Field-visited, forwarded to executive review.
        $eyeCamp = Campaign::query()->create([
            'seeker_id' => $seekers['coastal_volunteers']->id,
            'category' => Campaign::CATEGORY_CAMP,
            'title' => 'Free Eye Camp for Coastal Villages',
            'description' => "A one-day free eye camp for 5 coastal villages in Patuakhali District - screening, glasses, and referrals for cataract surgery where needed. Organized with a local health NGO.",
            'hospital_name' => 'Patuakhali District Health Complex',
            'latitude' => 22.3596,
            'longitude' => 90.3298,
            'target_amount' => 150_000_00,
            'raised_amount' => 0,
            'status' => Campaign::STATUS_EXECUTIVE_REVIEW,
            'assigned_volunteer_id' => $staff['volunteers'][1]->id,
            'volunteer_assigned_at' => now()->subDays(6),
            'deadline' => now()->addMonths(2),
        ]);
        FieldVisitReport::query()->create([
            'campaign_id' => $eyeCamp->id,
            'volunteer_id' => $staff['volunteers'][1]->id,
            'notes' => 'Confirmed partnership with Patuakhali District Health Complex and the organizing NGO. Village leaders in all 5 target locations confirmed the need - nearest permanent eye clinic is 3+ hours away. Budget breakdown checks out against typical camp costs for this scale.',
            'recommendation' => FieldVisitReport::RECOMMENDATION_APPROVE,
        ]);

        // 6. Just submitted, awaiting volunteer assignment.
        Campaign::query()->create([
            'seeker_id' => $seekers['rezaul']->id,
            'category' => Campaign::CATEGORY_EMERGENCY,
            'title' => "Cyclone Shelter Rebuild, Cox's Bazar",
            'description' => "The community cyclone shelter in Cox's Bazar Sadar was damaged beyond safe use in last season's storms. With cyclone season approaching again, this campaign funds structural repairs so it can serve as emergency shelter for 300+ residents.",
            'hospital_name' => "Cox's Bazar Sadar Union Office",
            'latitude' => 21.4272,
            'longitude' => 92.0058,
            'target_amount' => 800_000_00,
            'raised_amount' => 0,
            'status' => Campaign::STATUS_PENDING_VERIFICATION,
            'deadline' => now()->addMonths(4),
        ]);

        // 7. Draft - a seeker's in-progress, unsubmitted campaign.
        Campaign::query()->create([
            'seeker_id' => $seekers['farhana']->id,
            'category' => Campaign::CATEGORY_CAMP,
            'title' => 'Winter Blanket Drive',
            'description' => 'Basic info saved. Still gathering hospital/beneficiary details before submitting for verification.',
            'hospital_name' => '',
            'target_amount' => 60_000_00,
            'raised_amount' => 0,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        // 8. Rejected - shows the rejection flow with a real reason on record.
        Campaign::query()->create([
            'seeker_id' => $seekers['abdul']->id,
            'category' => Campaign::CATEGORY_TREATMENT,
            'title' => 'Herbal Medicine Clinic Setup',
            'description' => 'Requesting funds to set up a small herbal medicine clinic.',
            'hospital_name' => 'Self-operated clinic (unverified)',
            'target_amount' => 300_000_00,
            'raised_amount' => 0,
            'status' => Campaign::STATUS_REJECTED,
            'rejection_reason' => "Unable to verify a licensed medical affiliation for the claimed facility during the field visit, and the submitted documents did not match hospital records. Doesn't meet the platform's medical-verification requirements - resubmit with documentation from a registered medical institution if this changes.",
        ]);

        // A refund request on record for one of Rahim's donors, to show that flow too.
        $refundDonation = $rahim->donations()->where('user_id', $donors[0]->id)->first();
        if ($refundDonation) {
            RefundRequest::query()->create([
                'donation_id' => $refundDonation->id,
                'user_id' => $donors[0]->id,
                'reason' => 'Donated twice by accident from two different cards.',
                'status' => RefundRequest::STATUS_PENDING,
            ]);
        }
    }

    /**
     * @param  array<array{0: int, 1: ?User, 2: bool}>  $donations  [amountInTaka, donorUserOrNull, isAnonymous]
     */
    private function donate(DonationCompletionService $completion, Campaign $campaign, array $donations): void
    {
        foreach ($donations as [$amountTaka, $donor, $isAnonymous]) {
            $donation = Donation::query()->create([
                'campaign_id' => $campaign->id,
                'user_id' => $donor?->id,
                'donor_name' => $isAnonymous ? 'Anonymous' : ($donor->name ?? 'Guest Donor'),
                'donor_email' => $donor->email ?? 'guest'.random_int(1000, 9999).'@manobikfund.demo',
                'amount' => $amountTaka * 100,
                'currency' => 'BDT',
                // No Faker here on purpose - fakerphp/faker is a require-dev
                // package, absent from production's --no-dev composer install.
                'gateway' => random_int(0, 1) === 0 ? Donation::GATEWAY_SHURJOPAY : Donation::GATEWAY_STRIPE,
                'transaction_id' => (string) Str::uuid(),
                'status' => Donation::STATUS_PENDING,
                'is_anonymous' => $isAnonymous,
            ]);

            $completion->complete($donation);
        }
    }

    private function addUpdates(Campaign $campaign, User $postedBy, array $contents): void
    {
        foreach ($contents as $index => $content) {
            $timestamp = now()->subDays(count($contents) - $index - 1);

            tap(new CampaignUpdate, function (CampaignUpdate $update) use ($campaign, $postedBy, $content, $timestamp) {
                $update->forceFill([
                    'campaign_id' => $campaign->id,
                    'posted_by' => $postedBy->id,
                    'content' => $content,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])->save();
            });
        }
    }

    private function addWbcTrend(Campaign $campaign): void
    {
        $points = [4200, 4800, 5100, 6200, 7300, 8500, 9200];

        foreach ($points as $index => $value) {
            TreatmentParameter::query()->create([
                'campaign_id' => $campaign->id,
                'parameter_type' => TreatmentParameter::TYPE_WBC_COUNT,
                'label' => 'WBC Count',
                'value' => (string) $value,
                'unit' => 'cells/mcL',
                'recorded_at' => now()->subDays(count($points) - $index),
                'is_verified' => true,
            ]);
        }
    }

    /**
     * @param  array{verification: User, executive: User, volunteers: User[]}  $staff
     */
    private function makeBloodNetwork(array $staff): void
    {
        $donorProfiles = [
            ['Kamal Hossain', 'O+', 23.7461, 90.3742, true],
            ['Nusrat Jahan', 'B+', 23.7509, 90.3935, true],
            ['Jahangir Alam', 'A+', 23.7104, 90.4074, false],
            ['Rina Akter', 'AB+', 23.7808, 90.4142, true],
            ['Shakil Ahmed', 'O-', 23.7275, 90.3853, true],
            ['Mahmuda Khatun', 'A-', 23.7644, 90.3891, true],
        ];

        foreach ($donorProfiles as [$name, $group, $lat, $lng, $available]) {
            $email = Str::slug($name).'.blood@manobikfund.demo';
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => bcrypt('password'), 'email_verified_at' => now()]
            );
            $user->syncRoles(['user']);

            BloodDonor::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['blood_group' => $group, 'latitude' => $lat, 'longitude' => $lng, 'is_available' => $available]
            );
        }

        BloodRequest::query()->create([
            'requester_name' => 'Dr. Farida Yasmin',
            'requester_phone' => '01711223344',
            'blood_group' => 'O+',
            'hospital_name' => 'Dhaka Medical College Hospital',
            'urgency' => BloodRequest::URGENCY_CRITICAL,
            'status' => BloodRequest::STATUS_OPEN,
        ]);
        BloodRequest::query()->create([
            'requester_name' => 'Md. Salim Reza',
            'requester_phone' => '01822334455',
            'blood_group' => 'AB-',
            'hospital_name' => 'Square Hospital, Dhaka',
            'urgency' => BloodRequest::URGENCY_URGENT,
            'status' => BloodRequest::STATUS_OPEN,
        ]);

        BloodDriveEvent::query()->create([
            'organized_by' => $staff['volunteers'][0]->id,
            'title' => 'University Campus Blood Drive',
            'description' => 'A one-day blood donation drive open to students and staff, in partnership with the campus health center.',
            'location' => 'Dhaka University Central Field',
            'scheduled_at' => now()->addWeeks(2),
            'status' => BloodDriveEvent::STATUS_UPCOMING,
        ]);
    }

    /**
     * @param  array{verification: User, executive: User, volunteers: User[]}  $staff
     */
    private function makeAmbulances(array $staff): void
    {
        $ambulances = [
            ['Dhaka City Ambulance Service', '01711000001', 'van', 'Dhaka', 23.7806, 90.4074],
            ['Chattogram Emergency Response', '01711000002', 'micro', 'Chattogram', 22.3569, 91.7832],
            ['Sylhet Rescue Ambulance', '01711000003', 'van', 'Sylhet', 24.8949, 91.8687],
            ['Khulna Medical Transport', '01711000004', 'freezer', 'Khulna', 22.8456, 89.5403],
            ["Cox's Bazar Coastal Ambulance", '01711000005', 'van', "Cox's Bazar", 21.4272, 92.0058],
        ];

        foreach ($ambulances as [$name, $phone, $type, $district, $lat, $lng]) {
            Ambulance::query()->firstOrCreate(
                ['name' => $name],
                [
                    'driver_contact' => $phone,
                    'vehicle_type' => $type,
                    'district' => $district,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'is_available' => true,
                    'added_by' => $staff['verification']->id,
                ]
            );
        }
    }
}
