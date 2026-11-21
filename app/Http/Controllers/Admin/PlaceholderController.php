<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Placeholder for an admin section that is not built yet.
 *
 * Why this exists rather than a missing route or an empty page:
 *
 *  - A dead link in a console teaches an operator that the console is
 *    unreliable, and they stop trusting the links that do work.
 *  - An empty table implies "there is nothing here", which reads as a fact. The
 *    truth is "this does not exist yet", which is a different message entirely.
 *
 * The page states what is missing and which milestone brings it. It never shows
 * invented rows — that is the L-004 failure, and an ops console is the worst
 * place for plausible fake data.
 */
class PlaceholderController extends Controller
{
    /** Sections that may be requested, with their honest status. */
    private const SECTIONS = [
        'jobs' => [
            'title' => 'Jobs',
            'intro' => 'A full job browser: every job regardless of status, with the ability to archive or edit any of them.',
            'missing' => [
                'Listing jobs across all statuses with filtering',
                'Archiving a funded job through the refund path rather than stranding the money',
                'Editing a job on a poster\'s behalf',
            ],
            'arrives' => 'M5, alongside escrow funding.',
            'available_now' => [
                'Job drafts are counted on the Overview page',
                'Posters manage their own jobs under “My jobs” in the customer app',
            ],
        ],
        'escrow' => [
            'title' => 'Escrow & releases',
            'intro' => 'Releasing a milestone to a freelancer, and refunding unreleased escrow to a poster.',
            'missing' => [
                'Releasing one milestone, or the full remaining escrow of a single-amount job',
                'Refunding the unreleased remainder while leaving released milestones paid',
                'The idempotency guard that stops the same milestone being released twice',
            ],
            'arrives' => 'M5. This is the highest-risk action in the product, so it ships with its own verification rather than as a button added late.',
            'available_now' => [
                'Money can be deposited and approved — see Deposit review',
                'Every deposit decision is already written to the audit log',
            ],
        ],
    ];

    public function __invoke(Request $request, string $section): View
    {
        $detail = self::SECTIONS[$section] ?? null;

        // An unknown section is a 404, not a generic "coming soon" page. A page
        // that always answers hides typos in links.
        if ($detail === null) {
            abort(404);
        }

        return view('admin.placeholder', [
            'section' => $section,
            'detail' => $detail,
        ]);
    }
}
