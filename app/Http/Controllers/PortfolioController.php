<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PortfolioRequest;
use App\Models\Portfolio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Portfolio items (FR-008).
 *
 * Every action is authorised against the specific record, not merely against
 * "is this user a freelancer". That is what stops freelancer A editing B's item.
 *
 * Route model binding is used for update/delete so the policy receives the real
 * record; an id belonging to someone else produces 403, not a hopeful 404.
 */
class PortfolioController extends Controller
{
    public function index(Request $request): View
    {
        return view('freelancer.portfolio.index', [
            'items' => $request->user()->portfolios()->get(),
        ]);
    }

    public function store(PortfolioRequest $request): RedirectResponse
    {
        $this->authorize('create', Portfolio::class);

        $item = new Portfolio($request->validatedItem());
        $item->user_id = $request->user()->id;
        $item->sort_order = (int) ($request->user()->portfolios()->max('sort_order') ?? 0) + 1;
        $item->save();

        return back()->with('status', 'Portfolio item added.');
    }

    public function update(PortfolioRequest $request, Portfolio $portfolio): RedirectResponse
    {
        $this->authorize('update', $portfolio);

        $portfolio->fill($request->validatedItem());
        $portfolio->save();

        return back()->with('status', 'Portfolio item updated.');
    }

    public function destroy(Request $request, Portfolio $portfolio): RedirectResponse
    {
        $this->authorize('delete', $portfolio);

        if ($portfolio->image_path !== null) {
            Storage::disk('public')->delete($portfolio->image_path);
        }

        $portfolio->delete();

        return back()->with('status', 'Portfolio item removed.');
    }
}
