<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

redirectWithState('error', 'Recruitment write actions are not yet enabled on this page.');
