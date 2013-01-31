<?php

require_once 'Symfony/Component/ClassLoader/UniversalClassLoader.php';
$loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespace('Netzhuffle', __DIR__);
$loader->register();