@php
    $flashToasts = array_filter([
        ['type' => 'success', 'message' => session('success')],
        ['type' => 'status', 'message' => session('status')],
        ['type' => 'error', 'message' => session('error')],
        ['type' => 'info', 'message' => session('info')],
    ], fn ($toast) => filled($toast['message']));
@endphp

@if(!empty($flashToasts))
    <div class="app-toast-stack" aria-live="polite" aria-atomic="true">
        @foreach($flashToasts as $index => $toast)
            @php($toastId = 'app-toast-dismiss-' . $index)
            <div class="app-toast-wrap">
                <input id="{{ $toastId }}" class="app-toast-dismiss-toggle" type="checkbox" hidden>
                <div class="app-toast app-toast-{{ $toast['type'] }}" role="status">
                    <span class="app-toast-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M8 12.5l2.6 2.6L16.5 9.5"></path>
                        </svg>
                    </span>
                    <p class="app-toast-message">{{ $toast['message'] }}</p>
                    <label for="{{ $toastId }}" class="app-toast-dismiss" aria-label="Dismiss notification">×</label>
                </div>
            </div>
        @endforeach
    </div>
@endif
