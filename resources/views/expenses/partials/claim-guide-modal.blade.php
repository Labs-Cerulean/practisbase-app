{{-- Sole-trader expense claim guide — keep copy plain; traps first. --}}
<div id="expense-claim-guide-modal" class="pb-modal" hidden>
    <div class="pb-modal-backdrop" data-close-expense-guide></div>
    <div class="pb-modal-panel" role="dialog" aria-modal="true" aria-labelledby="expense-claim-guide-title" style="max-width: 640px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.75rem;">
            <h2 id="expense-claim-guide-title" style="margin: 0; color: var(--primary-navy); font-size: 1.15rem;">What can I claim?</h2>
            <button type="button" data-close-expense-guide aria-label="Close" style="background: none; border: none; color: var(--text-muted); font-size: 1.35rem; line-height: 1; cursor: pointer; padding: 0 0.15rem;">&times;</button>
        </div>

        <p style="margin: 0 0 1rem; color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">
            For Maltese <strong style="color: var(--primary-navy);">sole traders</strong>, you may only deduct costs that are <strong style="color: var(--primary-navy);">wholly and exclusively</strong> for your practice — or the <strong style="color: var(--primary-navy);">business share</strong> of a mixed cost. Personal spending is not deductible, even if you pay it from the same account.
        </p>

        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.15rem;">
            <p style="margin: 0; font-size: 0.85rem; color: #92400e; line-height: 1.45; font-weight: 600;">Common traps — do not dump these in at 100%</p>
            <ul style="margin: 0.5rem 0 0; padding-left: 1.15rem; color: #78350f; font-size: 0.85rem; line-height: 1.5;">
                <li><strong>Car</strong> — purchase, insurance, road licence, servicing: only the practice-use share. Private commuting and family use are not claimable in full.</li>
                <li><strong>Fuel</strong> — same rule. A full tank used for mixed driving is not a full practice expense.</li>
                <li><strong>Home bills</strong> — electricity, internet, water, rent: only a fair share of the space/time used for work, not the whole household bill.</li>
                <li><strong>Personal insurance</strong> — life, private health, home contents for personal belongings: generally not practice deductions.</li>
            </ul>
        </div>

        <div style="display: grid; gap: 1rem; margin-bottom: 1.15rem;">
            <div>
                <h3 style="margin: 0 0 0.4rem; font-size: 0.9rem; color: var(--primary-navy);">Usually claimable (practice costs)</h3>
                <ul style="margin: 0; padding-left: 1.15rem; color: var(--text-muted); font-size: 0.85rem; line-height: 1.5;">
                    <li>Clinic/office rent, practice software, professional fees, warrant/registration, marketing for the practice</li>
                    <li>Equipment and tools used for clients (laptops and cars are often capital / wear &amp; tear — not always 100% in year one)</li>
                    <li>Travel that is genuinely for client work or practice duties (keep notes/receipts)</li>
                </ul>
            </div>
            <div>
                <h3 style="margin: 0 0 0.4rem; font-size: 0.9rem; color: var(--primary-navy);">Usually not claimable</h3>
                <ul style="margin: 0; padding-left: 1.15rem; color: var(--text-muted); font-size: 0.85rem; line-height: 1.5;">
                    <li>Groceries, holidays, personal clothing (unless a required uniform), gym, entertainment that is private</li>
                    <li>Fines and penalties</li>
                    <li>Your own drawings / salary to yourself (sole traders take profit, they do not “expense” drawings)</li>
                </ul>
            </div>
        </div>

        <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.15rem;">
            <p style="margin: 0 0 0.45rem; font-size: 0.85rem; font-weight: 600; color: var(--primary-navy);">Car, fuel &amp; working from home</p>
            <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted); line-height: 1.5;">
                If a cost is mixed (car, fuel, home office bills), only log what fairly belongs to the practice — or note the business share. Logging 100% of a household or family car cost is a frequent audit risk. When in doubt, keep a simple diary of practice days/trips and ask your advisor before claiming the edge cases.
            </p>
        </div>

        <p style="margin: 0 0 1.15rem; font-size: 0.75rem; color: var(--text-muted); line-height: 1.45;">
            This is a general sole-trader guide, not tax advice. Rules and evidence expectations can vary — confirm unusual items with a Maltese tax advisor before you file.
        </p>

        <button type="button" data-close-expense-guide style="width: 100%; padding: 0.75rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Got it</button>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('expense-claim-guide-modal');
    if (!modal) return;

    function openGuide(e) {
        if (e) e.preventDefault();
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    }
    function closeGuide() {
        modal.hidden = true;
        document.body.style.overflow = '';
    }

    document.querySelectorAll('[data-open-expense-guide]').forEach(function (el) {
        el.addEventListener('click', openGuide);
    });
    modal.querySelectorAll('[data-close-expense-guide]').forEach(function (el) {
        el.addEventListener('click', closeGuide);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) closeGuide();
    });
})();
</script>
