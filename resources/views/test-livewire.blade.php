@extends('layouts.app')

@section('content')
  @livewire('edit-seats-modal', ['layout' => 'test', 'event' => null, 'venue' => null, 'ticketTypes' => []])
@endsection
