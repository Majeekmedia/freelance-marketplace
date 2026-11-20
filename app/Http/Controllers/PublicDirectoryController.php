<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FreelancerProfile;
use Illuminate\View\View;

class PublicDirectoryController extends Controller
{
    /** Public freelancer directory (FR-007). */
    public function freelancers(): View
    {
        $profiles = FreelancerProfile::query()
            ->publiclyVisible()
            ->with('user')
            ->orderByDesc('updated_at')
            ->paginate(24);

        return view('public.freelancers.index', ['profiles' => $profiles]);
    }
}
