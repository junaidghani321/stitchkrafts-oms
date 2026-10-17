<?php

namespace Tests\Feature;

use App\Models\UserDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * What self-registration does with the username it is handed.
 *
 * The field is letters only, and the country code rides along into the column:
 * `john` is stored as `johnus`, `johnuk` or `johnau` depending on where they
 * signed up, which is also what makes the handle free once per country.
 */
class RegistrationUsernameTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A registration the validator is happy with, overrides last.
     *
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'John Smith',
            'email' => fake()->unique()->safeEmail(),
            'username' => 'john',
            'country' => 'us',
            'company_name' => 'Stitch Co',
            'contact_number' => '+1 555 0100',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    /**
     * Registration signs the new account in and the route sits behind the
     * guest middleware, so a test that registers more than once has to step
     * back out to the guest door in between — otherwise the second attempt is
     * redirected away and never reaches the validator.
     *
     * @param  array<string, string>  $overrides
     */
    private function registerAsGuest(array $overrides = []): TestResponse
    {
        Auth::logout();
        $this->flushSession();

        return $this->post(route('register'), $this->payload($overrides));
    }

    public function test_it_appends_the_country_code_to_the_stored_username(): void
    {
        $this->registerAsGuest()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_details', [
            'username' => 'johnus',
            'country' => 'us',
        ]);
    }

    public function test_it_stores_the_username_lowercased(): void
    {
        $this->registerAsGuest(['username' => 'John'])->assertSessionHasNoErrors();

        $this->assertSame('johnus', UserDetail::sole()->username);
    }

    public function test_the_same_username_may_be_taken_once_per_country(): void
    {
        foreach (['us' => 'johnus', 'uk' => 'johnuk', 'au' => 'johnau'] as $country => $stored) {
            $this->registerAsGuest(['country' => $country])->assertSessionHasNoErrors();

            $this->assertDatabaseHas('user_details', ['username' => $stored]);
        }

        $this->assertDatabaseCount('user_details', 3);
    }

    public function test_it_rejects_a_username_already_taken_in_the_same_country(): void
    {
        $this->registerAsGuest()->assertSessionHasNoErrors();

        // The second registration differs only in its email, so the username
        // is the only thing left to collide.
        $this->registerAsGuest()->assertSessionHasErrors('username');

        $this->assertDatabaseCount('user_details', 1);
        $this->assertDatabaseCount('users', 1);
    }

    /**
     * The collision is judged on the qualified handle, so a username that only
     * matches what another country already stored is still free.
     */
    public function test_it_allows_a_username_matching_another_countrys_stored_handle(): void
    {
        $this->registerAsGuest()->assertSessionHasNoErrors();

        $this->registerAsGuest(['username' => 'johnus', 'country' => 'uk'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_details', ['username' => 'johnusuk']);
    }

    #[DataProvider('rejectedUsernames')]
    public function test_it_rejects_a_username_that_is_not_letters_only(string $username): void
    {
        $this->registerAsGuest(['username' => $username])
            ->assertSessionHasErrors('username');

        $this->assertDatabaseCount('users', 0);
    }

    /** @return array<string, array{string}> */
    public static function rejectedUsernames(): array
    {
        return [
            'digits' => ['john123'],
            'underscore' => ['john_smith'],
            'dash' => ['john-smith'],
            'space' => ['john smith'],
            'dot' => ['john.smith'],
            'too short' => ['jo'],
            'too long' => [str_repeat('a', UserDetail::USERNAME_MAX + 1)],
        ];
    }

    /**
     * The longest handle still has to fit: 48 typed characters plus the
     * two-character code is exactly the column's 50.
     */
    public function test_it_accepts_a_username_at_the_length_limit(): void
    {
        $username = str_repeat('a', UserDetail::USERNAME_MAX);

        $this->registerAsGuest(['username' => $username])->assertSessionHasNoErrors();

        $this->assertSame($username.'us', UserDetail::sole()->username);
    }
}
