@extends('layouts.enduser')

@section('page_title', 'My Profile')
@section('page_subtitle', 'Manage your account details and account access.')

@section('content')
@php
    $displayName = method_exists($user, 'displayName') ? $user->displayName() : trim((string) ($user->full_name ?? $user->email ?? 'User'));
    $photoUrl = method_exists($user, 'profilePhotoUrl') ? $user->profilePhotoUrl() : null;
@endphp

<section class="panel settings-profile">
    <div class="panel-head">
        <h2><span class="icon-inline"><x-icon name="profile" /> Profile</span></h2>
        <p class="muted">Update your personal account details.</p>
    </div>

    <div class="settings-profile-row">
        <div class="settings-profile-avatar" aria-hidden="true">
            @if($photoUrl)
                <img src="{{ $photoUrl }}" alt="Profile photo">
            @else
                <div class="settings-profile-initials">{{ method_exists($user, 'initials') ? $user->initials() : \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($displayName, 0, 2)) }}</div>
            @endif
        </div>
        <div class="settings-profile-meta">
            <p class="muted">Signed in as</p>
            <p class="settings-profile-name"><strong>{{ $displayName }}</strong></p>
            <p class="settings-profile-role">{{ $user->roleLabel() }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('homepage.profile.update') }}" class="mt-10">
        @csrf
        <label>Full Name</label>
        <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}" autocomplete="name" required>

        <label>Email Address</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="email" required>

        <button class="btn mt-10" type="submit">Save Profile</button>
    </form>

    <form method="POST" action="{{ route('homepage.profile.photo.update') }}" enctype="multipart/form-data" class="mt-10">
        @csrf
        <label>Profile Picture</label>
        <input type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp" required>
        <div class="settings-profile-actions mt-10">
            <button class="btn" type="submit">{{ $photoUrl ? 'Change Picture' : 'Upload Picture' }}</button>
        </div>
    </form>

    @if($photoUrl)
        <form method="POST" action="{{ route('homepage.profile.photo.remove') }}" class="mt-10">
            @csrf
            <button class="btn btn-danger" type="submit">Remove Picture</button>
        </form>
    @endif
</section>

<section class="panel">
    <div class="panel-head">
        <h2><span class="icon-inline"><x-icon name="settings" /> Change Password</span></h2>
        <p class="muted">Use your current password to set a new one.</p>
    </div>

    <form method="POST" action="{{ route('homepage.profile.password.update') }}">
        @csrf

        <label>Current Password</label>
        <input type="password" name="current_password" autocomplete="current-password" required>

        <label>New Password</label>
        <input type="password" name="password" autocomplete="new-password" required>

        <label>Confirm New Password</label>
        <input type="password" name="password_confirmation" autocomplete="new-password" required>

        <button class="btn mt-10" type="submit">Update Password</button>
    </form>
</section>

<section class="panel">
    <div class="panel-head">
        <h2><span class="icon-inline"><x-icon name="delete" /> Delete Account</span></h2>
        <p class="muted">Submit an account deletion request</p>
    </div>

    @if($user->account_deletion_requested_at)
        <div class="alert alert-warning">
            Deletion request submitted on {{ $user->account_deletion_requested_at->format('M d, Y h:i A') }}.
        </div>
    @else
        @php($showDeleteConfirmStep = $errors->has('delete_account') || $errors->has('current_password'))
        <form method="POST" action="{{ route('homepage.profile.destroy') }}" class="js-delete-request-form">
            @csrf
            @method('DELETE')

            <button class="btn btn-danger" type="button" id="showDeleteRequestStep" {{ $showDeleteConfirmStep ? 'hidden' : '' }}>
                Request Account Deletion
            </button>

            <div id="deleteRequestStep" class="mt-10" {{ $showDeleteConfirmStep ? '' : 'hidden' }}>
                <label>Current Password</label>
                <input type="password" name="current_password" id="deleteRequestPassword" autocomplete="current-password" {{ $showDeleteConfirmStep ? 'required' : '' }}>

                @error('current_password')
                    <div class="alert alert-error mt-10">{{ $message }}</div>
                @enderror

                @error('delete_account')
                    <div class="alert alert-error mt-10">{{ $message }}</div>
                @enderror

                <div class="settings-profile-actions mt-10">
                    <button class="btn btn-secondary" type="button" id="cancelDeleteRequestStep">Cancel</button>
                    <button class="btn btn-danger" type="submit">Continue</button>
                </div>
            </div>
        </form>
    @endif
</section>

@if(!$user->account_deletion_requested_at)
    <div class="logout-modal" id="deleteRequestConfirmModal" aria-hidden="true">
        <div class="logout-modal__backdrop" data-dismiss-delete-request></div>
        <div class="logout-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="deleteRequestTitle" aria-describedby="deleteRequestDesc">
            <div class="logout-modal__icon" aria-hidden="true">!</div>
            <h2 id="deleteRequestTitle">Confirm Deletion Request</h2>
            <p id="deleteRequestDesc">Are you sure you want to submit your account deletion request?</p>
            <div class="logout-modal__actions">
                <button type="button" class="btn btn-secondary" data-dismiss-delete-request>Cancel</button>
                <button type="button" class="btn btn-danger" id="deleteRequestConfirmBtn">Submit Request</button>
            </div>
        </div>
    </div>

    <script>
    (() => {
        const modal = document.getElementById('deleteRequestConfirmModal');
        const confirmButton = document.getElementById('deleteRequestConfirmBtn');
        const form = document.querySelector('.js-delete-request-form');
        const showStepButton = document.getElementById('showDeleteRequestStep');
        const cancelStepButton = document.getElementById('cancelDeleteRequestStep');
        const step = document.getElementById('deleteRequestStep');
        const passwordInput = document.getElementById('deleteRequestPassword');

        if (!modal || !confirmButton || !form || !step || !passwordInput) {
            return;
        }

        const dismissButtons = modal.querySelectorAll('[data-dismiss-delete-request]');
        const setStepOpen = (open) => {
            step.hidden = !open;
            passwordInput.required = open;

            if (showStepButton) {
                showStepButton.hidden = open;
            }

            if (open) {
                passwordInput.focus();
            } else {
                passwordInput.value = '';
                if (showStepButton) {
                    showStepButton.focus();
                }
            }
        };

        const openModal = () => {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            confirmButton.focus();
        };

        const closeModal = () => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        };

        if (showStepButton) {
            showStepButton.addEventListener('click', () => {
                setStepOpen(true);
            });
        }

        if (cancelStepButton) {
            cancelStepButton.addEventListener('click', () => {
                setStepOpen(false);
            });
        }

        form.addEventListener('submit', (event) => {
            if (form.dataset.skipConfirm === '1') {
                return;
            }

            event.preventDefault();
            openModal();
        });

        confirmButton.addEventListener('click', () => {
            form.dataset.skipConfirm = '1';
            form.submit();
        });

        dismissButtons.forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });

        if (!step.hidden) {
            passwordInput.required = true;
        }
    })();
    </script>
@endif
@endsection
