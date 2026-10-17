<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Country;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | Self-registration is the customer door only. Staff accounts are minted by
    | an admin in the users CRUD, so `type` is never read from the request —
    | see create() below.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/user/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', 'unique:user_details,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'country' => ['required', Rule::enum(Country::class)],
            'contact_number' => ['nullable', 'string', 'max:30'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * The account and its detail row are one unit, so a failure on either
     * leaves no half-registered user behind.
     *
     * @return User
     */
    protected function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            // forceCreate, not create: `type` is a privilege column deliberately
            // excluded from the model's fillable list. Pinning it to Customer
            // here is what keeps registration from minting a staff account,
            // whatever the request body carries.
            //
            // ->value, not the enum case: the `type` Attribute caches an object
            // it is handed (withObjectCaching), so passing UserType::Customer
            // would make $user->type read back as the enum rather than the
            // 'user' slug the middleware and sidebar compare against.
            $user = User::forceCreate([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'type' => UserType::Customer->value,
            ]);

            $user->detail()->create([
                'username' => $data['username'],
                'country' => $data['country'],
                'company_name' => $data['company_name'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
            ]);

            return $user;
        });
    }
}
