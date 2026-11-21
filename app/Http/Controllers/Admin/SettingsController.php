<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\LogoUploader;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Site settings (FR-027, FR-035).
 *
 * Brand name, logo and contact details. Everything written here appears in the
 * header, footer and page titles on the next page load, because no template
 * contains the brand literally — `NoHardcodedBrandTest` enforces that.
 *
 * The logo goes through `LogoUploader`, which rejects SVG. That matters more on
 * the admin side than anywhere else: an SVG logo is served from the site's own
 * origin inside an authenticated session, so it is a stored-XSS vector aimed at
 * whoever is signed in.
 */
class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.site', [
            'settings' => SiteSettings::all(),
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'min:2', 'max:60'],
            'brand_tagline' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['required', 'email', 'max:254'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'support_hours' => ['nullable', 'string', 'max:120'],
            'logo' => LogoUploader::rules(),
            'remove_logo' => ['sometimes', 'boolean'],
        ]);

        $before = [
            'brand_name' => SiteSettings::brandName(),
            'contact_email' => (string) SiteSettings::get('contact_email'),
        ];

        // Logo first: if the upload fails, nothing else is written and the admin
        // is not left with a changed name and an unchanged logo.
        if ($request->hasFile('logo')) {
            LogoUploader::store($request->file('logo'));
        } elseif ($request->boolean('remove_logo')) {
            LogoUploader::remove();
        }

        SiteSettings::setMany([
            'brand_name' => $validated['brand_name'],
            'brand_tagline' => $validated['brand_tagline'] ?? null,
            'contact_email' => $validated['contact_email'],
            'contact_phone' => $validated['contact_phone'] ?? null,
            'support_hours' => $validated['support_hours'] ?? null,
        ]);

        $audit->record(
            actor: $request->user(),
            action: 'settings.site_updated',
            targetType: 'settings',
            targetId: 0,
            reason: 'Site settings updated from the admin console',
            context: [
                'before' => $before,
                'after' => [
                    'brand_name' => SiteSettings::brandName(),
                    'contact_email' => (string) SiteSettings::get('contact_email'),
                ],
                'logo_changed' => $request->hasFile('logo'),
                'logo_removed' => $request->boolean('remove_logo'),
            ],
        );

        return redirect()
            ->route('admin.settings.site')
            ->with('status', 'Settings saved. Every page shows the new details from now on.');
    }
}
