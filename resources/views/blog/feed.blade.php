{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>Blog — Kinderentertainers.nl</title>
        <link>{{ route('blog.index') }}</link>
        <description>Tips en achtergrond over kinderentertainers, kinderfeestjes en boeken via Kinderentertainers.nl.</description>
        <language>nl-nl</language>
        <atom:link href="{{ route('blog.feed') }}" rel="self" type="application/rss+xml"/>
        @if ($updatedAt)
            <lastBuildDate>{{ $updatedAt->toRfc7231String() }}</lastBuildDate>
        @endif
        @foreach ($posts as $post)
            <item>
                <title>{{ $post->title }}</title>
                <link>{{ $post->canonicalUrl() }}</link>
                <guid isPermaLink="true">{{ route('blog.show', $post) }}</guid>
                @if ($post->published_at)
                    <pubDate>{{ $post->published_at->toRfc7231String() }}</pubDate>
                @endif
                <description>{{ $post->metaDescriptionText() }}</description>
            </item>
        @endforeach
    </channel>
</rss>
