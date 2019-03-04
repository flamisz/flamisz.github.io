---
layout: post
title:  "Laravel queue and pusher"
date:   2019-03-05 07:00:00 +1300
categories: laravel php
tags: laravel
---
I've read an article on [Toptal blog](https://www.toptal.com/blog) about how to handle time consuming tasks in Laravel: [Handling Intensive Tasks with Laravel](https://www.toptal.com/laravel/handling-intensive-tasks-with-laravel).

That blog is interesting:
- the coder uses `symfony/process` package for handling those long running tasks
- to notify the users about the process actual state, it's just calling frequently an endpoint with jquery ajax call.

In this article I want to show the Laravel way to do this by using the powerful built-in queue and broadcasting systems.

<!--more-->

### Laravel Queues

Laravel has a [queue](https://laravel.com/docs/5.8/queues) system to processing time consuming task, such as email sending, process files, etc. Deferring these tasks speeds up your application’s requests. We can choose from a variety of queue systems, like Amazon SQS, Redis, Beanstalk, Redis, DB. In the example application I will use Redis queue.

The original article’s long running task is an Excel import. In my application I won’t build that part (I just use `sleep` to simulate the long running task).

### Jobs and Events

The simplest way to generate a job is using the  `artisan` command:

```
php artisan make:job ImportBill
```

This will generate the class file into the `app/Jobs` folder. Create the handle process simulating a file import process:

```php
public function handle()
{
    $rows = collect(range(1, 1000));

    $chunks = collect($rows)->chunk(100);

    foreach ($chunks as $key => $chunk) {
        $chunk->each(function ($row) {
            // do something with the row
            $row = $row * 10;
        });

        event(new ImportChunkReady($key));
    }

    event(new ImportReady($this->file));
}
```

The first part is just the processing: creating dummy rows and iterates over the items. The two last lines are more interesting: dispatching events to notify the system about something happened.
To register event listeners and events add the classes into the `EventServiceProvider`:

```php
protected $listen = [
    'App\Events\ImportChunkReady' => [
        'App\Listeners\SendImportChunkReadyNotification',
    ],
    'App\Events\ImportReady' => [
        'App\Listeners\SendImportReadyNotification',
    ],
];
```

We registered two events and listeners. The first is to handling when a part of import is ready (every 100 rows) the second is to handle when the whole file import is ready. By running the `php artisan event:generate` command Laravel generates the classes.

For this application the listeners are not interesting, because I won’t do anything in the back-end (but we could, like sending email or slack notification, etc), I just broadcast the events to the front-end by using a cloud service, [pusher](https://pusher.com).  (Note: you need an account, but a free one is enough for try this out).

To inform Laravel that a given event should be broadcast, implement the `Illuminate\Contracts\Broadcasting\ShouldBroadcast` interface and `broadcatOn` method on the event class:

```php
public function broadcastOn()
{
    return new Channel('channel');
}
```

### Laravel Echo

We are very lucky and don’t have to implement a pusher interface on the back-end and front-end because Laravel provides both. In the back-end we just choose pusher as broadcast driver, in the front-end we install [Laravel Echo](https://laravel.com/docs/5.8/broadcasting#receiving-broadcasts):

```
npm install —save laravel-echo pusher-js
or
yarn add laravel-echo pusher-js
```

Laravel Echo is a JavaScript library that makes it painless to subscribe to channels and listen for events broadcast by Laravel. In this way we can show live notifications to the users.

We are ready in the back, let’s go to the front.
This is how we can listen the events in javascript:

```javascript
Echo.channel('channel')
    .listen('ImportChunkReady', (e) => {
        let count = (e.chunk + 1) * 100;
        this.status = count + ' rows processed';
    });
```

In this app I use `vue.js` javascript framework to handle the events from pusher because Laravel works well with `vue.js`. I created a component handling the notifications. On every page where I put this component the user can get the notification. (Note: this article does not contain how to setup your front-end, but using [Laravel Mix](https://laravel.com/docs/5.8/mix) is very easy).

```javascript
<template>
    <div class="card">
        <div class="card-header">Live Notifications</div>

        <div class="card-body">
            <div class="alert alert-success" role="alert" v-text="status" v-if="status"></div>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                status: ''
            }
        },

        mounted() {
            console.log('Component mounted.');

            Echo.channel('channel')
                .listen('ImportChunkReady', (e) => {
                    let count = (e.chunk + 1) * 100;
                    this.status = count + ' rows processed';
                });

            Echo.channel('channel')
                .listen('ImportReady', (e) => {
                    this.status = e.file + ' processed';
                });

            Event.$on('process-started', message => this.statusMessage(message));
        },

        methods: {
            statusMessage(message) {
                this.status = message;
            }
        }
    }
</script>
```

### Route and Controller
Generating the bills we need a route and a controller:

```php
// routes/web.php
// ...
Route::post('/bill', 'BillController@store');

// app/Http/Controllers/BillController.php
// ...
use App\Jobs\ImportBill;

public function store()
{
    $file = request('file', 'bill01.xls');

    // this is where we dispatch the job
    ImportBill::dispatch($file);
}
```

### Summary

1. User starts the importing process by sending a `POST` request to the  `/bill` endpoint
2. The controller dispatch the job `ImportBill` job
3. The job importing the file and firing the events
4. The events start the broadcasts
5. Laravel send the messages to Pusher
6. Laravel Echo subscribing and getting messages from Pusher
7. Vue.js shows the messages to the user

### +1
Find the application code on [github](https://github.com/flamisz/toptal-tasks).

You can try the application on this page: [https://lara-test.flamiszoltan.me/](https://lara-test.flamiszoltan.me/). Just push the start button and wait for the notifications. You can navigate to the other page during the process and get the notifications there. Currently the app sending the notifications in every 3 seconds (to simulate the processing time).

This is just a very basic application showing a few interesting things what you can do with Laravel, but the queue system is much more powerful. To handle long running processes inside a process, like importing an excel file, but we don't know how many rows we have, I would chain jobs (separate jobs for every chunk - eg 100 rows/chunk). Something like this:

```php
$jobs = [];

foreach ($chunks as $chunk) {
    $jobs[] = new ImportBillChunk($chunk);
}

$jobs[] = new FinishImportBill($file);

ImportBillStart::withChain($jobs)->dispatch();
```

If your application is heavy with jobs, the best way to handle and check them is using Laravel Horizon. But that's an other story...

Thanks for reading, any comment, please find me on [twitter](https://twitter.com/flamisz).

