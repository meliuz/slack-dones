<?php

namespace Tests\Services;

use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;

use App\Services\UserService;

class UserServiceTest extends \PHPUnit_Framework_TestCase
{
    private $userService;

    public function setUp()
    {
        $app = new Application();
        $app->register(new DoctrineServiceProvider(), [
            'db.options' => [
                'driver' => 'pdo_sqlite',
                'memory' => true
            ],
        ]);

        $this->userService = new UserService($app['db']);

        $stmt = $app['db']->prepare('
            CREATE TABLE user (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slack_id INTEGER NOT NULL,
                name VARCHAR NOT NULL
            )
        ');
        $stmt->execute();
    }

    public function testGetAll()
    {
        $data = $this->userService->getAll();
        $this->assertNotNull($data);
    }

    function testSave()
    {
        $user = [
            'name' => '@test',
            'slack_id' => 'A01BCD23E',
        ];

        $this->userService->save($user);

        $data = $this->userService->getAll();

        $this->assertEquals(1, count($data));
    }

    function testUpdate()
    {
        $user = [
            'name' => '@test1',
            'slack_id' => 'B12CDE34F',
        ];

        $this->userService->save($user);

        $user = [
            'name' => '@test2',
            'slack_id' => 'C23DEF45G',
        ];

        $this->userService->update(1, $user);

        $data = $this->userService->getAll();

        $this->assertEquals('@test2', $data[0]['name']);
    }

    function testDelete()
    {
        $user = [
            'name' => '@test3',
            'slack_id' => 'D34EFG56H',
        ];

        $this->userService->save($user);
        $this->userService->delete(1);

        $data = $this->userService->getAll();

        $this->assertEquals(0, count($data));
    }
}
