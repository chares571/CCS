@extends('layouts.master-admin')

@section('page_title', 'Super Administrator Dashboard')
@section('page_subtitle', 'Enrollment statistics and chart analytics')

@section('content')
@php
    $gradeTotal = max(1, (int) collect($stats['by_grade'])->sum());
    $genderTotal = max(1, (int) collect($genderStats)->sum());
    $male = (int) ($genderStats['male'] ?? 0);
    $female = (int) ($genderStats['female'] ?? 0);
    $knownGenderTotal = max(1, $male + $female);
    $malePct = round(($male / $knownGenderTotal) * 100);
    $femalePct = max(0, 100 - $malePct);
    $gradeGenderStats = $stats['by_grade_gender'] ?? [];
    $gradePalette = [
        ['#2f7ebd', '#63a6d9'],
        ['#d1732f', '#e4a45f'],
        ['#2f9d67', '#62be8f'],
        ['#7d63c7', '#a188dc'],
        ['#c05252', '#da7d7d'],
        ['#2f9aa4', '#67c1c9'],
        ['#b08a2f', '#d4b363'],
    ];
@endphp

<section class="dashboard-topbar panel">
    <div class="dash-head-left">
        <h2>Analytics Overview</h2>
        <p class="muted">Dashboard view for enrollment statistics and chart insights.</p>
    </div>
    <div class="dash-head-right">
        <a class="notif notif-link" href="{{ route('master.monitoring') }}" title="Pending applications awaiting action">
            <x-icon name="bell" />
            @if(($notificationCount ?? 0) > 0)
                <span class="notif-badge">{{ $notificationCount > 99 ? '99+' : $notificationCount }}</span>
            @endif
        </a>
        <div class="avatar">S</div>
    </div>
</section>

<section class="stats-grid dashboard-stats">
    <article class="stat-hero stat-blue"><span class="icon"><x-icon name="total" /></span><div><h3>{{ $stats['total'] }}</h3><p>Total Enrollees</p></div></article>
    <article class="stat-hero stat-lightblue"><span class="icon"><x-icon name="pending" /></span><div><h3>{{ $stats['pending'] }}</h3><p>Pending Applications</p></div></article>
    <article class="stat-hero stat-purple"><span class="icon"><x-icon name="reviewed" /></span><div><h3>{{ $stats['reviewed'] }}</h3><p>Internally Tagged Reviewed</p></div></article>
    <article class="stat-hero stat-green"><span class="icon"><x-icon name="approved" /></span><div><h3>{{ $stats['approved'] }}</h3><p>Enrolled Students</p></div></article>
</section>

<section class="chart-stack">
    <article class="panel chart-panel">
        <div class="panel-head"><h3>Enrollment Distribution per Grade Level</h3></div>
        <div class="grade-bar-graph" role="img" aria-label="Enrollment distribution by grade level">
            @foreach($stats['by_grade'] as $grade => $count)
                @php
                    $pct = round(($count / $gradeTotal) * 100);
                    $barHeight = max($pct, 8);
                    [$barStart, $barEnd] = $gradePalette[$loop->index % count($gradePalette)];
                    $maleByGrade = (int) data_get($gradeGenderStats, $grade.'.male', 0);
                    $femaleByGrade = (int) data_get($gradeGenderStats, $grade.'.female', 0);
                @endphp
                <div
                    class="grade-bar-item"
                    style="--bar-color-start: {{ $barStart }}; --bar-color-end: {{ $barEnd }};"
                    data-tooltip="Male: {{ $maleByGrade }}&#10;Female: {{ $femaleByGrade }}"
                >
                    <div class="grade-bar-value">{{ $count }}</div>
                    <div class="grade-bar-track">
                        <div class="grade-bar-fill" style="height: {{ $barHeight }}%;"></div>
                    </div>
                    <div class="grade-bar-label">{{ $grade }}</div>
                </div>
            @endforeach
        </div>
    </article>

    <article class="panel chart-panel">
        <div class="panel-head"><h3>Gender Distribution</h3></div>
        <div class="pie-wrap">
            <div class="pie-chart" style="background: conic-gradient(#2f7ebd 0 {{ $malePct }}%, #d64b9a {{ $malePct }}% 100%);"></div>
            <div class="pie-legend">
                <p><span class="dot d1"></span> Male: {{ $male }}</p>
                <p><span class="dot d2"></span> Female: {{ $female }}</p>
            </div>
        </div>
    </article>
</section>
@endsection
