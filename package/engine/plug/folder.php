<?php

Folder::_('pack', function($folder, $as = 'package-%{id}%.zip') {
    return (new Package($folder))->pack($as);
});