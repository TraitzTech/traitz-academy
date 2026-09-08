/**
 * Shared shapes for the Traitz Academy Community (TAC).
 * Mirrors the Eloquent models in app/Models/Tac*.php and CommunityMember.
 */

export interface TacTrack {
    id: number;
    name: string;
    slug: string;
    tagline: string | null;
    description?: string | null;
    icon: string | null;
    accent_color: string | null;
    cover_image: string | null;
    sort_order?: number;
    is_active?: boolean;
    members_count?: number;
    activities_count?: number;
    upcoming_count?: number;
    mentors_count?: number;
    mentors?: TacLeader[];
}

export type TacRoleType =
    | 'lead'
    | 'co_lead'
    | 'secretary'
    | 'technical_lead'
    | 'track_mentor'
    | 'school_lead'
    | 'partnership_lead';

/**
 * Any platform, any label — a person lists whatever they actually use
 * (LinkedIn, X, Instagram, GitHub, a personal site…). The key is the display
 * label, the value is the URL.
 */
export type SocialLinks = Record<string, string | null | undefined>;

export interface TacLeader {
    id: number;
    user_id: number | null;
    community_member_id: number | null;
    name: string;
    slug: string;
    photo_path: string | null;
    role_type: TacRoleType;
    role_title: string | null;
    tac_track_id: number | null;
    school: string | null;
    bio: string | null;
    email: string | null;
    phone: string | null;
    social_links: SocialLinks | null;
    started_on: string | null;
    ended_on: string | null;
    is_active: boolean;
    is_featured: boolean;
    sort_order: number;
    track?: TacTrack | null;
    user?: { id: number; name: string; email: string } | null;
    responsibilities?: TacLeaderResponsibility[];
    performance_reviews?: TacLeaderPerformanceReview[];
}

export type ResponsibilityStatus = 'pending' | 'in_progress' | 'completed';

export interface TacLeaderResponsibility {
    id: number;
    tac_leader_id: number;
    title: string;
    description: string | null;
    status: ResponsibilityStatus;
    due_date: string | null;
    completed_at: string | null;
    assigned_by?: { id: number; name: string } | null;
    created_at: string;
}

export interface TacLeaderPerformanceReview {
    id: number;
    tac_leader_id: number;
    rating: number;
    period_label: string | null;
    notes: string | null;
    reviewed_by?: { id: number; name: string } | null;
    created_at: string;
}

export type TacActivityType =
    | 'event'
    | 'workshop'
    | 'training'
    | 'bootcamp'
    | 'internship'
    | 'handout'
    | 'competition';

export type TacActivityStatus =
    | 'draft'
    | 'published'
    | 'cancelled'
    | 'completed';

export interface TacActivity {
    id: number;
    title: string;
    slug: string;
    type: TacActivityType;
    tac_track_id: number | null;
    program_id: number | null;
    summary: string | null;
    description: string | null;
    cover_image: string | null;
    location_type: 'physical' | 'virtual' | 'hybrid';
    location: string | null;
    meeting_url: string | null;
    starts_at: string | null;
    ends_at: string | null;
    timezone: string;
    is_recurring: boolean;
    recurrence: { frequency?: string; count?: number } | null;
    parent_activity_id: number | null;
    capacity: number | null;
    registration_required: boolean;
    registration_opens_at: string | null;
    registration_closes_at: string | null;
    is_paid: boolean;
    price: number;
    currency: string;
    organizer_leader_id: number | null;
    status: TacActivityStatus;
    published_at: string | null;
    is_featured: boolean;
    outcome_summary: string | null;
    highlights: string[] | null;
    rsvp_count: number;
    track?: TacTrack | null;
    organizer?: TacLeader | null;
    program?: { id: number; title: string; slug?: string } | null;
    media?: TacActivityMedium[];
    occurrences?: TacActivity[];
    competition_criteria?: TacCompetitionCriterion[];
    rsvps_count?: number;
    competition_entries_count?: number;
    occurrences_count?: number;
}

export interface TacActivityMedium {
    id: number;
    tac_activity_id: number;
    path: string;
    caption: string | null;
    sort_order: number;
}

export type RsvpStatus =
    | 'registered'
    | 'confirmed'
    | 'waitlisted'
    | 'attended'
    | 'cancelled'
    | 'no_show';

export interface TacActivityRsvp {
    id: number;
    tac_activity_id: number;
    community_member_id: number;
    status: RsvpStatus;
    payment_status: 'free' | 'pending' | 'paid' | 'failed' | 'refunded';
    amount: number;
    currency: string;
    payment_reference: string | null;
    payment_phone: string | null;
    paid_at: string | null;
    note: string | null;
    checked_in_at: string | null;
    reminded_at: string | null;
    created_at: string;
    activity?: TacActivity;
    member?: CommunityMember;
}

export type MemberCurrentStatus =
    | 'student'
    | 'past_intern'
    | 'tech_enthusiast'
    | 'professional'
    | 'other';

export type MembershipStatus =
    | 'member'
    | 'contributor'
    | 'mentor'
    | 'lead'
    | 'alumni';

export type MemberSource =
    | 'join_form'
    | 'program_application'
    | 'event'
    | 'ai_forge'
    | 'course'
    | 'internship'
    | 'admin'
    | 'import';

export interface CommunityMember {
    id: number;
    user_id: number | null;
    first_name: string;
    last_name: string | null;
    full_name: string;
    email: string;
    phone: string | null;
    school: string | null;
    current_status: MemberCurrentStatus;
    heard_about: string | null;
    bio: string | null;
    avatar_path: string | null;
    social_links: SocialLinks | null;
    source: MemberSource;
    membership_status: MembershipStatus;
    lifecycle_status: 'active' | 'dormant' | 'unsubscribed' | 'blocked';
    engagement_score: number;
    directory_opt_in: boolean;
    email_opt_in: boolean;
    joined_at: string | null;
    welcomed_at: string | null;
    last_engaged_at: string | null;
    admin_notes: string | null;
    tracks?: TacTrack[];
    user?: { id: number; name: string; email: string; role?: string } | null;
}

export interface TacCompetitionCriterion {
    id: number;
    tac_activity_id: number;
    label: string;
    description: string | null;
    max_score: number;
    weight: number;
    sort_order: number;
}

export interface TacCompetitionEntry {
    id: number;
    tac_activity_id: number;
    community_member_id: number;
    title: string;
    description: string | null;
    repo_url: string | null;
    demo_url: string | null;
    video_url: string | null;
    attachment_path: string | null;
    team_name: string | null;
    team_members: string[] | null;
    status: 'draft' | 'submitted' | 'under_review' | 'scored' | 'disqualified';
    submitted_at: string | null;
    total_score: number | null;
    rank: number | null;
    is_winner: boolean;
    award: string | null;
    judge_notes: string | null;
    results_published_at: string | null;
    member?: CommunityMember | null;
    activity?: TacActivity;
}

export interface TacPartner {
    id: number;
    name: string;
    slug: string;
    logo_path: string | null;
    website_url: string | null;
    tier: 'platinum' | 'gold' | 'silver' | 'academic' | 'community';
    description: string | null;
    contact_name: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    partnership_lead_id: number | null;
    started_on: string | null;
    ended_on: string | null;
    is_active: boolean;
    is_featured: boolean;
    sort_order: number;
    partnership_lead?: TacLeader | null;
}

export interface SelectOption {
    value: string;
    label: string;
}

export interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
}
