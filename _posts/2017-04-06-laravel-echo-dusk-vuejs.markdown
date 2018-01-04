---
layout: post
title:  "Let's try Laravel Echo, Dusk and Vue.js"
date:   2017-05-08 08:00:00 +1400
categories: laravel app
tags: laravel, echo, dusk, vuejs
twitter: 861512696939790336
twitter_description: "Post about creating an app using Laravel Echo, Pusher and VueJs."
twitter_image: "comments2.gif"
redirect_from: "/laravel/app/2017/05/08/laravel-echo-dusk-vuejs.html"
---

The best way to learn new things and features is build something on that feature. Of course you can learn the basics from video courses, but you have to use it to be confident.

That’s why I create a small “something” to learn and practice how to use and how **Laravel Echo** with **Pusher** works. It’s a kind of tutorial for me.

![Comment app gif]({{ site.url }}/assets/images/comments2.gif)

<!--more-->

Therefore I build a simple website with comment module where users will get live notifications about comments.

For front end I will use **vue.js** because I’d like to learn the basics.

Another thing I would like to practice is testing in **Laravel**, especially the new browser test, **Dusk**.

## Round one: brainstorming
The site will be very simple, at least at the beginning stage. It just has articles and on the article pages will be the comments.

**Guest user can**
* read articles
* read comments

**Logged in users can**
* read articles
* read comments
* write comments
* reply comments (v2)
* edit/delete own comments (v2)
* vote up/down comments (v2)

**Admin functions**
* Delete comments (v2)
* Set comments as spam (v2)

**Live Notifications**
* New comment(s) on page
* Somebody replied your comment (v2)
* Somebody voted up/down your comment (v2)

**Live log page for admin**
* Comments live refresh (v2)
* Comment page for users to seeeditdelete own comments (v2)
* Comment page for admin to seeset spamdelete comments (v2)

## Round two: database

**Design**

Maybe it’s a little bit “old school” but I like design the database on paper in the beginning. I know, maybe (sure) it will change later, but when I design the database I design the whole system in my head. And I like seeing the database, table names, field names, not just in the migration files.

For the users I use the Laravel built in user migrations with a little modification because I need admin user.

**Migration**

I need two tables:

* Comments table
* Articles table

I know I’ll need models and controllers as well, so I create a model with migration and controller:

{% highlight php %}
php artisan make:model Comment -mc
php artisan make:model Article -mc
{% endhighlight %}

