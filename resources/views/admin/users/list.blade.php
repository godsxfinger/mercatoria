@extends('layouts.app')

@section('content')
@php
    $activeCutoff = now()->subHours(24);
    $baseQuery = array_filter([
        'role' => $roleFilter ?? null,
        'search' => $searchQuery ?? null,
        'per_page' => $perPage ?? 25,
    ], fn ($value) => $value !== null && $value !== '');

    $sortDirectionFor = function (string $column) use ($sortColumn, $sortDirection): string {
        if ($sortColumn === $column) {
            return $sortDirection === 'asc' ? 'desc' : 'asc';
        }

        return 'asc';
    };

    $sortUrlFor = function (string $column) use ($baseQuery, $sortDirectionFor): string {
        return route('admin.users', array_merge($baseQuery, [
            'sort' => $column,
            'direction' => $sortDirectionFor($column),
        ]));
    };
@endphp
<div class="user-list-container">
    <div class="user-list-card">
        <div class="user-list-header-row">
            <h2 class="user-list-title">{{ ($roleFilter ?? null) === 'vendor' ? 'Vendor List' : 'User List' }}</h2>
            <form action="{{ route('admin.users') }}" method="GET" class="user-list-search-form">
                @if(($roleFilter ?? null) === 'vendor')
                    <input type="hidden" name="role" value="vendor">
                @endif
                <div class="user-list-search-group">
                    <input
                        type="text"
                        name="search"
                        value="{{ $searchQuery ?? '' }}"
                        class="user-list-search-input"
                        placeholder="Search by username or exact ID"
                        aria-label="Search users"
                    >
                    <button type="submit" class="user-list-search-btn">Search</button>
                </div>
                @if(!empty($searchQuery))
                    <a href="{{ route('admin.users', array_filter(['role' => $roleFilter ?? null])) }}" class="user-list-clear-btn">Clear</a>
                @endif
            </form>
        </div>
        <div class="user-list-table-container">
            <table class="user-list-table">
                <colgroup>
                    <col class="user-list-col-id">
                    <col class="user-list-col-username">
                    <col class="user-list-col-last-login">
                    <col class="user-list-col-status">
                    <col class="user-list-col-action">
                </colgroup>
                <thead>
                    <tr>
                        <th class="{{ $sortColumn === 'id' ? 'user-list-th-active' : '' }}">
                            <a href="{{ $sortUrlFor('id') }}" class="user-list-sort-link">
                                <span>ID</span>
                                <span class="user-list-sort-chevron">{{ $sortColumn === 'id' ? ($sortDirection === 'asc' ? '▲' : '▼') : '▾' }}</span>
                            </a>
                        </th>
                        <th class="{{ $sortColumn === 'username' ? 'user-list-th-active' : '' }}">
                            <a href="{{ $sortUrlFor('username') }}" class="user-list-sort-link">
                                <span>Username</span>
                                <span class="user-list-sort-chevron">{{ $sortColumn === 'username' ? ($sortDirection === 'asc' ? '▲' : '▼') : '▾' }}</span>
                            </a>
                        </th>
                        <th class="{{ $sortColumn === 'last_login' ? 'user-list-th-active' : '' }}">
                            <a href="{{ $sortUrlFor('last_login') }}" class="user-list-sort-link">
                                <span>Last Login</span>
                                <span class="user-list-sort-chevron">{{ $sortColumn === 'last_login' ? ($sortDirection === 'asc' ? '▲' : '▼') : '▾' }}</span>
                            </a>
                        </th>
                        <th class="{{ $sortColumn === 'status' ? 'user-list-th-active' : '' }}">
                            <a href="{{ $sortUrlFor('status') }}" class="user-list-sort-link">
                                <span>Status</span>
                                <span class="user-list-sort-chevron">{{ $sortColumn === 'status' ? ($sortDirection === 'asc' ? '▲' : '▼') : '▾' }}</span>
                            </a>
                        </th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            if (is_null($user->last_login)) {
                                $statusKey = 'never_logged_in';
                                $statusLabel = 'Never Logged In';
                            } elseif ($user->last_login->greaterThanOrEqualTo($activeCutoff)) {
                                $statusKey = 'active';
                                $statusLabel = 'Active';
                            } else {
                                $statusKey = 'offline';
                                $statusLabel = 'Offline';
                            }
                        @endphp
                        <tr class="user-list-row">
                            <td>
                                <span class="user-list-id-text" title="{{ $user->id }}">{{ $user->id }}</span>
                            </td>
                            <td>
                                <span class="user-list-username-text">{{ $user->username }}</span>
                            </td>
                            <td>
                                <span class="user-list-last-login-text">{{ $user->last_login ? $user->last_login->format('Y-m-d / H:i') : 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="user-list-status-badge user-list-status-{{ $statusKey }}">{{ $statusLabel }}</span>
                            </td>
                            <td>
                                <a href="{{ route('admin.users.details', $user->id) }}" class="user-list-btn">User Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="user-list-empty-state">No users found for the current search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="user-list-pagination-bar">
            <div class="user-list-pagination-meta">
                <span>Total: {{ number_format($users->total()) }}</span>
                <span>Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }}</span>
            </div>
            <form action="{{ route('admin.users') }}" method="GET" class="user-list-page-size-form">
                @if(($roleFilter ?? null) === 'vendor')
                    <input type="hidden" name="role" value="vendor">
                @endif
                @if(!empty($searchQuery))
                    <input type="hidden" name="search" value="{{ $searchQuery }}">
                @endif
                <input type="hidden" name="sort" value="{{ $sortColumn }}">
                <input type="hidden" name="direction" value="{{ $sortDirection }}">
                <label for="user-list-per-page">Rows:</label>
                <select id="user-list-per-page" name="per_page">
                    @foreach([10, 25, 50] as $size)
                        <option value="{{ $size }}" {{ (int) $perPage === $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                </select>
                <button type="submit" class="user-list-page-size-btn">Apply</button>
            </form>
        </div>
        <div class="user-list-pagination">
            {{ $users->onEachSide(1)->links() }}
        </div>
    </div>
</div>
@endsection
