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

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['token' => $token, 'token_type' => 'Bearer'], 200);
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
