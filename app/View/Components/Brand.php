<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Support\LogoUploader;
use App\Support\SiteSettings;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Brand mark.
 *
 * Renders the uploaded logo when one exists, and a text wordmark otherwise so
 * the site is never brandless (FR-035). Every view uses this component; none
 * writes the brand name directly — a test enforces that.
 */
class Brand extends Component
{
    public function __construct(
        public ?string $brandName = null,
        public ?string $logoUrl = null,
        public ?string $initials = null,
    ) {
        $this->brandName ??= SiteSettings::brandName();
        $this->logoUrl ??= LogoUploader::url();
        $this->initials ??= SiteSettings::initials();
    }

    public function render(): View
    {
        return view('components.brand');
    }
}
