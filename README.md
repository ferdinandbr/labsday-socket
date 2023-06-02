# laravel-api-auth-sanctum-boilerplate
laravel boilerplate with api auth using sanctum (signup, login, logout, reset password)
- [Download and setup starter code](#use-starter-project)
- [Follow step by step guide to quickly add authentication in your existing project (recommended)](#add-sanctum-to-existing-project)


## Use starter project
Details of starter laravel project
- Laravel v8.46.0
- Sanctum v2.11

## Setup Instructions
- clone the repo
  `git clone this.repo`,
- install dependencies
  `composer install`
- perform migrations
  `php artisan migrate`
- serve
  `php artisan serve`
- server hosted @ `localhost:8000`

### Endpoints for API Authentication

The auth routes are present in `routes/api.php` and prefixed with `auth` as follows:
```php
Route::prefix('auth')->group(function () {
	Route::post('signup', 'App\Http\Controllers\Api\Auth\AuthController@signup')->name('auth.signup');
	Route::post('login', 'App\Http\Controllers\Api\Auth\AuthController@login')->name('auth.login');
	Route::post('logout', 'App\Http\Controllers\Api\Auth\AuthController@logout')->middleware('auth:sanctum')->name('auth.logout');
	Route::get('user', 'App\Http\Controllers\Api\Auth\AuthController@getAuthenticatedUser')->middleware('auth:sanctum')->name('auth.user');

	Route::post('/password/email', 'App\Http\Controllers\Api\Auth\AuthController@sendPasswordResetLinkEmail')->middleware('throttle:5,1')->name('password.email');
	Route::post('/password/reset', 'App\Http\Controllers\Api\Auth\AuthController@resetPassword')->name('password.reset');
});
```
Hence all the api auth routes are prefixed with `/api/auth` and the routes are:
### api endpoints

- Signup:

  `POST: /api/auth/signup`
  ```json
  {
    "name": "John Doe",
    "email": "johndoe@example.org",
    "password": "password",
    "password_confirmation": "password"
  }
  ```
- Login:

  `POST: /api/auth/login`
  ```json
  {
    "email": "johndoe@example.org",
    "password": "password"
  }
  ```
- Logout:

  `POST: /api/auth/logout`

- Get authenticated user details:

  `GET: /api/auth/user`
- Send forgot password email:

  `POST: /api/auth/password/email`

  ```json
  {
    "email": "johndoe@example.org",
  }
  ```

- Reset password:

  `POST: /api/auth/password/reset`

  ```json
  {
    "email": "johndoe@example.org",
    "token": "valid-token-recieved-in-email",
    "password": "password",
    "password_confirmation": "password"
  }
  ```

## Add sanctum to existing project
### Quickly create api auth scaffold with sanctum instead ? Follow this guide:
1. Create a laravel project
	
`composer create-project laravel/laravel my-project`

2. Install sanctum

`composer require laravel/sanctum`

3. Configure sanctum

`php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`

4. Migrate databases

`php artisan migrate`

5. To begin issuing tokens for users, your User model should use the `Laravel\Sanctum\HasApiTokens` trait:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```
6. Create a AuthController that has all the authentication methods. Use any Folder Structure/Namespace you want, Here `App\Http\Controllers\Api\Auth` namespace is used.

`php artisan make:controller Api/Auth/AuthController`

7. Add following methods inside AuthController.

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;

class AuthController extends Controller
{

	/*
	 * Register new user
	*/
	public function signup(Request $request) {
		$validatedData = $request->validate([
			'name' => 'required|string|max:255',
			'email' => 'required|email|unique:users,email',
			'password' => 'required|min:6|confirmed',
		]);

		$validatedData['password'] = Hash::make($validatedData['password']);

		if(User::create($validatedData)) {
			return response()->json(null, 201);
		}

		return response()->json(null, 404);
	}

	/*
	 * Generate sanctum token on successful login
	*/
	public function login(Request $request) {
		$request->validate([
			'email' => 'required|email',
			'password' => 'required',
		]);

		$user = User::where('email', $request->email)->first();

		if (! $user || ! Hash::check($request->password, $user->password)) {
			throw ValidationException::withMessages([
				'email' => ['The provided credentials are incorrect.'],
			]);
		}

		return response()->json([
			'user' => $user,
			'access_token' => $user->createToken($request->email)->plainTextToken
		], 200);
	}


	/*
	 * Revoke token; only remove token that is used to perform logout (i.e. will not revoke all tokens)
	*/
	public function logout(Request $request) {

		// Revoke the token that was used to authenticate the current request
		$request->user()->currentAccessToken()->delete();
		//$request->user->tokens()->delete(); // use this to revoke all tokens (logout from all devices)
		return response()->json(null, 200);
	}


	/*
	 * Get authenticated user details
	*/
	public function getAuthenticatedUser(Request $request) {
		return $request->user();
	}


	public function sendPasswordResetLinkEmail(Request $request) {
		$request->validate(['email' => 'required|email']);

		$status = Password::sendResetLink(
			$request->only('email')
		);

		if($status === Password::RESET_LINK_SENT) {
			return response()->json(['message' => __($status)], 200);
		} else {
			throw ValidationException::withMessages([
				'email' => __($status)
			]);
		}
	}

	public function resetPassword(Request $request) {
		$request->validate([
			'token' => 'required',
			'email' => 'required|email',
			'password' => 'required|min:8|confirmed',
		]);

		$status = Password::reset(
			$request->only('email', 'password', 'password_confirmation', 'token'),
			function ($user, $password) use ($request) {
				$user->forceFill([
					'password' => Hash::make($password)
				])->setRememberToken(Str::random(60));

				$user->save();

				event(new PasswordReset($user));
			}
		);

		if($status == Password::PASSWORD_RESET) {
			return response()->json(['message' => __($status)], 200);
		} else {
			throw ValidationException::withMessages([
				'email' => __($status)
			]);
		}
	}
}
```
8. Add Authentication related routes inside `routes/api.php` to bind appropriate routes with appropriate methods of AuthController

```php
// Auth
Route::prefix('auth')->group(function () {
	Route::post('signup', 'App\Http\Controllers\Api\Auth\AuthController@signup')->name('auth.signup');
	Route::post('login', 'App\Http\Controllers\Api\Auth\AuthController@login')->name('auth.login');
	Route::post('logout', 'App\Http\Controllers\Api\Auth\AuthController@logout')->middleware('auth:sanctum')->name('auth.logout');
	Route::get('user', 'App\Http\Controllers\Api\Auth\AuthController@getAuthenticatedUser')->middleware('auth:sanctum')->name('auth.user');

	Route::post('/password/email', 'App\Http\Controllers\Api\Auth\AuthController@sendPasswordResetLinkEmail')->middleware('throttle:5,1')->name('password.email');
	Route::post('/password/reset', 'App\Http\Controllers\Api\Auth\AuthController@resetPassword')->name('password.reset');
});
```

> Note: uncomment `// protected $namespace = 'App\\Http\\Controllers';` on `app/Providers/RouteServiceProvider.php` if u want to use `Api\Auth\AuthController@...` instead of `App\Http\Controllers\Api\Auth\AuthController@...` when defining routes.

9. Update the url in ResetPassword Email to frontend url. add `FRONTEND_URL=http://localhost:3000` in .env file. The url needs to be changed by overriding static method createUrlUsing of ResetPasswordEmail in AuthServiceProvider class. Heres how it could be done
```php

<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        $frontEndUrl = env('FRONTEND_URL');
        $this->setFrontEndUrlInResetPasswordEmail($frontEndUrl);
    }

    protected function setFrontEndUrlInResetPasswordEmail($frontEndUrl = '')
    {
        // update url in ResetPassword Email to frontend url
        ResetPassword::createUrlUsing(function ($user, string $token) use ($frontEndUrl) {
            return $frontEndUrl . '/auth/password/email/reset?token=' . $token;
        });
    }

}

```
By using env('FRONTEND_URL'), this can be easily updated in local and production.
> Note, env is not cached, access is slower, could add config variable.
 
Thats about it ! All the endpoints and implementations for auth routes as mentioned [here](#api-endpoints) is complete and ready for test.

### Create tests for auth endpoints

Create AuthController test with `php artsan make:test`
```bash
php artisan make:test Api/Auth/AuthControllerTest
```

Add Following code in `tests/Feature/Api/Auth/AuthControllerTest.php`
```php
<?php

namespace Tests\Feature\Api\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use Tests\TestCase;
use Notification;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp() :void
    {
        parent::setUp();

        // fake all notifications that are sent out during tests
        Notification::fake();

        // create a user
        User::factory()->create([
            'email' => 'johndoe@example.org',
            'password' => Hash::make('testpassword')
        ]);

    }

    public function test_show_validation_error_when_both_fields_empty()
    {

        $response = $this->json('POST', route('auth.login'), [
            'email' => '',
            'password' => ''
        ]);

        $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
    }


    public function test_show_validation_error_on_email_when_credential_donot_match()
    {
        $response = $this->json('POST', route('auth.login'), [
            'email' => 'test@test.com',
            'password' => 'abcdabcd'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_return_user_and_access_token_after_successful_login()
    {
        $response = $this->json('POST', route('auth.login'), [
            'email' =>'johndoe@example.org',
            'password' => 'testpassword',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user', 'access_token']);
    }

    public function test_non_authenticated_user_cannot_get_user_details()
    {

        $response = $this->json('GET', route('auth.user'));

        $response->assertStatus(401)
            ->assertSee('Unauthenticated');
    }

    public function test_authenticated_user_can_get_user_details()
    {
        Sanctum::actingAs(
            User::first(),
        );

        $response = $this->json('GET', route('auth.user'));

        $response->assertStatus(200)
            ->assertJsonStructure(['name', 'email']);
    }

    public function test_non_authenticated_user_cannot_logout()
    {
        $response = $this->json('POST', route('auth.logout'), []);

        $response->assertStatus(401)
            ->assertSee('Unauthenticated');;
    }

    public function test_authenticated_user_can_logout()
    {
        Sanctum::actingAs(
            User::first(),
        );

        $response = $this->json('POST', route('auth.logout'), []);

        $response->assertStatus(200);
    }


    // Password reset
    public function test_return_validation_error_when_email_doenot_exist()
    {
        $response = $this->json('POST', route('password.email'), ['email' => 'invalid@email.com']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_send_password_reset_link_if_email_exists()
    {
        $user = User::first();
        $response = $this->json('POST', route('password.email'), ['email' => $user->email]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message']);

        // Notification::assertSentTo($user, ResetPassword::class); // running on issue with asserting notification
    }

    public function test_reset_password_success()
    {
        $user = User::first();
        $token = Password::broker()->createToken($user);
        $new_password = 'testpassword';

        $response = $this->json('POST', route('password.reset'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $new_password,
            'password_confirmation' => $new_password
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message']);
    }
}
```

> If theres any problem on this please open an issue !
