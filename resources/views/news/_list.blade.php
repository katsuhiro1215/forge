<ul class="news-list reveal">
  @foreach($posts as $post)
    <li><a href="{{ route('news.show', $post) }}">
      <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('Y.m.d') }}</time>
      <span class="news-type news-type-{{ $post->type }}">{{ $post->typeLabel() }}</span>
      <span class="news-title">{{ $post->title }}</span>
    </a></li>
  @endforeach
</ul>
