<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRegistrationRequest;
use App\Models\Branch;
use App\Models\MemberCategory;
use App\Services\MemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MemberRegistrationController extends Controller
{
    public function create(): View
    {
        return view('public.member-register', [
            'branches' => Branch::query()->orderBy('name')->get(),
            'memberCategories' => MemberCategory::query()->orderBy('max_books')->get(),
        ]);
    }

    public function store(StoreMemberRegistrationRequest $request, MemberService $memberService): RedirectResponse
    {
        $memberService->register($request->validated());

        return redirect()
            ->route('member.register')
            ->with('success', 'Registration received. A librarian will review your membership.');
    }
}
