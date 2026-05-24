<?php

it('config/site.php lists three allowed themes', function () {
    $themes = config('site.themes');
    expect($themes)->toHaveKeys(['theme-cream', 'theme-onyx', 'theme-editorial']);
});
