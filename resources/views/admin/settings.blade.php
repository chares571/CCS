@extends('layouts.admin')

@section('page_title', 'Account Settings')
@section('page_subtitle', 'Administrator account security settings')

@section('content')
<section class="panel">
    <div class="panel-head">
        <h3>Change Email and Password</h3>
        <p class="muted">You can change your mail address and set a new password for your Admin account.</p>
        <p class="muted">Current email: <strong>{{ $user->email }}</strong></p>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf

        <label>New Email Address</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required>

        <label>Current Password</label>
        <input type="password" name="current_password" autocomplete="current-password" required>

        <label>New Password</label>
        <input type="password" name="password" autocomplete="new-password" required>

        <label>Confirm New Password</label>
        <input type="password" name="password_confirmation" autocomplete="new-password" required>

        <button class="btn mt-10" type="submit">Save Changes</button>
    </form>
</section>
@endsection
