<?php

namespace Tests\App\Http\Controllers;

use App\Book;
use Laravel\Lumen\Testing\DatabaseMigrations;
use TestCase;
use Illuminate\Http\Response;

class BooksControllerValidationTest extends TestCase
{
    use DatabaseMigrations;

    /** @test **/
    public function it_validates_required_fields_when_creating_a_new_book()
    {
        $this->json('POST', '/books', []);

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->response->getStatusCode());

        $body = json_decode($this->response->getContent(), true);

        $this->assertArrayHasKey('title', $body);
        $this->assertArrayHasKey('description', $body);

        $this->assertEquals(["The title field is required."], $body['title']);
        $this->assertEquals(["Please fill out the description."], $body['description']);
    }

    /** @test **/
    public function it_validates_required_fields_when_updating_a_book()
    {
        $book = $this->bookFactory();

        $this->json("PUT", "/books/{$book->id}", []);

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->response->getStatusCode());

        $body = json_decode($this->response->getContent(), true);

        $this->assertArrayHasKey('title', $body);
        $this->assertArrayHasKey('description', $body);

        $this->assertEquals(["The title field is required."], $body['title']);
        $this->assertEquals(["Please fill out the description."], $body['description']);
    }

    /** @test **/
    public function title_fails_create_validation_when_just_too_long()
    {
        // Creating a book
        $book = $this->bookFactory();

        $book->title = str_repeat('a', 256);

        $this->json("POST","/books", [
            'title' => $book->title,
            'description' => $book->description,
            'author_id' => $book->author->id,
        ]);

        $this
            ->seeStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->seeJson([
                'title' => ["The title may not be greater than 255 characters."]
            ])
            ->notSeeInDatabase('books', ['title' => $book->title]);
    }

    /** @test **/
    public function title_fails_update_validation_when_just_too_long()
    {
        // Updating a book
        $book = $this->bookFactory();

        $book->title = str_repeat('a', 256);

        $this->json("PUT","/books/{$book->id}", [
            'title' => $book->title,
            'description' => $book->description,
            'author_id' => $book->author->id,
        ]);

        $this
            ->seeStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->seeJson([
                'title' => ["The title may not be greater than 255 characters."]
            ])
            ->notSeeInDatabase('books', ['title' => $book->title]);
    }

    /** @test **/
    public function title_passes_create_validation_when_exactly_max()
    {
        // Creating a new Book
        $book = $this->bookFactory();

        $book->title = str_repeat('a', 255);

        $this->json("POST", "/books", [
            'title' => $book->title,
            'description' => $book->description,
            'author_id' => $book->author->id,
        ]);

        $this
            ->seeStatusCode(Response::HTTP_CREATED)
            ->seeInDatabase('books', ['title' => $book->title]);
    }

    /** @test **/
    public function title_passes_update_validation_when_exactly_max()
    {
        // Updating a book
        $book = $this->bookFactory();

        $book->title = str_repeat('a', 255);

        $this->json("PUT", "/books/{$book->id}", [
            'title' => $book->title,
            'description' => $book->description,
            'author_id' => $book->author->id,
        ]);

        $this
            ->seeStatusCode(Response::HTTP_OK)
            ->seeInDatabase('books', ['title' => $book->title]);
    }
}