* [Articles table](https://github.com/flamisz/laravel-echo/blob/master/database/migrations/2017_03_22_032837_create_articles_table.php)
* [Comments table](https://github.com/flamisz/laravel-echo/blob/master/database/migrations/2017_04_02_003005_create_comments_table.php)

**Seeding**

I need some dummy data. The simplest way is to use Laravel model factory and seeding.

* [ModelFactory.php](https://github.com/flamisz/laravel-echo/blob/master/database/factories/ModelFactory.php)
* [AdminUserSeeder.php](https://github.com/flamisz/laravel-echo/blob/master/database/seeds/AdminUserSeeder.php)
* [ArticleSeeder.php](https://github.com/flamisz/laravel-echo/blob/master/database/seeds/ArticleSeeder.php)
* [DatabaseSeeder.php](https://github.com/flamisz/laravel-echo/blob/master/database/seeds/DatabaseSeeder.php)

{% highlight php %}
php artisan db:seed
{% endhighlight %}

That's all, now I have dummy data in my database.


## Round three: first tests and views
TDD - test driven development

**Settings**

{% highlight php %}
phpunit.xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
{% endhighlight %}

**Feature tests**
* [read articles](https://github.com/flamisz/laravel-echo/tree/master/tests/Feature/ReadArticlesTest.php)
* [create comments](https://github.com/flamisz/laravel-echo/tree/master/tests/Feature/AddCommentTest.php)

**Unit tests**
* tests for the Article and Comment models

    [articles](https://github.com/flamisz/laravel-echo/tree/master/tests/Unit/ArticleTest.php)
    [comments](https://github.com/flamisz/laravel-echo/tree/master/tests/Unit/CommentTest.php)

I’m not a “maniac” TDD tester, so I’m using my browser for “visual” tests as well. I know you can create web application without open any browser, but that’s just not for me. I like to see my app during the development phase. But the tests are great of course, especially during refactoring.

Laravel uses [Bootstrap](http://getbootstrap.com) css framework for its basic views so in the beginning I will use it. Maybe later I’ll switch to [Bulma](http://bulma.io) .

## Create a form
The next step is to create the actual form where the users can submit the comments for the article. Let’s try [Laravel Dusk](https://laravel.com/docs/dusk).

**Settings**
Because Laravel Dusk runs in a separate process, we can't use the memory based sqlite database for testing. But we can create a separate `.env` file for dusk where we can set another default database connection. Don't forget to create the empty `database/testing.sqlite` file.

{% highlight php %}
.env.dusk.local
DB_CONNECTION=sqlite_testing

config/database.php
'sqlite_testing' => [
            'driver' => 'sqlite',
            'database' => database_path('testing.sqlite'),
            'prefix' => '',
],
{% endhighlight %}

Laravel Dusk fills in and submit the forms for us.

{% highlight php %}
php artisan dusk --filter CreateCommentTest
{% endhighlight %}

**Quick Tip**

Sometimes I had problems with Dusk, but usually this helped:
{% highlight php %}
php artisan view:clear
{% endhighlight %}

## Round four: Vue.js in action

**Laravel** helps you in the front-end as well. It has an API, called **Laravel Mix**, to compiling the application's assets (css, javascript).

{% highlight php %}
npm intall
npm run dev
{% endhighlight %}

The users can comment on an article's page. In the show Article controller's show method I call the `article.show` view. In that **blade** file will use the **vue** components.

{% highlight php %}
{% raw %}
@if (auth()->check())
    <comment-form article-id="{{ $article->id }}"></comment-form>
@else
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            Please <a href="{{ route('login') }}">login</a> to create comment.
        </div>
    </div>
@endif

<comment-list article="{{ $article->slug }}"></comment-list>
{% endraw %}
{% endhighlight %}

The [comment-form](https://github.com/flamisz/laravel-echo/tree/master/resources/assets/js/components/Comment-form.vue) component just a small input field, where a logged in user can publish the comment.

The [comment-list](https://github.com/flamisz/laravel-echo/tree/master/resources/assets/js/components/Comment-list.vue) component show the comments of the article. It loads the comments for the actual article with an ajax call:

{% highlight javascript %}
axios.get('/articles/' + this.article + '/comments')
    .then(response => this.comments = response.data);
{% endhighlight %}

It contains the [comment](https://github.com/flamisz/laravel-echo/tree/master/resources/assets/js/components/Comment.vue) component to show a simple comment.

In the header of the a comment I show the time, when the comment was created. First, I'd like to show it for 'humans' (eg: 2 days ago), secondly I'd like to refresh it, let's say every minute.

If I don't want to refresh it, I can do something like this in the [Comment model](https://github.com/flamisz/laravel-echo/tree/master/app/Comment.php):

{% highlight php %}
<?php
protected $appends = ['formatted_created_at'];

public function getFormattedCreatedAtAttribute()
{
    return $this->created_at->diffForHumans();
}
{% endhighlight %}

After this it will be always loaded to the json file of the model. So when I get the comments in the `comment-list` component (by the way, I use **axios** for Ajax calls), every comment's has this attribute. In the html we will see something like this (good practise to include the original time as well, good for testing for example).

{% highlight html %}
<time datetime="2017-05-07 12:44:19"> 2 hours ago</time>
{% endhighlight %}

But, I'd like to refresh it every minute. In the comment-list component I have a `now` data, and I refresh it every minute:

{% highlight javascript %}
window.setInterval(() => {
    this.now = (new Date()).getTime();
},1000*60);
{% endhighlight %}

I give this data as `props` to the child comment elements. In the comment component I have `computed property`:

{% highlight javascript %}
computed: {
    diffForHumans: function () {
        return moment(this.comment.created_at).from(this.now)
    }
}
{% endhighlight %}

A computed property in **vue.js** will re-evaluate when some of its dependencies have changed. I use `moment` javacript package to convert the time to readable for humans.

When a user publish a comment, the `comment-form` component post it to the Laravel, the backend. It saves it to the database and send back the comment, because we don't want to reload the page to show the new comment. The `comment-form` get this new comment, but somehow we have to notify the `comment-list` about this new comment. I use a `vue component` for this called `Event`. This `Event` component is available for every vue components. This is how they can communicate with each other.

{% highlight javascript %}
// app.js
window.Event = new Vue();

// Comment-form.vue
Event.$emit('comment-was-submitted', response.data);

// Comment-list.vue
Event.$on('comment-was-submitted', comment => {
    this.comments.unshift(comment);
});
{% endhighlight %}

That's all. The comments array gets this new comment, so it appears on the page.
Of course we have a validation for the form in the backend. If the `comment-form` catches an error from the backend, it shows it to the user:

{% highlight javascript %}
// catch after axios post
.catch(error => {
    this.bodyError = error.response.data.body[0];
    this.disableButton = false
});

// show the error in the form
<span class="help-block" v-if="bodyError" v-text="bodyError"></span>
{% endhighlight %}

## Round five: live notifications with Pusher

Laravel makes it easy to "broadcast" any events in the application. In the front-end we can subcribe to this channel by using [Laravel Echo](https://laravel.com/docs/broadcasting#receiving-broadcasts) Javascript library.

I use [Pusher](https://pusher.com) as broadcaster. They have free Sandbox Plan. More than enough to try this service.

I create an `event`, [CommentCreated.php](https://github.com/flamisz/laravel-echo/blob/master/app/Events/CommentCreated.php) and "fire" it when the comment is created:

{% highlight php %}
<?php
// CommentController.php
broadcast(new CommentCreated($comment))->toOthers();
{% endhighlight %}

The `comment-list.vue` component listen to this event:
{% highlight javascript %}
Echo.channel('comment.' + this.article)
    .listen('CommentCreated', (e) => {
        flash('New comment on page.');
        this.newComments.unshift(e.comment);
    });
{% endhighlight %}

I flash a message about this new comment (how I flash, see later) and push it to a `newComment` array. I could push it to the `comment` array, but I don't want to show it unless the user want to. I have two computed property in the `comment-list.vue` and this is how I show the new message label in the header. If the user click on the label, I show the new comments

{% highlight javascript %}
{% raw %}
computed: {
    hasNewComment: function () {
        return (!!this.newComments.length)
    },
    newCommentMessage: function () {
        return this.newComments.length + ' new'
    }
},

// show the label
<h3>Vue Comments
    <span v-if="hasNewComment" @click="loadNewComments" class="label label-info" style="cursor:pointer">
        {{ newCommentMessage }}
    </span>
</h3>

// show the new comments
loadNewComments() {
    this.comments = this.newComments.concat(this.comments);
    this.newComments = [];
}
{% endraw %}
{% endhighlight %}

### The Flash message
I learned this flash message technic from Jeffrey Way at a [Laracasts](https://laracasts.com) course. It's very simple, but it can be used both in back-end and front-end. Of course it can be upgraded to a more clever version, which has more options, but for now, it's ok.

Basically the [Flash component](https://github.com/flamisz/laravel-echo/blob/master/resources/assets/js/components/Flash.vue) uses the `Event` component. To use in the back-end, need to import this component in the basic layout and put a `flash` message to the session. In every Js we just call `flash` method to show a message. After 3 seconds it deletes the message.

{% highlight php %}
{% raw %}
<?php
// layout
    <flash message="{{ session('flash') }}"></flash>

// put a flash message to the session
    return redirect('/')->with('flash', 'This is the message!');

// call it on front-end
    flash('New comment on page.');
{% endraw %}
{% endhighlight %}

## Extra Dusk test

In this test we check, if the broadcast is working between browser pages.
{% highlight php %}
<?php
public function a_user_can_view_if_somebody_write_a_comment()
{
    $userWriter = factory(User::class)->create();
    $userReader = factory(User::class)->create();
    $article = factory(Article::class)->create();

    $this->browse(function ($first, $second) use ($article, $userWriter, $userReader) {
        $first->loginAs($userReader)
               ->visit("/articles/{$article->slug}")
               ->waitUntil('app.__vue__._isMounted');

        $second->loginAs($userWriter)
                ->visit("/articles/{$article->slug}")
                ->waitUntil('app.__vue__._isMounted')
                ->type('body', 'This is my comment')
                ->press('Publish');

        $first->waitFor('.label-info')
               ->press('.label-info')
               ->waitForText('This is my comment')
               ->assertSee($userWriter->name);
    });
}
{% endhighlight %}

## Conclusion

This is just the tip of the iceberg but after we learn the basics we can easily develop something bigger. For me the next step is to build a complete comment module with `VueJS` and integrate it to `Laravel`.

[Github](https://github.com/flamisz/laravel-echo) page of the app.

