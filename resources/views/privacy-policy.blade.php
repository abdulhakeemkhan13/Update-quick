
<div class="container py-3">
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="mb-4">Privacy Policy</h2>
            <p>Last updated: {{ now()->format('F d, Y') }}</p>

            <p>This Privacy Policy describes how {{ config('app.name') }} (“we”, “our”, “us”) collects, uses, and protects your personal information.</p>

            <h5>1. Information We Collect</h5>
            <p>We collect information you provide directly to us, such as when you create an account, process transactions, or contact support.</p>

            <h5>2. How We Use Information</h5>
            <p>Your information is used to operate, maintain, and improve our ERP platform and related services.</p>

            <h5>3. Data Protection</h5>
            <p>We implement appropriate technical and organizational security measures to protect your data.</p>

            <h5>4. Sharing of Data</h5>
            <p>We do not sell your personal data. We may share limited information with third parties as required by law or to enable integrations (e.g., QuickBooks).</p>

            <h5>5. Your Rights</h5>
            <p>You can request access, correction, or deletion of your personal information at any time.</p>

            <h5>6. Contact Us</h5>
            <p>If you have any questions, please contact us at <a href="mailto:support@{{ Str::after(config('app.url'), '//') }}">support@{{ Str::after(config('app.url'), '//') }}</a>.</p>
        </div>
    </div>
</div>
