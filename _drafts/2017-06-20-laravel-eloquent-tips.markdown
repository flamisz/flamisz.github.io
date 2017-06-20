---
layout: post
title:  "Laravel Eloquent tips"
date:   2017-06-20 09:00:00 +1400
categories: laravel eloquent
tags: laravel, eloquent

---

`Laravel Eloquent ORM` is a great tool to interact with our database. It is easy to use and well documented. 

Unfortunately sometimes we have to deal with an old, inherited database which we can't modify. In this case we need some tricks if we don't want to use raw SQL queries.

Some other times we need a functionality which is not exist yet or we just don't know it. A good example is the `withCount` method which was introduced in `Laravel 5.2`. Before that you needed some extra work, a little trick to place a column with the count of a relation on the result model.

As I mentioned, `Eloquent ORM` is well documented, but we can find some cool stuff if we check the `API Documentation`.

<!--more-->

## Tip 1: selectSub

Once I worked on project where I had to get data from a database table which was used in other part of the application so I couldn't modify it.

That table was attached to my table but in that table every `id` had a prefix. This table was in a `Polymorphic Relations` but the type of the relation was built in the `id`. 

Because `Laravel Eloquent` works differently I couldn't use a simple `Eloquent` relation but I wanted to use the `withCount` method or something that works like that so I can put the count of the related data into the result model.

I searched the `API Documentation` and found [selectSub](https://laravel.com/api/5.4/Illuminate/Database/Query/Builder.html#method_selectSub). With it you can add a subselect to the query. I used a normal `withCount` method and checked what kind of subquery were created by `Laravel` (note: [Listen to your SQL queries in Laravel](/sql_listen))

{% highlight php %}
<?php
(select count(*) from `question_histories` where `questions`.`id` = `question_histories`.`question_id`) as `histories_count`
{% endhighlight %}

After this I could create my own subquery and put into the whole query (the `concat` is a `MySql` string operator):

{% highlight php %}
<?php
Question::where('slug', $slug)
    ->withCount('histories')
    ->selectSub("select count(*) from comments where page_id = concat('qa-' ,questions.id)", 'comments_count')
    ->first();
{% endhighlight %}

## Tip 2: whereRaw and selectRaw

When I created a small full-text search field with `Vue.js` and `Laravel` on a `MySql` database (I know Laravel Scout with Algolia is a much better solution, but for this project in the beginning it was sufficient) I used this trick (note: [Simple full text search with Vue.js - Laravel - MySql](/search)).

{% highlight php %}
<?php
$this->validate($request, [
    'search' => ''
]);

$searchString = $request->search;

$where = "MATCH (title,body) AGAINST ('{$searchString}' IN NATURAL LANGUAGE MODE)";
$select = "id, title, slug, MATCH (title,body) AGAINST ('{$searchString}' IN NATURAL LANGUAGE MODE) AS score";

$questions = Question::selectRaw($select)
                     ->whereRaw($where)
                     ->take(10)->get();
return $questions;

{% endhighlight %}






