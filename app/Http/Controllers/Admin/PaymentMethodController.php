<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\PaymentMethodType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethodRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin payment method management (FR-038).
 *
 * Everything here is behind the `admin` gate, which returns 404 rather than 403
 * to non-admins so the area is not even confirmed to exist.
 */
class PaymentMethodController extends Controller
{
    public function index(): View
    {
        return view('admin.payment-methods.index', [
            // Ordered by type then sort order so the admin can see at a glance
            // whether they have a bank option enabled for someone who cannot or
            // will not use crypto.
            'methods' => PaymentMethod::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.payment-methods.create', [
            'types' => PaymentMethodType::cases(),
        ]);
    }

    public function store(PaymentMethodRequest $request): RedirectResponse
    {
        $method = new PaymentMethod($request->methodAttributes());

        /*
         * `type` is set explicitly because it is deliberately not fillable.
         * It decides which fields are required, so it must never arrive purely
         * from request input and be trusted to shape its own validation.
         */
        $method->type = PaymentMethodType::from((string) $request->input('type'));
        $method->clearIrrelevantFields();
        $method->save();

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('status', 'Payment method added.');
    }

    public function edit(PaymentMethod $paymentMethod): View
    {
        return view('admin.payment-methods.edit', [
            'method' => $paymentMethod,
            'types' => PaymentMethodType::cases(),
        ]);
    }

    public function update(PaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->fill($request->methodAttributes());
        $paymentMethod->type = PaymentMethodType::from((string) $request->input('type'));
        $paymentMethod->clearIrrelevantFields();
        $paymentMethod->save();

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('status', 'Payment method updated.');
    }

    /**
     * Enable or disable.
     *
     * Disabling is the normal way to retire a method: it disappears from the
     * funding page on the next load while every existing deposit request keeps
     * its snapshot and stays readable (FR-038b).
     */
    public function toggle(PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->is_enabled = ! $paymentMethod->is_enabled;
        $paymentMethod->save();

        return back()->with(
            'status',
            $paymentMethod->is_enabled
                ? 'Payment method enabled — it is visible to posters again.'
                : 'Payment method disabled — it is hidden from posters.',
        );
    }

    /**
     * Soft-delete.
     *
     * Soft, never hard: deposit requests reference the method for history, and
     * a hard delete would leave those requests pointing at nothing (FR-038e).
     */
    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->delete();

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('status', 'Payment method removed. Existing deposit requests keep their record of it.');
    }
}
