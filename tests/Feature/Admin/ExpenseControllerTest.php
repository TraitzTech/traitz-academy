<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// ── Index ──────────────────────────────────────────────────────────

it('allows executives to view all expenses', function () {
    $executive = User::factory()->create(['role' => User::ROLE_CEO]);
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->count(3)->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $executive->id,
    ]);

    $response = $this->actingAs($executive)->get(route('admin.expenses.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Expenses/Index')
        ->has('expenses.data', 3)
        ->has('stats')
        ->has('chartData')
        ->where('isExecutive', true)
    );
});

it('scopes expenses for program coordinators to their own records', function () {
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);
    $otherUser = User::factory()->create(['role' => User::ROLE_CTO]);
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->count(2)->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $coordinator->id,
    ]);

    Expense::factory()->count(3)->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $otherUser->id,
    ]);

    $response = $this->actingAs($coordinator)->get(route('admin.expenses.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Expenses/Index')
        ->has('expenses.data', 2)
        ->where('isExecutive', false)
    );
});

it('denies regular users access to expense tracking', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $response = $this->actingAs($user)->get(route('admin.expenses.index'));

    $response->assertForbidden();
});

// ── Filters ──────────────────────────────────────────────────────

it('filters expenses by category', function () {
    $executive = User::factory()->create(['role' => User::ROLE_CEO]);
    $cat1 = ExpenseCategory::factory()->create(['name' => 'Transport', 'slug' => 'transport']);
    $cat2 = ExpenseCategory::factory()->create(['name' => 'Food', 'slug' => 'food']);

    Expense::factory()->count(2)->create([
        'expense_category_id' => $cat1->id,
        'recorded_by' => $executive->id,
    ]);

    Expense::factory()->create([
        'expense_category_id' => $cat2->id,
        'recorded_by' => $executive->id,
    ]);

    $response = $this->actingAs($executive)->get(route('admin.expenses.index', ['category_id' => $cat1->id]));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('expenses.data', 2)
    );
});

it('filters expenses by date range', function () {
    $executive = User::factory()->create(['role' => User::ROLE_CEO]);
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $executive->id,
        'expense_date' => '2026-01-15',
    ]);

    Expense::factory()->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $executive->id,
        'expense_date' => '2026-02-10',
    ]);

    $response = $this->actingAs($executive)->get(route('admin.expenses.index', [
        'date_from' => '2026-02-01',
        'date_to' => '2026-02-28',
    ]));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('expenses.data', 1)
    );
});

// ── Store ──────────────────────────────────────────────────────

