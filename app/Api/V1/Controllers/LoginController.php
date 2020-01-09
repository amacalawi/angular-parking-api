<?php

namespace App\Api\V1\Controllers;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\LoginRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Auth;
use App\User;

class LoginController extends Controller
{
    /**
     * Log the user in
     *
     * @param LoginRequest $request
     * @param JWTAuth $JWTAuth
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request, JWTAuth $JWTAuth)
    {
        $credentials = $request->only(['email', 'password']);

        try {
            $token = Auth::guard()->attempt($credentials);

            if(!$token) {
                throw new AccessDeniedHttpException();
            }

        } catch (JWTException $e) {
            throw new HttpException(500);
        }

        $res = User::with([
            'user_roles' =>  function($q) { 
                $q->with([
                    'role.privileges'
                ])->select(['user_id', 'role_id'])
                ->where('is_active', '1'); 
            },
        ])->where(
            'id', Auth::user()->id
        )->get();

        $res = $res->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'password' => $user->password,
                'roles' => $user->user_roles->map(function($a) { return $a->role->name; }),
                'privileges' => $user->user_roles->map(function($a) { return $a->role->privileges->map(function($b) { return $b->slugs; }); }),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'is_active' => $user->is_active
            ];
        });

        $privileges = array();
        foreach($res[0]['privileges'] as $priv) {
            if (!in_array($priv, $privileges)) {
                $privileges[] = $priv;
            }
        }

        return response()
            ->json([
                'status' => 'ok',
                'token' => $token,
                'user_id' => $res[0]['id'],
                'roles' => $res[0]['roles'],
                'privileges' => $privileges,
                'expires_in' => Auth::guard()->factory()->getTTL() * 60
            ]);
    }
}
