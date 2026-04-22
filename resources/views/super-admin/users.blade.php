@extends('layouts.super-admin')

@section('page_title', 'User Accounts')
@section('page_subtitle', 'Role management, account activation control, and deletion approvals')

@section('content')
<section class="panel">
    <div class="panel-head"><h2> All User Accounts</h2></div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Deletion Request</th>
                <th>Role Change</th>
                <th>Activation</th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->full_name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role }}</td>
                    <td>
                        <span class="badge {{ $user->is_active ? 'approved' : 'rejected' }}">
                            {{ $user->is_active ? 'ACTIVE' : 'DEACTIVATED' }}
                        </span>
                    </td>
                    <td>
                        @if($user->account_deletion_requested_at)
                            <div class="action-row">
                                <span class="badge pending">REQUESTED</span>
                                <span class="muted">{{ $user->account_deletion_requested_at->format('M d, Y h:i A') }}</span>
                                <form method="POST" action="{{ route('super-admin.users.approve-deletion', $user) }}" onsubmit="return confirm('Approve this account deletion request? This will deactivate and remove the account.');">
                                    @csrf
                                    <button class="btn btn-danger mt-6" type="submit">Approve Deletion</button>
                                </form>
                            </div>
                        @elseif(in_array($user->role, ['parent', 'student'], true))
                            <span class="muted">No request</span>
                        @else
                            <span class="muted">N/A</span>
                        @endif
                    </td>
                    <td>
                        @if($user->role !== 'super_admin')
                            <form method="POST" action="{{ route('super-admin.users.update-role', $user) }}">
                                @csrf
                                <select name="role">
                                    <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>admin</option>
                                    <option value="parent" {{ $user->role === 'parent' ? 'selected' : '' }}>parent</option>
                                    <option value="student" {{ $user->role === 'student' ? 'selected' : '' }}>student</option>
                                </select>
                                <button class="btn btn-secondary mt-6" type="submit"> Update Role</button>
                            </form>
                        @else
                            <span class="muted">Locked</span>
                        @endif
                    </td>
                    <td>
                        @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('super-admin.users.toggle-active', $user) }}">
                                @csrf
                                <button class="btn {{ $user->is_active ? 'btn-danger' : 'btn' }}" type="submit">
                                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        @else
                            <span class="muted">Current account</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $users->links() }}</div>
</section>
@endsection
