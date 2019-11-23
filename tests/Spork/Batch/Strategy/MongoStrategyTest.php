<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Batch\Strategy;

use PHPUnit\Framework\TestCase;
use Spork\EventDispatcher\Events;
use Spork\ProcessManager;

class MongoStrategyTest extends TestCase
{
    private $mongo;
    private $manager;

    protected function setUp(): void
    {
        if (!class_exists('MongoClient', false)) {
            $this->markTestSkipped('Mongo extension is not loaded');
        }

        try {
            $this->mongo = new \MongoClient();
        } catch (\MongoConnectionException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $this->manager = new ProcessManager();
        $this->manager->setDebug(true);

        // close the connection prior to forking
        $mongo = $this->mongo;
        $this->manager->addListener(Events::PRE_FORK, function () use ($mongo) {
            $mongo->close();
        });
    }

    protected function tearDown(): void
    {
        if ($this->mongo) {
            $this->mongo->close();
        }

        unset($this->mongo, $this->manager);
    }

    public function testBatchJob()
    {
        $coll = $this->mongo->spork->widgets;

        $coll->remove();
        $coll->batchInsert([
            ['name' => 'Widget 1'],
            ['name' => 'Widget 2'],
            ['name' => 'Widget 3'],
        ]);

        $this->manager->createBatchJob($coll->find(), new MongoStrategy())
            ->execute(function ($doc) use ($coll) {
                $coll->update(
                    ['_id' => $doc['_id']],
                    ['$set' => ['seen' => true]]
                );
            });

        $this->manager->wait();

        foreach ($coll->find() as $doc) {
            $this->assertArrayHasKey('seen', $doc);
        }
    }
}
