<?php
/**
 * Start Clockwork Benchmark
 * @param string $name
 * @param string $descr
 */


function cws($name = 'Event', $descr = '')
{
    if (true){
        Clockwork::startEvent($name, $descr ?: $name);
    }
}

/**
 * Log Clockwork Message
 * @param mixed $message
 */
function cw($message = NULL)
{
    if (true){
        Clockwork::info($message);
    }
}

/**
 * End Clockwork Benchmark
 * @param string $name
 */
function cwe($name = 'Event', $description = null)
{
    if (true){
        if (isset($description))
            Clockwork::getTimeline()->data[$name]['description'] = $description;
        Clockwork::endEvent($name);
    }
}