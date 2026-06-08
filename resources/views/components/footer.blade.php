<footer class="footer">
    <div class="footer-container">
        <div class="footer-content">
            <a href="{{ route('pgp-key') }}" class="footer-button">PGP Key</a>
            @if(config('marketplace.show_javascript_warning'))
                <div class="footer-javascript-warning-left">
                    <span class="footer-javascript-warning-text-left">Please Disable JavaScript</span>
                </div>
            @endif
            @if(config('marketplace.show_javascript_warning'))
                <div class="footer-javascript-warning-right">
                    <img src="{{ asset('images/javascript-logo.png') }}" alt="JavaScript Logo" class="footer-javascript-warning-icon">
                    <span class="footer-javascript-warning-text-right">Warning</span>
                    <img src="{{ asset('images/javascript-warning.gif') }}" alt="JavaScript Warning" class="footer-javascript-warning-gif">
                </div>
            @endif
            <a href="{{ route('canary') }}" class="footer-button">Canary</a>
        </div>
    </div>
</footer>
