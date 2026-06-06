@extends('layouts.admin')
@section('title', 'Edit category')
@section('page-title', 'Edit category')
@section('page-description', 'Update category details without affecting books')
@section('content')
<form method="POST" action="{{ route('admin.categories.update', $category) }}" class="panel mx-auto max-w-2xl p-6">@include('admin.categories._form')</form>
@endsection
