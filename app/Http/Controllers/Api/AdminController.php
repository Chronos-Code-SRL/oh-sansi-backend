<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users_academic_responsible = User::where('roles_id', 2)->get();
        $users_evaluator = User::where('roles_id', 3)->get();

        $data = [
            'users_academic_responsible'=> [
                'user' =>  $users_academic_responsible,
                'status' => 201
            ],
            'users_evaluator' => [
                'user' =>  $users_evaluator,
                'status' => 201
            ],
        ];
        
        return response()->json($data, 200);
    }

    public function destroy()
    {
        
    }
}
