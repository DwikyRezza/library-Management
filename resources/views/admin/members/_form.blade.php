@csrf
@method('PUT')
<div class="grid gap-5 sm:grid-cols-2">
    <div><label class="label">First name</label><input class="input" name="first_name" value="{{ old('first_name', $member->first_name) }}" required><x-field-error name="first_name" /></div>
    <div><label class="label">Last name</label><input class="input" name="last_name" value="{{ old('last_name', $member->last_name) }}" required><x-field-error name="last_name" /></div>
    <div><label class="label">Email</label><input class="input" type="email" name="email" value="{{ old('email', $member->email) }}" required><x-field-error name="email" /></div>
    <div><label class="label">Phone</label><input class="input" name="phone" value="{{ old('phone', $member->phone) }}"><x-field-error name="phone" /></div>
    <div><label class="label">Roll number</label><input class="input" name="roll_number" value="{{ old('roll_number', $member->roll_number) }}" required><x-field-error name="roll_number" /></div>
    <div><label class="label">Year</label><input class="input" type="number" min="1" max="8" name="year" value="{{ old('year', $member->year) }}"><x-field-error name="year" /></div>
    <div><label class="label">Branch</label><select class="input" name="branch_id"><option value="">No branch</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}" @selected(old('branch_id', $member->branch_id) == $branch->id)>{{ $branch->name }}</option>@endforeach</select><x-field-error name="branch_id" /></div>
    <div><label class="label">Member category</label><select class="input" name="member_category_id" required>@foreach ($memberCategories as $category)<option value="{{ $category->id }}" @selected(old('member_category_id', $member->member_category_id) == $category->id)>{{ $category->name }} · {{ $category->max_books }} books</option>@endforeach</select><x-field-error name="member_category_id" /></div>
</div>
<div class="mt-6 flex justify-end gap-3"><a href="{{ route('admin.members.show', $member) }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save changes</button></div>
