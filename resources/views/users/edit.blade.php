@extends('layouts.app', ['title' => 'Edit User'])
@section('content')<div class="card"><div class="card-body p-4"><form method="post" action="{{ route('users.update',$user) }}">@method('put')@include('users.form')</form></div></div>@endsection
