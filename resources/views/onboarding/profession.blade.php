<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Profession | PractisBase</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        * { box-sizing: border-box; }
        body {
            background-color: var(--bg-canvas);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 1rem;
            margin: 0;
        }
        .auth-card {
            background: var(--bg-surface);
            padding: 1.35rem 1.25rem 1.75rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            width: 100%;
            max-width: 720px;
        }
        @media (min-width: 640px) {
            body { padding: 1.75rem; align-items: center; }
            .auth-card { padding: 2rem 2.25rem 2.25rem; }
        }
        .auth-header { text-align: center; margin-bottom: 1.5rem; }
        .step-indicator { font-size: 0.8rem; color: var(--primary-cerulean); font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.4rem; }
        .auth-header h2 { color: var(--primary-navy); margin: 0 0 0.35rem; font-size: 1.35rem; }
        .auth-header p { color: var(--text-muted); font-size: 0.88rem; margin: 0; line-height: 1.45; }
        .resume-note { margin-top: 0.55rem; font-size: 0.78rem; color: var(--text-muted); line-height: 1.4; }

        .profession-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.25rem; }
        .prof-label {
            border: 2px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: 0.9rem 0.65rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
            color: var(--primary-navy);
            font-size: 0.9rem;
        }
        .prof-label:hover { border-color: var(--primary-cerulean); background: rgba(2, 132, 199, 0.05); }
        .prof-label.selected { border-color: var(--primary-cerulean); background: rgba(2, 132, 199, 0.1); }
        .profession-grid input[type="radio"] { display: none; }

        .warrant-section, #customProfessionBox {
            display: none;
            background: #f8fafc;
            padding: 1.15rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-light);
            margin-bottom: 1.25rem;
        }
        .form-group { margin-bottom: 0.9rem; }
        .form-group:last-child { margin-bottom: 0; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.4rem; color: var(--primary-navy); font-size: 0.88rem; }
        .form-group select, .form-group input {
            width: 100%;
            padding: 0.7rem 0.8rem;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-family: inherit;
            font-size: 1rem;
            background: white;
        }

        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            background: var(--primary-cerulean);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
        }
        .btn-submit:disabled { background: var(--border-light); color: var(--text-muted); cursor: not-allowed; }
        .btn-submit:not(:disabled):hover { background: var(--primary-cerulean-hover); }
    </style>
    @include('partials.pwa-head')
</head>
<body>

    <div class="auth-card">
        <div class="auth-header">
            <div class="step-indicator">Step 2 of 3</div>
            <h2>What is your profession?</h2>
            <p>This sets your dashboard and profession tools for your sole trader work.</p>
            <p class="resume-note">Your account is already saved. If you leave, sign in again to continue from here.</p>
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

            <div id="customProfessionBox">
                <div class="form-group">
                    <label>Please specify your profession</label>
                    <input type="text" name="custom_profession" id="customProfessionInput" list="professionSuggestions" placeholder="e.g. Accountant, Lawyer">
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
                <h4 style="margin: 0 0 0.85rem; color: var(--primary-navy); font-size: 0.95rem;">Warrant details (optional)</h4>
                <div class="form-group">
                    <label>Warranting body</label>
                    <select name="warrant_choice" id="warrantChoice" onchange="handleWarrantChoice()">
                        <option value="blank">Prefer not to say</option>
                        <option value="main" id="warrantMainOption">Main body</option>
                        <option value="international">International body (specify)</option>
                    </select>
                </div>
                <div class="form-group" id="internationalBox" style="display: none;">
                    <label>Which international body?</label>
                    <input type="text" name="warrant_international" id="warrantInternational" placeholder="e.g. GMC, RIBA, ICE">
                </div>
                <div class="form-group">
                    <label>Warrant number <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
                    <input type="text" name="warrant_number" id="warrantNumber" placeholder="e.g. 12345">
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn" disabled>Continue</button>
        </form>
    </div>

    <script>
        const MAIN_BODIES = {
            'Medical Professional': 'Medical Council Malta',
            'Architect / Perit': 'Kamra tal-Periti',
            'Engineer': 'Engineering Board'
        };

        function handleWarrantChoice() {
            const choice = document.getElementById('warrantChoice').value;
            const intlBox = document.getElementById('internationalBox');
            const intlInput = document.getElementById('warrantInternational');
            if (choice === 'international') {
                intlBox.style.display = 'block';
                intlInput.required = true;
            } else {
                intlBox.style.display = 'none';
                intlInput.required = false;
                intlInput.value = '';
            }
        }

        function toggleWarrant(radio) {
            document.getElementById('submitBtn').disabled = false;
            document.querySelectorAll('.prof-label').forEach(lbl => lbl.classList.remove('selected'));
            radio.parentElement.classList.add('selected');

            const warrantBox = document.getElementById('warrantBox');
            const customProfessionBox = document.getElementById('customProfessionBox');
            const customInput = document.getElementById('customProfessionInput');
            const mainOption = document.getElementById('warrantMainOption');
            const requiresWarrant = Object.prototype.hasOwnProperty.call(MAIN_BODIES, radio.value);
            const isOther = radio.value === 'Other';

            if (requiresWarrant) {
                warrantBox.style.display = 'block';
                mainOption.textContent = MAIN_BODIES[radio.value];
                mainOption.disabled = false;
                document.getElementById('warrantChoice').value = 'main';
                handleWarrantChoice();
            } else {
                warrantBox.style.display = 'none';
                document.getElementById('warrantChoice').value = 'blank';
                document.getElementById('warrantNumber').value = '';
                handleWarrantChoice();
            }

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
