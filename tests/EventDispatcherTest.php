<?php
/**
 * Created for EventDispatcher.
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 20.08.2017 1:21
 */

namespace XAKEPEHOK\EventDispatcher;


use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class EventDispatcherTest extends TestCase
{

    /** @var ContainerInterface */
    private $container;

    /** @var EventInterface */
    private $event_1;

    /** @var EventInterface */
    private $event_2;

    /** @var ListenerInterface */
    private $listener_11;

    /** @var ListenerInterface */
    private $listener_111;

    /** @var ListenerInterface */
    private $listener_1111;

    /** @var ListenerInterface */
    private $listener_22;

    /** @var array */
    private $config;

    /** @var EventDispatcher */
    private $dispatcher;

    private $eventChecker = [];

    public function setUp()
    {
        $this->event_1 = new class() implements EventInterface {
            public function getName()
            {
                return 'Event 1';
            }
        };

        $this->event_2 = new class() implements EventInterface {
            public function getName()
            {
                return 'Event 2';
            }
        };

        /** @noinspection PhpUndefinedFieldInspection */
        $this->listener_11 = new class($this->eventChecker) implements ListenerInterface {

            private $checker;

            public function __construct(&$checker)
            {
                $this->checker = &$checker;
            }

            public function handle(EventInterface $event)
            {
                /** @noinspection PhpUndefinedMethodInspection */
                $this->checker[] = $event->getName() . ':Listener 11';
            }
        };

        /** @noinspection PhpUndefinedFieldInspection */
        $this->listener_111 = new class($this->eventChecker) implements ListenerInterface {

            private $checker;

            public function __construct(&$checker)
            {
                $this->checker = &$checker;
            }

            public function handle(EventInterface $event)
            {
                /** @noinspection PhpUndefinedMethodInspection */
                $this->checker[] = $event->getName() . ':Listener 111';
            }
        };

        /** @noinspection PhpUndefinedFieldInspection */
        $this->listener_1111 = new class($this->eventChecker) implements ListenerInterface {

            private $checker;

            public function __construct(&$checker)
            {
                $this->checker = &$checker;
            }

            public function handle(EventInterface $event)
            {
                /** @noinspection PhpUndefinedMethodInspection */
                $this->checker[] = $event->getName() . ':Listener 1111';
            }
        };

        /** @noinspection PhpUndefinedFieldInspection */
        $this->listener_22 = new class($this->eventChecker) implements ListenerInterface {

            private $checker;

            public function __construct(&$checker)
            {
                $this->checker = &$checker;
            }

            public function handle(EventInterface $event)
            {
                /** @noinspection PhpUndefinedMethodInspection */
                $this->checker[] = $event->getName() . ':Listener 22';
            }
        };

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('get')->willReturnCallback(function ($class) {
            switch ($class) {
                case get_class($this->listener_11):
                    return $this->listener_11;
                case get_class($this->listener_111):
                    return $this->listener_111;
                case get_class($this->listener_1111):
                    return $this->listener_1111;
                case get_class($this->listener_22):
                    return $this->listener_22;
            }
            return null;
        });

        $this->config = [
            get_class($this->event_1) => [
                get_class($this->listener_11),
                get_class($this->listener_111),
            ]
        ];

        $this->dispatcher = new EventDispatcher($this->container, $this->config);
    }

    public function testConstruct()
    {
        $config = [
            get_class($this->event_1) => [
                get_class($this->listener_11),
                get_class($this->listener_111),
            ],
            get_class($this->event_2) => [
                get_class($this->listener_22),
            ],
        ];

        $configToConstructor = $config;
        $configToConstructor[get_class($this->event_2)][] = get_class($this->listener_22);
        $dispatcher = new EventDispatcher($this->container, $configToConstructor);

        $this->assertEquals($config, $dispatcher->getConfig());
    }

    public function testConstructNotEventInterfaceClass()
    {
        $this->expectException(EventDispatcherException::class);
        $config = [
            get_class($this) => [
                get_class($this->listener_22),
            ],
        ];
        new EventDispatcher($this->container, $config);
    }

    public function testConstructNotListenerInterfaceClass()
    {
        $this->expectException(EventDispatcherException::class);
        $config = [
            get_class($this->event_1) => [
                get_class($this),
            ],
        ];
        new EventDispatcher($this->container, $config);
    }

    public function testGetConfig()
    {
        $this->assertEquals($this->config, $this->dispatcher->getConfig());
    }

    public function testAddEventListenerClass()
    {
        $eventClass = get_class($this->event_2);
        $listenerClass = get_class($this->listener_22);

        $this->dispatcher->addEventListener($eventClass, $listenerClass);
        $this->dispatcher->addEventListener($eventClass, $listenerClass);

        $this->config[$eventClass][] = $listenerClass;
        $this->assertEquals($this->config, $this->dispatcher->getConfig());
    }

    public function testAddEventListenerObject()
    {
        $eventClass = get_class($this->event_2);

        $this->dispatcher->addEventListener($eventClass, $this->listener_22);
        $this->dispatcher->addEventListener($eventClass, $this->listener_22);

        $this->config[$eventClass][] = $this->listener_22;
        $this->assertEquals($this->config, $this->dispatcher->getConfig());
    }

    public function testAddNotEventListenerClass()
    {
        $this->expectException(EventDispatcherException::class);
        $eventClass = get_class($this);
        $listenerClass = get_class($this->listener_22);

        $this->dispatcher->addEventListener($eventClass, $listenerClass);
    }

    public function testAddEventNotListenerClass()
    {
        $this->expectException(EventDispatcherException::class);
        $eventClass = get_class($this->event_2);
        $listenerClass = get_class($this);

        $this->dispatcher->addEventListener($eventClass, $listenerClass);
    }

    public function testRemoveEventListener()
    {
        $eventClass = get_class($this->event_1);
        $listenerClass = get_class($this->listener_11);

        $this->dispatcher->removeEventListener($eventClass, $listenerClass);
        $this->dispatcher->removeEventListener($eventClass, $listenerClass);

        $expectedConfig = [
            $eventClass => [
                get_class($this->listener_111),
            ],
        ];

        $this->assertEquals($expectedConfig, $this->dispatcher->getConfig());
    }

    public function testGetDispatchImmediately()
    {
        $this->dispatcher->dispatchImmediately($this->event_1);
        $this->assertEquals([
            'Event 1:Listener 11',
            'Event 1:Listener 111',
        ], $this->eventChecker);
    }

    public function testGetFlushQueue()
    {
        $this->assertEquals([], $this->dispatcher->getFlushQueue());
    }

    public function testDispatchOnFlush()
    {
        $this->dispatcher->dispatchOnFlush($this->event_1);
        $this->dispatcher->dispatchOnFlush($this->event_2);
        $this->assertEquals([
            $this->event_1,
            $this->event_2,
        ], $this->dispatcher->getFlushQueue());
        $this->assertEquals([], $this->eventChecker);
    }

    public function testFlush()
    {
        $eventClass = get_class($this->event_2);
        $listenerClass = get_class($this->listener_22);
        $this->dispatcher->addEventListener($eventClass, $listenerClass);

        $this->dispatcher->dispatchOnFlush($this->event_1);
        $this->dispatcher->dispatchOnFlush($this->event_2);
        $this->dispatcher->flush();

        $this->assertEquals([
            'Event 1:Listener 11',
            'Event 1:Listener 111',
            'Event 2:Listener 22',
        ], $this->eventChecker);

        $this->assertEquals([], $this->dispatcher->getFlushQueue());
    }

}
