<?php

use Mockery as m;
use OpenCFP\Application;
use OpenCFP\Environment;

class AdminTalkControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test that the index page grabs a collection of talks
     * and successfully displays them
     *
     * @test
     */
    public function indexPageDisplaysTalksCorrectly()
    {
        $app = new Application(BASE_PATH, Environment::testing());
        ob_start();
        $app->run();
        ob_end_clean();

        // Create a pretend user
        $user = m::mock('StdClass');
        $user->shouldReceive('hasPermission')->with('admin')->andReturn(true);
        $user->shouldReceive('getId')->andReturn(1);

        // Create a test double for Sentry
        $sentry = m::mock('StdClass');
        $sentry->shouldReceive('check')->andReturn(true);
        $sentry->shouldReceive('getUser')->andReturn($user);
        $app['sentry'] = $sentry;

        // Create an in-memory database
        $cfg = new \Spot\Config;
        $cfg->addConnection('sqlite', [
            'dbname' => 'sqlite::memory',
            'driver' => 'pdo_sqlite'
        ]);
        $app['spot'] = new \Spot\Locator($cfg);

        // Create a fake request
        $req = m::mock('Symfony\Component\HttpFoundation\Request');
        $paramBag = m::mock('Symfony\Component\HttpFoundation\ParameterBag');

        $queryParams = [
            'page' => 1,
            'per_page' => 20,
            'sort' => 'ASC',
            'order_by' => 'title',
            'filter' => null,
        ];
        $paramBag->shouldReceive('all')->andReturn($queryParams);

        $req->shouldReceive('get')->with('page')->andReturn($queryParams['page']);
        $req->shouldReceive('get')->with('per_page')->andReturn($queryParams['per_page']);
        $req->shouldReceive('get')->with('sort')->andReturn($queryParams['sort']);
        $req->shouldReceive('get')->with('order_by')->andReturn($queryParams['order_by']);
        $req->shouldReceive('get')->with('filter')->andReturn($queryParams['filter']);
        $req->query = $paramBag;
        $req->shouldReceive('getRequestUri')->andReturn('foo');

        $this->createTestData($app['spot']);
        $controller = new \OpenCFP\Http\Controller\Admin\TalksController();
        $controller->setApplication($app);
        $response = $controller->indexAction($req, $app);
        $this->assertContains('Test Title', (string) $response);
        $this->assertContains('Test User', (string) $response);
    }

    protected function createTestData($spot)
    {
        $user_mapper = $spot->mapper('OpenCFP\Domain\Entity\User');
        $user_mapper->migrate();
        $user = $user_mapper->build([
            'email' => 'test@test.com',
            'password' => 'randompasswordhashed',
            'first_name' => 'Test',
            'last_name' => 'User',
            'activated' => 1,
            'transportation' => 0,
            'hotel' => 0
        ]);
        $user_mapper->save($user);

        $favorite_mapper = $spot->mapper('OpenCFP\Domain\Entity\Favorite');
        $favorite_mapper->migrate();

        $talk_mapper = $spot->mapper('OpenCFP\Domain\Entity\Talk');
        $talk_mapper->migrate();

        $talk_comment_mapper = $spot->mapper('OpenCFP\Domain\Entity\TalkComment');
        $talk_comment_mapper->migrate();

        $talk_meta_mapper = $spot->mapper('OpenCFP\Domain\Entity\TalkMeta');
        $talk_meta_mapper->migrate();

        $talk = $talk_mapper->build([
            'title' => 'Test Title',
            'description' => 'Test title description',
            'user_id' => 1
        ]);
        $talk_mapper->save($talk);
    }
}
