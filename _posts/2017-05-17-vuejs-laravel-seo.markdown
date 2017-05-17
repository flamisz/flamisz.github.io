---
layout: post
title:  "Vuejs and SEO with Laravel"
date:   2017-05-17 20:00:00 +1400
categories: laravel vuejs
tags: laravel, vuejs, seo
twitter: 
twitter_description: "Post about Vue.js and SEO. Can Google indexing pages built by Laravel with Vue components and Ajax API calls?"
twitter_image: "seo-vue-twitter.jpg"
---

I read this in the `Vue.js` documentation:

>Note that as of now, Google and Bing can index synchronous JavaScript applications just fine. Synchronous being the key word there. If your app starts with a loading spinner, then fetches content via Ajax, the crawler will not wait for you to finish. This means if you have content fetched asynchronously on pages where SEO is important, SSR might be necessary.

So I tried it.

![Seo Vue 1]({{ site.url }}/assets/images/seo-vue-01.jpg)

<!--more-->

I don't use SSR (server-side-rendering) but I'd like to know, how can the Google indexing a page which fetches content via Ajax. I wanted a proof it works.

Therefore I created a `Laravel` app. A very simple one which gets data from the database and show it on a page.

Without `Vue.js` and `Ajax` the code looks like this:

{% highlight javascript %}
{% raw %}
// routes/web.php
Route::get('articles/{article}', function (App\Article $article) {
    $article->load('comments.owner');
    return view('articles')->with('article', $article);
});

// resources/views/articles.blade.php
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">{{ $article->title }}</div>

                <div class="panel-body">
                    {{ $article->body }}
                </div>
            </div>
        </div>

        <div class="col-md-8 col-md-offset-2">
            <h3>Comments</h3>
        </div>
        
        @foreach ($article->comments as $comment)
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">{{ $comment->owner->name }}</div>
                
                    <div class="panel-body">
                        {{ $comment->body }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
{% endraw %}
{% endhighlight %}

I created the same page but this time I fetched the data via `Ajax` API calls:

{% highlight javascript %}
{% raw %}

// routes/web.php
Route::get('articles/{article}', function ($articleId) {
    return view('articles-vue')->with('articleId', $articleId);
});

// routes/api.php
Route::get('article', function () {
    return App\Article::find(request('article_id'));
});

Route::get('comments', function () {
    return App\Comment::where('article_id', request('article_id'))->with('owner')->get();
});

// resources/views/articles-vue.blade.php
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <v-article article-id="{{ $articleId }}"></v-article>
    </div>
</div>
@endsection

// resources/assets/js/components/Article.vue
<template>
    <div class="container">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">{{ article.title }}</div>

                <div class="panel-body">{{ article.body }}</div>
            </div>
        </div>

        <div class="col-md-8 col-md-offset-2">
            <h3>Comments</h3>
        </div>

        <comment v-for="comment in comments" :comment="comment" :key="comment.id"></comment>
    </div>
</template>

<script>
    export default {
        props: ['articleId'],
        data: function () {
            return {
                article: [],
                comments: []
            }
        },
        mounted() {
            console.log('Article mounted.');
            axios.get('/api/article', {
                    params: {
                        article_id: this.articleId
                    }
                })
                .then(response => this.article = response.data);
            axios.get('/api/comments', {
                    params: {
                        article_id: this.articleId
                    }
                })
                .then(response => this.comments = response.data);
        }
    }
</script>

// resources/assets/js/components/Comment.vue
<template>
    <div class="col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">{{ comment.owner.name }}</div>
        
            <div class="panel-body">
                {{ comment.body }}
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['comment'],
        mounted() {
            console.log('Comment mounted.');
        }
    }
</script>

{% endraw %}
{% endhighlight %}

The results of the two pages were the same. I installed the app to a server where I created two subdomains and added them two `Google` webmaster tool. 

The first try was the `Fetch as Google`. I clicked on the `Fetch and render` button and waited for the result. 
On the `Fetching` tab we can see the html source code. The main part of my page looked like this:

{% highlight html %}
{% raw %}
<div class="container">
    <div class="row">
        <v-article article-id="3"></v-article>
    </div>
</div>
{% endraw %}
{% endhighlight %}

Not too promising. BUT! On the rendering page I found this:

![Seo Vue 1]({{ site.url }}/assets/images/seo-vue-01.jpg)

So the `googlebot` can see what the users can see! Good news. But I didn't stop here. I requested the indexing and waiting. And waiting. And waiting. Sometimes I checked `google` site and searching for this:

`site:3-14.co final test`

Finally next day I got something:

![Seo Vue 2]({{ site.url }}/assets/images/seo-vue-02.jpg)

It worked. 

I tried one more thing. I was searching for this:

`site:3-14.co comments`

This way I could see if the normal page (without `Ajax`) is more recent on `Google`, but not:

![Seo Vue 3]({{ site.url }}/assets/images/seo-vue-03.jpg)

### Conclusion

We don't need to be afraid to use API and Ajax calls in our applications even if the `SEO` is important to our site, because `Google` can fetch the content properly. But don't forget:

>Synchronous being the key word there.








