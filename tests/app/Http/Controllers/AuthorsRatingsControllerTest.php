<?php

namespace Tests\App\Http\Controllers;

use App\Author;
use App\Rating;
use Laravel\Lumen\Testing\DatabaseMigrations;
use TestCase;

class AuthorsRatingsControllerTest extends TestCase
{
    use DatabaseMigrations;

    /** @test **/
    public function store_can_add_a_rating_to_an_author()
    {
        $author = factory(Author::class)->create();

        $this->json("POST", "/authors/{$author->id}/ratings", ['value' => 5]);

        $this
            ->seeStatusCode(201)
            ->seeJson([
                'value' => 5
            ])
            ->seeJson([
                'rel' => 'author',
                'href' => route('authors.show', ['id' => $author->id])
            ]);

        $body = $this->response->getData(true);
        $this->assertArrayHasKey('data', $body);

        $data = $body['data'];
        $this->assertArrayHasKey('links', $data);
    }

    /** @test **/
    public function store_fails_when_the_author_is_invalid()
    {
        $this->json('POST', '/authors/1/ratings', []);
        $this->seeStatusCode(404);
    }

    /** @test **/
    public function destroy_can_delete_an_author_rating()
    {
        $author = factory(Author::class)->create();
        $ratings = $author->ratings()->saveMany(
            factory(Rating::class, 2)->make()
        );

        $this->assertCount(2, $ratings);

        $ratings->each(function (Rating $rating) use ($author) {
            $this->seeInDatabase('ratings', [
                'rateable_id' => $author->id,
                'id' => $rating->id
            ]);
        });

        $ratingToDelete = $ratings->first();
        $this
            ->delete(
                "/authors/{$author->id}/ratings/{$ratingToDelete->id}"
            )
            ->seeStatusCode(204);

        $dbAuthor = Author::find($author->id);
        $this->assertCount(1, $dbAuthor->ratings);
        $this->notSeeInDatabase(
            'ratings',
            ['id' => $ratingToDelete->id]
        );
    }

    /** @test **/
    public function destroy_should_not_delete_ratings_from_another_author()
    {
        $authors = factory(Author::class, 2)->create();
        $authors->each(function (Author $author) {
            $author->ratings()->saveMany(
                factory(Rating::class, 2)->make()
            );
        });

        $firstAuthor = $authors->first();
        $rating = $authors
            ->last()
            ->ratings()
            ->first();

        $this->json(
            "DELETE",
            "/authors/{$firstAuthor->id}/ratings/{$rating->id}",
            []
        )->seeStatusCode(404);
    }

    /** @test **/
    public function destroy_fails_when_the_author_is_invalid()
    {
        $this->json(
            "DELETE",
            '/authors/1/ratings/1',
            []
        )->seeStatusCode(404);
    }
}
