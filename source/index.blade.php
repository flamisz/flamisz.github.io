---
pagination:
  collection: posts
  perPage: 25
---

@extends('_layouts.main')

@section('body')
    @foreach ($pagination->items as $post)
        <p class="text-sm">{{ date('F j, Y', $post->date) }}</p>
        <h3><a href="{{ $post->getUrl() }}">{{ $post->title }}</a></h3>
        <div>{!! $post->excerpt() !!}</div>
    @endforeach

    @if ($next = $pagination->next)
        <a href="{{ $page->baseUrl }}{{ $next }}">See more »</a>
    @endif
@endsection
