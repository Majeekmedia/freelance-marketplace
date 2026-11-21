@extends('layouts.admin')

@section('title', 'Site settings')

@section('content')
<h1>Site settings</h1>
<p class="sub">
    The brand name and logo are data, not code. Changing them here changes every
    page, including this console's header.
</p>

<form method="POST" action="{{ route('admin.settings.site.update') }}" enctype="multipart/form-data">
    @csrf

    <div class="split">
        <div>
            <div class="card">
                <h2 class="section-head">Brand</h2>

                <div class="field">
                    <label for="brand_name">Site name</label>
                    <input id="brand_name" name="brand_name" type="text" required
                           minlength="2" maxlength="60"
                           value="{{ old('brand_name', $settings['brand_name']) }}">
                    <p class="hint">Appears in the header, page titles and outgoing email.</p>
                </div>

                <div class="field">
                    <label for="brand_tagline">Tagline <span class="optional">(optional)</span></label>
                    <input id="brand_tagline" name="brand_tagline" type="text" maxlength="120"
                           value="{{ old('brand_tagline', $settings['brand_tagline']) }}">
                </div>

                <div class="field">
                    <label for="logo">Logo <span class="optional">(optional)</span></label>
                    <input id="logo" name="logo" type="file"
                           accept="image/png,image/jpeg,image/webp">
                    <p class="hint">
                        PNG, JPEG or WebP, up to 2 MB. SVG is refused on purpose — an SVG can
                        carry script and would run inside an admin session.
                    </p>

                    @if (\App\Support\LogoUploader::url())
                        <div class="logo-preview">
                            <img src="{{ \App\Support\LogoUploader::url() }}" alt="Current logo">
                            <label class="logo-remove">
                                <input type="checkbox" name="remove_logo" value="1">
                                Remove the logo and go back to the wordmark
                            </label>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <h2 class="section-head">Contact</h2>

                <div class="duo">
                    <div class="field">
                        <label for="contact_email">Contact email</label>
                        <input id="contact_email" name="contact_email" type="email" required
                               maxlength="254"
                               value="{{ old('contact_email', $settings['contact_email']) }}">
                    </div>
                    <div class="field">
                        <label for="contact_phone">Contact phone <span class="optional">(optional)</span></label>
                        <input id="contact_phone" name="contact_phone" type="text" maxlength="40"
                               value="{{ old('contact_phone', $settings['contact_phone']) }}">
                    </div>
                </div>

                <div class="field" style="margin-bottom:0">
                    <label for="support_hours">Support hours <span class="optional">(optional)</span></label>
                    <input id="support_hours" name="support_hours" type="text" maxlength="120"
                           value="{{ old('support_hours', $settings['support_hours']) }}"
                           placeholder="Mon–Fri, 09:00–17:00 UTC">
                </div>
            </div>

            <div class="form-actions">
                <button class="btn" type="submit">Save settings</button>
                <a class="btn btn-secondary" href="{{ route('admin.overview') }}">Cancel</a>
            </div>
        </div>

        <div>
            <div class="card">
                <h2 class="section-head">Where it appears</h2>
                <ul class="plain-list">
                    <li>The header wordmark and logo on the customer site</li>
                    <li>The header of this admin console</li>
                    <li>Every page title</li>
                    <li>The footer and outgoing email</li>
                </ul>
                <p class="note" style="margin-top:.9rem">
                    No template contains the brand name literally. A test fails the build
                    if one ever does, so these settings cannot be silently overridden.
                </p>
            </div>
        </div>
    </div>
</form>
@endsection

@push('styles')
<style>
    .section-head{font-family:var(--serif);font-weight:400;font-size:1.05rem;margin:0 0 .9rem}
    .field{margin-bottom:1rem}
    .field label{display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.35rem}
    .field .optional{color:var(--ink-3);font-weight:400}
    .field input{
        width:100%;padding:.6rem .7rem;border:1px solid var(--rule-2);border-radius:6px;
        font:inherit;font-size:.9rem;background:#fff;color:var(--ink);min-width:0;
    }
    .field input[type="file"]{padding:.5rem;font-size:.85rem}
    .field input:focus{outline:2px solid var(--forest);outline-offset:1px;border-color:var(--forest)}
    .hint{font-size:.75rem;color:var(--ink-3);margin:.3rem 0 0;line-height:1.5}
    .form-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1.1rem}

    .logo-preview{margin-top:.9rem;padding-top:.9rem;border-top:1px solid var(--rule)}
    .logo-preview img{display:block;max-height:56px;width:auto;margin-bottom:.7rem}
    .logo-remove{display:flex;align-items:flex-start;gap:.5rem;font-size:.8rem;color:var(--ink-2);cursor:pointer;line-height:1.45}
    .logo-remove input{width:15px;height:15px;margin:.1rem 0 0;flex:none}

    .plain-list{margin:0;padding-left:1.1rem;font-size:.82rem;color:var(--ink-2);line-height:1.65}
    .plain-list li{margin-bottom:.4rem}
    .plain-list li:last-child{margin-bottom:0}
    .note{font-size:.76rem;color:var(--ink-3);line-height:1.55}

    @media (max-width:560px){ .form-actions .btn{flex:1} }
</style>
@endpush
