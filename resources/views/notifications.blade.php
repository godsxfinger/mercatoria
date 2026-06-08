@extends('layouts.app')
@section('content')

<div class="notifications-container">
    <div class="notifications-card">
        <div class="notifications-header">
            <h1 class="notifications-title">Your Notifications</h1>
        </div>
        @if($notifications->isEmpty())
            <div class="notifications-empty">
                <p>You don't have any notifications yet.</p>
            </div>
        @else
            <div class="notifications-list">
                @foreach($notifications as $notification)
                    <div class="notifications-item {{ !$notification->pivot->read ? 'notifications-item-unread' : '' }}">
                        <div class="notifications-item-header">
                            <h3 class="notifications-item-title">
                                {{ $notification->title }}
                            </h3>
                            <span class="notifications-time">
                                {{ $notification->created_at->format('d-m-Y / H:i') }}
                            </span>
                        </div>
                        <div class="notifications-item-meta">
                            @if($notification->type === 'bulk')
                                <span class="notifications-item-from">
                                    From: {{ $notification->sender->username ?? 'System' }}
                                </span>
                            @endif
                            <span class="notifications-item-meta-pill">
                                {{ $notification->pivot->read ? 'Read' : 'Unread' }}
                            </span>
                        </div>
                        <p class="notifications-item-message">
                            {{ $notification->message }}
                        </p>
                        <div class="notifications-item-footer">
                            <div class="notifications-actions">
                                @if(!$notification->pivot->read)
                                    <form method="POST" action="{{ route('notifications.mark-read', ['notification' => $notification->id]) }}">
                                        @csrf
                                        <button type="submit" class="notifications-btn notifications-btn-read">Mark as Read</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('notifications.destroy', ['notification' => $notification->id]) }}">
                                    @csrf
                                    <button type="submit" class="notifications-btn notifications-btn-delete">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="notifications-pagination">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
