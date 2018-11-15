![Bakery](artwork.png)

[![Build Status](https://travis-ci.org/scrnhq/laravel-bakery.svg?branch=master)](https://travis-ci.org/scrnhq/laravel-bakery)
[![Latest Stable Version](https://poser.pugx.org/scrnhq/laravel-bakery/version)](https://packagist.org/packages/scrnhq/laravel-bakery)
[![Total Downloads](https://poser.pugx.org/scrnhq/laravel-bakery/downloads)](https://packagist.org/packages/scrnhq/laravel-bakery)
[![codecov](https://codecov.io/gh/scrnhq/laravel-bakery/branch/master/graph/badge.svg)](https://codecov.io/gh/scrnhq/laravel-bakery)
[![StyleCI](https://github.styleci.io/repos/109427894/shield?style=flat)](https://github.styleci.io/repos/109427894)

An on-the-fly GraphQL Schema generator from Eloquent models for Laravel.

- [Version Compatibility](#version-compatibility)
- [Installation](#installation)
- [Quickstart](#quickstart)

## Version Compatibility

| Laravel | Bakery |
| :------ | :----- |
| 5.4.x   | 1.0.x  |
| 5.5.x   | 1.0.x  |
| 5.6.x   | 2.0.x  |

## Installation

Install via composer:

```
composer require scrnhq/laravel-bakery
```

or require in _composer.json_:

```json
{
    "require": {
        "scrnhq/laravel-bakery": "^2.0"
    }
}
```

then run `composer update` in your terminal to install Bakery.

Once this has finished, you will need to add the service provider to the providers array in your `app.php` config as follows:

**This package supports Laravel's package auto-discovery; if you are using Laravel 5.5 or above you can skip this step.**

```php
Bakery\BakeryServiceProvider::class,
```

## Quickstart

First publish the configuration file of Bakery by running the following command in your terminal:

```
php artisan vendor:publish --provider="Bakery\BakeryServiceProvider"
```

Now open up `config/bakery.php` and you will see a `models` property that contains an empty array.
Here we can start defining the model schemas for our models. Model schemas are classes that lets you define fields,
relationships and behaviour for your models that Bakery uses to set up a GraphQL schema.

Let's start by creating a model schema! You can put these wherever you want, for example you can put them in a
`ModelSchemas` directory. 

```php
namespace App\ModelSchemas\UserSchema;

use App\User;
use Bakery\Eloquent\ModelSchema;
use Bakery\Support\Facades\Bakery;

class UserSchema extends ModelSchema
{
	protected $model = User::class;

	public function fields() {
		return [
			'email' => Bakery::string(),
		];
	}
}
```

### Queries

To test this out, open up your Laravel application and go to `/graphiql`. Here you will see an interactive playground to execute GraphQL queries and mutations. Now execute the following query (assuming you have made your User model introspectable):

```gql
query {
  users {
    items {
      id
    }
  }
}
```

If everything is set up properly you will get a collection of users in your database! Now to fetch an individual user you can execute the following query:

```gql
query {
  user(id: "1") {
    id
  }
}
```

Just like Laravel, Bakery follows certain naming conventions. It uses Laravel's pluralization library to transform your model name in to queries so you can fetch an individual user by _user_ and a collection of users by _users_.

### Fields

One of the differences between GraphQL and Eloquent is that GraphQL is a little bit stricter when it comes to defining its schemas than Laravel is to defining its models. To create the types and queries you need to tell us a little bit about which attributes on your model you want to expose! These attributes are called `fields` in GraphQL and you can define them by that name on your model like so:

```diff
<?php

namespace App;

+ use GraphQL\Type\Definition\Type;
use Bakery\Eloquent\Introspectable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{

+    public function fields()
+    {
+        return [
+            'email' => Type::string();
+        ];
+    }

}
```

This tells Bakery that there is an email field on the model with the type string. [Here you can see a full overview and documentation of the underlying GraphQL type system](http://webonyx.github.io/graphql-php/type-system/).

Now you are able to query email addresses from your query, like so:

```gql
query {
  user(id: "1") {
    id
    email
  }
}
```

### Relations

Bakery is also capable of returning data of other models related to the model you are querying. Let's say a user has articles, so you have defined a relationship on the model like so:

`User.php`

```php
public function articles()
{
    return $this->hasMany(Article::class);
}
```

`Article.php`

```php
<?php

namespace App;

use GraphQL\Type\Definition\Type;
use Bakery\Eloquent\Introspectable;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{

    public function fields()
    {
        return [
            'title' => Type::string(),
        ];
    }
}
```

Now we need to tell Bakery about this new article model by updating the config.

`config/bakery.php`

```diff
return [
    'models' => [
        App\User::class,
+       App\Article::class,
    ],
];
```

Once you have set up your relationship in Eloquent, you just need to define it so that Bakery knows about it by setting the relations field on your user model:

`User.php`

```php
public function relations()
{
    return [
        'articles' => Type::listOf(Bakery::type('Article'))
    ];
}
```

Now you can easily fetch all the articles from a user in a single query like so:

```gql
query {
  user(id: "1") {
    id
    articles {
      id
    }
  }
}
```
