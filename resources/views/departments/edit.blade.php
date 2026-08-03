@extends('layouts.app', ['title' => 'Edit Bagian'])
@section('content')<div class="card"><div class="card-body p-4"><form method="post" action="{{ route('departments.update',$department) }}">@method('put')@include('departments.form')</form></div></div>@endsection
