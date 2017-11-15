---
layout: post
title:  "Twitter as comment system"
date:   2017-11-15 12:00:00 +1400
categories: tips
tags: twitter, jekyll
twitter: 930595790128427009
twitter_description: "Post about how I'm using Twitter as a comment system on my Jekyll GitHub Page."
twitter_image: "twitter-comment.jpg"
---

Yesterday I read a tweet from  [@freekmurze](https://twitter.com/freekmurze/status/930175357294141440):
![tweet]({{ site.url }}/assets/images/tweet-2017-11-14.jpg)

When I created this little blog site I had the same feeling. I didn't want to use disqus and I wanted to use the basic GitHub Pages Jekyll functions only. So I decided to use twitter as a small comment system.

In this post I show you how I did it.

<!--more-->

I have a `twitter` tag in the post's front matter which stores the tweet's status code. This tweet will be our `comment` tweet for this blog post. I like to share the blog post and use that as a comment tweet, it's a little bit tricky, because first you need the blog post, share it on `twitter` and than modify the markdown to put the proper status code in it. I can live with it.

{% highlight html %}
{% raw %}
twitter: 864754806119727104
twitter_description: "Post about how I'm using Twitter as a comment system on my Jekyll GitHub Page."
twitter_image: "twitter-comment.jpg"
{% endraw %}
{% endhighlight %}

The other two twitter specific tags are for the twitter card. I have a `twitter-card.html` include, which I use in the page's header

{% highlight html %}
{% raw %}
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:site" content="{{ site.twitter_username }}" />
<meta name="twitter:title" content="{% if page.title %}{{ page.title | escape }}{% else %}{{ site.title | escape }}{% endif %}" />
<meta name="twitter:description" content="{% if page.twitter_description %}{{ page.twitter_description | escape }}{% else %}{{ site.description | escape }}{% endif %}" />
<meta name="twitter:image" content="{% if page.twitter_image %}{{ "/assets/images/" | append: page.twitter_image | absolute_url }}{% else %}{{ "/assets/images/default_twitter.jpg" | absolute_url }}{% endif %}" />
{% endraw %}
{% endhighlight %}

This is how my `twitter-box.html` looks like. I include it in the post layout. It's just an easy way by Twitter to embed a tweet on your website with `widgets.js`.
{% highlight html %}
{% raw %}
<div class="flex-container">
    <div class="comment">
        <h2>Comment on Twitter</h2>
        <p>You can leave a comment by replying this tweet.</p>
    </div>

    <div class="twitter-box">
        <blockquote class="twitter-tweet" data-cards="hidden" data-lang="en" data-width="380" data-link-color="#4caf50">
            <a href="https://twitter.com/flamisz/status/{{ page.twitter }}"></a>
        </blockquote>
        <script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>
    </div>
</div>
{% endraw %}
{% endhighlight %}

Include the `twitter-card` in the post layout. The good thing is that, you can see the likes, replays and retweets on this card. Of course it's clickable so the readers can easily reply or retweet it.
{% highlight html %}
{% raw %}
{% if page.twitter %}
    {% include twitter-box.html %}
{% endif %}
{% endraw %}
{% endhighlight %}

So the basic steps are:
* Put a twitter tag with a tweet's status code into the post front matter
* Use the twitter embedding code somewhere in your layout

You can check my whole code for this blog [here](https://github.com/flamisz/flamisz.github.io).
