<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMemberRequest;
use App\Models\Branch;
use App\Models\Member;
use App\Models\MemberCategory;
use App\Services\MemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'category' => ['nullable', 'integer', 'exists:member_categories,id'],
        ]);

        $members = Member::query()
            ->with(['memberCategory', 'branch'])
            ->search($filters['q'] ?? null)
            ->approvalStatus($filters['status'] ?? null)
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('member_category_id', $category))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.members.index', [
            'members' => $members,
            'memberCategories' => MemberCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function pending(): View
    {
        return view('admin.members.pending', [
            'members' => Member::query()
                ->with(['memberCategory', 'branch'])
                ->approvalStatus(Member::STATUS_PENDING)
                ->oldest()
                ->paginate(15),
        ]);
    }

    public function show(Member $member): View
    {
        $member->load(['memberCategory', 'branch']);

        return view('admin.members.show', [
            'member' => $member,
            'transactions' => $member->transactions()
                ->with('bookCopy.book')
                ->latest('issued_at')
                ->paginate(10),
        ]);
    }

    public function edit(Member $member): View
    {
        return view('admin.members.edit', [
            'member' => $member,
            'branches' => Branch::query()->orderBy('name')->get(),
            'memberCategories' => MemberCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateMemberRequest $request, Member $member, MemberService $memberService): RedirectResponse
    {
        $memberService->update($member, $request->validated());

        return redirect()->route('admin.members.show', $member)->with('success', 'Member updated.');
    }

    public function destroy(Member $member, MemberService $memberService): RedirectResponse
    {
        $memberService->delete($member);

        return redirect()->route('admin.members.index')->with('success', 'Member archived.');
    }

    public function approve(Member $member, MemberService $memberService): RedirectResponse
    {
        $memberService->approve($member);

        return back()->with('success', 'Member approved.');
    }

    public function reject(Member $member, MemberService $memberService): RedirectResponse
    {
        $memberService->reject($member);

        return back()->with('success', 'Member rejected.');
    }
}
