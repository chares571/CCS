@extends('layouts.master-admin')

@section('page_title', 'Enrollment Form')
@section('page_subtitle', 'View, print, and securely edit enrollment application')
@section('page_title_above')
    <a class="btn btn-logout btn-icon" href="{{ route('master.monitoring') }}" aria-label="Return to monitoring list">
        <x-icon name="back" />
        <span class="sr-only">Return</span>
    </a>
@endsection

@section('content')
<section class="panel print-hide">
    <div class="panel-head">
        <h3>Application #{{ $application->id }}</h3>
        <p class="muted">Status: <span class="badge {{ $application->status }}">{{ \App\Support\StatusLabel::forSuperAdmin($application->status) }}</span></p>
    </div>
    <div class="action-row">
        <button class="btn" type="button" onclick="window.print()">Print</button>
    </div>

    @if(!$canEdit)
        <form method="POST" action="{{ route('master.monitoring.unlock-edit', $application) }}">
            @csrf
            <label>Super Admin Password (required to edit)</label>
            <input type="password" name="password" required>
            <button class="btn mt-8" type="submit">Unlock Edit</button>
        </form>
    @else
        <div class="alert alert-success mt-10">Edit mode unlocked for this form. Save your updates below.</div>
    @endif
</section>
<section class="enrollment-paper-wrap">
    <article class="enrollment-paper">
        @include('enduser.partials.enrollment-paper-head', [
            'application' => $application,
        ])

        @if($canEdit)
            <form method="POST" action="{{ route('master.monitoring.update', $application) }}">
                @csrf
                @method('PUT')
                @include('enduser.partials.enrollment-form-fields', [
                    'application' => $application,
                    'readonly' => false,
                ])
                <div class="enrollment-actions print-hide">
                    <button class="btn" type="submit">Save Changes</button>
                </div>
            </form>
        @else
            @include('enduser.partials.enrollment-form-fields', [
                'application' => $application,
                'readonly' => true,
            ])
        @endif
    </article>
</section>
@endsection
