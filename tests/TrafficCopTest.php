<?php

namespace Orchid\Crud\Tests;

use Orchid\Crud\Tests\Fixtures\PostResource;
use Orchid\Crud\Tests\Models\Post;

class TrafficCopTest extends TestCase
{
    public function testBaseCopResource(): void
    {
        $post = Post::factory()->create();
        $post->touch();
        $retrievedAt = $post->updated_at->subMinutes(5)->toJson();

        $this
            ->followingRedirects()
            ->from(route('platform.resource.edit', [
                'resource' => PostResource::uriKey(),
                'id'       => $post,
            ]))
            ->post(route('platform.resource.edit', [
                'resource' => PostResource::uriKey(),
                'id'       => $post,
                'method'   => 'update',
            ]), [
                'model'         => $post->toArray(),
                '_retrieved_at' => $retrievedAt,
            ])
            ->assertSee(PostResource::trafficCopMessage())
            ->assertOk();
    }

    /**
     * Set the URL of the previous request.
     *
     * @param string $url
     *
     * @return $this
     */
    public function from(string $url)
    {
        session()->setPreviousUrl($url);

        return $this;
    }
}