it('allows admins to create an expense', function () {
    $executive = User::factory()->create(['role' => User::ROLE_CTO]);
    $category = ExpenseCategory::factory()->create();

    $response = $this->actingAs($executive)->post(route('admin.expenses.store'), [
        'expense_category_id' => $category->id,
        'title' => 'Purchase of notebooks',
        'amount' => 15000,
        'expense_date' => '2026-02-15',
        'payment_method' => 'cash',
        'vendor' => 'Paper Store',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('expenses', [
        'title' => 'Purchase of notebooks',
        'amount' => '15000.00',
        'recorded_by' => $executive->id,
        'expense_category_id' => $category->id,
        'vendor' => 'Paper Store',
    ]);
});

it('allows program coordinators to create an expense', function () {
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);
    $category = ExpenseCategory::factory()->create();

    $response = $this->actingAs($coordinator)->post(route('admin.expenses.store'), [
        'expense_category_id' => $category->id,
        'title' => 'Transport cost',
        'amount' => 5000,
        'expense_date' => '2026-02-18',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('expenses', [
        'title' => 'Transport cost',
        'recorded_by' => $coordinator->id,
    ]);
});

it('validates required fields on expense creation', function () {
    $executive = User::factory()->create(['role' => User::ROLE_CEO]);

    $response = $this->actingAs($executive)->post(route('admin.expenses.store'), []);

    $response->assertSessionHasErrors(['title', 'amount', 'expense_date', 'expense_category_id']);
});

it('rejects future dates for expenses', function () {
    $executive = User::factory()->create(['role' => User::ROLE_CEO]);
    $category = ExpenseCategory::factory()->create();

    $response = $this->actingAs($executive)->post(route('admin.expenses.store'), [
        'expense_category_id' => $category->id,
        'title' => 'Future expense',
        'amount' => 1000,
        'expense_date' => '2027-01-01',
    ]);

    $response->assertSessionHasErrors('expense_date');
});

it('allows executives to specify recorded_by when creating an expense', function () {
    $cto = User::factory()->create(['role' => User::ROLE_CTO]);
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);
    $category = ExpenseCategory::factory()->create();

    $response = $this->actingAs($cto)->post(route('admin.expenses.store'), [
        'expense_category_id' => $category->id,
        'title' => 'CEO recorded expense',
        'amount' => 25000,
        'expense_date' => '2026-02-15',
        'recorded_by' => $ceo->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('expenses', [
        'title' => 'CEO recorded expense',
        'recorded_by' => $ceo->id,
    ]);
});

it('ignores recorded_by from coordinators and defaults to themselves', function () {
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);
    $category = ExpenseCategory::factory()->create();

    $response = $this->actingAs($coordinator)->post(route('admin.expenses.store'), [
        'expense_category_id' => $category->id,
        'title' => 'Coordinator expense',
        'amount' => 5000,
        'expense_date' => '2026-02-15',
        'recorded_by' => $ceo->id,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('expenses', [
        'title' => 'Coordinator expense',
        'recorded_by' => $coordinator->id,
    ]);
});

it('allows executives to change recorded_by when updating an expense', function () {
    $cto = User::factory()->create(['role' => User::ROLE_CTO]);
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);
    $category = ExpenseCategory::factory()->create();

    $expense = Expense::factory()->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $cto->id,
        'title' => 'Wrongly attributed',
    ]);

    $response = $this->actingAs($cto)->patch(route('admin.expenses.update', $expense), [
        'expense_category_id' => $category->id,
        'title' => 'Correctly attributed',
        'amount' => 20000,
        'expense_date' => '2026-02-15',
        'recorded_by' => $ceo->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'recorded_by' => $ceo->id,
    ]);
});

// ── Update ──────────────────────────────────────────────────────

it('allows executives to update any expense', function () {
    $executive = User::factory()->create(['role' => User::ROLE_CEO]);
    $otherUser = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);
    $category = ExpenseCategory::factory()->create();

    $expense = Expense::factory()->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $otherUser->id,
        'title' => 'Original title',
    ]);

    $response = $this->actingAs($executive)->patch(route('admin.expenses.update', $expense), [
        'expense_category_id' => $category->id,
        'title' => 'Updated title',
        'amount' => 20000,
        'expense_date' => '2026-02-15',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'title' => 'Updated title',
        'amount' => '20000.00',
    ]);
});

it('allows coordinators to update their own expenses only', function () {
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);
    $category = ExpenseCategory::factory()->create();

    $ownExpense = Expense::factory()->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $coordinator->id,
    ]);

    $response = $this->actingAs($coordinator)->patch(route('admin.expenses.update', $ownExpense), [
        'expense_category_id' => $category->id,
        'title' => 'My updated expense',
        'amount' => 8000,
        'expense_date' => '2026-02-15',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

it('denies coordinators from updating expenses recorded by others', function () {
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);
    $otherUser = User::factory()->create(['role' => User::ROLE_CTO]);
    $category = ExpenseCategory::factory()->create();

    $otherExpense = Expense::factory()->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $otherUser->id,
    ]);

    $response = $this->actingAs($coordinator)->patch(route('admin.expenses.update', $otherExpense), [
        'expense_category_id' => $category->id,
        'title' => 'Trying to update',
        'amount' => 8000,
        'expense_date' => '2026-02-15',
    ]);

    $response->assertForbidden();
});

// ── Delete ──────────────────────────────────────────────────────

it('allows executives to delete any expense', function () {
    $executive = User::factory()->create(['role' => User::ROLE_CEO]);
    $category = ExpenseCategory::factory()->create();

    $expense = Expense::factory()->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $executive->id,
    ]);

    $response = $this->actingAs($executive)->delete(route('admin.expenses.destroy', $expense));

    $response->assertRedirect();
    $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
});

it('denies coordinators from deleting expenses recorded by others', function () {
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);
    $otherUser = User::factory()->create(['role' => User::ROLE_CTO]);
    $category = ExpenseCategory::factory()->create();

    $expense = Expense::factory()->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $otherUser->id,
    ]);

    $response = $this->actingAs($coordinator)->delete(route('admin.expenses.destroy', $expense));

    $response->assertForbidden();
    $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
});

// ── Bulk Delete ──────────────────────────────────────────────────

