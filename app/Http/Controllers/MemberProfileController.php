<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\MemberCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberProfileController extends Controller
{
    /**
     * Show the member's profile form.
     */
    public function show(): View
    {
        $member = auth('member')->user();

        return view('member.profile', [
            'member' => $member,
            'branches' => Branch::query()->orderBy('name')->get(),
            'memberCategories' => MemberCategory::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the member's profile details.
     */
    public function update(Request $request): RedirectResponse
    {
        $member = auth('member')->user();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('members', 'username')->ignore($member->id),
            ],
            'phone' => ['required', 'string', 'max:50'],
            'roll_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('members', 'roll_number')->ignore($member->id),
            ],
            'branch_id' => ['required', 'exists:branches,id'],
            'year' => ['required', 'integer', 'between:1,8'],
            'member_category_id' => ['required', 'exists:member_categories,id'],
        ]);

        $member->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'username' => $data['username'],
            'phone' => $data['phone'],
            'roll_number' => $data['roll_number'],
            'branch_id' => $data['branch_id'],
            'year' => $data['year'],
            'member_category_id' => $data['member_category_id'],
        ]);

        $member->save();

        return redirect()
            ->route('books.search')
            ->with('success', 'Profil Anda berhasil diperbarui.');
    }
}
