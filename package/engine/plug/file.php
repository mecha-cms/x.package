<?php

File::_('pack', function($file, $as = 'package-%{id}%.zip') {
    return (new Package($file))->pack($as);
});