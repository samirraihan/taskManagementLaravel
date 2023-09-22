<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Traits\HttpResponses;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use HttpResponses;
    use SoftDeletes;

    private $tableName = 'Tasks';

    public function index() {
        $getData = Task::all();

        return $this->success([
            'getData' => $getData
        ]);
    }

    public function store(Request $request) {
        $store = Task::create([
            'title' => $request->title,
            'description' => $request->description
        ]);

        if ($store) {
            return $this->success([
                'status' => 'success',
                'message' => 'Task created successfully'
            ]);
        } else {
            return $this->error([
                'status' => 'error',
                'message' => 'Task creation failed'
            ], 200);
        }
    }

    public function edit($id) {
        $getData = Task::find($id);

        return $this->success([
            'getData' => $getData
        ]);
    }

    public function update(Request $request) {
        // 
    }
}
