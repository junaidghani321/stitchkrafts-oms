<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Settings → Email.
 *
 * Preferences belong to a user rather than a role, so one controller serves
 * every screen; only the view path differs. The switches a given user sees
 * come from config/notification_preferences.php keyed by their role.
 */
class EmailPreferencesController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view($this->viewFor($user->type), [
            'preferences' => array_values($user->emailPreferences()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $posted = (array) $request->input('preferences', []);

        // Only keys this role actually offers are stored. Anything else in the
        // payload is ignored, so a crafted POST cannot set a switch the user
        // has no way to see or turn back off.
        $allowed = array_keys(config('notification_preferences.'.$user->type, []));

        $saved = [];

        foreach ($allowed as $key) {
            // An unchecked switch is not posted at all — absence means off.
            $saved[$key] = (bool) ($posted[$key] ?? false);
        }

        $user->forceFill(['notification_preferences' => $saved])->save();

        return back()->with('status', 'Your email preferences have been saved.');
    }

    private function viewFor(string $role): string
    {
        return match ($role) {
            'admin' => 'admin.settings.email',
            'manager' => 'manager.settings.email',
            'employee' => 'employee.settings.email',
            default => 'user.settings.email',
        };
    }
}
