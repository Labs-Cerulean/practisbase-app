<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pricing & Tiers | PractisBase</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/css/style.css">
    
    <style>
        /* Specific layouts for the public page, using our master variables */
        body { background-color: var(--bg-canvas); }
        .public-header { 
            padding: var(--space-md) 5%; 
            background: #fff; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: var(--shadow-sm); 
        }
        .pricing-container { max-width: 1100px; margin: 4rem auto; padding: 0 var(--space-lg); }
        .pricing-header { text-align: center; margin-bottom: 3rem; }
        .pricing-header h1 { color: var(--primary-navy); font-size: 2.5rem; margin-bottom: 1rem; }
        .pricing-header p { color: var(--text-muted); font-size: 1.125rem; }
        
        .pricing-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 2rem; 
        }
        .pricing-card { 
            background: var(--bg-surface); 
            border-radius: var(--radius-lg); 
            padding: 2.5rem; 
            box-shadow: var(--shadow-md); 
            border: 1px solid var(--border-light); 
            display: flex; 
            flex-direction: column; 
        }
        .pricing-card.popular { 
            border: 2px solid var(--primary-cerulean); 
            transform: scale(1.03); 
            position: relative; 
        }
        .popular-badge { 
            position: absolute; 
            top: -14px; 
            left: 50%; 
            transform: translateX(-50%); 
            background: var(--primary-cerulean); 
            color: white; 
            padding: 0.25rem 1rem; 
            border-radius: 20px; 
            font-size: 0.75rem; 
            font-weight: 700; 
            text-transform: uppercase; 
        }
        
        .tier-name { font-size: 1.25rem; font-weight: 700; color: var(--primary-navy); margin-bottom: 0.5rem; }
        .tier-price { font-size: 2.5rem; font-weight: 700; color: var(--primary-navy); margin-bottom: 1.5rem; }
        .tier-price span { font-size: 1rem; color: var(--text-muted); font-weight: 400; }
        
        .feature-list { list-style: none; padding: 0; margin-bottom: 2.5rem; flex-grow: 1; }
        .feature-list li { margin-bottom: 0.75rem; color: var(--text-main); display: flex; align-items: flex-start; gap: 0.5rem; }
        .feature-list li::before { content: '✓'; color: var(--primary-cerulean); font-weight: bold; }
        
        .btn-tier { 
            display: inline-block; 
            text-align: center; 
            width: 100%; 
            padding: 0.75rem; 
            border-radius: var(--radius-md); 
            font-weight: 600; 
            transition: all 0.2s ease; 
            cursor: pointer;
        }
        .btn-outline { background: transparent; border: 1px solid var(--primary-cerulean); color: var(--primary-cerulean); }
        .btn-outline:hover { background: rgba(2, 132, 199, 0.05); }
        .btn-solid { background: var(--primary-cerulean); color: white; border: 1px solid var(--primary-cerulean); }
        .btn-solid:hover { background: var(--primary-cerulean-hover); }
    </style>
</head>
<body>

    <header class="public-header">
        <div style="height: 40px; overflow: hidden; display: flex; align-items: center;">
            <img src="/images/logo.png" alt="PractisBase" style="height: 100%; object-fit: contain;">
        </div>
        <div>
            <a href="/" style="color: var(--primary-navy); font-weight: 600; margin-right: 1.5rem;">Log In</a>
            <a href="#" class="btn-tier btn-solid" style="padding: 0.5rem 1rem; width: auto;">Get Started</a>
        </div>
    </header>

    <main class="pricing-container">
        <div class="pricing-header">
            <h1>Simple, Professional Pricing</h1>
            <p>Built exclusively for Maltese professionals. Upgrade as your practice grows.</p>
        </div>

        <div class="pricing-grid">
            
            <div class="pricing-card">
                <div class="tier-name">Free</div>
                <div class="tier-price">€0<span>/mo</span></div>
                <ul class="feature-list">
                    <li><strong>Up to 5 Clients</strong></li>
                    <li>Basic Invoices & Ledger</li>
                    <li>Summary Dashboard</li>
                    <li>Standard Support</li>
                </ul>
                <a href="#" class="btn-tier btn-outline">Start for Free</a>
            </div>

            <div class="pricing-card popular">
                <div class="popular-badge">Most Popular</div>
                <div class="tier-name">Standard</div>
                <div class="tier-price">€29<span>/mo</span></div>
                <ul class="feature-list">
                    <li><strong>Unlimited Clients</strong></li>
                    <li>Custom Branding & Logo on Invoices</li>
                    <li>Expense Tracking & Receipts</li>
                    <li>Document & File Uploads</li>
                    <li><strong>Automated TA22 Form</strong></li>
                </ul>
                <a href="#" class="btn-tier btn-solid">Upgrade to Standard</a>
            </div>

            <div class="pricing-card">
                <div class="tier-name">Pro</div>
                <div class="tier-price">€59<span>/mo</span></div>
                <ul class="feature-list">
                    <li>All Standard Features</li>
                    <li><strong>Profession-Specific Tools:</strong></li>
                    <li>Patient Journals (Doctors)</li>
                    <li>Document Stamper (Architects & Periti)</li>
                    <li>Priority Support</li>
                </ul>
                <a href="#" class="btn-tier btn-outline">Go Pro</a>
            </div>

        </div>
    </main>

</body>
</html>