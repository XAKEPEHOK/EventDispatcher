<?php
/**
 * Created for EventDispatcher.
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 26.05.2017 23:41
 */

namespace XAKEPEHOK\EventDispatcher;


use Psr\Container\ContainerInterface;

class EventDispatcher implements EventDispatcherInterface
{

    /** @var ContainerInterface  */
    protected $container;

    /** @var array соответствие событий и слушателей */
    protected $config = [];

    /** @var EventInterface[] */
    protected $flushQueue = [];

    private $listeners = [];

    /**
     * @param ContainerInterface $container
     * @param array $config содерждит в себе массив следующего вида
     * [
     *    OrderCreatedEvent::class => [
     *        OrderCreatedTrigger::class,
     *        OrderChangedTrigger::class
     *    ],
     * ];
     */
    public function __construct(ContainerInterface $container, array $config = [])
    {
        $this->container = $container;
        foreach ($config as $eventClass => $listeners) {
            foreach ($listeners as $listener) {
                $this->addEventListener($eventClass, $listener);
            }
        }
    }

    /**
     * Возвращает конфигурацию связанных событий и слушателей
     * @return array
     */
    public function getConfig(): array
    {
        $config = $this->config;
        foreach ($config as &$listeners) {
            $listeners = array_values($listeners);
        }
        return $config;
    }

    /**
     * Добавляет слушателя для заданного события
     * @param string $eventClass
     * @param string|ListenerInterface $listenerOrClass
     * @throws EventDispatcherException
     */
    public function addEventListener(string $eventClass, $listenerOrClass)
    {
        $listenerClass = is_string($listenerOrClass) ? $listenerOrClass : get_class($listenerOrClass);

        $this->guardEventClassNotEventInterface($eventClass);
        $this->guardListenerClassNotListenerInterface($listenerClass);

        $this->config[$eventClass][$listenerClass] = $listenerOrClass;
    }

    /**
     * Удаляет слушателя для заданного события
     * @param string $eventClass
     * @param string $listenerClass
     */
    public function removeEventListener(string $eventClass, string $listenerClass)
    {
        if (isset($this->config[$eventClass])) {
            if (isset($this->config[$eventClass][$listenerClass])) {
                unset($this->config[$eventClass][$listenerClass]);
            }
        }
    }

    /**
     * Выполняет слушателей заданного события
     * @param EventInterface $event
     */
    public function dispatchImmediately(EventInterface $event)
    {
        $listeners = $this->config[get_class($event)] ?? [];
        foreach ($listeners as $listenerClass) {
            $listener = is_string($listenerClass) ? $this->getListenerObject($listenerClass) : $listenerClass;
            $listener->handle($event);
        }
    }

    /**
     * Возвращает массив событий, запланированных на flush
     * @return EventInterface[]
     */
    public function getFlushQueue(): array
    {
        return $this->flushQueue;
    }

    /**
     * Планирует отложенное выполнение слушателей события
     * @param EventInterface $event
     */
    public function dispatchOnFlush(EventInterface $event)
    {
        $this->flushQueue[] = $event;
    }

    /**
     * Вызывает слушатели событий, которые были запланированны на отложенное выполнение
     */
    public function flush()
    {
        foreach ($this->flushQueue as $i => $event) {
            $this->dispatchImmediately($event);
            unset($this->flushQueue[$i]);
        }
    }

    /**
     * Создает или возвращает уже созданный экземлляр ListenerInterface по имени класса
     * @param string $class
     * @return ListenerInterface
     */
    protected function getListenerObject(string $class): ListenerInterface
    {
        if (!isset($this->listeners[$class])) {
            $this->listeners[$class] = $this->container->get($class);
        }
        return $this->listeners[$class];
    }

    private function guardEventClassNotEventInterface(string $eventClass)
    {
        if (!is_subclass_of($eventClass, EventInterface::class)) {
            throw new EventDispatcherException(
                'Event class should be implement of EventInterface'
            );
        }
    }

    private function guardListenerClassNotListenerInterface(string $listenerClass)
    {
        if (!is_subclass_of($listenerClass, ListenerInterface::class)) {
            throw new EventDispatcherException(
                'Listener class should be implement of ListenerInterface'
            );
        }
    }

}