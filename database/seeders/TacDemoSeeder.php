<?php

namespace Database\Seeders;

use App\Models\CommunityMember;
use App\Models\TacActivity;
use App\Models\TacCompetitionCriterion;
use App\Models\TacLeader;
use App\Models\TacPartner;
use App\Models\TacTrack;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Sample TAC content for development and for previewing the community.
 *
 * Leadership is the real TAC roster (as supplied by the community team), not
 * placeholder data — everything else (members, activities, partners) is
 * filler so the platform has something to look at while real content is
 * loaded through the admin panel.
 *
 * Run with:  php artisan db:seed --class=TacDemoSeeder
 * Idempotent: records are keyed by name/slug/email, so re-running updates
 * rather than duplicates. Not wired into DatabaseSeeder — production should
 * never get filler members or activities.
 */
class TacDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tracks = TacTrack::query()->ordered()->get()->keyBy('slug');

        if ($tracks->isEmpty()) {
            $this->command?->warn('No TAC tracks found — run migrations first.');

            return;
        }

        $this->clearPlaceholderLeaders();

        $leaders = $this->seedLeaders($tracks);
        $this->seedPartners();
        $this->seedMembers($tracks);
        $this->seedActivities($tracks, $leaders);

        $this->command?->info('TAC demo content seeded.');
    }

    /**
     * Remove the earlier fictional roster this seeder used to create, so
     * re-running it against a database seeded before the real team's
     * details were available doesn't leave invented people behind.
     */
    private function clearPlaceholderLeaders(): void
    {
        TacLeader::query()
            ->where('email', 'like', '%@traitz.tech')
            ->orWhere('name', 'Franklin Oben')
            ->delete();
    }

    /**
     * @return \Illuminate\Support\Collection<string, TacLeader>
     */
    private function seedLeaders($tracks)
    {
        $roster = [
            [
                'name' => 'Nyanga Piethras Ekwendi',
                'role_type' => TacLeader::ROLE_LEAD,
                'bio' => 'As the Community Lead, I have as responsibility to ensure the smooth operation and growth of the Community, its Leads, and members as well. I have foundational knowledge on Leadership and I believe with that and as I learn more, we can build a sustainable Community.',
                'email' => 'nyangapiethras2@gmail.com',
                'is_featured' => true,
                'social_links' => [
                    'LinkedIn' => 'https://www.linkedin.com/in/nyangapiethras',
                    'X' => 'https://x.com/nyangap558',
                    'Instagram' => 'https://www.instagram.com/nyangapiethras',
                ],
            ],
            [
                'name' => 'Nkwain Blaise Ngam',
                'role_type' => TacLeader::ROLE_CO_LEAD,
                'role_title' => 'Assistant Community Lead',
                'bio' => 'Nkwain Blaise Ngam is a mobile app developer, community builder, mentor, and technology enthusiast passionate about using technology and leadership to create meaningful opportunities for others. He has contributed to and led initiatives across technology, mentorship, and youth development, with a growing interest in building products, empowering communities, and helping young people turn ideas into practical impact.',
                'is_featured' => true,
                'social_links' => [
                    'LinkedIn' => 'https://www.linkedin.com/in/nkwain-blaise-ngam/',
                    'X' => 'https://x.com/degentlegiant',
                    'Instagram' => 'https://www.instagram.com/thegentlegiant54/',
                ],
            ],
            [
                'name' => 'Ndifon Melvin Konteng',
                'role_type' => TacLeader::ROLE_SECRETARY,
                'role_title' => 'Media and Communications Lead',
                'bio' => 'I’m a mobile app developer and a content creator with a keen interest in technology and creativity. Outside of work, I’m a football lover who enjoys watching and talking about the beautiful game.',
                'email' => 'melvinkonteng@gmail.com',
                'social_links' => [
                    'LinkedIn' => 'https://www.linkedin.com/in/ndifon-melvin-k-86683131a',
                ],
            ],
            [
                'name' => 'Enow Franky-Reinhard',
                'role_type' => TacLeader::ROLE_TRACK_MENTOR,
                'role_title' => 'Mobile Dev Lead',
                'track' => 'mobile-app-development',
                'bio' => 'I lead the mobile development track at TAC, where I guide members in building real world mobile apps using Flutter. My focus is on practical learning, clean code and helping the team ship.',
                'email' => 'awankome@gmail.com',
            ],
            [
                'name' => 'Abongnui Mc-Elmer A.',
                'role_type' => TacLeader::ROLE_TRACK_MENTOR,
                'role_title' => 'Frontend Co-Lead',
                'track' => 'web-development',
                'bio' => 'Computer Engineering student and frontend co-lead at TAC.',
                'email' => 'abongnui381@gmail.com',
                'phone' => '+237653146158',
            ],
            [
                'name' => 'Roberta Musi-Sambat Nkweti',
                'role_type' => TacLeader::ROLE_SCHOOL_LEAD,
                'role_title' => 'Founding Lead',
                'school' => 'NAHPI',
                'bio' => 'I am the Founding Lead at Traitz Academy, where I am committed to helping young people develop practical skills and gain meaningful real-world experience. I am passionate about creating opportunities that empower young people to confidently prepare for and launch their careers.',
                'social_links' => [
                    'LinkedIn' => 'https://www.linkedin.com/in/roberta-musi-sambat-60272a354',
                ],
            ],
        ];

        $leaders = collect();

        foreach ($roster as $index => $row) {
            $leader = TacLeader::query()->updateOrCreate(
                ['name' => $row['name'], 'role_type' => $row['role_type']],
                [
                    'role_title' => $row['role_title'] ?? null,
                    'tac_track_id' => isset($row['track']) ? $tracks[$row['track']]->id : null,
                    'school' => $row['school'] ?? null,
                    'bio' => $row['bio'] ?? null,
                    'email' => $row['email'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'social_links' => $row['social_links'] ?? null,
                    'started_on' => now()->subMonths(rand(1, 10))->toDateString(),
                    'is_active' => true,
                    'is_featured' => $row['is_featured'] ?? false,
                    'sort_order' => $index,
                ],
            );

            $leaders->put($row['name'], $leader);
        }

        return $leaders;
    }

    private function seedPartners(): void
    {
        foreach ([
            ['name' => 'Orange Cameroun', 'tier' => 'platinum', 'description' => 'Connectivity partner supporting TAC workshops across campuses.'],
            ['name' => 'MTN Cameroon', 'tier' => 'gold', 'description' => 'Sponsors the annual TAC bootcamp series.'],
            ['name' => 'University of Buea', 'tier' => 'academic', 'description' => 'Hosts TAC meetups and provides lab space for hardware sessions.'],
            ['name' => 'Silicon Mountain Hub', 'tier' => 'community', 'description' => 'Community partner and co-working host for member sessions.'],
        ] as $index => $row) {
            TacPartner::query()->updateOrCreate(
                ['slug' => Str::slug($row['name'])],
                [
                    ...$row,
                    'is_active' => true,
                    'is_featured' => $index < 2,
                    'sort_order' => $index,
                    'started_on' => now()->subMonths(rand(3, 20))->toDateString(),
                ],
            );
        }
    }

    private function seedMembers($tracks): void
    {
        $schools = [
            'University of Buea',
            'ICT University',
            'University of Bamenda',
            'Catholic University Institute of Buea',
            'University of Douala',
        ];

        $names = [
            ['Ngozi', 'Eze'], ['Kwame', 'Asante'], ['Amina', 'Boateng'],
            ['Chidi', 'Okafor'], ['Fatou', 'Diallo'], ['Tunde', 'Adeyemi'],
            ['Zainab', 'Musa'], ['Kofi', 'Mensah'], ['Adaeze', 'Nwosu'],
            ['Ibrahim', 'Sow'], ['Nadia', 'Kamga'], ['Eric', 'Tamfu'],
            ['Blaise', 'Nkemtaji'], ['Sylvie', 'Atangana'], ['Marcel', 'Ondoa'],
            ['Larissa', 'Mbeng'], ['Patrick', 'Ewane'], ['Odette', 'Fokou'],
        ];

        $sources = [
            CommunityMember::SOURCE_JOIN_FORM,
            CommunityMember::SOURCE_PROGRAM_APPLICATION,
            CommunityMember::SOURCE_EVENT,
            CommunityMember::SOURCE_AI_FORGE,
            CommunityMember::SOURCE_COURSE,
        ];

        $trackIds = $tracks->pluck('id')->all();

        foreach ($names as $index => [$first, $last]) {
            $member = CommunityMember::query()->updateOrCreate(
                ['email' => Str::lower("{$first}.{$last}@example.com")],
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'phone' => '+2376'.rand(70000000, 99999999),
                    'school' => $schools[$index % count($schools)],
                    'current_status' => $index % 5 === 0
                        ? CommunityMember::STATUS_PAST_INTERN
                        : CommunityMember::STATUS_STUDENT,
                    'source' => $sources[$index % count($sources)],
                    'membership_status' => $index % 7 === 0
                        ? CommunityMember::MEMBERSHIP_CONTRIBUTOR
                        : CommunityMember::MEMBERSHIP_MEMBER,
                    'directory_opt_in' => $index % 3 !== 0,
                    'engagement_score' => rand(0, 25),
                    'bio' => 'Building things and learning fast.',
                    'joined_at' => now()->subDays(rand(1, 330)),
                ],
            );

            $member->tracks()->syncWithoutDetaching([
                $trackIds[$index % count($trackIds)] => ['is_primary' => true],
                $trackIds[($index + 3) % count($trackIds)] => ['is_primary' => false],
            ]);
        }
    }

    private function seedActivities($tracks, $leaders): void
    {
        $rows = [
            [
                'title' => 'Build your first Laravel API',
                'type' => TacActivity::TYPE_WORKSHOP,
                'track' => 'web-development',
                'mentor' => 'Abongnui Mc-Elmer A.',
                'summary' => 'A hands-on session taking you from an empty project to a working, authenticated REST API.',
                'days' => 9,
                'capacity' => 40,
            ],
            [
                'title' => 'Figma to production: a designer–developer handoff',
                'type' => TacActivity::TYPE_WORKSHOP,
                'track' => 'ui-ux-graphic-design',
                'summary' => 'How to hand a design over so it actually gets built the way you drew it.',
                'days' => 16,
                'capacity' => 30,
            ],
            [
                'title' => 'Machine Learning Bootcamp — 4 weeks',
                'type' => TacActivity::TYPE_BOOTCAMP,
                'track' => 'ai-machine-learning',
                'summary' => 'Four intensive weeks from linear regression to deploying a model behind an API.',
                'days' => 30,
                'capacity' => 25,
                'paid' => 15000,
            ],
            [
                'title' => 'Securing a small business network',
                'type' => TacActivity::TYPE_TRAINING,
                'track' => 'networking-cybersecurity',
                'summary' => 'Practical hardening, monitoring and incident response for real networks.',
                'days' => 23,
                'capacity' => 35,
            ],
            [
                'title' => 'TAC Monthly Meetup',
                'type' => TacActivity::TYPE_EVENT,
                'track' => null,
                'mentor' => 'Nyanga Piethras Ekwendi',
                'summary' => 'Our recurring community night — lightning talks, demos and introductions.',
                'days' => 5,
                'recurring' => ['frequency' => 'monthly', 'count' => 6],
            ],
            [
                'title' => 'IoT starter kit handout',
                'type' => TacActivity::TYPE_HANDOUT,
                'track' => 'arduino-iot',
                'summary' => 'Collect an Arduino starter kit and the project brief that goes with it.',
                'days' => 12,
                'capacity' => 20,
            ],
        ];

        foreach ($rows as $row) {
            $starts = now()->addDays($row['days'])->setTime(14, 0);

            $activity = TacActivity::query()->updateOrCreate(
                ['slug' => Str::slug($row['title'])],
                [
                    'title' => $row['title'],
                    'type' => $row['type'],
                    'tac_track_id' => $row['track'] ? $tracks[$row['track']]->id : null,
                    'summary' => $row['summary'],
                    'description' => '<p>'.$row['summary'].'</p><p>Bring a laptop. Everything else is provided.</p>',
                    'location_type' => 'physical',
                    'location' => 'Traitz Academy, Molyko, Buea',
                    'starts_at' => $starts,
                    'ends_at' => $starts->copy()->addHours(3),
                    'capacity' => $row['capacity'] ?? null,
                    'is_paid' => isset($row['paid']),
                    'price' => $row['paid'] ?? 0,
                    'is_recurring' => isset($row['recurring']),
                    'recurrence' => $row['recurring'] ?? null,
                    'organizer_leader_id' => isset($row['mentor']) ? $leaders->get($row['mentor'])?->id : null,
                    'status' => TacActivity::STATUS_PUBLISHED,
                    'published_at' => now(),
                    'is_featured' => $row['title'] === 'Build your first Laravel API',
                ],
            );

            $this->attachRsvps($activity);
        }

        // A past activity, so the archive is not empty.
        $past = TacActivity::query()->updateOrCreate(
            ['slug' => 'intro-to-git-and-github'],
            [
                'title' => 'Intro to Git and GitHub',
                'type' => TacActivity::TYPE_WORKSHOP,
                'tac_track_id' => $tracks['web-development']->id,
                'summary' => 'Version control from zero — branches, merges and pull requests.',
                'location_type' => 'physical',
                'location' => 'University of Buea, ICT Block',
                'starts_at' => now()->subDays(21)->setTime(14, 0),
                'ends_at' => now()->subDays(21)->setTime(17, 0),
                'status' => TacActivity::STATUS_COMPLETED,
                'published_at' => now()->subDays(40),
                'outcome_summary' => '38 members attended. Every one of them left with a repository pushed and a pull request opened — several have kept contributing since.',
                'organizer_leader_id' => $leaders->get('Abongnui Mc-Elmer A.')?->id,
            ],
        );
        $this->attachRsvps($past, attended: true);

        // A competition, complete with its judging rubric.
        $competition = TacActivity::query()->updateOrCreate(
            ['slug' => 'tac-build-challenge'],
            [
                'title' => 'TAC Build Challenge',
                'type' => TacActivity::TYPE_COMPETITION,
                'tac_track_id' => null,
                'summary' => 'Build something useful for your campus in three weeks. Any track, any stack.',
                'description' => '<p>Ship a working project that solves a real problem on your campus. Solo or in teams of up to four.</p>',
                'location_type' => 'hybrid',
                'location' => 'Traitz Academy, Buea',
                'meeting_url' => 'https://meet.google.com/tac-build-challenge',
                'starts_at' => now()->addDays(14)->setTime(9, 0),
                'ends_at' => now()->addDays(35)->setTime(18, 0),
                'registration_closes_at' => now()->addDays(12),
                'status' => TacActivity::STATUS_PUBLISHED,
                'published_at' => now(),
                'organizer_leader_id' => $leaders->get('Nkwain Blaise Ngam')?->id,
            ],
        );

        foreach ([
            ['label' => 'Problem fit', 'description' => 'Does it solve a real problem people actually have?', 'max_score' => 10, 'weight' => 3],
            ['label' => 'Technical execution', 'description' => 'Code quality, architecture and whether it actually works.', 'max_score' => 10, 'weight' => 3],
            ['label' => 'Design & usability', 'description' => 'Can somebody use it without being shown how?', 'max_score' => 10, 'weight' => 2],
            ['label' => 'Presentation', 'description' => 'Clarity of the demo and the write-up.', 'max_score' => 10, 'weight' => 1],
        ] as $index => $criterion) {
            TacCompetitionCriterion::query()->updateOrCreate(
                ['tac_activity_id' => $competition->id, 'label' => $criterion['label']],
                [...$criterion, 'sort_order' => $index],
            );
        }

        $this->attachRsvps($competition);
    }

    private function attachRsvps(TacActivity $activity, bool $attended = false): void
    {
        $members = CommunityMember::query()->inRandomOrder()->take(rand(4, 12))->get();

        foreach ($members as $member) {
            $activity->rsvps()->updateOrCreate(
                ['community_member_id' => $member->id],
                [
                    'status' => $attended ? 'attended' : 'registered',
                    'payment_status' => $activity->is_paid ? 'pending' : 'free',
                    'amount' => $activity->is_paid ? $activity->price : 0,
                    'currency' => $activity->currency,
                    'checked_in_at' => $attended ? $activity->starts_at : null,
                ],
            );
        }

        $activity->syncRsvpCount();
    }
}
