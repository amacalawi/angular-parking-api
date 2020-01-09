<?php

namespace App\Api\V1\Controllers;

use Config;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\LoginRequest;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Hash;
use Auth;
use App\User;
use App\Model\UserRole;

class UserController extends Controller
{   
    private $carbon;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(Carbon $carbon)
    {
        date_default_timezone_set('Asia/Manila');
        $this->carbon = $carbon;
        $this->middleware('jwt.auth', []);
    }

    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(Auth::guard()->user());
    }

    public function index(Request $request, $keywords) 
    {   
        if ($keywords == 'all') {
            $res = User::with([
                'user_roles' =>  function($q) { 
                    $q->with([
                        'role'
                    ])->select(['user_id', 'role_id'])
                    ->where('is_active', '1'); 
                },
            ])->where([
                'is_active' => '1'
            ])->orderBy('id', 'ASC')->get();

            $res = $res->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => $user->password,
                    'roles' => $user->user_roles->map(function($a) { return ' '.$a->role->name; }),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'is_active' => $user->is_active
                ];
            });
        } else {
            $res = User::where('is_active', 1)->orderBy('id', 'ASC')->get();
        }

        return response()
        ->json([
            'status' => 'ok',
            'data' => $res
        ]);
    }

    public function create(Request $request, JWTAuth $JWTAuth)
    {   
        $timestamp = $this->carbon::now();

        $search = User::where([
            'email' => $request->input('email')
        ])->get();

        if($search->count() > 0) {
            return response()
            ->json([
                'status' => 'not',
                'data' => $search->first(),
                'message' => 
                [
                    'info' => 'Oops!',
                    'text' => 'The email is already exist.', 
                    'type' => 'error'
                ]
            ]);
        }

        $res = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$res) {
            throw new NotFoundHttpException();
        }  
        
        foreach ($request->input('role_id') as $role) {
            $user_role = UserRole::create([
                'user_id' => $res->id,
                'role_id' => $role,
                'created_at' => $timestamp,
                'created_by' => Auth::user()->id
            ]);
        }

        return response()
        ->json([
            'status' => 'ok',
            'data' => $res,
            'message' => 
            [
                'info' => 'Success!',
                'text' => 'The information has been successfully saved.',
                'type' => 'success'
            ]
        ]);
    }

    public function update(Request $request, $id)
    {   
        $timestamp = $this->carbon::now();

        $res = User::find($id);

        if(!$res) {
            throw new NotFoundHttpException();
        }
        
        $search = User::where('email', $request->input('email'))->where('id', '!=', $id)->get();

        if($search->count() > 0) {
            return response()
            ->json([
                'status' => 'not',
                'data' => $search->first(),
                'message' => 
                [
                    'info' => 'Oops!',
                    'text' => 'The rfid no is already exist.',
                    'type' => 'error'
                ]
            ]);
        }

        $res->name = $request->input('name');
        $res->email = $request->input('email');
        $res->updated_at = $timestamp;
        $res->updated_by = Auth::user()->id;
        if ($res->password != $request->input('password')) {
            $res->password = $request->input('password');
        }

        UserRole::where('user_id', $res->id)->update([
            'updated_at' => $timestamp,
            'updated_by' => Auth::user()->id,
            'is_active' => 0
        ]);

        foreach ($request->input('role_id') as $role) {
            $exist = UserRole::where([
                'user_id' => $res->id,
                'role_id' => $role
            ])->get();

            if ($exist->count() > 0) {
                $user_role = UserRole::where([
                    'id' => $exist->first()->id
                ])->update([
                    'user_id' => $res->id,
                    'role_id' => $role,
                    'updated_at' => $timestamp,
                    'updated_by' => Auth::user()->id,
                    'is_active' => 1
                ]);
            } else {
                $user_role = UserRole::create([
                    'user_id' => $res->id,
                    'role_id' => $role,
                    'created_at' => $timestamp,
                    'created_by' => Auth::user()->id
                ]);
            }
        }

        if ($res->update()) {
            return response()
            ->json([
                'status' => 'ok',
                'data' => $res,
                'message' => 
                [
                    'info' => 'Success!',
                    'text' => 'The information has been successfully saved.',
                    'type' => 'success'
                ]
            ]);
        } else {
            throw new NotFoundHttpException();
        }
    }

    public function find(Request $request, $id)
    {   
        $res = User::with([
            'user_roles' =>  function($q) { 
                $q->with([
                    'role'
                ])->select(['user_id', 'role_id'])
                ->where('is_active', '1'); 
            },
        ])->where(
            'id', $id
        )->get();

        
        if (!$res) {
            throw new NotFoundHttpException();
        }

        $res = $res->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'password' => $user->password,
                'roles' => $user->user_roles->map(function($a) { return $a->role_id; }),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'is_active' => $user->is_active
            ];
        });

        return response()
        ->json([
            'status' => 'ok',
            'data' => $res
        ]);
    }   
}
