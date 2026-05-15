<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | PractisBase</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body { background-color: var(--bg-canvas); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 2rem; }
        .auth-card { background: var(--bg-surface); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); width: 100%; max-width: 600px; }
        .auth-header { text-align: center; margin-bottom: 2rem; }
        .auth-header img { height: 50px; margin-bottom: 1rem; }
        
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--primary-navy); }
        .form-group input { width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-family: inherit; }
        
        /* The Legal Scroll Box */
        .legal-box { height: 200px; overflow-y: scroll; border: 1px solid var(--border-light); background: var(--bg-canvas); padding: 1rem; border-radius: var(--radius-md); font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem; }
        .legal-box h4 { color: var(--primary-navy); margin-top: 1rem; margin-bottom: 0.5rem; }
        .legal-box h4:first-child { margin-top: 0; }
        
        .checkbox-group { display: flex; align-items: flex-start; gap: 0.5rem; margin-bottom: 2rem; }
        .checkbox-group input { margin-top: 0.25rem; cursor: pointer; }
        
        .btn-submit { width: 100%; padding: 0.75rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; transition: opacity 0.2s; }
        .btn-submit:disabled { background: var(--text-muted); cursor: not-allowed; opacity: 0.5; }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="auth-header">
            <img src="/images/logo.png" alt="PractisBase">
            <h2>Join PractisBase</h2>
            <p style="color: var(--text-muted);">Create your professional account.</p>
        </div>

        <form action="/register-submit" method="POST" id="registerForm">
            <div class="form-group">
                <label>Full Name / Practice Name</label>
                <input type="text" name="name" required placeholder="Dr. John Borg">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="john@example.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--primary-navy);">Master Service Agreement & GDPR</label>
            <div class="legal-box" id="legalScrollBox">
                <h4>1. Nature of Service & Limitation of Liability</h4>
                <p>PractisBase ("The Software") provides database, administrative, and templating tools. PractisBase does not provide medical, architectural, engineering, or legal advice. The User (You) assumes full and total liability for any actions taken, documents generated, or diagnoses made using The Software. Under no circumstances shall PractisBase or its creators be held liable for malpractice, loss of business, data entry errors, or regulatory fines incurred by the User in the Republic of Malta or elsewhere.</p>
                
                <h4>2. GDPR & Data Roles</h4>
                <p>In accordance with the EU General Data Protection Regulation (GDPR), the User acts as the "Data Controller" for all client and patient information uploaded. PractisBase acts solely as the "Data Processor." We claim absolutely no ownership over your client data. You are solely responsible for obtaining legal consent from your clients/patients to store their data digitally.</p>
                
                <h4>3. System Uptime & Data Loss</h4>
                <p>While we employ enterprise-grade backups, The Software is provided "as-is" without warranty of absolute uptime. The User agrees to hold PractisBase harmless in the event of server outages or data corruption. We strongly advise Users to utilize the "Export" functionality regularly.</p>
                
                <p style="text-align: center; margin-top: 2rem; font-weight: bold; color: var(--primary-navy);">[ End of Agreement - Please scroll to the bottom to accept ]</p>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="acceptTerms" name="accept_terms" disabled>
                <label for="acceptTerms" style="font-size: 0.85rem; color: var(--text-main);">
                    I have read and agree to the Master Service Agreement, including the Limitation of Liability and GDPR Data Processor terms.
                </label>
            </div>

            <input type="hidden" name="read_duration_seconds" id="readDurationInput" value="0">

            <button type="submit" class="btn-submit" id="submitBtn" disabled>Create Account</button>
        </form>
    </div>

    <script>
        // Start the stopwatch when the page loads
        const pageLoadTime = Date.now();
        
        const legalBox = document.getElementById('legalScrollBox');
        const checkbox = document.getElementById('acceptTerms');
        const submitBtn = document.getElementById('submitBtn');
        const readDurationInput = document.getElementById('readDurationInput');

        // Logic to force scrolling
        legalBox.addEventListener('scroll', function() {
            // Check if user has scrolled to the bottom (with a 5px buffer for different browsers)
            if (legalBox.scrollTop + legalBox.clientHeight >= legalBox.scrollHeight - 5) {
                checkbox.disabled = false;
            }
        });

        // Logic to enable button and calculate time
        checkbox.addEventListener('change', function() {
            submitBtn.disabled = !this.checked;
            
            if(this.checked) {
                // Calculate how many seconds passed since the page loaded
                const timeAccepted = Date.now();
                const secondsSpent = Math.floor((timeAccepted - pageLoadTime) / 1000);
                readDurationInput.value = secondsSpent;
            }
        });
    </script>
</body>
</html>