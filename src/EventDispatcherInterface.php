<?php
/**
 * Created for EventDispatcher.
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 26.05.2017 23:41
 */

namespace XAKEPEHOK\EventDispatcher;


interface EventDispatcherInterface
{

    public function addEventListener(string $eventClass, $listenerOrClass);

    public function removeEventListener(string $eventClass, string $listenerClass);

    public function dispatchImmediately(EventInterface $event);

    public function dispatchOnFlush(EventInterface $event);

    public function flush();

}