@extends('layouts.admin')
@section('title', 'Add category')
@section('page-title', 'Add category')
@section('page-description', 'Create a new catalog classification')
@section('content')
<form method="POST" action="{{ route('admin.categories.store') }}" class="panel mx-auto max-w-2xl p-6">@include('admin.categories._form')</form>
@endsection
