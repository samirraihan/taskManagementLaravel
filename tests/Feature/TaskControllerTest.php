<?php

namespace Tests\Feature;

use App\Mail\TaskAssigned;
use App\Mail\TaskComment;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Support\Facades\Mail;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }
    // fetch tasks
    public function test_fetch_tasks()
    {
        $this->actingAs($this->user);
        $response = $this->get('/api/v1/admin/tasks/fetch');
        $response->assertStatus(200);
    }
    // create task
    public function test_create_task()
    {
        $this->actingAs($this->user);

        $users = User::factory()->count(5)->create();

        $data = [
            'title' => 'Test Task',
            'description' => 'This is a test task description',
            'user_ids' => [2, 3, 4],
        ];

        Mail::fake();

        $response = $this->json('POST', '/api/v1/admin/tasks/store', $data);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'message',
        ]);

        foreach ($data['user_ids'] as $userId) {
            $user = $users->find($userId);

            if ($user) {
                Mail::assertSent(TaskAssigned::class, function ($mail) use ($user) {
                    return $mail->hasTo($user->email) && $mail->task->title === 'Test Task' && $mail->task->description === 'This is a test task description';
                });
            }
        }
    }
    // edit task
    public function test_edit_task()
    {
        $this->actingAs($this->user);

        $task = Task::factory()->create([
            'title' => 'Test Task',
            'description' => 'This is a test task description',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get("/api/v1/admin/tasks/{$task->id}/edit");

        $response->assertStatus(200);
    }

    // update task
    public function test_update_task()
    {
        $this->actingAs($this->user);

        $users = User::factory()->count(5)->create();

        $task = Task::factory()->create([
            'title' => 'Test Task',
            'description' => 'This is a test task description',
        ]);

        $data = [
            'title' => 'New Task Title',
            'description' => 'New Task Description',
            'user_ids' => [1, 2, 3],
        ];

        Mail::fake();

        $response = $this->json('POST', "/api/v1/admin/tasks/{$task->id}/update", $data);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'message',
        ]);

        $newlyAssignedUsers = User::whereIn('id', $data['user_ids'])->get();
        foreach ($newlyAssignedUsers as $user) {
            Mail::assertSent(TaskAssigned::class, function ($mail) use ($user) {
                return $mail->hasTo($user->email);
            });
        }
    }

    // delete task
    public function test_delete_task()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task = Task::factory()->create([
            'created_by' => $user->id,
        ]);

        $response = $this->json('POST', "/api/v1/admin/tasks/{$task->id}/delete");

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'message',
        ]);

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    }
    // comment task
    public function testCommentOnTask()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task = Task::factory()->create([
            'created_by' => $user->id,
        ]);

        $commentData = Comment::factory()->make([
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        Mail::fake();

        $response = $this->json('POST', "/api/v1/admin/tasks/{$task->id}/comment", [
            'body' => $commentData->body,
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'message',
        ]);

        $assignedUsers = $task->users()->whereNull('task_user.deleted_at')->where('user_id', '!=', $task->created_by)->get();
        $allUsers = $assignedUsers->push($user);

        foreach ($allUsers as $recipientUser) {
            Mail::assertSent(TaskComment::class, function ($mail) use ($recipientUser, $commentData) {
                return $mail->hasTo($recipientUser->email) && $mail->comment->body === $commentData->body;
            });
        }
    }
}
