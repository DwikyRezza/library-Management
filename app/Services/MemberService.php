<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MemberService
{
    public function register(array $data): Member
    {
        return DB::transaction(function () use ($data): Member {
            return Member::query()->create([
                ...Arr::only($data, [
                    'first_name',
                    'last_name',
                    'username',
                    'email',
                    'password',
                    'phone',
                    'roll_number',
                    'branch_id',
                    'year',
                    'member_category_id',
                ]),
                'member_code' => $this->nextMemberCode(),
                'approved' => false,
                'rejected' => false,
                'books_borrowed_count' => 0,
            ]);
        });
    }

    public function approve(Member $member): Member
    {
        return DB::transaction(function () use ($member): Member {
            $member = Member::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();

            if ($member->approved && ! $member->rejected) {
                return $member;
            }

            if ($member->rejected) {
                throw ValidationException::withMessages([
                    'member' => 'Rejected members cannot be approved without a separate reactivation flow.',
                ]);
            }

            $member->forceFill([
                'approved' => true,
                'rejected' => false,
                'approved_at' => now(),
                'rejected_at' => null,
            ])->save();

            return $member->refresh();
        });
    }

    public function reject(Member $member): Member
    {
        return DB::transaction(function () use ($member): Member {
            $member = Member::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();

            if ($member->rejected && ! $member->approved) {
                return $member;
            }

            if ($member->approved) {
                throw ValidationException::withMessages([
                    'member' => 'Approved members cannot be rejected without a separate suspension flow.',
                ]);
            }

            $member->forceFill([
                'approved' => false,
                'rejected' => true,
                'approved_at' => null,
                'rejected_at' => now(),
            ])->save();

            return $member->refresh();
        });
    }

    public function update(Member $member, array $data): Member
    {
        return DB::transaction(function () use ($member, $data): Member {
            $member->fill(Arr::only($data, [
                'first_name',
                'last_name',
                'email',
                'phone',
                'roll_number',
                'branch_id',
                'year',
                'member_category_id',
            ]))->save();

            return $member->refresh();
        });
    }

    public function delete(Member $member): void
    {
        DB::transaction(function () use ($member): void {
            $member = Member::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();

            if ($member->transactions()->active()->exists()) {
                throw ValidationException::withMessages([
                    'member' => 'Member cannot be deleted while they have active borrowed books.',
                ]);
            }

            $member->delete();
        });
    }

    private function nextMemberCode(): string
    {
        do {
            $code = 'MBR-'.Str::upper(Str::substr((string) Str::ulid(), -8));
        } while (Member::withTrashed()->where('member_code', $code)->exists());

        return $code;
    }
}
