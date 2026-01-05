<?php

namespace App\Exceptions;

use Exception;

class UrbanWatchException extends Exception
{
    //

    public function render($request){
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 400);
    }
}
