@extends('layouts.admin')
@section('title', 'Edit book')
@section('page-title', 'Edit book')
@section('page-description', 'Update metadata without resetting physical copies')
@section('content')
<form method="POST" action="{{ route('admin.books.update', $book) }}" class="panel mx-auto max-w-4xl p-6">@include('admin.books._form')</form>
@endsection
