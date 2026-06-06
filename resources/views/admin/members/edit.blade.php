@extends('layouts.admin')
@section('title', 'Edit member')
@section('page-title', 'Edit member')
@section('page-description', 'Update profile and borrowing category')
@section('content')
<form method="POST" action="{{ route('admin.members.update', $member) }}" class="panel mx-auto max-w-4xl p-6">@include('admin.members._form')</form>
@endsection