it('allows executives to bulk delete expenses', function () {
    $executive = User::factory()->create(['role' => User::ROLE_CEO]);
    $category = ExpenseCategory::factory()->create();

    $expenses = Expense::factory()->count(3)->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $executive->id,
    ]);

    $response = $this->actingAs($executive)->post(route('admin.expenses.bulk-destroy'), [
        'ids' => $expenses->pluck('id')->toArray(),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseCount('expenses', 0);
});

it('limits coordinator bulk delete to own expenses', function () {
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);
    $otherUser = User::factory()->create(['role' => User::ROLE_CTO]);
    $category = ExpenseCategory::factory()->create();

    $ownExpense = Expense::factory()->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $coordinator->id,
    ]);

    $otherExpense = Expense::factory()->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $otherUser->id,
    ]);

    $response = $this->actingAs($coordinator)->post(route('admin.expenses.bulk-destroy'), [
        'ids' => [$ownExpense->id, $otherExpense->id],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseMissing('expenses', ['id' => $ownExpense->id]);
    $this->assertDatabaseHas('expenses', ['id' => $otherExpense->id]);
});

// ── Export ──────────────────────────────────────────────────────

it('exports expenses as CSV', function () {
    $executive = User::factory()->create(['role' => User::ROLE_CEO]);
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->count(2)->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $executive->id,
    ]);

    $response = $this->actingAs($executive)->get(route('admin.expenses.export'));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('text/csv');
});

// ── Categories (Executive Only) ──────────────────────────────────

it('allows executives to view categories', function () {
    $executive = User::factory()->create(['role' => User::ROLE_CEO]);
    ExpenseCategory::factory()->count(3)->create();

    $response = $this->actingAs($executive)->get(route('admin.expense-categories.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Expenses/Categories')
        ->has('categories', 3)
    );
});

it('denies coordinators from managing categories', function () {
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);

    $response = $this->actingAs($coordinator)->get(route('admin.expense-categories.index'));

    $response->assertForbidden();
});

it('creates a category', function () {
    $executive = User::factory()->create(['role' => User::ROLE_CEO]);

    $response = $this->actingAs($executive)->post(route('admin.expense-categories.store'), [
        'name' => 'Office Supplies',
        'description' => 'All office stuff',
        'color' => '#3b82f6',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('expense_categories', [
        'name' => 'Office Supplies',
        'slug' => 'office-supplies',
        'color' => '#3b82f6',
    ]);
});

it('updates a category', function () {
    $executive = User::factory()->create(['role' => User::ROLE_CEO]);
    $category = ExpenseCategory::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

    $response = $this->actingAs($executive)->patch(route('admin.expense-categories.update', $category), [
        'name' => 'New Name',
        'description' => 'Updated desc',
        'color' => '#ef4444',
        'is_active' => true,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('expense_categories', [
        'id' => $category->id,
        'name' => 'New Name',
        'slug' => 'new-name',
    ]);
});

it('prevents deleting a category with expenses', function () {
    $executive = User::factory()->create(['role' => User::ROLE_CEO]);
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $executive->id,
    ]);

    $response = $this->actingAs($executive)->delete(route('admin.expense-categories.destroy', $category));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('expense_categories', ['id' => $category->id]);
});

it('allows deleting an empty category', function () {
    $executive = User::factory()->create(['role' => User::ROLE_CEO]);
    $category = ExpenseCategory::factory()->create();

    $response = $this->actingAs($executive)->delete(route('admin.expense-categories.destroy', $category));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('expense_categories', ['id' => $category->id]);
});

// ── Stats & Balance ──────────────────────────────────────────────

it('shows balance data for executives (collections vs expenses)', function () {
    $executive = User::factory()->create(['role' => User::ROLE_CEO]);
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $executive->id,
        'amount' => 50000,
        'expense_date' => now(),
    ]);

    $response = $this->actingAs($executive)->get(route('admin.expenses.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('stats.total_expenses', fn ($val) => (float) $val === 50000.0)
        ->where('isExecutive', true)
        ->has('stats.balance')
        ->has('stats.total_collections')
    );
});

it('does not expose collections data to coordinators', function () {
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->create([
        'expense_category_id' => $category->id,
        'recorded_by' => $coordinator->id,
        'amount' => 10000,
    ]);

    $response = $this->actingAs($coordinator)->get(route('admin.expenses.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('isExecutive', false)
        ->where('stats.total_collections', 0)
        ->where('stats.balance', 0)
    );
});
