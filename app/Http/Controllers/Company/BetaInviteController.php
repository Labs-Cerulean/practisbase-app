<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\BetaInviteCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BetaInviteController extends Controller
{
    public function index(): View
    {
        $invites = BetaInviteCode::query()
            ->with(['redeemedBy'])
            ->orderByDesc('id')
            ->paginate(40);

        return view('company.beta-invites', [
            'invites' => $invites,
            'packages' => BetaInviteCode::PACKAGES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pro_package' => 'required|in:'.implode(',', array_keys(BetaInviteCode::PACKAGES)),
            'label' => 'nullable|string|max:120',
            'expires_days' => 'nullable|integer|min:1|max:365',
        ]);

        $expiresDays = (int) ($data['expires_days'] ?? 30);

        $invite = BetaInviteCode::create([
            'code' => BetaInviteCode::generateCode($data['pro_package']),
            'pro_package' => $data['pro_package'],
            'label' => $data['label'] ?: null,
            'max_uses' => 1,
            'uses_count' => 0,
            'expires_at' => now()->addDays($expiresDays),
            'created_by_user_id' => Auth::id(),
        ]);

        return redirect('/company/beta-invites')
            ->with('success', 'Invite created: '.$invite->code.' ('.$invite->packageLabel().').');
    }

    public function revoke(int $id): RedirectResponse
    {
        $invite = BetaInviteCode::query()->where('id', $id)->firstOrFail();

        if ($invite->revoked_at === null) {
            $invite->update(['revoked_at' => now()]);
        }

        return redirect('/company/beta-invites')
            ->with('success', 'Invite '.$invite->code.' revoked.');
    }
}
