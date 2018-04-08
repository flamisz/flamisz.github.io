---
layout: post
title:  "Codeigniter 3 and Twitter authentication"
date:   2018-04-08 12:00:00 +1400
categories: codeigniter php
tags: codeigniter, twitter, authentication
twitter: 982803726560718849
twitter_description: "Codeigniter 3 and Twitter authentication"
twitter_image: "twitter_ci.jpg"
github_link: "https://github.com/flamisz/ci_test"
---
Why Codeigniter? In my everyday job I've been using Laravel in the last 2 years. Yes, it is a good framework, lots of functionality, beautiful codebase..., but sometimes it is just feels to much to me. Sometimes I want a more light-weighted stuff. I used to work with Codeigniter a few years ago, so I wanted to try how it feels these days. I can tell you: it feels good :smile:.

<!--more-->

#### Basic steps of user authentication

1. User visits our page
2. Check if already logged in
3. If yes, let browsing
4. If not login with twitter account
5. Save user details into the db
6. Remember the user

These are the basic steps, this is how I want my authentication system to work.

#### Setup Codeigniter 

It is very easy to run a Codeigniter app. Just download and unzip it, run `php -S localhost:8080` and it is ready to go, but I prefer to use [valet](https://laravel.com/docs/valet) as my development environment (only for Mac). For this app I use `mysql` database (in the **valet** installation there is a help how to install it on Mac).

**Public folder**

My first step with every CI app is to create a `public` folder and put the `index.php` in it (**valet** handles it out of the box, but other servers may needs extra config) and modify the **system** and **application folder** variables: 

```php
// public/index.php
// ...
$system_path = '../system';
// ...
$application_folder = '../application';
```

**Composer**

Twitter uses [oauth](https://oauth.net/) to authenticate users. For `oauth` authentication I picked [league/oauth1-client](https://github.com/thephpleague/oauth1-client) package. They have a very simple [twitter example](https://github.com/thephpleague/oauth1-client/blob/master/resources/examples/twitter.php). The easiest way to install this package is to use composer.
```
$ composer require league/oauth1-client
```

Codeigniter is ready to use composer and can auto-load:

```php
// application/config/config.php
// ...
$config['composer_autoload'] = '../vendor/autoload.php';
```

#### Get user data from twitter

I need a route to user login: `auth/login`. I want to stay close to the box (I mean to the framework) during the whole project, so I need an **Auth Controller** with a **login** method. I use the [example](https://github.com/thephpleague/oauth1-client/blob/master/resources/examples/twitter.php) from the oauth client's github repository for the authentication process. It is a one page php file in the example so I convert a little bit to use in the controller [`application/controllers/Auth.php`]({{ page.github_link }}/blob/twitter-oath/application/controllers/Auth.php).

The first part of the process is the basic oath: get the credentials from twitter (the user has to give the permission to our app - ooh, forget to mention, you need a twitter app [https://apps.twitter.com/](https://apps.twitter.com/)) and with the given credentials we can get user's data from twitter. If the user doesn't give the permission we redirect back and flash a message.

```php
// application/controllers/Auth.php
// get user data from twitter
// ...
$twitter_user = $this->server->getUserDetails($tokenCredentials);

$twitter_user = [
  'uid' => $twitter_user->uid,
  'nickname' => $twitter_user->nickname,
  'email' => $twitter_user->email,
  'name' => $twitter_user->name,
  'avatar' => $twitter_user->imageUrl,
  'token' => $tokenCredentials->getIdentifier(),
  'token_secret' => $tokenCredentials->getSecret()
];
```

When I have the data from user our part of the process begins.

```php
// application/controllers/Auth.php
// get the user from the model, login and remember
$user = $this->user_model->from_oauth($twitter_user);

$this->authenticate->login($user);
$this->authenticate->remember($user);
```


#### Create or find user

I need a database, a user table in it and a [`user_model`]({{ page.github_link }}/blob/twitter-oath/application/models/User_model.php). I use [Sequel Pro](https://www.sequelpro.com/) for **mySql** databases. I create a `database` folder in my project root and save the [`create_table.sql`]({{ page.github_link }}/blob/twitter-oath/database/create_table.sql). in it (and run on my database).

The user create process:
1. Find the user by the twitter uid
2. If we can't find the user, create one with the given data from twitter
3. In the end give back the found or the newly created user

```php
// application/models/User_model.php
// ...
public function from_oauth($oauth_user)
{
return $this->find_by('uid', $oauth_user['uid']) ?:
                      $this->create_from_oauth($oauth_user);
}

public function find_by($attribute, $value)
{
$query = $this->db->get_where('users', [$attribute => $value], 1);

return $query->row();
}

public function create_from_oauth($oauth_user)
{
if ($user = $this->find_by('nickname', $oauth_user['nickname']))
{
  $this->db->update('users',
                    ['nickname' => $user->nickname . '-' . $user->id],
                    ['id' => $user->id]
                  );
}

return $this->db->insert('users', $oauth_user) ?
              $this->find_by('id', $this->db->insert_id()) : FALSE;
}
```

In the `create_from_auth` method I check if there is a user in the database with the same nickname. In this case I modify it in the db. The twitter nickname is unique on twitter but if somebody modify it after registered in my app and doesn't log in after the modification it is possible a new user picked the same nickname. I want the nickname unique in my application because I can use it as a slug for user's pages.

#### Login and remember user

Lots of apps has a *remember me* function: some of them are with a checkbox some of them are automatic. In this app it'll be automatic. The user stays login and remember it until logs out.

To handle the authentication I create a library: [`Authenticate.php`]({{ page.github_link }}/blob/twitter-oath/application/libraries/Authenticate.php). This library responsible for anything connected to the actual user:

- login
- remember
- check if we have logged in user and who is it if yes
- logout

The basic login (without the remember me) is very simple:
1. save the user's id into the session (Codeigniter has a session library, we don't have to implement that and I use the file session)
2. to get back the logged in user we can check if the session has user_id and get this user from the db

```php
// basic login and logged in user
// libraries/Authenticate.php simple version
public function login($user)
{
  $this->CI->session->user_id = $user->id;
}

public function current_user()
{
  if ($user_id = $this->CI->session->user_id)
  {
    $this->current_user = $this->current_user ?: $this->CI->user_model->find_by('id', $user_id);
  }

  return $this->current_user;
}
```

**Note:** the `$this->current_user` is a protected property of the Authenticate library, so we don't have to touch the database multiple times if the`current_user` appeared multiple times on the page. Easy to check it if we use Codeigniter profiling function. Just put this line in the **Welcome** controller:
```php
$this->output->enable_profiler(TRUE);
```

The **remember me** version of the log in system is a little bit more complex. It starts with the `Authenticate->remember($user)` function. 

1. save a remember token to the database
2. save the user's id into a cookie
3. save the remember token into a permanent (long life) cookie

```php
// remember
// libraries/Authenticate.php
// ...
public function remember($user)
{
  $remember_token = $this->CI->user_model->remember($user);
  set_coded_permanent_cookie('user_id', $user->id);
  set_permanent_cookie('remember_token', $remember_token);
}
```

The remember token is the user model's job:

```php
// models/User_model.php
// ...
public function remember($user)
{
  $remember_token = bin2hex(random_bytes(32));

  $update_data = [
    'remember_digest' => password_hash($remember_token, PASSWORD_DEFAULT)
  ];

  $this->db->update('users', $update_data, ['id' => $user->id]);

  return $remember_token;
}
```

1. generate a random hexadecimal number
2. save the hash version of this into to db (as the `remember_digest` of the given user)
3. send back the normal (not hashed) version of the token

Codeigniter has a very simple cookie helper but fortunately easy to [extend it](https://www.codeigniter.com/user_guide/general/helpers.html#extending-helpers). I created three simple methods to extend this helper.

{% highlight php %}
// helpers/MY_cookie_helper.php
function set_coded_permanent_cookie($name, $value)
{
  set_cookie($name, base64_encode($value), 60 * 60 * 24 * 365 * 10);
}

function set_permanent_cookie($name, $value)
{
  set_cookie($name, $value, 60 * 60 * 24 * 365 * 10);
}

function get_coded_cookie($name)
{
  return base64_decode(get_cookie($name));
}
{% endhighlight %}

After I have the remember token:

```php
set_coded_permanent_cookie('user_id', $user->id);
set_permanent_cookie('remember_token', $remember_token);
```

Save the coded version of id into a cookie and save the remember token into an other cookie. Permanent means 10 years in this case.

#### How this remember me works now?

With this line I can check in any of my controllers if the user is logged in or not:

```php
$is_logged_in = $this->authenticate->is_logged_in();
```

**What's happening under the hood?**

```php
// Authenticate.php
// ...

public function is_logged_in()
{
  return ! ! $this->current_user();
}

public function current_user()
{
  if ($user_id = $this->CI->session->user_id)
  {
    $this->current_user = $this->current_user ?: $this->CI->user_model->find_by('id', $user_id);
  }
  elseif ($user_id = get_coded_cookie('user_id'))
  {
    $user = $this->CI->user_model->find_by('id', $user_id);
    if ($user && $this->CI->user_model->is_authenticated($user, get_cookie('remember_token')))
    {
      $this->login($user);
      $this->current_user = $user;
    }
  }

  return $this->current_user;
}

// User_model.php
// ...

public function is_authenticated($user, $token)
{
  if (! $user->remember_digest)
  {
    return FALSE;
  }

  return password_verify($token, $user->remember_digest);
}
```

The **current_user()** has an extra `elseif` now.
1. if the user doesn't have a session (the session is valid only limited time) we try to get the user_id from the cookie (permanent cookie) 
2. if we found it, get the user from the db
3. if the user exists check if the user remember token is valid

Note: in the `if` and `elseif` lines inside the brackets are not comparisons (which would use double-equals ==), but rather are assignments.

### Tests

**Codeigniter** has a basic [test library](https://www.codeigniter.com/user_guide/libraries/unit_testing.html). It is really, really basic but I don't want to use other libraries so just some basic tests with this will do the job.

The easiest way to work with this library is to create a new controller, like [`application/controllers/Test.php`]({{ page.github_link }}/blob/twitter-oath/application/controllers/Test.php). 

In the **construct** function call the library and because the test touches the database, close the live one and load the test db.

```php
// controllers/Test.php
// ...

public function __construct()
{
  parent::__construct();

  $this->load->library('unit_test');
  $this->db->close();
  $this->load->database('test');
}
```

I create one function so I can run the test in the browser on the `test/user_login` route. This function contains 2 tests.

```php
public function user_login()
{
  $this->load->model('user_model');
  $this->db->empty_table('users');
  $this->_add_user();

  $twitter_user = [
    'uid' => '654321',
    'nickname' => 'test_nick',
    'email' => 'new@example.com',
    'name' => 'New Name',
    'avatar' => 'http://via.placeholder.com/50x50',
    'token' => 'token',
    'token_secret' => 'secret'
  ];

  $this->user_model->from_oauth($twitter_user);

  $user_old = $this->user_model->find_by('uid', '123456');
  $user_new = $this->user_model->find_by('uid', '654321');

  $test_name = 'Old user get modified nickname';
  $old_nick = 'test_nick-' . $user_old->id;
  $this->unit->run($user_old->nickname, $old_nick, $test_name);

  $test_name = 'New user get the nickname';
  $this->unit->run($user_new->nickname, 'test_nick', $test_name);

  echo $this->unit->report();
}
```

The test checks if a new user's nickname already in the database what will happen (modify the actual nickname in the database).

### Conclusion

This is just a basic user login with Twitter and a basic remember me function with Codeigniter. Yes, Codeigniter is an old framework, but sometimes good to work with it. It is very simple, easy to learn and easy to work with it. 

Some thinks can be upgraded, e.g. save the actual user data from twitter in every login, but for start it is ok.

And I forgot to mention that the user can logout:

```php
public function logout()
{
  $this->forget($this->current_user());
  unset($_SESSION['user_id']);
  $this->current_user = NULL;
}

public function forget($user)
{
  $this->CI->user_model->forget($user);
  delete_cookie('user_id');
  delete_cookie('remember_token');
}
```
