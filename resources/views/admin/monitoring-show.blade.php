@extends('layouts.admin')

@section('page_title', 'Enrollment Form')
@section('page_subtitle', 'View, print, and review enrollment application')
@section('page_title_above')
    <a class="btn btn-logout btn-icon" href="{{ route('admin.monitoring') }}" aria-label="Return to monitoring list">
        <x-icon name="back" />
        <span class="sr-only">Return</span>
    </a>
@endsection

@section('content')
<section class="panel print-hide">
    <div class="panel-head">
        <h3>Application #{{ $application->id }}</h3>
        <p class="muted">Status: <span class="badge {{ $application->status }}">{{ \App\Support\StatusLabel::for($application->status) }}</span></p>
    </div>
    <div class="action-row">
        <button class="btn" type="button" onclick="window.print()">Print</button>
    </div>
</section>

<section class="enrollment-paper-wrap">
    <article class="enrollment-paper">
        @include('enduser.partials.enrollment-paper-head', [
            'application' => $application,
        ])
        @include('enduser.partials.enrollment-form-fields', [
            'application' => $application,
            'readonly' => true,
        ])
    </article>
</section>

@if($application->canBeReviewedByAdmin())
    <section class="panel print-hide">
        <div class="panel-head">
            <h3>Review Action</h3>
            <p class="muted">Submit review after checking the enrollment form details above.</p>
        </div>
        <form method="POST" action="{{ route('admin.applications.review', $application) }}">
            @csrf
            <label>Review Remarks (optional)</label>
            <input type="text" name="remarks" placeholder="Enter remarks">
            <button class="btn mt-8" type="submit">Mark as Reviewed</button>
        </form>
    </section>
@endif
@endsection
