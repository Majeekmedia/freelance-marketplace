@php
    /**
     * Shared form for creating and editing a payment method (FR-038).
     *
     * One partial rather than two near-identical forms: a field added to create
     * and forgotten in edit is how a method ends up unsavable in one direction.
     *
     * @var \App\Models\PaymentMethod|null $method
     * @var array $types
     */
    $isEdit = isset($method) && $method !== null;
    $selected = old('type', $isEdit ? $method->type->value : $types[0]->value);
@endphp

<div class="field">
    <label for="label">Label</label>
    <input id="label" name="label" type="text" required minlength="3" maxlength="80"
           value="{{ old('label', $isEdit ? $method->label : '') }}"
           placeholder="e.g. Main bank account">
    <p class="hint">Shown to posters. Name the bank or asset, not an internal reference.</p>
</div>

<div class="field">
    <label for="type">Type</label>
    <select id="type" name="type" required data-pm-type>
        @foreach ($types as $type)
            <option value="{{ $type->value }}" @selected($selected === $type->value)>
                {{ $type->label() }}
            </option>
        @endforeach
    </select>
    <p class="hint">Changing the type clears the fields that no longer apply.</p>
</div>

{{--
    Both field groups are rendered and shown/hidden by type.
    Hidden inputs are also `disabled` by the script, so a browser back-button
    state cannot submit a stale crypto address alongside a bank account.
--}}
<fieldset class="pm-group" data-pm-group="bank_transfer">
    <legend>Bank details</legend>

    <div class="duo">
        <div>
            <label for="bank_name">Bank name</label>
            <input id="bank_name" name="bank_name" type="text" maxlength="120"
                   value="{{ old('bank_name', $isEdit ? $method->bank_name : '') }}"
                   placeholder="GTBank">
        </div>
        <div>
            <label for="account_name">Account name</label>
            <input id="account_name" name="account_name" type="text" maxlength="120"
                   value="{{ old('account_name', $isEdit ? $method->account_name : '') }}"
                   placeholder="Name on the account">
        </div>
    </div>

    <div class="duo" style="margin-top:.8rem">
        <div>
            <label for="account_number">Account number</label>
            <input id="account_number" name="account_number" type="text" maxlength="60"
                   value="{{ old('account_number', $isEdit ? $method->account_number : '') }}"
                   placeholder="0123456789">
        </div>
        <div>
            <label for="routing_detail">Routing / sort code <span class="optional">(optional)</span></label>
            <input id="routing_detail" name="routing_detail" type="text" maxlength="160"
                   value="{{ old('routing_detail', $isEdit ? $method->routing_detail : '') }}"
                   placeholder="SWIFT, sort code, IBAN tail">
        </div>
    </div>
</fieldset>

<fieldset class="pm-group" data-pm-group="crypto">
    <legend>Crypto details</legend>

    <div class="duo">
        <div>
            <label for="crypto_asset">Asset</label>
            <input id="crypto_asset" name="crypto_asset" type="text" maxlength="20"
                   value="{{ old('crypto_asset', $isEdit ? $method->crypto_asset : '') }}"
                   placeholder="USDT">
        </div>
        <div>
            <label for="crypto_network">Network</label>
            <input id="crypto_network" name="crypto_network" type="text" maxlength="40"
                   value="{{ old('crypto_network', $isEdit ? $method->crypto_network : '') }}"
                   placeholder="TRC-20">
            <p class="hint">Must be exact. Sending on the wrong network loses the funds.</p>
        </div>
    </div>

    <div style="margin-top:.8rem">
        <label for="crypto_address">Wallet address</label>
        <input id="crypto_address" name="crypto_address" type="text" maxlength="120"
               value="{{ old('crypto_address', $isEdit ? $method->crypto_address : '') }}"
               placeholder="TXyz...">
    </div>
</fieldset>

<div class="field" style="max-width:200px">
    <label for="sort_order">Sort order <span class="optional">(optional)</span></label>
    <input id="sort_order" name="sort_order" type="number" min="0" max="9999"
           value="{{ old('sort_order', $isEdit ? $method->sort_order : 0) }}">
</div>

<label class="pm-check">
    <input type="checkbox" name="is_enabled" value="1"
           @checked(old('is_enabled', $isEdit ? $method->is_enabled : true))>
    <span>Enabled — visible to posters on the funding page</span>
</label>

@push('scripts')
<script>
/*
 * Show the field group for the chosen type and disable the other one.
 *
 * Disabling matters: a disabled input is not submitted, so the server never
 * receives a bank name for a crypto method (or vice versa) even if the browser
 * restores form state after a back navigation.
 */
(function () {
    var select = document.querySelector('[data-pm-type]');
    if (!select) return;

    var groups = Array.prototype.slice.call(document.querySelectorAll('[data-pm-group]'));

    function apply() {
        groups.forEach(function (group) {
            var matches = group.getAttribute('data-pm-group') === select.value;
            group.hidden = !matches;
            group.querySelectorAll('input, select, textarea').forEach(function (input) {
                input.disabled = !matches;
            });
        });
    }

    select.addEventListener('change', apply);
    apply();
})();
</script>
@endpush
