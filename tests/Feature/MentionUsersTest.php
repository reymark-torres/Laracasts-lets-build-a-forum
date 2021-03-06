<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MentionUsersTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    function mentioned_users_in_a_reply_are_notified()
    {
        // Given I have a user, Reymark, who is signed in.
        $reymark = create('App\User', ['name' => 'Reymark']);
        $this->signIn($reymark);

        // And another user, LadyMorganne
        $lady = create('App\User', ['name' => 'LadyMorganne']);

        // If we have a thread
        $thread = create('App\Thread');
        // And Reymark replies and mentions @LadyMorganne
        $reply = make('App\Reply', [
            'body' => '@LadyMorganne @TaliaShyn look at this.'
        ]);
        $this->json('post', $thread->path() . '/replies', $reply->toArray());

        // Then, LadyMorganne should be notified
        $this->assertCount(1, $lady->notifications);
    }

    /** @test */
    function it_can_fetch_all_mentioned_users_starting_with_the_given_characters()
    {
        create('App\User', ['name' => 'lady 1']);
        create('App\User', ['name' => 'lady 2']);
        create('App\User', ['name' => 'mister 1']);

        $results = $this->json('GET', '/api/users', ['name' => 'lady']);

        $this->assertCount(2, $results->json());
    }
}
