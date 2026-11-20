<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Job;
use App\Models\Portfolio;
use App\Models\User;
use App\Policies\FreelancerProfilePolicy;
use App\Policies\JobPolicy;
use App\Policies\PortfolioPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Policy map.
     *
     * `User` maps to FreelancerProfilePolicy because the *subject* of a profile
     * check is the owner, not a separate model — a profile is 1:1 with a user.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => FreelancerProfilePolicy::class,
        Portfolio::class => PortfolioPolicy::class,
        Job::class => JobPolicy::class,
    ];
}
