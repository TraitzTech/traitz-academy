<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AdminAccountCredentialsNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->withCount('applications')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => User::count(),
            'executives' => User::whereIn('role', [User::ROLE_CTO, User::ROLE_CEO, User::ROLE_ADMIN_LEGACY])->count(),
            'program_coordinators' => User::where('role', User::ROLE_PROGRAM_COORDINATOR)->count(),
            'users' => User::where('role', User::ROLE_USER)->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
            'stats' => $stats,
            'roleOptions' => User::managedRoleOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $allowedRoles = implode(',', User::managedRoleOptions());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:'.$allowedRoles,
        ]);

        $plainPassword = $validated['password'] ?? (string) Str::password(12);
        $validated['password'] = Hash::make($plainPassword);

        $user = User::create($validated);

        $user->notify(new AdminAccountCredentialsNotification(
            temporaryPassword: $plainPassword,
            createdBy: $request->user(),
        ));

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully. Login credentials have been sent to the user email.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
        ]);
    }

    public function show(User $user): Response
    {
        $user->load(['applications' => function ($query) {
            $query->with('program:id,title,category')->latest()->limit(10);
        }]);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'stats' => [
                'total_applications' => $user->applications()->count(),
                'accepted_applications' => $user->applications()->where('status', 'accepted')->count(),
                'pending_applications' => $user->applications()->where('status', 'pending')->count(),
            ],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $allowedRoles = implode(',', User::managedRoleOptions());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:'.$allowedRoles,
        ]);

        if ($validated['password']) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function toggleRole(User $user): RedirectResponse
    {
        // Prevent self-demotion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot change your own role.');
        }

        if ($user->isExecutive()) {
            return back()->with('error', 'Executive roles can only be changed from the edit user form.');
        }

        $user->update([
            'role' => $user->role === User::ROLE_PROGRAM_COORDINATOR ? User::ROLE_USER : User::ROLE_PROGRAM_COORDINATOR,
        ]);

        return back()->with('success', 'User role updated.');
    }

    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'format' => 'required|in:csv,xlsx,phones',
        ]);

        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('name')->get();
        $format = $request->format;

        if ($format === 'phones') {
            $filename = 'user-phone-numbers-'.now()->format('Y-m-d').'.txt';

            return response()->streamDownload(function () use ($users) {
                $phones = $users->filter(fn ($user) => ! empty($user->phone))
                    ->pluck('phone')
                    ->unique();

                foreach ($phones as $phone) {
                    echo $phone."\n";
                }
            }, $filename, [
                'Content-Type' => 'text/plain',
            ]);
        }

        // CSV / Excel export
        $extension = $format === 'xlsx' ? 'csv' : 'csv';
        $filename = 'users-export-'.now()->format('Y-m-d').'.'.$extension;

        return response()->streamDownload(function () use ($users) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Email', 'Phone', 'Role', 'Email Verified', 'Joined']);

            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->name,
                    $user->email,
                    $user->phone ?? '',
                    $user->role,
                    $user->email_verified_at ? $user->email_verified_at->format('Y-m-d') : 'No',
                    $user->created_at->format('Y-m-d'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:users,id',
        ]);

        // Prevent self-deletion
        $idsToDelete = array_filter($validated['ids'], fn ($id) => $id !== auth()->id());

        if (count($idsToDelete) === 0) {
            return back()->with('error', 'No users were deleted. You cannot delete yourself.');
        }

        User::whereIn('id', $idsToDelete)->delete();

        $count = count($idsToDelete);

        return back()->with('success', "{$count} user(s) deleted successfully.");
    }
}
