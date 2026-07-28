<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Profession | PractisBase</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body { background-color: var(--bg-canvas); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 2rem; }
        .auth-card { background: var(--bg-surface); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); width: 100%; max-width: 550px; }
        .auth-header { text-align: center; margin-bottom: 2rem; }
        .step-indicator { font-size: 0.85rem; color: var(--primary-cerulean); font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem; }
        
        .profession-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; }
        .prof-label { 
            border: 2px solid var(--border-light); 
            border-radius: var(--radius-md); 
            padding: 1rem; 
            text-align: center; 
            cursor: pointer; 
            transition: all 0.2s; 
            font-weight: 600; 
            color: var(--primary-navy); 
        }
        .prof-label:hover { border-color: var(--primary-cerulean); background: rgba(2, 132, 199, 0.05); }
        .prof-label.selected { border-color: var(--primary-cerulean); background: rgba(2, 132, 199, 0.1); }
        /* Hide actual radio buttons */
        .profession-grid input[type="radio"] { display: none; }

        .warrant-section { display: none; background: #f8fafc; padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--primary-navy); font-size: 0.9rem; }
        .form-group select, .form-group input { width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-family: inherit; font-size: 0.95rem; }
        
        .btn-submit { width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.2s; }
        .btn-submit:hover { background: var(--primary-cerulean-hover); }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="auth-header">
            <div class="step-indicator">Step 2 of 3</div>
            <h2 style="color: var(--primary-navy); margin-bottom: 0.25rem;">What is your profession?</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">This customizes your dashboard and Pro features for your sole-trader practice.</p>
        </div>

        <form action="/onboarding/profession-submit" method="POST">
            @csrf
            <div class="profession-grid">
                <label class="prof-label" id="lbl_doctor">
                    <input type="radio" name="profession" value="Medical Professional" onchange="toggleWarrant(this)">
                    Medical Professional
                </label>
                <label class="prof-label" id="lbl_architect">
                    <input type="radio" name="profession" value="Architect / Perit" onchange="toggleWarrant(this)">
                    Architect / Perit
                </label>
                <label class="prof-label" id="lbl_engineer">
                    <input type="radio" name="profession" value="Engineer" onchange="toggleWarrant(this)">
                    Engineer
                </label>
                <label class="prof-label" id="lbl_tutor">
                    <input type="radio" name="profession" value="Tutor / Lecturer" onchange="toggleWarrant(this)">
                    Tutor / Lecturer
                </label>
                <label class="prof-label" id="lbl_other" style="grid-column: 1 / -1;">
                    <input type="radio" name="profession" value="Other" onchange="toggleWarrant(this)">
                    Other (Freelance, Consultant, etc.)
                </label>
            </div>

            <div id="customProfessionBox" style="display: none; background: #f8fafc; padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); margin-bottom: 1.5rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Please specify your profession</label>
                    <input type="text" name="custom_profession" id="customProfessionInput" list="professionSuggestions" placeholder="e.g. Accountant, Lawyer, Graphic Designer">
                    
                    <datalist id="professionSuggestions">
                        @if(isset($customProfessions))
                            @foreach($customProfessions as $prof)
                                <option value="{{ $prof }}">
                            @endforeach
                        @endif
                    </datalist>
                </div>
            </div>

            <div class="warrant-section" id="warrantBox">
                <h4 style="margin-top: 0; margin-bottom: 1rem; color: var(--primary-navy);">Official Warrant Details</h4>
                <div class="form-group">
                    <label>Warranting Body</label>
                    <select name="warrant_type" id="warrantType">
                        <option value="">Select Governing Body...</option>
                        <option value="Medical Council Malta">Medical Council Malta</option>
                        <option value="Periti Warranting Board">Periti Warranting Board</option>
                        <option value="Engineering Board">Engineering Board</option>
                        <option value="Other">Other / International</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Warrant Number</label>
                    <input type="text" name="warrant_number" id="warrantNumber" placeholder="e.g. 12345">
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn" disabled>Continue to Plan Selection</button>
        </form>
    </div>

    <script>
        function toggleWarrant(radio) {
            document.getElementById('submitBtn').disabled = false;

            document.querySelectorAll('.prof-label').forEach(lbl => lbl.classList.remove('selected'));
            radio.parentElement.classList.add('selected');

            const warrantBox = document.getElementById('warrantBox');
            const customProfessionBox = document.getElementById('customProfessionBox');
            const customInput = document.getElementById('customProfessionInput');
            
            const requiresWarrant = ['Medical Professional', 'Architect / Perit', 'Engineer'].includes(radio.value);
            const isOther = radio.value === 'Other';
            
            // Handle Warrant Box
            if (requiresWarrant) {
                warrantBox.style.display = 'block';
                const dropdown = document.getElementById('warrantType');
                if(radio.value === 'Medical Professional') dropdown.value = 'Medical Council Malta';
                if(radio.value === 'Architect / Perit') dropdown.value = 'Periti Warranting Board';
                if(radio.value === 'Engineer') dropdown.value = 'Engineering Board';
            } else {
                warrantBox.style.display = 'none';
                document.getElementById('warrantType').value = '';
                document.getElementById('warrantNumber').value = '';
            }

            // Handle Custom Profession Box
            if (isOther) {
                customProfessionBox.style.display = 'block';
                customInput.required = true;
                customInput.focus();
            } else {
                customProfessionBox.style.display = 'none';
                customInput.required = false;
                customInput.value = '';
            }
        }
    </script>
</body>
</html>