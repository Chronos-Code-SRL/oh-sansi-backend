<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $users = User::all();
        
        if($users -> isEmpty()){
            $data = [
                'message' => 'Users not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'users' => $users,
            'status' => 200
        ];
        
        return response()->json($data, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string',
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

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => $request->password,
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

        $data = [
            'user' => $user,
            'status' => 201
        ];
        return response()->json($data, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            $data = [
                'message' => 'User not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        return response()->json($user, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);
        if (!$user) {
            $data = [
                'message' => 'User not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'email' => [
                'required', 
                'email',
                Rule::unique('users', 'email')->ignore($user->id)
            ],
            'password' => 'nullable|string',
            'ci' => [
                'required',
                'min:6',
                'max:12',
                Rule::unique('users', 'ci')->ignore($user->id),
            ],
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

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->password = $request->password;
        $user->ci = $request->ci;
        $user->phone_number = $request->phone_number;
        $user->genre = $request->genre;
        $user->roles_id = $request->roles_id;
        $user->save();

        $data = [
            'message' => 'Update user',
            'user' => $user,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            $data = [
                'message' => 'User not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
    
        $user->delete();

        $data = [
            'message' => 'Deleted user',
            'status' => 200
        ];

        return response()->json($data, 200);
    }
}
