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
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Registro de usuario",
     *     description="Crea un nuevo usuario en el sistema",
     *     tags={"Autenticación"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name","last_name","email","password","password_confirmation","ci","phone_number","genre","roles_id"},
     *             @OA\Property(property="first_name", type="string", example="Maria"),
     *             @OA\Property(property="last_name", type="string", example="Perez"),
     *             @OA\Property(property="email", type="string", format="email", example="maria@example.com"),
     *             @OA\Property(property="ci", type="string", example="12345678"),
     *             @OA\Property(property="phone_number", type="string", example="71717717"),
     *             @OA\Property(property="genre", type="string", enum={"masculino","femenino"}, example="femenino"),
     *             @OA\Property(property="roles_id", type="integer", example=2),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuario registrado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User registration successfully"),
     *             @OA\Property(property="acces_token", type="string", example="2|gfdsgsgfsgsgf..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Error de validación"),
     *     @OA\Response(response=500, description="Error al crear el usuario")
     * )
     */
    public function register(Request $request)
    {

        // $userExists = User::where('ci', $request->ci)->first();
        // if ($userExists) {
        //     $this->syncUserAreasOlympiad($userExists, $request->areas_id, $request->olympiad_id);

        //     $data = [
        //         'message' => 'User updated successfully',
        //         'user' => $userExists,
        //     ];
        //     return response()->json($data, 200);
        // }

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
            
            $currentRole = DB::table('user_roles')
                ->where('user_id', $user->id)
                ->first();

            if ($currentRole && $currentRole->role_id != $request->roles_id) {
                $userRoleId = $this->changeUserRole($user, $request->roles_id);

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
            // 'roles_id' => $request->roles_id,
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

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Iniciar sesión",
     *     description="Autenticar usuario y devolver token",
     *     tags={"Autenticación"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="maria@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="MP12345678")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="2|gfdsgsgfsgsgf..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autorizado")
     * )
     */
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
    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Cerrar sesión",
     *     description="Elimina el token del usuario autenticado",
     *     tags={"Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logged out successfully")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function generate_password($name, $ci)
    {
        $full_name = trim($name);
        $array_names = [];
        $name = "";
        $password = "";

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

        for ($i = 0; $i < sizeof($array_names); $i++) {
            $password .= $array_names[$i][0];
        }

        return strtoupper($password) . $ci;
    }

    private function syncUserAreasOlympiad($userRoleId, $areas_id, $olympiad_id)
    {
        $existingAreas = DB::table('user_area_olympiads')
            ->where('user_role_id', $userRoleId)
            ->where('olympiad_id', $olympiad_id)
            ->pluck('area_id')
            ->toArray();

        // Calcular nuevas áreas a insertar
        $newAreas = array_diff($areas_id, $existingAreas);
        // elimina areas que no estan en el arreglo
        $areasToDelete = array_diff($existingAreas, $areas_id);

        // Insertar solo las nuevas
        foreach ($newAreas as $areaId) {
            DB::table('user_area_olympiads')->insert([
                'user_role_id' => $userRoleId,
                'area_id' => $areaId,
                'olympiad_id' => $olympiad_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!empty($areasToDelete)) {
            DB::table('user_area_olympiads')
            ->where('user_role_id', $userRoleId)
            ->where('olympiad_id', $olympiad_id)
            ->whereIn('area_id', $areasToDelete)
            ->delete();
        }
    }

    public function searchUser(string $olympiadId, string $ci, string $roleId)
    {
        // search user by ci
        $user = User::where('ci', $ci)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Unregistered user',
                'status' => 404
            ], 404);
        }

        // search user by role
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

        // if not found, return unregistered user with role
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

    private function changeUserRole($user, $newRoleId)
    {
        // search old role
        $oldRole = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->first();

        if ($oldRole) {
            // delete areas associated with old role
            DB::table('user_area_olympiads')
                ->where('user_role_id', $oldRole->id)
                ->delete();

            // delete rol
            DB::table('user_roles')
                ->where('id', $oldRole->id)
                ->delete();
        }

        // create new role
        return DB::table('user_roles')->insertGetId([
            'user_id' => $user->id,
            'role_id' => $newRoleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
