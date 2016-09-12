<?php

namespace Dinhkhanh\MongoDBAclBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Dinhkhanh\MongoDBAclBundle\DependencyInjection\MongoDBAclExtension;

/**
 * @author Richard Shank <develop@zestic.com>
 * @author Piotr Walków <walkowpiotr@gmail.com>
 */
class MongoDBAclBundle extends Bundle
{
    /**
     * @return ExtensionInterface
     */
    public function getContainerExtension()
    {
        return new MongoDBAclExtension();
    }
}
