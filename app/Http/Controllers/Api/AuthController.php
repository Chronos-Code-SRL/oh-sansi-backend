<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use \stdClass;
use App\Models\User;

class AuthController extends Controller
{
    //
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'email' => 'required|email|unique:users,email',
            // 'password' => 'required|string|confirmed',
            'ci' => 'required|min:6|max:12|unique:users,ci',
            'phone_number' => 'required|min:7|max:15',
            'genre' => 'required|in:masculino,femenino',
            'roles_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error in data validation',
                'error' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $full_name = $request->first_name . ' ' . $request->last_name;
        $generate_password = $this->generate_password($full_name, $request->ci);
        echo $generate_password;

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($generate_password),
            'ci' => $request->ci,
            'phone_number' => $request->phone_number,
            'genre' => $request->genre,
            'roles_id' => $request->roles_id
        ]);

        if (!$user) {
            $data = [
                'message' => 'Error creating user',
                'status' => 500
            ];
            return response()->json($data, 500);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $data = [
            'message' => 'User registration successfully',
            'acces_token' => $token,
            'token_type' => 'Bearer'
        ];

        return response()->json($data, 201);
    }

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Unaunthorized'], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['token' => $token, 'token_type' => 'Bearer'], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function generate_password($name, $ci){
        $full_name = trim($name);
        $array_names = [];
        $name = "";
        $password = "";

        for ($i=0; $i < strlen($full_name); $i++) { 
            if ($full_name[$i] != ' ') {
                $name .= $full_name[$i];
            }else{
                if ($name != "") {
                    array_push($array_names, $name);
                    $name = "";
                }
            }
        }

        if (!$name == "") {
            array_push($array_names, $name);
        }

        for ($i=0; $i < sizeof($array_names); $i++) { 
            $password .= $array_names[$i][0];
        }
        
        return strtoupper($password) . $ci;
    }
}
