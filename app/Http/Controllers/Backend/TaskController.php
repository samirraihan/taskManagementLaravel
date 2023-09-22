<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Mail\TaskAssigned;
use App\Mail\TaskComment;
use App\Models\Comment;
use App\Traits\HttpResponses;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TaskController extends Controller
{
    use HttpResponses;
    use SoftDeletes;

    private $tableName = 'Tasks';

    public function index()
    {
        // Get all tasks
        $getData = Task::all();

        return $this->success([
            'getData' => $getData
        ]);
    }

    public function store(Request $request)
    {
        // Store the task
        $store = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'created_by' => auth()->user()->id
        ]);

        $store->users()->attach(auth()->user()->id);

        if ($store) {
            return $this->success([
                'status' => 'success',
                'message' => 'Task created successfully'
            ]);
        } else {
            return $this->error([
                'status' => 'error',
                'message' => 'Task creation failed'
            ], 500);
        }
    }

    public function edit($id)
    {
        // Get the task
        $getData = Task::find($id);

        return $this->success([
            'getData' => $getData
        ]);
    }

    public function update(Request $request, $id)
    {
        $task = Task::find($id);

        if (!$task) {
            return $this->error([
                'status' => 'error',
                'message' => 'Task not found'
            ], 404);
        }

        $taskCreator = User::find($task->created_by);
        $user = auth()->user();
        if (!$task->users()->where('user_id', $user->id)->exists() || !$taskCreator->is($user)) {
            return $this->error([
                'status' => 'error',
                'message' => 'You are not eligible to update this task'
            ], 403); // Forbidden
        }

        // Update the task
        $task->update([
            'title' => $request->input('title'),
            'description' => $request->input('description')
        ]);

        // Get the currently assigned users to the task
        $currentlyAssignedUserIds = $task->users->pluck('id')->where('delete_at', NULL)->toArray();

        // Filter out user IDs that are already assigned
        $newUserIds = array_diff($request->input('user_ids', []), $currentlyAssignedUserIds);
        $alreadyAssignedUserIds = array_intersect($request->input('user_ids', []), $currentlyAssignedUserIds);

        if ($newUserIds) {
            // Attach the new users to the task
            $task->users()->attach($newUserIds);

            if ($task->users()->count() > 0) {
                $users = User::whereIn('id', $newUserIds)->get();
                foreach ($users as $user) {
                    // Send email to the user
                    Mail::to($user->email)->send(new TaskAssigned($task));
                }
            }
        }

        // Add softdelete to already assigned users from the task
        $task->users()->updateExistingPivot($alreadyAssignedUserIds, [
            'deleted_at' => now()
        ]);

        return $this->success([
            'status' => 'success',
            'message' => 'Users assigned successfully to the task'
        ]);
    }

    public function delete(Request $request, $id)
    {
        $task = Task::find($id);
        if (!$task) {
            return $this->error([
                'status' => 'error',
                'message' => 'Task not found'
            ], 404);
        }

        $taskCreator = User::find($task->created_by);
        $user = auth()->user();
        if (!$task->users()->where('user_id', $user->id)->exists() || !$taskCreator->is($user)) {
            return $this->error([
                'status' => 'error',
                'message' => 'You are not eligible to delete this task'
            ], 403); // Forbidden
        }

        // Delete the task
        $delete = $task->delete();

        // Delete the task from the pivot table
        $task->users()->updateExistingPivot($task->users->pluck('id')->toArray(), [
            'deleted_at' => now()
        ]);

        if ($delete) {
            return $this->success([
                'status' => 'success',
                'message' => 'Task deleted successfully'
            ]);
        } else {
            return $this->error([
                'status' => 'error',
                'message' => 'Task deletion failed'
            ], 500);
        }
    }

    public function comment(Request $request, $id)
    {
        // Get the task
        $task = Task::find($id);

        if (!$task) {
            return $this->error([
                'status' => 'error',
                'message' => 'Task not found'
            ], 404);
        }

        // Check if the authenticated user is assigned to the task
        $taskCreator = User::find($task->created_by);
        $user = auth()->user();
        if (!$task->users()->where('user_id', $user->id)->exists() || !$taskCreator->is($user)) {
            return $this->error([
                'status' => 'error',
                'message' => 'You are not eligible to comment on this task'
            ], 403); // Forbidden
        }

        // Store the comment in comments table
        $comment = Comment::create([
            'task_id' => $id,
            'user_id' => $user->id,
            'body' => $request->body
        ]);

        // send email to the task creator by task table created_by and task_user table assigned users
        $assignedUsers = $task->users()->where('user_id', '!=', $task->created_by)->get();
        $users = $assignedUsers->push($taskCreator);
        foreach ($users as $user) {
            // Send email to the user
            Mail::to($user->email)->send(new TaskComment($task, $comment));
        }

        if ($comment) {
            return $this->success([
                'status' => 'success',
                'message' => 'Comment added successfully'
            ]);
        } else {
            return $this->error([
                'status' => 'error',
                'message' => 'Comment addition failed'
            ], 500);
        }
    }
}
