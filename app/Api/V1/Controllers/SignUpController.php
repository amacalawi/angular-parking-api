<?php

namespace App\Api\V1\Controllers;

use Config;
use App\User;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\SignUpRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Hash;
use App\Model\UserRole;

class SignUpController extends Controller
{   
    private $carbon;
    
    public function __construct(Carbon $carbon)
    {
        date_default_timezone_set('Asia/Manila');
        $this->carbon = $carbon;
    }

    public function signUp(SignUpRequest $request, JWTAuth $JWTAuth)
    {   
        $timestamp = $this->carbon::now();

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$user) {
            throw new HttpException(500);
        }  

        foreach ($request->input('role_id') as $role) {
            $user_role = UserRole::create([
                'user_id' => $user->id,
                'role_id' => $role,
                'created_at' => $timestamp,
                'created_by' => Auth::user()->id
            ]);
        }

        if(!Config::get('boilerplate.sign_up.release_token')) {
            return response()->json([
                'status' => 'ok'
            ], 201);
        }

        $token = $JWTAuth->fromUser($user);
        return response()->json([
            'status' => 'ok',
            'token' => $token
        ], 201);
    }
}
