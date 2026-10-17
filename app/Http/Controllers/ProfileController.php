<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Settings → Profile.
 *
 * One controller for every role, exactly like EmailPreferencesController: a
 * profile belongs to a user, not to a role, and only the view path differs.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view($this->viewFor($request->user()->type), [
            'profile' => $request->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Company is a customer field. Dropping it for other roles stops a
        // crafted POST writing a column their screen never offered.
        if (! $user->isCustomer()) {
            unset($data['company']);
        }

        $user->fill($data)->save();

        return back()->with('status', 'Your profile has been updated.');
    }

    /**
     * Change the signed-in user's own password.
     *
     * The session id is rotated afterwards — standard hygiene around a
     * credential change, so a session id captured beforehand is worthless.
     *
     * Note this does NOT sign out the user's other browsers: that needs the
     * AuthenticateSession middleware, which this app does not run.
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => Hash::make($request->validated('password')),
        ])->save();

        $request->session()->regenerate();

        return back()->with('status', 'Your password has been changed.');
    }

    private function viewFor(string $role): string
    {
        return match ($role) {
            'admin' => 'admin.settings.profile',
            'manager' => 'manager.settings.profile',
            'employee' => 'employee.settings.profile',
            default => 'user.settings.profile',
        };
    }
}
