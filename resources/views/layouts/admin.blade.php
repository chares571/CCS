@extends('layouts.master')

@section('page_title', 'Admin Dashboard')
@section('page_subtitle', 'Operational Review and Monitoring')

@section('sidebar')
<a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
    <span class="nav-ico"><x-icon name="dashboard" /></span> Dashboard
</a>
<a class="sidebar-link {{ request()->routeIs('admin.monitoring') || request()->routeIs('admin.monitoring.show') ? 'active' : '' }}" href="{{ route('admin.monitoring') }}">
    <span class="nav-ico"><x-icon name="monitor" /></span> Monitoring
</a>
<a class="sidebar-link {{ request()->routeIs('admin.monitoring.hardcopy.*') ? 'active' : '' }}" href="{{ route('admin.monitoring.hardcopy.create') }}">
    <span class="nav-ico"><x-icon name="create" /></span> Assisted Enrollment
</a>
<a class="sidebar-link {{ request()->routeIs('admin.applications.index') ? 'active' : '' }}" href="{{ route('admin.applications.index') }}">
    <span class="nav-ico"><x-icon name="applications" /></span> Reviewed Enrollees
</a>
<a class="sidebar-link {{ request()->routeIs('admin.enrolled-students') ? 'active' : '' }}" href="{{ route('admin.enrolled-students') }}">
    <span class="nav-ico"><x-icon name="users" /></span> Enrolled Students
</a>
<a class="sidebar-link {{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}" href="{{ route('admin.announcements.index') }}" style="margin-top: 8px;">
    <span class="nav-ico"><x-icon name="announcements" /></span> Announcements
</a>
<a class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}" style="margin-top: 8px;">
    <span class="nav-ico"><x-icon name="settings" /></span> Account Settings
</a>
@endsection
