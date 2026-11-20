<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Account approval (FR-004, FR-024).
 *
 * The status transitions live on the User model and throw on an illegal move,
 * so this controller stays thin: it authorises, delegates and reports. It does
 * not decide what is legal.
 *
 * Every action that changes access must be audited. The audit logger arrives
 * with T-012; until then these actions are recorded in the application log with
 * actor and target so the transition is not untraceable.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $type = $request->string('type')->toString();

        $users = User::query()
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'statusFilter' => $status,
            'typeFilter' => $type,
            'pendingCount' => User::pending()->count(),
        ]);
    }

    public function show(User $user): View
    {
        return view('admin.users.show', ['user' => $user]);
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        return $this->transition($request, $user, UserStatus::APPROVED, 'approved');
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        return $this->transition($request, $user, UserStatus::REJECTED, 'rejected');
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        return $this->transition($request, $user, UserStatus::SUSPENDED, 'suspended');
    }

    public function reinstate(Request $request, User $user): RedirectResponse
    {
        return $this->transition($request, $user, UserStatus::APPROVED, 'reinstated');
    }

    /**
     * Delete an account.
     *
     * Soft delete only — a hard delete would orphan ledger history and break the
     * balance reconciliation guarantee (FR-024, FR-041).
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        if ($user->isAdmin()) {
            // Removing the last admin would lock everyone out of the platform.
            $otherAdmins = User::admins()->whereKeyNot($user->id)->count();

            if ($otherAdmins === 0) {
                return back()->withErrors(['user' => 'You cannot delete the only administrator.']);
            }
        }

        return $this->transition($request, $user, UserStatus::DELETED, 'deleted');
    }

    private function transition(
        Request $request,
        User $target,
        UserStatus $to,
        string $verb,
    ): RedirectResponse {
        if ($target->id === $request->user()->id && $to !== UserStatus::APPROVED) {
            return back()->withErrors(['user' => 'You cannot change your own access level.']);
        }

        try {
            $target->transitionTo($to);
        } catch (\DomainException $e) {
            return back()->withErrors(['user' => $e->getMessage()]);
        }

        // Recorded now; replaced by the persistent audit trail in T-012.
        logger()->info('account.transition', [
            'actor_id' => $request->user()->id,
            'target_id' => $target->id,
            'from' => $target->getOriginal('status'),
            'to' => $to->value,
        ]);

        return back()->with('status', sprintf(
            '%s has been %s.',
            $target->name,
            $verb
        ));
    }
}
