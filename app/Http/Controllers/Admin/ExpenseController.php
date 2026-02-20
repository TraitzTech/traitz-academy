<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use App\Models\Program;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $authUser = $request->user();

        $query = Expense::query()
            ->with(['category:id,name,color', 'recorder:id,name,role', 'program:id,title']);

        $this->applyVisibilityScope($query, $authUser);
        $this->applyFilters($query, $request);

        $expenses = $query->latest('expense_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $categories = ExpenseCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        $programs = Program::query()
            ->orderBy('title')
            ->get(['id', 'title']);

        $stats = $this->buildStats($request, $authUser);
        $chartData = $this->buildChartData($request, $authUser);

        $collectors = collect();
        if ($authUser->isExecutive()) {
            $collectors = User::query()
                ->whereIn('role', [
                    User::ROLE_CTO,
                    User::ROLE_CEO,
                    User::ROLE_PROGRAM_COORDINATOR,
                    User::ROLE_ADMIN_LEGACY,
                ])
                ->whereHas('recordedExpenses')
                ->orderBy('name')
                ->get(['id', 'name', 'role']);
        }

        return Inertia::render('Admin/Expenses/Index', [
            'expenses' => $expenses,
            'filters' => $request->only([
                'search', 'category_id', 'program_id', 'recorded_by',
                'date_from', 'date_to', 'payment_method',
            ]),
            'categories' => $categories,
            'programs' => $programs,
            'collectors' => $collectors,
            'stats' => $stats,
            'chartData' => $chartData,
            'isExecutive' => $authUser->isExecutive(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'program_id' => 'nullable|exists:programs,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01|max:99999999.99',
            'payment_method' => 'nullable|string|in:cash,mobile_money,bank_transfer,cheque',
            'receipt_reference' => 'nullable|string|max:100',
            'vendor' => 'nullable|string|max:255',
            'expense_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['recorded_by'] = $request->user()->id;
        $validated['currency'] = 'XAF';
        $validated['status'] = 'approved';

        if (empty($validated['receipt_reference'])) {
            $validated['receipt_reference'] = 'EXP-'.strtoupper(Str::random(8));
        }

        Expense::create($validated);

        return back()->with('success', 'Expense recorded successfully.');
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $authUser = $request->user();

        if (! $authUser->canManageExpense($expense)) {
            abort(403, 'You do not have permission to update this expense.');
        }

        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'program_id' => 'nullable|exists:programs,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01|max:99999999.99',
            'payment_method' => 'nullable|string|in:cash,mobile_money,bank_transfer,cheque',
            'receipt_reference' => 'nullable|string|max:100',
            'vendor' => 'nullable|string|max:255',
            'expense_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:500',
        ]);

        $expense->update($validated);

        return back()->with('success', 'Expense updated successfully.');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $authUser = $request->user();

        if (! $authUser->canManageExpense($expense)) {
            abort(403, 'You do not have permission to delete this expense.');
        }

        $expense->delete();

        return back()->with('success', 'Expense deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $authUser = $request->user();

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:expenses,id',
        ]);

        $query = Expense::query()->whereIn('id', $validated['ids']);

        if ($authUser->isProgramCoordinator()) {
            $query->where('recorded_by', $authUser->id);
        }

        $deleted = $query->delete();

        return back()->with('success', "{$deleted} expense(s) deleted successfully.");
    }

    public function export(Request $request): StreamedResponse
    {
        $authUser = $request->user();
        $query = Expense::query()
            ->with(['category:id,name', 'recorder:id,name', 'program:id,title']);

        $this->applyVisibilityScope($query, $authUser);
        $this->applyFilters($query, $request);

        $expenses = $query->latest('expense_date')->get();

        return response()->streamDownload(function () use ($expenses) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID', 'Date', 'Title', 'Category', 'Amount (XAF)', 'Payment Method',
                'Vendor', 'Receipt Reference', 'Program', 'Recorded By', 'Notes', 'Created At',
            ]);

            foreach ($expenses as $expense) {
                fputcsv($handle, [
                    $expense->id,
                    $expense->expense_date->format('Y-m-d'),
                    $expense->title,
                    $expense->category?->name ?? 'N/A',
                    number_format((float) $expense->amount, 2),
                    $expense->payment_method ?? 'N/A',
                    $expense->vendor ?? 'N/A',
                    $expense->receipt_reference ?? 'N/A',
                    $expense->program?->title ?? 'N/A',
                    $expense->recorder?->name ?? 'N/A',
                    $expense->notes ?? '',
                    $expense->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        }, 'expenses-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Manage expense categories (executive only).
     */
    public function categories(Request $request): Response
    {
        $categories = ExpenseCategory::query()
            ->withCount('expenses')
            ->withSum('expenses', 'amount')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Expenses/Categories', [
            'categories' => $categories,
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:expense_categories,name',
            'description' => 'nullable|string|max:255',
            'color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        ExpenseCategory::create($validated);

        return back()->with('success', 'Category created successfully.');
    }

    public function updateCategory(Request $request, ExpenseCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:expense_categories,name,'.$category->id,
            'description' => 'nullable|string|max:255',
            'color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'required|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category->update($validated);

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroyCategory(Request $request, ExpenseCategory $category): RedirectResponse
    {
        if ($category->expenses()->exists()) {
            return back()->with('error', 'Cannot delete a category that has expenses. Please reassign them first.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }

    private function applyVisibilityScope(Builder $query, User $user): void
    {
        if ($user->isProgramCoordinator()) {
            $query->where('recorded_by', $user->id);
        }
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('vendor', 'like', "%{$search}%")
                    ->orWhere('receipt_reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->input('category_id'));
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->input('program_id'));
        }

        if ($request->filled('recorded_by')) {
            $query->where('recorded_by', $request->input('recorded_by'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->input('date_to'));
        }
    }

    /**
     * @return array{total_expenses: float, expense_count: int, total_collections: float, balance: float, avg_expense: float, this_month_expenses: float}
     */
    private function buildStats(Request $request, User $user): array
    {
        $expenseQuery = Expense::query();
        $this->applyVisibilityScope($expenseQuery, $user);
        $this->applyFilters($expenseQuery, $request);

        $totalExpenses = (float) (clone $expenseQuery)->sum('amount');
        $expenseCount = (clone $expenseQuery)->count();
        $avgExpense = $expenseCount > 0 ? round($totalExpenses / $expenseCount, 2) : 0;

        $thisMonthExpenses = (float) (clone $expenseQuery)
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        // Collections (successful payments) — only for executives
        $totalCollections = 0;
        $balance = 0;

        if ($user->isExecutive()) {
            $collectionsQuery = Payment::query()->where('status', 'successful');

            if ($request->filled('date_from')) {
                $collectionsQuery->whereDate('paid_at', '>=', $request->input('date_from'));
            }

            if ($request->filled('date_to')) {
                $collectionsQuery->whereDate('paid_at', '<=', $request->input('date_to'));
            }

            if ($request->filled('program_id')) {
                $collectionsQuery->where('program_id', $request->input('program_id'));
            }

            $totalCollections = (float) $collectionsQuery->sum('amount');
            $balance = round($totalCollections - $totalExpenses, 2);
        }

        return [
            'total_expenses' => $totalExpenses,
            'expense_count' => $expenseCount,
            'total_collections' => $totalCollections,
            'balance' => $balance,
            'avg_expense' => $avgExpense,
            'this_month_expenses' => $thisMonthExpenses,
        ];
    }

    /**
     * Build chart data for expenses over time and by category.
     *
     * @return array{monthly: array<int, array{month: string, expenses: float, collections: float}>, by_category: array<int, array{name: string, color: string, amount: float}>}
     */
    private function buildChartData(Request $request, User $user): array
    {
        $months = 12;
        $startDate = now()->subMonths($months - 1)->startOfMonth();

        // Monthly expenses — fetch raw and group in PHP for SQLite compatibility
        $expenseQuery = Expense::query()
            ->where('expense_date', '>=', $startDate)
            ->select('expense_date', 'amount');
        $this->applyVisibilityScope($expenseQuery, $user);

        if ($request->filled('category_id')) {
            $expenseQuery->where('expense_category_id', $request->input('category_id'));
        }

        if ($request->filled('program_id')) {
            $expenseQuery->where('program_id', $request->input('program_id'));
        }

        $monthlyExpenses = $expenseQuery->get()
            ->groupBy(fn ($e) => Carbon::parse($e->expense_date)->format('Y-m'))
            ->map(fn ($group) => (float) $group->sum('amount'));

        // Monthly collections (for executives)
        $monthlyCollections = collect();
        if ($user->isExecutive()) {
            $collectionsQuery = Payment::query()
                ->where('status', 'successful')
                ->where('paid_at', '>=', $startDate)
                ->select('paid_at', 'amount');

            if ($request->filled('program_id')) {
                $collectionsQuery->where('program_id', $request->input('program_id'));
            }

            $monthlyCollections = $collectionsQuery->get()
                ->groupBy(fn ($p) => Carbon::parse($p->paid_at)->format('Y-m'))
                ->map(fn ($group) => (float) $group->sum('amount'));
        }

        $monthly = [];
        for ($i = 0; $i < $months; $i++) {
            $date = now()->subMonths($months - 1 - $i)->startOfMonth();
            $key = $date->format('Y-m');
            $monthly[] = [
                'month' => $date->format('M Y'),
                'expenses' => (float) ($monthlyExpenses[$key] ?? 0),
                'collections' => (float) ($monthlyCollections[$key] ?? 0),
            ];
        }

        // Expenses by category
        $categoryQuery = Expense::query()
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->selectRaw('expense_categories.name, expense_categories.color, SUM(expenses.amount) as total')
            ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_categories.color')
            ->orderByDesc('total');

        $this->applyVisibilityScope($categoryQuery, $user);

        if ($request->filled('date_from')) {
            $categoryQuery->whereDate('expense_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $categoryQuery->whereDate('expense_date', '<=', $request->input('date_to'));
        }
        if ($request->filled('program_id')) {
            $categoryQuery->where('expenses.program_id', $request->input('program_id'));
        }

        $byCategory = $categoryQuery->get()->map(fn ($row) => [
            'name' => $row->name,
            'color' => $row->color,
            'amount' => (float) $row->total,
        ])->toArray();

        return [
            'monthly' => $monthly,
            'by_category' => $byCategory,
        ];
    }
}
