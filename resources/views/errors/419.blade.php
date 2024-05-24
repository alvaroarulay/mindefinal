@extends('layouts.app')
@section('content')
<p> Sesión Expirada</p>
<a href="{{route('login.show')}}">Iniciar Sesión</a>
@endsection