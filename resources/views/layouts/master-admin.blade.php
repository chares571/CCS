@extends('layouts.master')

@section('page_title', 'Super Administration')
@section('page_subtitle', 'School Enrollment Governance and Decision Management')

@section('sidebar')
<a class="sidebar-link {{ request()->routeIs('master.dashboard') ? 'active' : '' }}" href="{{ route('master.dashboard') }}">
    <span class="nav-ico"><x-icon name="dashboard" /></span> Dashboard
</a>
<a class="sidebar-link {{ request()->routeIs('master.monitoring*') ? 'active' : '' }}" href="{{ route('master.monitoring') }}">
    <span class="nav-ico"><x-icon name="monitor" /></span> Manage Enrollment
</a>
<a class="sidebar-link {{ request()->routeIs('master.enrolled-students') ? 'active' : '' }}" href="{{ route('master.enrolled-students') }}">
    <span class="nav-ico"><x-icon name="users" /></span> Enrolled Students
</a>
<a class="sidebar-link {{ request()->routeIs('master.enrollment-history') ? 'active' : '' }}" href="{{ route('master.enrollment-history') }}">
    <span class="nav-ico"><x-icon name="logs" /></span> Enrollment History
</a>
<a class="sidebar-link {{ request()->routeIs('master.school-years.*') ? 'active' : '' }}" href="{{ route('master.school-years.index') }}">
    <span class="nav-ico"><x-icon name="school-year" /></span> School Year Control
</a>
<a class="sidebar-link {{ request()->routeIs('master.reports.*') ? 'active' : '' }}" href="{{ route('master.reports.index') }}">
    <span class="nav-ico"><x-icon name="reports" /></span> Reports
</a>
<a class="sidebar-link {{ request()->routeIs('master.announcements.*') ? 'active' : '' }}" href="{{ route('master.announcements.index') }}">
    <span class="nav-ico"><x-icon name="announcements" /></span> Announcements
</a>
<a class="sidebar-link {{ request()->routeIs('master.users.*') ? 'active' : '' }}" href="{{ route('master.users.index') }}">
    <span class="nav-ico"><x-icon name="users" /></span> Users
</a>
<a class="sidebar-link {{ request()->routeIs('master.audit-logs.*') ? 'active' : '' }}" href="{{ route('master.audit-logs.index') }}">
    <span class="nav-ico"><x-icon name="logs" /></span> System Activity Logs
</a>
<a class="sidebar-link {{ request()->routeIs('master.backup.*') ? 'active' : '' }}" href="{{ route('master.backup.index') }}">
    <span class="nav-ico"><x-icon name="backup" /></span> Backup
</a>
<a class="sidebar-link {{ request()->routeIs('master.settings.*') ? 'active' : '' }}" href="{{ route('master.settings.index') }}">
    <span class="nav-ico"><x-icon name="settings" /></span> Account Settings
</a>
@endsection
