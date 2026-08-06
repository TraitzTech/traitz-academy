<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
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
        ]);

        return Inertia::render('Admin/Courses/Pricing', [
            'course' => $course,
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $saleRaw = $request->input('sale_price');
        $request->merge([
            'sale_price' => ($saleRaw === '' || $saleRaw === null) ? null : $saleRaw,
        ]);

        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'max_installments' => ['required', 'integer', 'min:1', 'max:12'],
            'is_featured' => ['boolean'],
        ]);

        $sale = $validated['sale_price'] !== null && $validated['sale_price'] !== ''
            ? (float) $validated['sale_price']
            : null;

        if ($sale !== null && $sale >= (float) $validated['price']) {
            return back()->withErrors(['sale_price' => 'Sale price must be less than the regular price.']);
        }

        if ((float) $validated['price'] <= 0) {
            $validated['max_installments'] = 1;
        }

        $course->update([
            'price' => $validated['price'],
            'sale_price' => $sale,
            'max_installments' => (int) $validated['max_installments'],
            'is_featured' => $request->boolean('is_featured'),
        ]);

        return back()->with('success', 'Course pricing updated.');
    }
}
