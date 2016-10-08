<?php

namespace Tests\App\Http\Controllers;

use App\Author;
use Laravel\Lumen\Testing\DatabaseMigrations;
use TestCase;

class AuthorsControllerValidationTest extends TestCase
{
    use DatabaseMigrations;

    /** @test **/
    public function validation_validates_required_fields()
    {
        $author = factory(Author::class)->create();

        $tests = [
            ['method' => 'POST', 'url' => '/authors'],
            ['method' => 'PUT', 'url' => "/authors/{$author->id}"]
        ];

        foreach ($tests as $test) {
            $this->json($test['method'], $test['url'], []);
            $this->seeStatusCode(422);

            $data = $this->response->getData(true);
            $fields = ['name', 'gender', 'biography'];

            foreach ($fields as $field) {
                $this->assertArrayHasKey($field, $data);
                $this->assertEquals(["The {$field} field is required."], $data[$field]);
            }
        }
    }

    /** @test **/
    public function validation_invalidates_incorrect_gender_data()
    {
        foreach ($this->getValidationTestData() as $test) {
            $test['data']['gender'] = 'unknown';

            $this->json($test['method'], $test['url'], $test['data']);

            $this->seeStatusCode(422);

            $data = $this->response->getData(true);
            $this->assertCount(1, $data);
            $this->assertArrayHasKey('gender', $data);
            $this->assertEquals(
                ["Gender format is invalid: must equal 'male' or 'female'"],
                $data['gender']
            );
        }
    }

    /** @test **/
    public function validation_invalidates_name_when_name_is_just_too_long()
    {
        foreach ($this->getValidationTestData() as $test) {
            $test['data']['name'] = str_repeat('a', 256);

            $this->json($test['method'], $test['url'], $test['data']);

            $this->seeStatusCode(422);

            $data = $this->response->getData(true);
            $this->assertCount(1, $data);
            $this->assertArrayHasKey('name', $data);
            $this->assertEquals(["The name may not be greater than 255 characters."], $data['name']);
        }
    }

    /** @test **/
    public function validation_is_valid_when_name_is_just_long_enough()
    {
        foreach ($this->getValidationTestData() as $test) {
            $test['data']['name'] = str_repeat('a', 255);

            $this->json($test['method'], $test['url'], $test['data']);

            $this->seeStatusCode($test['status']);
            $this->seeInDatabase('authors', $test['data']);
        }
    }

    /** @test **/
    public function store_returns_a_valid_location_header()
    {
        $postData = [
            'name' => 'H. G. Wells',
            'gender' => 'male',
            'biography' => 'Prolific Science-Fiction Writer'
        ];

        $this
            ->json('POST', '/authors', $postData)
            ->seeStatusCode(201);

        $data = $this->response->getData(true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data['data']);

        // Check the Location header
        $id = $data['data']['id'];
        $this->seeHeaderWithRegExp('Location', "#/authors/{$id}$#");
    }

    /** @test **/
    public function delete_can_remove_an_author_and_his_or_her_books()
    {
        $author = factory(Author::class)->create();

        $this
            ->delete("/authors/{$author->id}")
            ->seeStatusCode(204)
            ->notSeeInDatabase('authors', ['id' => $author->id])
            ->notSeeInDatabase('books', ['author_id' => $author->id]);
    }

    /** @test **/
    public function deleting_an_invalid_author_should_return_a_404()
    {
        $this
            ->json('DELETE', '/authors/99999', [])
            ->seeStatusCode(404);
    }

    /**
     * @return array
     */
    private function getValidationTestData()
    {
        $author = factory(Author::class)->create();
        return [
            // Create
            [
                'method' => 'post',
                'url' => '/authors',
                'status' => 201,
                'data' => [
                    'name' => 'John Doe',
                    'gender' => 'male',
                    'biography' => 'An anonymous author'
                ]
            ],
            // Update
            [
                'method' => 'put',
                'url' => "/authors/{$author->id}",
                'status' => 200,
                'data' => [
                    'name' => $author->name,
                    'gender' => $author->gender,
                    'biography' => $author->biography
                ]
            ]
        ];
    }
}
