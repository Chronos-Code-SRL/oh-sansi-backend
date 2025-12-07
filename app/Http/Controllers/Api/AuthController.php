<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use \stdClass;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    // Register new user or update existing one with role and olympiad areas
    public function register(Request $request)
    {
        // Validate input data
        $validator = Validator::make(
            $request->all(),
            [
                'first_name' => 'required|string|min:2|max:50',
                'last_name' => 'required|string|min:2|max:50',
                'email' => 'required|email',
                'ci' => 'required|min:6|max:12',
                'phone_number' => 'required|min:7|max:15',
                'genre' => 'required|in:masculino,femenino',
                'roles_id' => 'required|exists:roles,id',
                'areas_id' => 'required|array',
                'profesion' => 'nullable|string|max:100',
                'olympiad_id' => 'required|exists:olympiads,id',
            ]
        );

        if ($validator->fails()) {
            $data = [
                'message' => 'Error in data validation',
                'error' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        // search user by ci
        $user = User::where('ci', $request->ci)->first();
        if ($user) {
            // Update profession if role is tutor (role_id = 2)
            if ($request->roles_id == 2) {
                $user->update([
                    'profesion' => $request->profesion
                ]);
            }

            // Check current user role
            $currentRole = DB::table('user_roles')
                ->where('user_id', $user->id)
                ->first();

            // Handle role assignment based on current role
            if ($currentRole && $currentRole->role_id != $request->roles_id) {
                $userRoleId = $this->addUserRole($user, $request->roles_id);

            } else {
                if (!$currentRole) {
                    $userRoleId = DB::table('user_roles')->insertGetId([
                        'user_id' => $user->id,
                        'role_id' => $request->roles_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $userRoleId = $currentRole->id;
                }
            }

            $this->syncUserAreasOlympiad($userRoleId, $request->areas_id, $request->olympiad_id);

            return response()->json([
                'status' => 200,
                'message' => 'Usuario actualizado o inscrito en nuevas áreas.',
                'user' => $user,
            ]);
        }

        // Create new user with generated password
        $full_name = $request->first_name . ' ' . $request->last_name;
        $generate_password = $this->generate_password($full_name, $request->ci);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($generate_password),
            'ci' => $request->ci,
            'phone_number' => $request->phone_number,
            'genre' => $request->genre,
            'profesion' => $request->roles_id == 2 ? $request->profesion : null,
        ]);

        if (!$user) {
            $data = [
                'message' => 'Error creating user',
                'status' => 500
            ];
            return response()->json($data, 500);
        }

        $userRoleId = DB::table('user_roles')->insertGetId([
            'user_id' => $user->id,
            'role_id' => $request->roles_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);


        $this->syncUserAreasOlympiad($userRoleId, $request->areas_id, $request->olympiad_id);

        $token = $user->createToken('auth_token')->plainTextToken;

        $data = [
            'message' => 'User registration successfully',
            'acces_token' => $token,
            'token_type' => 'Bearer'
        ];

        return response()->json($data, 201);
    }

    // Authenticate user and return token
    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Unaunthorized'], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();

        $user->roles_id = $user->roles()
                ->select('roles.id', 'roles.name')
                ->get()
                ->makeHidden('pivot');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    // Generate password using first letters of names + CI
    public function generate_password($name, $ci)
    {
        $full_name = trim($name);
        $array_names = [];
        $name = "";
        $password = "";

        // Split full name into individual names
        for ($i = 0; $i < strlen($full_name); $i++) {
            if ($full_name[$i] != ' ') {
                $name .= $full_name[$i];
            } else {
                if ($name != "") {
                    array_push($array_names, $name);
                    $name = "";
                }
            }
        }

        if (!$name == "") {
            array_push($array_names, $name);
        }

        // Build password with first letters of each name + CI
        for ($i = 0; $i < sizeof($array_names); $i++) {
            $password .= $array_names[$i][0];
        }

        return strtoupper($password) . $ci;
    }

    // Sync user areas for specific olympiad avoiding duplicates
    private function syncUserAreasOlympiad($userRoleId, $areas_id, $olympiad_id)
    {
        $existingAreas = DB::table('user_area_olympiads')
            ->where('user_role_id', $userRoleId)
            ->where('olympiad_id', $olympiad_id)
            ->pluck('area_id')
            ->toArray();

        // Calculate new areas to insert
        $newAreas = array_diff($areas_id, $existingAreas);

        // Insert only new area assignments
        foreach ($newAreas as $areaId) {
            DB::table('user_area_olympiads')->insert([
                'user_role_id' => $userRoleId,
                'area_id' => $areaId,
                'olympiad_id' => $olympiad_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // Search user by CI and check olympiad registration
    public function searchUser(string $olympiadId, string $ci, string $roleId)
    {
        // Check if user exists by CI
        $user = User::where('ci', $ci)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Unregistered user',
                'status' => 404
            ], 404);
        }

        // Check if user has the required role
        $userRole = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $roleId)
            ->first();

        if (!$userRole) {
            return response()->json([
                'message' => 'User found but role not assigned',
                'status' => 404,
                'user' => $user
            ], 404);
        }

        // Check if user is registered in olympiad areas
        $areas = DB::table('user_area_olympiads')
            ->join('areas', 'user_area_olympiads.area_id', '=', 'areas.id')
            ->where('user_area_olympiads.user_role_id', $userRole->id)
            ->where('user_area_olympiads.olympiad_id', $olympiadId)
            ->select('areas.id', 'areas.name')
            ->get();

        if ($areas->count() > 0) {
            return response()->json([
                'status' => 200,
                'message' => 'User registered in the selected olympiad',
                'user' => $user,
                'areas' => $areas
            ]);
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'User registered, but not in the selected olympiad.',
                'user' => $user,
                'areas' => []
            ]);
        }
    }

    // Add new role to user or return existing one
    private function addUserRole($user, $newRoleId)
    {
        $existingRole = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $newRoleId)
            ->first();

        if ($existingRole) {
            return $existingRole->id;
        }

        return DB::table('user_roles')->insertGetId([
            'user_id' => $user->id,
            'role_id' => $newRoleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
