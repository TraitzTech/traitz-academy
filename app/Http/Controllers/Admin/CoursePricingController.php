<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseInstalmentPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoursePricingController extends Controller
{
    public function show(Course $course): Response
    {
        $course->load([
            'instructor:id,name',
            'category:id,name,slug',
            'instalmentPlans' => fn ($q) => $q->orderBy('id'),
        ]);

        return Inertia::render('Admin/Courses/Pricing', [
            'course' => $course,
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'is_featured' => ['boolean'],
        ]);

        $sale = $validated['sale_price'] !== null && $validated['sale_price'] !== ''
            ? (float) $validated['sale_price']
            : null;

        if ($sale !== null && $sale >= (float) $validated['price']) {
            return back()->withErrors(['sale_price' => 'Sale price must be less than the regular price.']);
        }

        $course->update([
            'price' => $validated['price'],
            'sale_price' => $sale,
            'is_featured' => $request->boolean('is_featured'),
        ]);

        return back()->with('success', 'Course pricing updated.');
    }

    public function storePlan(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'number_of_instalments' => ['required', 'integer', 'min:2', 'max:48'],
            'amount_per_instalment' => ['required', 'numeric', 'min:0'],
            'interval_in_days' => ['required', 'integer', 'min:1', 'max:365'],
            'is_active' => ['boolean'],
        ]);

        $course->instalmentPlans()->create([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Installment plan added.');
    }

    public function updatePlan(Request $request, Course $course, CourseInstalmentPlan $plan): RedirectResponse
    {
        abort_unless($plan->course_id === $course->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'number_of_instalments' => ['required', 'integer', 'min:2', 'max:48'],
            'amount_per_instalment' => ['required', 'numeric', 'min:0'],
            'interval_in_days' => ['required', 'integer', 'min:1', 'max:365'],
            'is_active' => ['boolean'],
        ]);

        $plan->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Installment plan updated.');
    }

    public function destroyPlan(Course $course, CourseInstalmentPlan $plan): RedirectResponse
    {
        abort_unless($plan->course_id === $course->id, 404);
        $plan->delete();

        return back()->with('success', 'Installment plan removed.');
    }
}
