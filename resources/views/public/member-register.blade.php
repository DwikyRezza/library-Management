@extends('layouts.app')

@section('title', 'Member registration - LibraFlow')

@section('content')
<section class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
    <div>
        <p class="font-bold text-indigo-600 dark:text-indigo-400">Membership</p>
        <h1 class="mt-2 text-4xl font-black">Open the door to more learning.</h1>
        <p class="mt-4 leading-7 text-slate-500 dark:text-slate-400">Submit your campus details. A librarian will verify the registration before borrowing access is enabled.</p>
        <div class="panel mt-8 p-5">
            <h2 class="font-bold">What happens next?</h2>
            <ol class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                <li><strong>1.</strong> We receive your registration.</li>
                <li><strong>2.</strong> A librarian reviews your campus details.</li>
                <li><strong>3.</strong> Your member code becomes ready for circulation.</li>
            </ol>
        </div>
    </div>
    <form method="POST" action="{{ route('member.register.store') }}" class="panel grid gap-5 p-6 sm:grid-cols-2" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        <div><label class="label">First name</label><input class="input" name="first_name" value="{{ old('first_name') }}" required><x-field-error name="first_name" /></div>
        <div><label class="label">Last name</label><input class="input" name="last_name" value="{{ old('last_name') }}" required><x-field-error name="last_name" /></div>
        <div><label class="label">Email</label><input class="input" type="email" name="email" value="{{ old('email') }}" required><x-field-error name="email" /></div>
        <div><label class="label">Phone</label><input class="input" name="phone" value="{{ old('phone') }}"><x-field-error name="phone" /></div>
        <div><label class="label">Roll number</label><input class="input" name="roll_number" value="{{ old('roll_number') }}" required><x-field-error name="roll_number" /></div>
        <div><label class="label">Year</label><input class="input" type="number" min="1" max="8" name="year" value="{{ old('year') }}"><x-field-error name="year" /></div>
        <div><label class="label">Branch</label><select class="input" name="branch_id"><option value="">Select branch</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>@endforeach</select><x-field-error name="branch_id" /></div>
        <div><label class="label">Member category</label><select class="input" name="member_category_id" required><option value="">Select category</option>@foreach ($memberCategories as $category)<option value="{{ $category->id }}" @selected(old('member_category_id') == $category->id)>{{ $category->name }} ({{ $category->max_books }} books)</option>@endforeach</select><x-field-error name="member_category_id" /></div>
        <div class="sm:col-span-2"><button class="btn-primary w-full" :disabled="submitting"><span x-text="submitting ? 'Submitting...' : 'Submit registration'"></span></button></div>
    </form>
</section>
@endsection
