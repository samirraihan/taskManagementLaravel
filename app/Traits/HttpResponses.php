<?php

namespace App\Traits;


trait HttpResponses
{
    protected function success($data, $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Data Fetched Successfully.',
            'status_code' => $code,
            'data' => $data
        ], $code);
    }

    protected function error($data, $code)
    {
        return response()->json([
            'status' => 'failed',
            'message' => 'Error Has Occurred!',
            'status_code' => $code,
            'data' => $data
        ], $code);
    }
}