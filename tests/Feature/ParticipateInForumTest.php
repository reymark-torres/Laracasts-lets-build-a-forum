<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ParticipateInForumTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    function unauthenticated_users_may_not_add_replies()
    {
        $this->withExceptionHandling()
             ->post('/threads/sample-channel/1/replies', [])
             ->assertRedirect('/login');
    }

    /** @test */
    function an_authenticated_user_may_participate_in_forum_threads()
    {
        // Given we have an authenticated user
        $this->signIn();

        // And an existing thread
        $thread = create('App\Thread');

        // When the user adds a reply to the thread
        $reply = make('App\Reply');
        $this->post($thread->path() .'/replies', $reply->toArray());

        // Then their reply should be visible on the page
        $this->assertDatabaseHas('replies', ['body' => $reply->body]);
        $this->assertEquals(1, $thread->fresh()->replies_count);
    }

    /** @test */
    function a_reply_requires_a_body()
    {
        $this->withExceptionHandling()->signIn();

        $thread = create('App\Thread');
        $reply = make('App\Reply', ['body' => null]);

        $this->post($thread->path() . '/replies', $reply->toArray())
             ->assertSessionHasErrors('body');
    }

    /** @test */
    public function unauthorized_users_cannot_delete_replies()
    {
        $this->withExceptionHandling();

        $reply = create('App\Reply');

        $this->delete("/replies/{$reply->reply_id}")
             ->assertRedirect('login');

        $this->signIn()
             ->delete("/replies/{$reply->reply_id}")
             ->assertStatus(403);
    }

    /** @test */
    function authorized_users_can_delete_replies()
    {
        $this->signIn();

        $reply = create('App\Reply', ['user_id' => auth()->id()]);

        $this->delete("/replies/{$reply->reply_id}");

        $this->assertDatabaseMissing('replies', ['reply_id' =>  $reply->reply_id]);
        $this->assertEquals(0, $reply->thread->fresh()->replies_count);
    }


    /** @test */
    public function unauthorized_users_cannot_update_replies()
    {
        $this->withExceptionHandling();

        $reply = create('App\Reply');

        $this->patch("/replies/{$reply->reply_id}")
             ->assertRedirect('login');

        $this->signIn()
             ->patch("/replies/{$reply->reply_id}")
             ->assertStatus(403);
    }


    /** @test */
    function authorized_users_can_update_replies()
    {
        $this->signIn();

        $reply = create('App\Reply', ['user_id' => auth()->id()]);

        $updatedReply = 'Hello world!';
        $this->patch("/replies/{$reply->reply_id}", ['body' => $updatedReply]);

        $this->assertDatabaseHas('replies', ['reply_id' => $reply->reply_id, 'body' => $updatedReply]);
    }

    /** @test */
    function replies_that_contain_spam_may_not_be_created()
    {
        $this->withExceptionHandling();

        // Given we have an authenticated user
        $this->signIn();

        // And an existing thread
        $thread = create('App\Thread');

        // When the user adds a "SPAM" reply to the thread
        $reply = make('App\Reply', [
            'body' => 'Yahoo Customer Support'
        ]);

        $this->json('post', $thread->path() . '/replies', $reply->toArray())
             ->assertStatus(422);
    }

    /** @test */
    function users_may_only_reply_a_maximum_of_once_per_minute()
    {
        $this->withExceptionHandling();

        $this->signIn();

        $thread = create('App\Thread');

        $reply = make('App\Reply', [
            'body' => 'My simple reply.'
        ]);

        $this->post($thread->path() . '/replies', $reply->toArray())
             ->assertStatus(200);

        $this->post($thread->path() . '/replies', $reply->toArray())
             ->assertStatus(429);
    }
}
