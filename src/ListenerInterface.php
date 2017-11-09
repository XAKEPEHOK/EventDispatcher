<?php
/**
 * Created for EventDispatcher.
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 26.05.2017 23:51
 */

namespace XAKEPEHOK\EventDispatcher;


interface ListenerInterface
{

    public function handle(EventInterface $event);

}