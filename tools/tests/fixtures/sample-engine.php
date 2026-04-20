<?php
function sample_hello(string $name): string {
    return "Hallo, $name";
}

function sample_shout(string $text): string {
    return strtoupper($text) . '!';
}
