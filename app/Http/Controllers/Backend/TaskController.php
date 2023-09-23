<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
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

    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 5);
        // Get all tasks
        $getData = Task::with('comments')->paginate($perPage);
        $users = User::all();

        return $this->success([
            'getData' => $getData,
            'users' => $users
        ]);
    }

    public function store(StoreTaskRequest $request)
    {
        $userID = auth()->user()->id;

        // Store the task
        $store = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'created_by' => $userID
        ]);

        // Attach the authenticated user and other selected users
        $userIds = array_merge([$userID], $request->user_ids);
        $store->users()->attach($userIds);

        // Send email notifications to assigned users
        $assignedUsers = User::whereIn('id', $userIds)->get();
        foreach ($assignedUsers as $user) {
            Mail::to($user->email)->send(new TaskAssigned($store));
        }

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
        $getData = Task::with(['users' => function ($query) {
            $query->where('deleted_at', NULL);
        }])->find($id);

        return $this->success([
            'getData' => $getData
        ]);
    }

    public function update(UpdateTaskRequest $request, $id)
    {
        $task = Task::find($id);

        if (!$task) {
            return $this->error([
                'status' => 'error',
                'message' => 'Task not found'
            ], 200);
        }

        $user = auth()->user();
        if (!($user->id === $task->created_by || $task->users()->where('user_id', $user->id)->exists())) {
            return $this->error([
                'status' => 'error',
                'message' => 'You are not eligible to update this task'
            ], 200); // Forbidden
        }

        // Update the task
        $task->update([
            'title' => $request->input('title'),
            'description' => $request->input('description')
        ]);

        $gotUserIds = $request->input('user_ids', []);
        // Get the currently assigned users to the task
        $currentlyAssignedUserIds = $task->users->pluck('id')->toArray();

        // Filter out user IDs to attach and detach
        $newUserIds = array_diff($gotUserIds, $currentlyAssignedUserIds);
        $deletedUserIds = array_diff($currentlyAssignedUserIds, $gotUserIds);
        $reassignedUserIds = array_intersect($currentlyAssignedUserIds, $gotUserIds);

        // Attach the new users to the task
        if (!empty($newUserIds)) {
            $task->users()->attach($newUserIds);

            $newlyAssignedUsers = User::whereIn('id', $newUserIds)->get();
            foreach ($newlyAssignedUsers as $user) {
                // Send email to the newly assigned user
                Mail::to($user->email)->send(new TaskAssigned($task));
            }
        }

        // Add soft delete to already assigned users
        if (!empty($deletedUserIds)) {
            $task->users()->updateExistingPivot($deletedUserIds, [
                'deleted_at' => now()
            ]);
        }

        // Remove soft delete from reassigned users
        if (!empty($reassignedUserIds)) {
            $task->users()->updateExistingPivot($reassignedUserIds, [
                'deleted_at' => NULL
            ]);
        }

        return $this->success([
            'status' => 'success',
            'message' => 'Task updated successfully'
        ]);
    }

    public function delete($id)
    {
        $task = Task::find($id);
        if (!$task) {
            return $this->error([
                'status' => 'error',
                'message' => 'Task not found'
            ], 404);
        }

        $user = auth()->user();
        if (!($user->id === $task->created_by || $task->users()->where('user_id', $user->id)->exists())) {
            return $this->error([
                'status' => 'error',
                'message' => 'You are not eligible to delete this task'
            ], 200); // Forbidden
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

    public function comment(StoreCommentRequest $request, $id)
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
        $user = auth()->user();
        if (!($user->id === $task->created_by || $task->users()->where('user_id', $user->id)->exists())) {
            return $this->error([
                'status' => 'error',
                'message' => 'You are not eligible to comment on this task'
            ], 200); // Forbidden
        }

        // Store the comment in comments table
        $comment = Comment::create([
            'task_id' => $id,
            'user_id' => $user->id,
            'body' => $request->body
        ]);

        // send email to the task creator by task table created_by and task_user table assigned users
        $assignedUsers = $task->users()->whereNull('task_user.deleted_at')->where('user_id', '!=', $task->created_by)->get();
        $users = $assignedUsers->push($user);

        if ($comment) {
            foreach ($users as $user) {
                // Send email to the user
                Mail::to($user->email)->send(new TaskComment($task, $comment));
            }
            
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
