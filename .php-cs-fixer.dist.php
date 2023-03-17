<?php

if (!file_exists(__DIR__.'/src')) {
    exit(0);
}

$finder = (new \PhpCsFixer\Finder())
    ->in(__DIR__.'/src')
;

return (new \PhpCsFixer\Config())
    ->setRules(array(
        '@PSR12' => true,
        '@PSR12:risky' => true,
        'yoda_style' => false,
        'header_comment' => [
            'header' => <<<EOF
OAuth2 Client Bundle
Copyright (c) KnpUniversity <http://knpuniversity.com/>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF
        ]
    ))
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
