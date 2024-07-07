<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IdentityController extends Controller
{
    public function identify(Request $request)
    {
        $email = $request->input('email');
        $phoneNumber = $request->input('phoneNumber');
    }
}
