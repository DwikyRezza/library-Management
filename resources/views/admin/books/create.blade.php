@extends('layouts.admin')
@section('title', 'Add book')
@section('page-title', 'Add book')
@section('page-description', 'Create metadata and generate physical copies')
@section('content')
<form method="POST" action="{{ route('admin.books.store') }}" class="panel mx-auto max-w-4xl p-6">@include('admin.books._form')</form>
@endsection
