@extends('layouts.app', ['title' => 'Tambah User'])
@section('content')<div class="card"><div class="card-body p-4"><form method="post" action="{{ route('users.store') }}">@include('users.form')</form></div></div>@endsection
