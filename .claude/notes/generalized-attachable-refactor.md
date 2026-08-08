# Generalizing Assignments/Live Classes/Schedule beyond Courses — handoff notes

**Status: implemented and verified, not yet cleaned up.** All backend tests pass (293), `npm run build` succeeds, manual tinker QA confirmed cohort-targeted assignment/live-class/schedule all resolve correctly and surface to enrolled interns.

## Why
User wanted Tasks (Assignments), Live Videos, and Schedule to work for Courses, Internship Cohorts, and Programs — not just Courses. Discussions (lesson-scoped Q&A) and Announcements (doesn't exist as a model) were explicitly ruled out of scope.

## What changed

### Shared foundation
- `app/Concerns/HasAttachable.php` — `morphTo()` trait, used by `Assignment` and `LmsSchedule`.
- `app/Services/RosterResolver.php` — `resolveStudentIds(Model $attachable)`: Course→Enrollment, Cohort→Internship(cohort_id), Program→Internship(program_id).
- `Cohort::studentIds()` / `Program::studentIds()` convenience wrappers.

### Database (all migrations already run in dev)
- `assignments` / `lms_schedules`: added nullable `attachable_type`/`attachable_id` columns + index, backfilled from `course_id`. **`assignments.course_id` was changed to nullable** (follow-up migration `2026_08_06_090300_make_assignments_course_id_nullable.php`) — this was a real bug caught during manual QA; without it, cohort/program-only assignments hard-fail on the NOT NULL constraint.
- `live_classes`: new `live_class_targets` polymorphic pivot table (`live_class_id, target_type, target_id`), backfilled from `live_class_courses`. Old pivot kept for legacy fallback in `LiveClass::resolveAudienceIds()`.
- `audience`/`access_type` DB enum values were **deliberately kept unchanged** (`course_students`, `access_type='course'`) — reinterpreted in app code as "attachable/group audience" rather than literally course-only, to avoid an enum-widening migration.

### Models
- `Assignment`, `LmsSchedule` — `use HasAttachable`, `attachable_type`/`attachable_id` in fillable (alongside legacy `course_id`, kept for now).
- `LiveClass` — `targetRows()`, `resolveAudienceIds()` (unions RosterResolver across all live_class_targets rows, falls back to old `courses()` pivot if empty), `scopeVisibleTo()`/`canUserJoin()` extended with Cohort/Program EXISTS subqueries against `internships`.

### Controllers
- `Lms/AssignmentController.php` — accepts `attachable_type` (course/cohort/program) + `attachable_id` instead of `course_id`. Tutor/supervisor ownership check: Course→`instructor_id`, Cohort/Program→`cohort_program` pivot `supervisor_id`. **No new update/show/destroy actions or AssignmentPolicy were added — that pre-existing gap stays out of scope per explicit user decision.**
- `Lms/ScheduleController.php` — same `attachable_type`/`attachable_id` pattern; `tutorIndex`/`tutorStore`/etc. now also allow `isSupervisor()` users, not just `isTutor()`.
- `Lms/StudentScheduleController.php` — the calendar-merge (`lmsSchedules()`+`assignmentItems()`+`liveClassItems()`+`personalItems()`) now resolves relevance via enrolled courses OR cohort/program internships. **Also fixed a pre-existing bug**: `assignmentItems()` was filtering on `audience === 'course_students'` but Assignment's real enum value is `all_course_students` — this typo meant course-audience assignments never showed on the student calendar. Fixed as part of generalizing this exact line.
- `Admin/LiveClassController.php`, `Tutor/LiveClassController.php` — new shared trait `app/Concerns/SyncsLiveClassTargets.php` (`syncTargets()`, `targetRowsForDisplay()`) writes/reads `live_class_targets` and dual-writes course-type targets into the legacy `live_class_courses` pivot. Tutor controller gained `supervisedCohorts()`/`supervisedPrograms()`/`reachableStudents()` helpers.

### Frontend
- `resources/js/components/assignments/AssignmentManager.vue`, `components/schedules/ScheduleManager.vue` — added a Type selector (Course/Cohort/Program) + dependent group `<select>`.
- `resources/js/components/live-classes/LiveClassForm.vue` — replaced single course checklist with three grouped checklists (Courses/Cohorts/Programs), submits as `targets: [{type, id}]`.
- All 4 LiveClass admin/tutor Create/Edit pages, both Assignment pages, both Schedule pages, and `Lms/Schedules/Index.vue` (student calendar) updated to pass/read the new props (`cohorts`, `programs`, `attachable`/`targets` instead of `course`).

## Deliberately deferred (do NOT do without discussing first)
1. **Cleanup migration**: `course_id` on assignments/lms_schedules and the `live_class_courses` table are still there for fallback/dual-write safety. Plan says drop them in a *separate* migration only "once verified in production" — not yet.
2. **Assignment CRUD gaps**: no `update`/`show`/`destroy` routes, no `AssignmentPolicy` class (auth is inline `abort_unless`). Pre-existing, explicitly left out of scope.
3. Discussions and Announcements were explicitly excluded from this generalization (Discussions is lesson-scoped, doesn't map cleanly; Announcements doesn't exist yet and should eventually reuse this same infra rather than be built separately).

## Full plan file
The original approved plan (with more architectural rationale/rollout ordering) is at:
`C:\Users\Miltech\.claude\plans\mighty-percolating-kahan.md`

## Production DB dry run — DONE (2026-08-06)
User downloaded a SQL dump from the live production database, imported it into a fresh local `traitz-academy` database (not the live server itself), and ran `php artisan migrate`. Succeeded cleanly — confirms the full 90-migration chain (48 production + 42 from this work) applies safely to real production data shapes, not just synthetic dev/test data. Live production itself was never touched.

**Remaining outstanding item before any deploy conversation: rotate the Jitsi private key.** It's still exposed in `origin/lms`'s pushed history on GitHub (removed only from the local `integration/lms-into-main` branch — see the security note earlier in this session). This is on the user's side (their Jitsi/JaaS account) and needs a `git filter-repo`/BFG pass separately if full history scrubbing is wanted.
