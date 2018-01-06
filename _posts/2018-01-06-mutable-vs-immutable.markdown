---
layout: post
title:  "Mutable vs Immutable problem"
date:   2018-01-06 12:00:00 +1400
categories: php laravel
tags: php
twitter:
twitter_description: "Mutable vs Immutable problem with intervention image library."
twitter_image: "mutable.jpg"
---

A few weeks ago I read an [article on medium](https://medium.com/@codebyjeff/whats-all-this-immutable-date-stuff-anyway-72d4130af8ce) about **mutable-immutable** objects and why we should care about them. The next day I read it, we got a "bug" in one of our functionality. I made that function and it turned out, it was a **mutable-immutable problem**.

<!--more-->

This functionality was a simple image handling: the user upload an image and we save it in different sizes. I use the [image.intervention.io](http://image.intervention.io/) library.

This is how the `imageSave` function looked like originally:

{% highlight php %}
public function imageSave($base64Image)
{
  $image = \ImageIntervention::make($base64Image);

  $image->fit(env('EBOOK_MEDIUM_WIDTH'), env('EBOOK_MEDIUM_HEIGHT'))
        ->save(env('UPLOAD_EBOOK_PATH') . $this->getImageFileName('medium'));

  $image->fit(env('EBOOK_THUMB_WIDTH'), env('EBOOK_THUMB_HEIGHT'))
        ->save(env('UPLOAD_EBOOK_PATH') . $this->getImageFileName('thumb'));

  $image->save(env('UPLOAD_EBOOK_PATH') . $this->getImageFileName('original'));
}
{% endhighlight %}

I needed to save a medium, a thumbnail and an original size image. It worked properly, but when the user checked the images on the front-end the original image had very bad quality. I knew it immediately what was the problem: the intervention library handled the `$image` as mutable object, so when I created the thumbnail size image it cropped and resized it (that is the `fit()` function). Of course the original image looked like sh*t in the browser: it was UPSIZED by the browser.

The easy way to fix is just save the images different order:

{% highlight php %}
public function imageSave($base64Image)
{
  $image = \ImageIntervention::make($base64Image);

  $image->save(env('UPLOAD_EBOOK_PATH') . $this->getImageFileName('original'));

  $image->fit(env('EBOOK_MEDIUM_WIDTH'), env('EBOOK_MEDIUM_HEIGHT'))
        ->save(env('UPLOAD_EBOOK_PATH') . $this->getImageFileName('medium'));

  $image->fit(env('EBOOK_THUMB_WIDTH'), env('EBOOK_THUMB_HEIGHT'))
        ->save(env('UPLOAD_EBOOK_PATH') . $this->getImageFileName('thumb'));
}
{% endhighlight %}

But sometimes it's not the best way to solve this problem. What if we need to crop to different ratios? What would be the correct order? I checked the `intervention` library and found the solution: `$img->backup();`. The library has two functions: `backup()` - `reset()` and this is how they work:

{% highlight php %}
// create empty canvas with black background
// create an image
$img = Image::make('public/foo.jpg');

// backup status
$img->backup();

// perform some modifications
$img->resize(320, 240);
$img->invert();
$img->save('public/small.jpg');

// reset image (return to backup state)
$img->reset();

// perform other modifications
$img->resize(640, 480);
$img->invert();
$img->save('public/large.jpg');
{% endhighlight %}

Just create a backup version of the `$image` object and later we can get this object back. Simple as :).
