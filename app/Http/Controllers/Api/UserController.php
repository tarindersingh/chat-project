<?php
/**
 * Created by PhpStorm.
 * User: tarin
 * Date: 28-06-2020
 * Time: 01:42 PM
 */

namespace App\Http\Controllers\Api;


use App\Helpers\Ajax;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;

class UserController extends Controller
{


    public function login(Request $request, Ajax $ajax)
    {
        $validator = validator($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            $ajax->field_errors($validator)->fail()->setStatusCode(Response::HTTP_BAD_REQUEST);
            return $ajax->send();
        }

        $data = $request->all();

        if(!Auth::attempt($data)) {
            return $ajax->message("Incorrect Email or Password!")
                ->fail()
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->send();
        }
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();
        return $ajax->param('token', [
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
        ])->success()
            ->send();
    }

    public function register(Request $request, Ajax $ajax)
    {

        $validator = validator($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            $ajax->field_errors($validator)->fail()->setStatusCode(Response::HTTP_BAD_REQUEST);
            return $ajax->send();
        }

        $data = $request->all();

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password'])
            ]);

            // And created user until here.

            $client = Client::where('password_client', 1)->first();

            $request->request->add([
                'grant_type' => 'password',
                'client_id' => $client->id,
                'client_secret' => $client->secret,
                'username' => $data['email'],
                'password' => $data['password'],
                'scope' => null,
            ]);

            $token = Request::create(
                'oauth/token',
                'POST'
            );
            DB::commit();
            return \Route::dispatch($token);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $ajax->fail()->setStatusCode(500)->message($exception->getMessage())
                ->send();
        }
    }

    public function getUsers(Ajax $ajax){
        $users = User::query()
            ->select('id', 'name')
            ->where('id', '!=', Auth::id())
            ->get();
        return $ajax->param('users', $users)
            ->success()
            ->send();
    }
}