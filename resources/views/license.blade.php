<div class="container py-3">
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="mb-4">License Agreement</h2>
            <p>Last updated: {{ now()->format('F d, Y') }}</p>

            <p>Welcome to {{ config('app.name') }}. This License Agreement governs the use of our ERP and accounting platform.</p>

            <h5>1. Grant of License</h5>
            <p>You are granted a non-exclusive, non-transferable license to use this software under the terms described herein.</p>

            <h5>2. Restrictions</h5>
            <p>You may not copy, modify, distribute, sell, or sublicense the software without explicit permission.</p>

            <h5>3. Ownership</h5>
            <p>All rights, titles, and interests in the software remain with {{ config('app.name') }} and its licensors.</p>

            <h5>4. Termination</h5>
            <p>This license is effective until terminated. We may terminate your license if you fail to comply with the terms.</p>

            <h5>5. Disclaimer</h5>
            <p>The software is provided “as is” without warranty of any kind.</p>
        </div>
    </div>
</div>
